<?php

if (!function_exists('__MPF_ImageResizeHandler')) {
    function __MPF_ImageResizeHandler(&$arCustomFile)
    {
        $arResizeParams = ['width' => 400, 'height' => 400];

        if ((!is_array($arCustomFile)) || !isset($arCustomFile['fileID'])) {
            return false;
        }

        $fileID = $arCustomFile['fileID'];

        $arFile = CFile::MakeFileArray($fileID);
        if (CFile::CheckImageFile($arFile) === null) {
            $aImgThumb = CFile::ResizeImageGet(
                $fileID,
                ['width' => 90, 'height' => 90],
                BX_RESIZE_IMAGE_EXACT,
                true
            );
            $arCustomFile['img_thumb_src'] = $aImgThumb['src'];

            if (!empty($arResizeParams)) {
                $aImgSource = CFile::ResizeImageGet(
                    $fileID,
                    ['width' => $arResizeParams['width'], 'height' => $arResizeParams['height']],
                    BX_RESIZE_IMAGE_PROPORTIONAL,
                    true
                );
                $arCustomFile['img_source_src'] = $aImgSource['src'];
                $arCustomFile['img_source_width'] = $aImgSource['width'];
                $arCustomFile['img_source_height'] = $aImgSource['height'];
            }
        }
    }
}

function createField($entityId, $fieldName, $fieldType = 'string')
{
    $arUserField = CUserTypeEntity::GetList([], ['ENTITY_ID' => $entityId, 'FIELD_NAME' => $fieldName])->Fetch();
    if ($arUserField) {
        return false;
    }

    $arFields = [
        'FIELD_NAME' => $fieldName,
        'ENTITY_ID' => $entityId,
        'USER_TYPE_ID' => $fieldType,
        'XML_ID' => $fieldName,
        'SORT' => 100,
        'MULTIPLE' => 'N',
        'MANDATORY' => 'N',
        'SHOW_FILTER' => 'I',
        'SHOW_IN_LIST' => 'Y',
        'EDIT_IN_LIST' => 'Y',
        'IS_SEARCHABLE' => 'N',
    ];
    $ob = new CUserTypeEntity();
    $FIELD_ID = $ob->Add($arFields);

    return $FIELD_ID;
}
