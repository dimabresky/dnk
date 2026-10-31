<?
// video options
$videoSource = strlen($arItem['PROPERTIES']['VIDEO_SOURCE']['VALUE_XML_ID']) ? $arItem['PROPERTIES']['VIDEO_SOURCE']['VALUE_XML_ID'] : 'LINK';
$videoSrc = $arItem['PROPERTIES']['VIDEO_SRC']['VALUE'];
if ($videoFileID = $arItem['PROPERTIES']['VIDEO']['VALUE']) {
    $videoFileSrc = CFile::GetPath($videoFileID);
}

// YES is legacy
$bShowVideo = $arItem['PROPERTIES']['SHOW_VIDEO']['VALUE_XML_ID'] == 'Y' || $arItem['PROPERTIES']['SHOW_VIDEO']['VALUE_XML_ID'] == 'YES';

$videoPlayer = $videoPlayerSrc = '';
$videoInfoItem = '';
if (
    $bShowVideo &&
    ($videoSource == 'LINK' ? strlen($videoSrc) : strlen($videoFileSrc))
) {
    $buttonVideoText = $arItem['PROPERTIES']['BUTTON_VIDEO_TEXT']['VALUE'];
    $buttonVideoClass = $arItem['PROPERTIES']['BUTTON_VIDEO_CLASS']['VALUE_XML_ID'] ? $arItem['PROPERTIES']['BUTTON_VIDEO_CLASS']['VALUE_XML_ID'] : 'btn-default';
    $buttonVideoColor = $arItem['PROPERTIES']['BUTTON_VIDEO_COLOR']['VALUE_XML_ID'] ? $arItem['PROPERTIES']['BUTTON_VIDEO_COLOR']['VALUE_XML_ID'] : '';
    $bVideoLoop = $arItem['PROPERTIES']['VIDEO_LOOP']['VALUE_XML_ID'] === 'YES';
    $bVideoDisableSound = $arItem['PROPERTIES']['VIDEO_DISABLE_SOUND']['VALUE_XML_ID'] === 'YES';
    $bVideoAutoStart = $arItem['PROPERTIES']['VIDEO_AUTOSTART']['VALUE_XML_ID'] === 'YES';
    $bVideoCover = $arItem['PROPERTIES']['VIDEO_COVER']['VALUE_XML_ID'] === 'YES';
    $bVideoUnderText = $arItem['PROPERTIES']['VIDEO_UNDER_TEXT']['VALUE_XML_ID'] === 'YES';
    
    if (strlen($videoSrc) && $videoSource === 'LINK') {
        $videoPlayer = 'YOUTUBE';
        $videoSrc = htmlspecialchars_decode($videoSrc);
        if (strpos($videoSrc, 'iframe') !== false) {
            $re = '/<iframe.*src=\"(.*)\".*><\/iframe>/isU';
            preg_match_all($re, $videoSrc, $arMatch);
            $videoSrc = $arMatch[1][0];
        }
        $videoPlayerSrc = $videoSrc;

        switch($videoSrc) {
            case(($v = strpos($videoSrc, 'vimeo.com/')) !== false):
                $videoPlayer = 'VIMEO';
                if (strpos($videoSrc, 'player.vimeo.com/') === false) {
                    $videoPlayerSrc = str_replace('vimeo.com/', 'player.vimeo.com/', $videoPlayerSrc);
                }
                if (strpos($videoSrc, 'vimeo.com/video/') === false) {
                    $videoPlayerSrc = str_replace('vimeo.com/', 'vimeo.com/video/', $videoPlayerSrc);
                }
                break;
            case(($v = strpos($videoSrc, 'rutube.ru/')) !== false):
                $videoPlayer = 'RUTUBE';
                break;
            case(strpos($videoSrc, 'watch?') !== false && ($v = strpos($videoSrc, 'v=')) !== false):
                $videoPlayerSrc = 'https://www.youtube.com/embed/'.substr($videoSrc, $v + 2, 11);
                break;
            case(strpos($videoSrc, 'youtu.be/') !== false && $v = strpos($videoSrc, 'youtu.be/')):
                $videoPlayerSrc = 'https://www.youtube.com/embed/'.substr($videoSrc, $v + 9, 11);
                break;
            case(strpos($videoSrc, 'embed/') !== false && $v = strpos($videoSrc, 'embed/')):
                $videoPlayerSrc = 'https://www.youtube.com/embed/'.substr($videoSrc, $v + 6, 11);
                break;
        }

        $bVideoPlayerYoutube = $videoPlayer === 'YOUTUBE';
        $bVideoPlayerVimeo = $videoPlayer === 'VIMEO';
        $bVideoPlayerRutube = $videoPlayer === 'RUTUBE';

        if (strlen($videoPlayerSrc)) {
            $videoPlayerSrc = trim($videoPlayerSrc.
                ($bVideoPlayerYoutube ? '?autoplay=1&enablejsapi=1&controls=0&showinfo=0&rel=0&disablekb=1&iv_load_policy=3' :
                ($bVideoPlayerVimeo ? '?autoplay=1&badge=0&byline=0&portrait=0&title=0' :
                ($bVideoPlayerRutube ? '?quality=1&autoStart=0&sTitle=false&sAuthor=false&platform=someplatform' : '')))
            );
        }
    }
    else{
        $videoPlayer = 'HTML5';
        $videoPlayerSrc = $videoFileSrc;
    }

    $videoInfoItem = ' data-video_source="'.$videoSource.'"';
    $videoInfoItem .= strlen($videoPlayer) ? ' data-video_player="'.$videoPlayer.'"' : '';
    $videoInfoItem .= strlen($videoPlayerSrc) ? ' data-video_src="'.$videoPlayerSrc.'"' : '';
    $videoInfoItem .= $bVideoAutoStart ? ' data-video_autoplay="1"' : '';
    $videoInfoItem .= $bVideoDisableSound ? ' data-video_disable_sound="1"' : '';
    $videoInfoItem .= $bVideoLoop ? ' data-video_loop="1"' : '';
    $videoInfoItem .= $bVideoCover ? ' data-video_cover="1"' : '';
}

if ($bShowVideo && $videoPlayerSrc) {
    $templateData['HAS_VIDEO'] = true;
}
