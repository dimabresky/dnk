<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

global $USER;

// устанавливает авторизацию при редиректе с формы авторизации по номеру телефона,
// так как PhoneAuth(aspro.premier) не отсылает куки для запоминания авторизации

if ($USER->IsAuthorized()) {
    
    $userId = (int)$USER->GetID();
    $USER->Authorize($userId, true);
}

LocalRedirect($_SERVER['HTTP_REFERER'] ?? '/');