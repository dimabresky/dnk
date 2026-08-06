<?php

namespace Dnk\PhpInterface;

use CFile;

/**
 * Builds an opaque JPEG from DETAIL_PICTURE with solid background #F8F8FC.
 */
final class FeedPictureComposer
{
    public const BACKGROUND_HEX = '#F8F8FC';
    public const BACKGROUND_R = 248;
    public const BACKGROUND_G = 248;
    public const BACKGROUND_B = 252;
    public const JPEG_QUALITY = 90;

    /**
     * @return array{path:string,name:string}
     *
     * @throws \RuntimeException
     */
    public static function composeFromFileId(int $fileId): array
    {
        if ($fileId <= 0) {
            throw new \RuntimeException('Invalid file id');
        }

        $file = CFile::GetFileArray($fileId);
        if (!is_array($file)) {
            throw new \RuntimeException('File not found: ' . $fileId);
        }

        $docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
        $srcRel = (string)($file['SRC'] ?? '');
        if ($docRoot === '' || $srcRel === '') {
            throw new \RuntimeException('Empty SRC for file ' . $fileId);
        }

        $srcPath = $docRoot . $srcRel;
        if (!is_file($srcPath)) {
            throw new \RuntimeException('Physical file missing: ' . $srcPath);
        }

        $workDir = $docRoot . '/upload/dnk_feed_picture/tmp';
        if (!is_dir($workDir) && !@mkdir($workDir, 0775, true) && !is_dir($workDir)) {
            throw new \RuntimeException('Cannot create work dir: ' . $workDir);
        }

        $baseName = pathinfo((string)($file['ORIGINAL_NAME'] ?? $file['FILE_NAME'] ?? 'image'), PATHINFO_FILENAME);
        $baseName = preg_replace('/[^a-zA-Z0-9_\-]+/u', '_', (string)$baseName) ?: 'image';
        $destName = $baseName . '_feed_' . $fileId . '.jpg';
        $destPath = $workDir . '/' . $destName;

        if (extension_loaded('imagick') && class_exists(\Imagick::class)) {
            self::composeWithImagick($srcPath, $destPath);
        } else {
            self::composeWithGd($srcPath, $destPath);
        }

        if (!is_file($destPath) || filesize($destPath) <= 0) {
            throw new \RuntimeException('Feed picture output is empty: ' . $destPath);
        }

        return [
            'path' => $destPath,
            'name' => $destName,
        ];
    }

    private static function composeWithImagick(string $srcPath, string $destPath): void
    {
        $image = new \Imagick($srcPath);
        $canvas = null;
        try {
            if ($image->getNumberImages() > 1) {
                $image->setIteratorIndex(0);
            }

            $width = $image->getImageWidth();
            $height = $image->getImageHeight();
            if ($width <= 0 || $height <= 0) {
                throw new \RuntimeException('Invalid image dimensions');
            }

            $canvas = new \Imagick();
            $canvas->newImage($width, $height, new \ImagickPixel(self::BACKGROUND_HEX));
            $canvas->setImageFormat('jpeg');
            $canvas->compositeImage($image, \Imagick::COMPOSITE_DEFAULT, 0, 0);
            $canvas->setImageCompression(\Imagick::COMPRESSION_JPEG);
            $canvas->setImageCompressionQuality(self::JPEG_QUALITY);

            if (!$canvas->writeImage($destPath)) {
                throw new \RuntimeException('Imagick writeImage failed');
            }
        } finally {
            if ($canvas instanceof \Imagick) {
                $canvas->clear();
            }
            $image->clear();
        }
    }

    private static function composeWithGd(string $srcPath, string $destPath): void
    {
        if (!function_exists('imagecreatetruecolor') || !function_exists('imagejpeg')) {
            throw new \RuntimeException('GD with JPEG support is required');
        }

        $src = self::gdLoadImage($srcPath);
        $width = imagesx($src);
        $height = imagesy($src);
        if ($width <= 0 || $height <= 0) {
            imagedestroy($src);
            throw new \RuntimeException('Invalid image dimensions');
        }

        $dst = imagecreatetruecolor($width, $height);
        if ($dst === false) {
            imagedestroy($src);
            throw new \RuntimeException('imagecreatetruecolor failed');
        }

        $bg = imagecolorallocate($dst, self::BACKGROUND_R, self::BACKGROUND_G, self::BACKGROUND_B);
        if ($bg === false) {
            imagedestroy($src);
            imagedestroy($dst);
            throw new \RuntimeException('imagecolorallocate failed');
        }

        imagefill($dst, 0, 0, $bg);
        imagealphablending($dst, true);
        imagecopy($dst, $src, 0, 0, 0, 0, $width, $height);
        imagedestroy($src);

        $ok = imagejpeg($dst, $destPath, self::JPEG_QUALITY);
        imagedestroy($dst);

        if (!$ok) {
            throw new \RuntimeException('imagejpeg failed');
        }
    }

    /**
     * @return \GdImage|resource
     */
    private static function gdLoadImage(string $srcPath)
    {
        $info = @getimagesize($srcPath);
        $type = is_array($info) ? (int)($info[2] ?? 0) : 0;

        $image = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($srcPath),
            IMAGETYPE_PNG => @imagecreatefrompng($srcPath),
            IMAGETYPE_GIF => @imagecreatefromgif($srcPath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($srcPath) : false,
            default => false,
        };

        if ($image === false) {
            throw new \RuntimeException('GD failed to load image: ' . $srcPath);
        }

        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP || $type === IMAGETYPE_GIF) {
            imagealphablending($image, true);
            imagesavealpha($image, true);
        }

        return $image;
    }
}
