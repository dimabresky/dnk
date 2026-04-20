<?
use \Bitrix\Main\Localization\Loc;

$mapLAT = $mapLON = $iCountShops = 0;
$arPlacemarks = array();

foreach($arItems as $arItem){
    if($arItem['PROPERTY_MAP_VALUE']){
        $arCoords = explode(',', $arItem['PROPERTY_MAP_VALUE']);
        $mapLAT += $arCoords[0];
        $mapLON += $arCoords[1];

        $phones = '';
        $arItem['PROPERTY_PHONE_VALUE'] = (is_array($arItem['PROPERTY_PHONE_VALUE']) ? $arItem['PROPERTY_PHONE_VALUE'] : ($arItem['PROPERTY_PHONE_VALUE'] ? array($arItem['PROPERTY_PHONE_VALUE']) : array()));
        foreach ($arItem['PROPERTY_PHONE_VALUE'] as $phone) {
            $phones .= '<div class="value"><a class="dark_link" rel= "nofollow" href="tel:'.str_replace(array(' ', ',', '-', '(', ')'), '', $phone).'">'.$phone.'</a></div>';
        }

        $emails = '';
        $arItem['PROPERTY_EMAIL_VALUE'] = (is_array($arItem['PROPERTY_EMAIL_VALUE']) ? $arItem['PROPERTY_EMAIL_VALUE'] : ($arItem['PROPERTY_EMAIL_VALUE'] ? array($arItem['PROPERTY_EMAIL_VALUE']) : array()));
        foreach ($arItem['PROPERTY_EMAIL_VALUE'] as $email) {
            $emails .= '<a class="dark_link" href="mailto:' .$email. '">' .$email . '</a>';
        }

        $metrolist = '';
        $arItem['PROPERTY_METRO_VALUE'] = (is_array($arItem['PROPERTY_METRO_VALUE']) ? $arItem['PROPERTY_METRO_VALUE'] : ($arItem['PROPERTY_METRO_VALUE'] ? array($arItem['PROPERTY_METRO_VALUE']) : array()));
        foreach ($arItem['PROPERTY_METRO_VALUE'] as $metro) {
            $metrolist .= '<span class="metro"><i></i>'. $metro . '</span>';
        }

        $address = $arItem['NAME'].($arItem['PROPERTY_ADDRESS_VALUE'] ? ', '.$arItem['PROPERTY_ADDRESS_VALUE'] : '');

        $popupOptions = [
            'ITEM' => [
                'NAME' => $address,
                'URL' => $arItem['DETAIL_PAGE_URL'],
                'EMAIL' => $arItem['PROPERTY_EMAIL_VALUE'],
                'EMAIL_HTML' => $emails,
                'PHONE' => $arItem['PROPERTY_PHONE_VALUE'],
                'PHONE_HTML' => $phones,
                'METRO' => $arItem['PROPERTY_METRO_VALUE'],
                'METRO_HTML' => $metrolist,
                'SCHEDULE' => $arItem['PROPERTY_SCHEDULE_VALUE']['TYPE'] === 'HTML' ? $arItem['~PROPERTY_SCHEDULE_VALUE']['TEXT'] : $arItem['PROPERTY_SCHEDULE_VALUE']['TEXT'],
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
                'BTN_CLASS' => 'btn btn-transparent',
            ],
            'SHOW_QUESTION_BTN' => 'Y',
            'SHOW_SOCIAL' => 'N',
            'SHOW_CLOSE' => 'N',
            'SHOW_TITLE' => 'N',
        ];

        $arPlacemarks[] = array(
            "LAT" => $arCoords[0],
            "LON" => $arCoords[1],
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
