<?
use \Bitrix\Main\Localization\Loc;

$mapLAT = $mapLON = $iCountShops = 0;
$arPlacemarks = [];

foreach($arStores as $arStore){
    if(
        $arStore['GPS_N'] &&
        $arStore['GPS_S']
    ){
        $mapLAT += floatval($arStore['GPS_N']);
        $mapLON += floatval($arStore['GPS_S']);

        $phones = '';
        $arStore['PHONE'] = (is_array($arStore['PHONE']) ? $arStore['PHONE'] : ($arStore['PHONE'] ? array($arStore['PHONE']) : []));
        foreach ($arStore['PHONE'] as $phone) {
            $phones .= '<div class="value"><a class="dark_link" rel= "nofollow" href="tel:'.str_replace(array(' ', ',', '-', '(', ')'), '', $phone).'">'.$phone.'</a></div>';
        }

        $emails = '';
        $arStore['EMAIL'] = (is_array($arStore['EMAIL']) ? $arStore['EMAIL'] : ($arStore['EMAIL'] ? [$arStore['EMAIL']] : []));
        foreach ($arStore['EMAIL'] as $email) {
            $emails .= '<a class="dark_link" href="mailto:'.$email.'">'.$email.'</a>';
        }

        $metrolist = '';
        $arStore['METRO'] = (is_array($arStore['METRO']) ? $arStore['METRO'] : ($arStore['METRO'] ? [$arStore['METRO']] : []));
        foreach ($arStore['METRO'] as $metro) {
            $metrolist .= '<span class="metro"><i></i>'.$metro.'</span>';
        }

        $address = $arStore['ADDRESS'];

        $popupOptions = [
            'ITEM' => [
                'NAME' => $address,
                'URL' => $arStore['URL'],
                'EMAIL' => $arStore['EMAIL'],
                'EMAIL_HTML' => $emails,
                'PHONE' => $arStore['PHONE'],
                'PHONE_HTML' => $phones,
                'METRO' => $arStore['METRO'],
                'METRO_HTML' => $metrolist,
                'SCHEDULE' => $arStore['SCHEDULE'],
                'DISPLAY_PROPERTIES' => [
                    'METRO' => [
                        'NAME' => Loc::getMessage('MYMS_TPL_METRO'),
                    ],
                    'SCHEDULE' => [
                        'NAME' => Loc::getMessage('MYMS_TPL_SCHEDULE'),
                    ],
                    'PHONE' => [
                        'NAME' =>  Loc::getMessage('MYMS_TPL_PHONE'),
                    ],
                    'EMAIL' => [
                        'NAME' => Loc::getMessage('MYMS_TPL_EMAIL'),
                    ]
                ]
            ],
            'PARAMS' => [
                'TITLE' => '',
                'BTN_CLASS' => 'btn btn-secondary-black',
            ],
            'SHOW_QUESTION_BTN' => 'Y',
            'SHOW_SOCIAL' => 'N',
            'SHOW_CLOSE' => 'N',
            'SHOW_TITLE' => 'N',
        ];

        $arPlacemarks[] = array(
            "LAT" => floatval($arStore['GPS_N']),
            "LON" => floatval($arStore['GPS_S']),
            "TEXT" => TSolution\Functions::getItemMapHtml($popupOptions),
        );

        ++$iCountShops;
    }
}

if($iCountShops){
    $mapLAT = floatval($mapLAT / $iCountShops);
    $mapLON = floatval($mapLON / $iCountShops);
    ?>
    <div class="contacts__map-wrapper">
        <div class="contacts__map map-view relative bordered outer-rounded-x">
            <?if($typeMap == 'GOOGLE'):?>
                <?$APPLICATION->IncludeComponent(
                    "bitrix:map.google.view",
                    "map",
                    array(
                        "API_KEY" => \Bitrix\Main\Config\Option::get('fileman', 'google_map_api_key', ''),
                        "INIT_MAP_TYPE" => "ROADMAP",
                        "COMPONENT_TEMPLATE" => "map",
                        "COMPOSITE_FRAME_MODE" => "A",
                        "COMPOSITE_FRAME_TYPE" => "AUTO",
                        "CONTROLS" => array(
                            0 => "SMALL_ZOOM_CONTROL",
                            1 => "TYPECONTROL",
                        ),
                        "OPTIONS" => array(
                            0 => "ENABLE_DBLCLICK_ZOOM",
                            1 => "ENABLE_DRAGGING",
                        ),
                        "MAP_DATA" => serialize(array("google_lat" => $mapLAT, "google_lon" => $mapLON, "google_scale" => 17, "PLACEMARKS" => $arPlacemarks)),
                        "MAP_HEIGHT" => "500",
                        "MAP_WIDTH" => "100%",
                        "MAP_ID" => "",
                        "ZOOM_BLOCK" => array(
                            "POSITION" => "right center",
                        )
                    ),
                    false
                );?>
            <?else:?>
                <?$APPLICATION->IncludeComponent(
                    "bitrix:map.yandex.view",
                    "map",
                    array(
                        "API_KEY" => \Bitrix\Main\Config\Option::get('fileman', 'yandex_map_api_key', ''),
                        "INIT_MAP_TYPE" => "MAP",
                        "COMPONENT_TEMPLATE" => "map",
                        "COMPOSITE_FRAME_MODE" => "A",
                        "COMPOSITE_FRAME_TYPE" => "AUTO",
                        "CONTROLS" => array(
                            0 => "ZOOM",
                            1 => "SMALLZOOM",
                            2 => "TYPECONTROL",
                        ),
                        "OPTIONS" => array(
                            0 => "ENABLE_DBLCLICK_ZOOM",
                            1 => "ENABLE_DRAGGING",
                        ),
                        "MAP_DATA" => serialize(array("yandex_lat" => $mapLAT, "yandex_lon" => $mapLON, "yandex_scale" => 17, "PLACEMARKS" => $arPlacemarks)),
                        "MAP_WIDTH" => "100%",
                        "MAP_HEIGHT" => "500",
                        "MAP_ID" => "",
                        "ZOOM_BLOCK" => array(
                            "POSITION" => "right center",
                        )
                    ),
                    false
                );?>
            <?endif;?>
        </div>
    </div>
    <?
}
