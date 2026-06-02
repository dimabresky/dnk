<?php

declare(strict_types=1);

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Mail\Event as MailEvent;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;
use Dnk\PhpInterface\CertificateBuyPhoneAuth;
use Dnk\PhpInterface\UserConsentService;
use Dnk\PhpInterface\Utils;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

Loc::loadMessages(__FILE__);

class DnkCertificateBuyComponent extends CBitrixComponent implements Controllerable
{
    private const PROP_NOMINAL = 'NOMINAL';
    private const PAYMENT_XML = 'cash_on_delivery';
    private const DELIVERY_XML_COURIER = 'courier';
    private const DELIVERY_XML_PICKUP = 'pickup';
    private const MAX_QTY = 99;

    /** Ключ в Bitrix-сессии: qty по element_id активных сертификатов (разрежающий массив). */
    private const SESSION_CART_KEY = 'dnk_cert_buy_cart';

    /** @inheritdoc */
    public function configureActions()
    {
        return [
            'submit' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                ],
            ],
            'saveCart' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                ],
            ],
            'phoneAuthStart' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                ],
            ],
            'phoneAuthConfirm' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                ],
            ],
        ];
    }

    public function onPrepareComponentParams($params)
    {
        $params = parent::onPrepareComponentParams($params);

        $params['CACHE_TIME'] = (int)($params['CACHE_TIME'] ?? 3600);
        $params['USER_CONSENT_ID'] = (int)($params['USER_CONSENT_ID'] ?? 0);

        return $params;
    }

    private function resolveCertificateCatalogIblockId(): int
    {
        return defined('DNK_CERTIFICATE_CATALOG_IBLOCK_ID') ? (int)DNK_CERTIFICATE_CATALOG_IBLOCK_ID : 0;
    }

    private function resolveCertificateRequestIblockId(): int
    {
        return defined('DNK_CERTIFICATE_REQUEST_IBLOCK_ID') ? (int)DNK_CERTIFICATE_REQUEST_IBLOCK_ID : 0;
    }

    private function resolvePickupStoresIblockId(): int
    {
        return defined('DNK_PICKUP_STORES_IBLOCK_ID') ? (int)DNK_PICKUP_STORES_IBLOCK_ID : 0;
    }

    public function executeComponent()
    {
        if (!Loader::includeModule('iblock')) {
            ShowError(GetMessage('DNK_CERT_BUY_ERR_MODULE_IBLOCK'));

            return;
        }
        
        $this->arParams = $this->onPrepareComponentParams($this->arParams);

        $certIblockId = $this->resolveCertificateCatalogIblockId();
        if ($certIblockId <= 0) {
            ShowError(GetMessage('DNK_CERT_BUY_ERR_PARAM_CERT_IBLOCK'));

            return;
        }

        $requestIblockId = $this->resolveCertificateRequestIblockId();
        if ($requestIblockId <= 0) {
            ShowError(GetMessage('DNK_CERT_BUY_ERR_PARAM_REQUEST_IBLOCK'));

            return;
        }

        $pickupIblockId = $this->resolvePickupStoresIblockId();

        $cacheTime = max(0, (int)$this->arParams['CACHE_TIME']);

        $cachePath = '/dnk/certificate.buy';
        $cacheId = implode('_', [$certIblockId, $pickupIblockId, LANGUAGE_ID]);

        if ($cacheTime >= 1) {
            if ($this->startResultCache($cacheTime, $cacheId, $cachePath)) {
                global $CACHE_MANAGER;

                $CACHE_MANAGER->StartTagCache($cachePath);
                $CACHE_MANAGER->RegisterTag('iblock_id_' . $certIblockId);
                if ($pickupIblockId > 0) {
                    $CACHE_MANAGER->RegisterTag('iblock_id_' . $pickupIblockId);
                }
                $CACHE_MANAGER->EndTagCache();

                $this->arResult['ITEMS'] = $this->loadCertificateItems($certIblockId);
                $this->arResult['PICKUP_STORES'] = $pickupIblockId > 0
                    ? $this->loadPickupStores($pickupIblockId)
                    : [];
                $this->endResultCache();
            }
        } else {
            $this->arResult['ITEMS'] = $this->loadCertificateItems($certIblockId);
            $this->arResult['PICKUP_STORES'] = $pickupIblockId > 0
                ? $this->loadPickupStores($pickupIblockId)
                : [];
        }

        if (!isset($this->arResult['ITEMS'])) {
            $this->arResult['ITEMS'] = [];
        }

        foreach ($this->arResult['ITEMS'] as &$row) {
            $nominal = round((float)$row['NOMINAL_VALUE'], 4);
            $row['NOMINAL_FORMATTED'] = $this->formatBynAmount($nominal);
            $row['NOMINAL'] = $nominal;
            $row['PICTURE'] = $this->resizeDetailPicture((int)$row['DETAIL_PICTURE']);
            unset($row['DETAIL_PICTURE'], $row['NOMINAL_VALUE']);
            unset($row);
        }

        $sessionCartSparse = $this->readSparseCartQuantitiesFromSession();
        $this->arResult['CART_SESSION'] = $this->filterSparseCartAgainstCatalog(
            $this->arResult['ITEMS'],
            $sessionCartSparse
        );

        $this->arResult['PROFILE'] = $this->loadProfilePrefill();

        if (!isset($this->arResult['PICKUP_STORES']) || !is_array($this->arResult['PICKUP_STORES'])) {
            $this->arResult['PICKUP_STORES'] = [];
        }

        $this->arResult['YANDEX_MAP_API_KEY'] = (string)Option::get('fileman', 'yandex_map_api_key', '');

        $this->arResult['PHONE_AUTH_ENABLED'] = CertificateBuyPhoneAuth::isEnabled();
        $this->arResult['USER_CONSENT_ID'] = $this->resolveOrderConsentAgreementId();
        $this->arResult['REGISTRATION_CONSENT_OPTION'] = 'AGREEMENT_REGISTRATION';
        $this->arResult['PHONE_CODE_RESEND_INTERVAL'] = (int)\CUser::PHONE_CODE_RESEND_INTERVAL;

        $this->includeComponentTemplate();
    }

    /**
     * Сохраняет текущее состояние «корзины» сертификатов в сессии (JSON payload.items как у submit, только активные элементы каталога).
     */
    public function saveCartAction(): array
    {
        Loader::includeModule('iblock');

        $certIblockId = $this->resolveCertificateCatalogIblockId();

        $httpRequest = Context::getCurrent()->getRequest();
        $payload = trim((string)$httpRequest->getPost('payload'));
        if ($payload === '') {
            return ['success' => false];
        }

        $decoded = json_decode($payload, true);
        if (!is_array($decoded)) {
            return ['success' => false];
        }

        $itemsIn = isset($decoded['items']) && is_array($decoded['items']) ? $decoded['items'] : [];

        if ($certIblockId <= 0) {
            return ['success' => false];
        }

        $allowed = [];
        foreach ($this->loadCertificateItems($certIblockId) as $cRow) {
            $allowed[(int)$cRow['id']] = true;
        }

        $store = [];

        foreach ($itemsIn as $row) {
            if (!is_array($row)) {
                continue;
            }
            $eid = (int)($row['id'] ?? 0);
            if ($eid <= 0 || !isset($allowed[$eid])) {
                continue;
            }
            $qty = (int)($row['qty'] ?? 0);
            $qty = max(0, min(self::MAX_QTY, $qty));
            if ($qty > 0) {
                $store[$eid] = $qty;
            }
        }

        $this->persistSparseCartToSession($store);

        return ['success' => true];
    }

    public function submitAction(): array
    {
        if ((int)CurrentUser::get()->getId() <= 0) {
            return [
                'success' => false,
                'needAuth' => true,
                'errors' => [GetMessage('DNK_CERT_BUY_ERR_NEED_AUTH')],
            ];
        }

        $decodedResult = $this->decodeSubmitPayloadFromRequest();
        if (empty($decodedResult['ok'])) {
            return ['success' => false, 'errors' => (array)($decodedResult['errors'] ?? [])];
        }

        return $this->createCertificateRequest((array)$decodedResult['payload']);
    }

    public function phoneAuthStartAction(): array
    {
        if ((int)CurrentUser::get()->getId() > 0) {
            return ['success' => false, 'errors' => [GetMessage('DNK_CERT_BUY_ERR_ALREADY_AUTH')]];
        }

        if (!CertificateBuyPhoneAuth::isEnabled()) {
            return ['success' => false, 'errors' => [GetMessage('DNK_CERT_BUY_ERR_PHONE_AUTH_OFF')]];
        }

        $httpRequest = Context::getCurrent()->getRequest();
        $contactName = trim(strip_tags((string)$httpRequest->getPost('contactName')));
        $contactPhone = trim((string)$httpRequest->getPost('contactPhone'));

        $validationError = $this->validateContactFields($contactName, $contactPhone);
        if ($validationError !== null) {
            return ['success' => false, 'errors' => [$validationError]];
        }

        if (!$this->isOrderConsentAccepted($httpRequest)) {
            return ['success' => false, 'errors' => [GetMessage('DNK_CERT_BUY_ERR_ORDER_CONSENT')]];
        }

        $lookup = CertificateBuyPhoneAuth::resolveUserIdByPhone($contactPhone);
        if (!($lookup['ok'] ?? false)) {
            if (!empty($lookup['ambiguous'])) {
                return ['success' => false, 'errors' => [GetMessage('DNK_CERT_BUY_ERR_PHONE_AMBIGUOUS')]];
            }

            return ['success' => false, 'errors' => [GetMessage('DNK_CERT_BUY_ERR_PHONE_LOOKUP')]];
        }

        $userId = $lookup['userId'] ?? null;
        if ($userId !== null && (int)$userId > 0) {
            $smsResult = CertificateBuyPhoneAuth::sendLoginCode((int)$userId);
            $scenario = CertificateBuyPhoneAuth::SCENARIO_LOGIN;
        } else {
            if (!$this->isRegistrationConsentAccepted($httpRequest)) {
                return ['success' => false, 'errors' => [GetMessage('DNK_CERT_BUY_ERR_REG_CONSENT')]];
            }

            $smsResult = CertificateBuyPhoneAuth::registerAndSendCode($contactPhone, $contactName);
            $scenario = CertificateBuyPhoneAuth::SCENARIO_REGISTER;
        }

        if (!($smsResult['ok'] ?? false)) {
            $errors = [GetMessage('DNK_CERT_BUY_ERR_SMS_SEND')];
            if (!empty($smsResult['registerMessage'])) {
                $errors[] = (string)$smsResult['registerMessage'];
            }
            if (!empty($smsResult['smsErrors']) && is_array($smsResult['smsErrors'])) {
                $errors = array_merge($errors, $smsResult['smsErrors']);
            }

            return ['success' => false, 'errors' => $errors];
        }

        return [
            'success' => true,
            'scenario' => $scenario,
            'signedData' => (string)($smsResult['signedData'] ?? ''),
            'resendInterval' => (int)($smsResult['resendInterval'] ?? \CUser::PHONE_CODE_RESEND_INTERVAL),
            'phoneMasked' => (string)($smsResult['phoneMasked'] ?? ''),
            'alreadySent' => !empty($smsResult['alreadySent']),
        ];
    }

    public function phoneAuthConfirmAction(): array
    {
        if ((int)CurrentUser::get()->getId() > 0) {
            return ['success' => false, 'errors' => [GetMessage('DNK_CERT_BUY_ERR_ALREADY_AUTH')]];
        }

        if (!CertificateBuyPhoneAuth::isEnabled()) {
            return ['success' => false, 'errors' => [GetMessage('DNK_CERT_BUY_ERR_PHONE_AUTH_OFF')]];
        }

        $httpRequest = Context::getCurrent()->getRequest();
        $smsCode = trim((string)$httpRequest->getPost('smsCode'));
        $signedData = trim((string)$httpRequest->getPost('signedData'));
        $scenario = trim((string)$httpRequest->getPost('scenario'));
        if ($signedData === '') {
            return ['success' => false, 'errors' => [GetMessage('DNK_CERT_BUY_ERR_SMS_VERIFY')]];
        }
        if (!in_array($scenario, [CertificateBuyPhoneAuth::SCENARIO_LOGIN, CertificateBuyPhoneAuth::SCENARIO_REGISTER], true)) {
            return ['success' => false, 'errors' => [GetMessage('DNK_CERT_BUY_ERR_SMS_VERIFY')]];
        }

        $decodedResult = $this->decodeSubmitPayloadFromRequest();
        if (empty($decodedResult['ok'])) {
            return ['success' => false, 'errors' => (array)($decodedResult['errors'] ?? [])];
        }

        $decoded = (array)$decodedResult['payload'];
        $contactPhone = trim((string)($decoded['contactPhone'] ?? ''));
        if ($smsCode === '') {
            return ['success' => false, 'errors' => [GetMessage('DNK_CERT_BUY_ERR_SMS_CODE')]];
        }

        $authResult = CertificateBuyPhoneAuth::verifyAndAuthorize($contactPhone, $smsCode, $scenario);
        if (!($authResult['ok'] ?? false)) {
            return ['success' => false, 'errors' => [GetMessage('DNK_CERT_BUY_ERR_SMS_VERIFY')]];
        }

        $userId = (int)($authResult['userId'] ?? 0);
        if ($userId <= 0) {
            return ['success' => false, 'errors' => [GetMessage('DNK_CERT_BUY_ERR_SMS_VERIFY')]];
        }

        $this->persistConsentsAfterAuth($userId, $scenario, $httpRequest);

        return $this->createCertificateRequest($decoded);
    }

    /**
     * @return array{ok: bool, payload?: array<string, mixed>, errors?: list<string>}
     */
    private function decodeSubmitPayloadFromRequest(): array
    {
        $httpRequest = Context::getCurrent()->getRequest();
        $payload = trim((string)$httpRequest->getPost('payload'));
        if ($payload === '') {
            return ['ok' => false, 'errors' => [GetMessage('DNK_CERT_BUY_ERR_SUBMIT_JSON')]];
        }

        $decoded = json_decode($payload, true);
        if (!is_array($decoded)) {
            return ['ok' => false, 'errors' => [GetMessage('DNK_CERT_BUY_ERR_SUBMIT_JSON')]];
        }

        $contactName = trim(strip_tags((string)($decoded['contactName'] ?? '')));
        $contactPhone = trim((string)($decoded['contactPhone'] ?? ''));
        $comment = trim(strip_tags((string)($decoded['comment'] ?? '')));

        if (mb_strlen($comment) > 2000) {
            return ['ok' => false, 'errors' => [GetMessage('DNK_CERT_BUY_ERR_COMMENT_LONG')]];
        }

        $nameError = $this->validateContactFields($contactName, $contactPhone);
        if ($nameError !== null) {
            return ['ok' => false, 'errors' => [$nameError]];
        }

        $decoded['contactName'] = $contactName;
        $decoded['contactPhone'] = $contactPhone;
        $decoded['comment'] = $comment;

        return ['ok' => true, 'payload' => $decoded];
    }

    private function validateContactFields(string $contactName, string $contactPhone): ?string
    {
        if ($contactName === '') {
            return GetMessage('DNK_CERT_BUY_ERR_CONTACT_NAME');
        }

        $digitsPhone = preg_replace('/\D+/', '', $contactPhone) ?: '';
        if (mb_strlen($digitsPhone) < 9) {
            return GetMessage('DNK_CERT_BUY_ERR_CONTACT_PHONE');
        }

        return null;
    }

    /**
     * @param array<string, mixed> $decoded
     */
    private function createCertificateRequest(array $decoded): array
    {
        Loader::includeModule('iblock');
        Loader::includeModule('currency');

        $certIblockId = $this->resolveCertificateCatalogIblockId();
        $requestIblockId = $this->resolveCertificateRequestIblockId();

        if ($certIblockId <= 0) {
            return ['success' => false, 'errors' => [GetMessage('DNK_CERT_BUY_ERR_PARAM_CERT_IBLOCK')]];
        }

        if ($requestIblockId <= 0) {
            return ['success' => false, 'errors' => [GetMessage('DNK_CERT_BUY_ERR_PARAM_REQUEST_IBLOCK')]];
        }

        $itemsIn = isset($decoded['items']) && is_array($decoded['items']) ? $decoded['items'] : [];
        $contactName = (string)($decoded['contactName'] ?? '');
        $contactPhone = (string)($decoded['contactPhone'] ?? '');
        $comment = (string)($decoded['comment'] ?? '');

        $deliveryXml = trim((string)($decoded['deliveryXmlId'] ?? self::DELIVERY_XML_COURIER));
        $paymentXml = trim((string)($decoded['paymentXmlId'] ?? self::PAYMENT_XML));
        if (!in_array($deliveryXml, [self::DELIVERY_XML_COURIER, self::DELIVERY_XML_PICKUP], true)) {
            $deliveryXml = self::DELIVERY_XML_COURIER;
        }
        if ($paymentXml !== self::PAYMENT_XML) {
            $paymentXml = self::PAYMENT_XML;
        }

        $pickupStoreId = (int)($decoded['pickupStoreId'] ?? 0);
        $pickupPoint = null;
        if ($deliveryXml === self::DELIVERY_XML_PICKUP) {
            $pickupIblockId = $this->resolvePickupStoresIblockId();
            if ($pickupIblockId <= 0 || $pickupStoreId <= 0) {
                return ['success' => false, 'errors' => [GetMessage('DNK_CERT_BUY_ERR_PICKUP_STORE')]];
            }
            $pickupPoint = $this->loadPickupStoreById($pickupIblockId, $pickupStoreId);
            if ($pickupPoint === null) {
                return ['success' => false, 'errors' => [GetMessage('DNK_CERT_BUY_ERR_PICKUP_STORE')]];
            }
        }

        $lines = [];
        foreach ($itemsIn as $row) {
            if (!is_array($row)) {
                continue;
            }
            $eid = (int)($row['id'] ?? 0);
            $qty = (int)($row['qty'] ?? 0);
            if ($eid <= 0 || $qty <= 0) {
                continue;
            }
            if ($qty > self::MAX_QTY) {
                $qty = self::MAX_QTY;
            }
            $nominal = $this->loadNominalForElement($certIblockId, $eid);
            if ($nominal <= 0) {
                continue;
            }
            $lines[] = [
                'element_id' => $eid,
                'nominal' => $nominal,
                'qty' => $qty,
                'line_sum' => round($nominal * $qty, 2),
            ];
        }

        if (!$lines) {
            return ['success' => false, 'errors' => [GetMessage('DNK_CERT_BUY_ERR_ITEMS_EMPTY')]];
        }

        $total = 0;
        foreach ($lines as $l) {
            $total += (float)$l['line_sum'];
        }
        $total = round($total, 2);

        $deliveryEnumId = $this->resolveListEnumByXml($requestIblockId, 'DELIVERY', $deliveryXml);
        $paymentEnumId = $this->resolveListEnumByXml($requestIblockId, 'PAYMENT', $paymentXml);
        if ($deliveryEnumId === null) {
            return ['success' => false, 'errors' => [GetMessage('DNK_CERT_BUY_ERR_DELIVERY_LOOKUP')]];
        }
        if ($paymentEnumId === null) {
            return ['success' => false, 'errors' => [GetMessage('DNK_CERT_BUY_ERR_PAYMENT_LOOKUP')]];
        }

        $elementIdMap = [];
        foreach ($lines as $lineRow) {
            $elementIdMap[(int)$lineRow['element_id']] = true;
        }
        $certificateElementIds = array_keys($elementIdMap);
        $nameByElId = $this->loadCertificateElementNames($certIblockId, $certificateElementIds);
        $deliveryCaption = $this->resolveListEnumCaption((int)$deliveryEnumId);
        $paymentCaption = $this->resolveListEnumCaption((int)$paymentEnumId);

        $userId = (int)CurrentUser::get()->getId();
        if ($userId <= 0) {
            return [
                'success' => false,
                'needAuth' => true,
                'errors' => [GetMessage('DNK_CERT_BUY_ERR_NEED_AUTH')],
            ];
        }

        $emailSnapshot = '';
        $u = UserTable::getRow([
            'select' => ['EMAIL'],
            'filter' => ['=ID' => $userId],
        ]);
        if (is_array($u)) {
            $emailSnapshot = trim((string)($u['EMAIL'] ?? ''));
        }

        $detailLines = [];
        foreach ($lines as $l) {
            $eid = (int)$l['element_id'];
            $detailLines[] = [
                'name' => $nameByElId[$eid] ?? ('Элемент №' . $eid),
                'nominal' => (float)$l['nominal'],
                'qty' => (int)$l['qty'],
                'lineSum' => (float)$l['line_sum'],
            ];
        }

        $orderDetailsPayload = [
            'contactName' => $contactName,
            'contactPhone' => $contactPhone,
            'contactEmail' => $emailSnapshot,
            'deliveryLabel' => $deliveryCaption,
            'paymentLabel' => $paymentCaption,
            'lines' => $detailLines,
            'total' => $total,
            'comment' => $comment,
        ];
        if ($pickupPoint !== null) {
            $orderDetailsPayload['pickupPoint'] = $pickupPoint;
        }

        $orderDetails = Utils::buildCertificateRequestOrderDetails($orderDetailsPayload);

        $elName = 'Заявка на сертификаты — ' . date('d.m.Y H:i');

        $jsonPayload = json_encode($lines, JSON_UNESCAPED_UNICODE);
        if ($jsonPayload === false) {
            return ['success' => false, 'errors' => [GetMessage('DNK_CERT_BUY_ERR_SUBMIT_JSON')]];
        }

        $propVals = [
            'TOTAL_SUM' => $total,
            'ITEMS_JSON' => $jsonPayload,
            'DELIVERY' => $deliveryEnumId,
            'PAYMENT' => $paymentEnumId,
            'CONTACT_NAME' => $contactName,
            'CONTACT_PHONE' => $contactPhone,
            'COMMENT' => $comment !== '' ? $comment : '',
            'USER' => $userId,
        ];

        if ($emailSnapshot !== '') {
            $propVals['CONTACT_EMAIL'] = $emailSnapshot;
        }

        $el = new CIBlockElement();

        $newId = (int)$el->Add([
            'IBLOCK_ID' => $requestIblockId,
            'ACTIVE' => 'Y',
            'NAME' => $elName,
            'DETAIL_TEXT' => $orderDetails['plain'],
            'DETAIL_TEXT_TYPE' => 'text',
            'PROPERTY_VALUES' => $propVals,
        ]);

        if ($newId <= 0) {
            return ['success' => false, 'errors' => [GetMessage('DNK_CERT_BUY_ERR_SAVE')]];
        }

        $siteIdForMail = (defined('SITE_ID') && is_string(SITE_ID) && SITE_ID !== '')
            ? SITE_ID
            : '';
        if ($siteIdForMail === '') {
            $appCtx = Context::getCurrent();
            if ($appCtx !== null) {
                $lid = $appCtx->getSite();
                $siteIdForMail = ($lid !== null && $lid !== '') ? $lid : 's1';
            } else {
                $siteIdForMail = 's1';
            }
        }

        $langMail = '';
        if (defined('LANGUAGE_ID') && is_string(LANGUAGE_ID) && LANGUAGE_ID !== '') {
            $langMail = LANGUAGE_ID;
        } else {
            $appCtxLang = Context::getCurrent();
            if ($appCtxLang !== null) {
                $langId = $appCtxLang->getLanguage();
                if ($langId !== null && $langId !== '') {
                    $langMail = (string)$langId;
                }
            }
        }

        $mailResult = MailEvent::send([
            'EVENT_NAME' => 'CUSTOM_MAIL',
            'LID' => $siteIdForMail,
            'LANGUAGE_ID' => $langMail,
            'MESSAGE_ID' => (int)DNK_CERTIFICATE_REQUEST_MAIL_TEMPLATE_ID,
            'C_FIELDS' => [
                'IBLOCK_ID' => (string)$requestIblockId,
                'ID' => (string)$newId,
                'DETAIL_INFO' => $orderDetails['html'],
            ],
        ]);

        if (!$mailResult->isSuccess()) {
            $mailErr = [];
            foreach ($mailResult->getErrors() as $errItem) {
                $mailErr[] = $errItem->getMessage();
            }
            if ($mailErr !== []) {
                error_log(sprintf(
                    '[dnk:certificate.buy] CUSTOM_MAIL enqueue failed requestId=%d: %s',
                    $newId,
                    implode('; ', $mailErr)
                ));
            }
        }

        $this->clearCertificateCartSession();

        return ['success' => true, 'requestId' => $newId];
    }

    private function resolveOrderConsentAgreementId(): int
    {
        $fromParams = (int)($this->arParams['USER_CONSENT_ID'] ?? 0);
        if ($fromParams > 0) {
            return $fromParams;
        }

        $resolved = UserConsentService::resolveAgreementIdByOption('AGREEMENT_PUBLIC_OFFER');

        return $resolved ?? 0;
    }

    private function isOrderConsentAccepted(\Bitrix\Main\HttpRequest $request): bool
    {
        $agreementId = $this->resolveOrderConsentAgreementId();
        if ($agreementId <= 0) {
            return true;
        }

        return $request->getPost('orderConsent') === 'Y';
    }

    private function isRegistrationConsentAccepted(\Bitrix\Main\HttpRequest $request): bool
    {
        $licenseName = class_exists(\TSolution\Validation::class)
            ? \TSolution\Validation::LICENSE_INPUT_NAME
            : 'licenses_register';

        return $request->getPost($licenseName) === 'Y'
            || $request->getPost('registrationConsent') === 'Y';
    }

    private function persistConsentsAfterAuth(int $userId, string $scenario, \Bitrix\Main\HttpRequest $request): void
    {
        $orderAgreementId = $this->resolveOrderConsentAgreementId();
        if ($orderAgreementId > 0 && $this->isOrderConsentAccepted($request)) {
            UserConsentService::acceptConsent(
                $userId,
                $orderAgreementId,
                UserConsentService::ORIGINATOR_ORDER
            );
        }

        if ($scenario === CertificateBuyPhoneAuth::SCENARIO_REGISTER) {
            $regAgreementId = UserConsentService::resolveAgreementIdByOption('AGREEMENT_REGISTRATION');
            if ($regAgreementId !== null && $regAgreementId > 0 && $this->isRegistrationConsentAccepted($request)) {
                UserConsentService::acceptConsent(
                    $userId,
                    $regAgreementId,
                    UserConsentService::ORIGINATOR_ACCEPT
                );
            }
        }
    }

    /**
     * @return array<int, int> element_id => qty (только qty > 0)
     */
    private function readSparseCartQuantitiesFromSession(): array
    {
        $session = Application::getInstance()->getSession();
        $session->start();

        $v = $session->get(self::SESSION_CART_KEY);
        if (!is_array($v)) {
            return [];
        }

        $out = [];
        foreach ($v as $key => $qty) {
            $eid = (int)$key;
            if ($eid <= 0) {
                continue;
            }
            $q = max(0, min(self::MAX_QTY, (int)$qty));
            if ($q > 0) {
                $out[$eid] = $q;
            }
        }

        return $out;
    }

    /**
     * @param array<int, array<string, mixed>> $catalogRows
     * @param array<int, int> $sparse
     *
     * @return array<int, int> только id из текущей витрины
     */
    private function filterSparseCartAgainstCatalog(array $catalogRows, array $sparse): array
    {
        if ($sparse === []) {
            return [];
        }

        $allowed = [];
        foreach ($catalogRows as $row) {
            $allowed[(int)($row['id'] ?? 0)] = true;
        }

        $filtered = [];

        foreach ($sparse as $eid => $qty) {
            $eid = (int)$eid;
            $qty = max(1, min(self::MAX_QTY, (int)$qty));
            if ($eid > 0 && isset($allowed[$eid])) {
                $filtered[$eid] = $qty;
            }
        }

        return $filtered;
    }

    /**
     * @param array<int, int> $sparse element_id => qty
     */
    private function persistSparseCartToSession(array $sparse): void
    {
        $session = Application::getInstance()->getSession();
        $session->start();

        if ($sparse === []) {
            $session->remove(self::SESSION_CART_KEY);

            return;
        }

        $session->set(self::SESSION_CART_KEY, $sparse);
    }

    private function clearCertificateCartSession(): void
    {
        $session = Application::getInstance()->getSession();
        $session->start();
        $session->remove(self::SESSION_CART_KEY);
    }

    /**
     * Все активные элементы инфоблока сертификатов без привязки к разделам.
     *
     * @return array<int, array{id: int, NAME: string, DETAIL_PICTURE: int, NOMINAL_VALUE: float}>
     */
    private function loadCertificateItems(int $certIblockId): array
    {
        $filter = ['IBLOCK_ID' => $certIblockId, 'ACTIVE' => 'Y'];

        $items = [];

        $rs = CIBlockElement::GetList(
            ['PROPERTY_' . self::PROP_NOMINAL => 'ASC', 'NAME' => 'ASC'],
            $filter,
            false,
            false
        );

        while ($block = $rs->GetNextElement()) {
            $fields = $block->GetFields();
            $props = $block->GetProperties();
            $nomProp = ($props[self::PROP_NOMINAL] ?? []);
            $pv = $nomProp['VALUE'] ?? null;
            $nom = self::normalizeNominalValue($pv ?? '');
            if ($nom <= 0) {
                continue;
            }

            $items[] = [
                'id' => (int)$fields['ID'],
                'NAME' => (string)$fields['NAME'],
                'DETAIL_PICTURE' => (int)$fields['DETAIL_PICTURE'],
                'NOMINAL_VALUE' => $nom,
            ];
        }

        return $items;
    }

    /**
     * @param mixed $value
     */
    private static function normalizeNominalValue($value): float
    {
        if (is_array($value)) {
            $first = reset($value);
            $value = $first !== false ? $first : '';
        }
        $s = trim((string)$value);
        if ($s === '') {
            return 0;
        }

        return round((float)str_replace(',', '.', preg_replace('/[^\d.,\-]/', '', $s)), 4);
    }

    private function formatBynAmount(float $amount): string
    {
        if (!Loader::includeModule('currency')) {
            return number_format($amount, 2, ',', '') . ' BYN';
        }

        return (string)\CCurrencyLang::CurrencyFormat($amount, 'BYN', true);
    }

    private function resizeDetailPicture(int $fileId): string
    {
        if ($fileId <= 0) {
            return '';
        }

        $resized = \CFile::ResizeImageGet(
            $fileId,
            ['width' => 360, 'height' => 220],
            BX_RESIZE_IMAGE_PROPORTIONAL,
            true
        );

        return is_array($resized) ? (string)($resized['src'] ?? '') : '';
    }

    /**
     * @return array{name: string, phone: string, isAuthorized: bool}
     */
    private function loadProfilePrefill(): array
    {
        $userId = (int)CurrentUser::get()->getId();
        if ($userId <= 0) {
            return ['name' => '', 'phone' => '', 'isAuthorized' => false];
        }

        $row = UserTable::getRow([
            'select' => ['NAME', 'LAST_NAME', 'SECOND_NAME', 'PERSONAL_PHONE', 'PERSONAL_MOBILE'],
            'filter' => ['=ID' => $userId],
        ]);

        if (!is_array($row)) {
            return ['name' => '', 'phone' => '', 'isAuthorized' => true];
        }

        $nameParts = array_filter([
            trim((string)($row['LAST_NAME'] ?? '')),
            trim((string)($row['NAME'] ?? '')),
            trim((string)($row['SECOND_NAME'] ?? '')),
        ]);

        $name = trim(implode(' ', $nameParts));

        $phone = trim((string)($row['PERSONAL_PHONE'] ?? ''));
        if ($phone === '') {
            $phone = trim((string)($row['PERSONAL_MOBILE'] ?? ''));
        }

        return [
            'name' => $name,
            'phone' => $phone,
            'isAuthorized' => true,
        ];
    }

    private function loadNominalForElement(int $certIblockId, int $elementId): float
    {
        $filter = [
            'IBLOCK_ID' => $certIblockId,
            'ID' => $elementId,
            'ACTIVE' => 'Y',
        ];

        $rs = CIBlockElement::GetList(
            [],
            $filter,
            false,
            false,
            ['ID', 'PROPERTY_' . self::PROP_NOMINAL]
        );

        if ($ob = $rs->Fetch()) {
            return self::normalizeNominalValue($ob['PROPERTY_' . self::PROP_NOMINAL . '_VALUE'] ?? '');
        }

        return 0.0;
    }

    private function resolveListEnumByXml(int $iblockId, string $propCode, string $xml): ?int
    {
        $p = CIBlockProperty::GetList([], ['IBLOCK_ID' => $iblockId, 'CODE' => $propCode])->Fetch();
        if (!is_array($p)) {
            return null;
        }
        $propId = (int)$p['ID'];
        if ($propId <= 0) {
            return null;
        }

        $e = CIBlockPropertyEnum::GetList([], ['PROPERTY_ID' => $propId, 'XML_ID' => $xml])->Fetch();

        return is_array($e) ? (int)$e['ID'] : null;
    }

    private function resolveListEnumCaption(int $enumId): string
    {
        if ($enumId <= 0) {
            return '';
        }

        $arr = CIBlockPropertyEnum::GetList([], ['ID' => $enumId])->Fetch();

        return is_array($arr) ? trim((string)($arr['VALUE'] ?? '')) : '';
    }

    /**
     * @param int[] $elementIds
     * @return array<int, string>
     */
    private function loadCertificateElementNames(int $certIblockId, array $elementIds): array
    {
        if ($certIblockId <= 0 || $elementIds === []) {
            return [];
        }

        $names = [];
        $rs = CIBlockElement::GetList(
            ['ID' => 'ASC'],
            [
                'IBLOCK_ID' => $certIblockId,
                'ID' => $elementIds,
                'CHECK_PERMISSIONS' => 'N',
            ],
            false,
            false,
            ['ID', 'NAME']
        );
        while ($row = $rs->Fetch()) {
            $names[(int)$row['ID']] = trim((string)($row['NAME'] ?? ''));
        }

        return $names;
    }

    /**
     * Активные точки самовывоза для фронтенда.
     *
     * @return list<array{
     *     id: int,
     *     name: string,
     *     picture: string,
     *     address: string,
     *     phone: string,
     *     schedule: string,
     *     lat: float|null,
     *     lon: float|null
     * }>
     */
    private function loadPickupStores(int $iblockId): array
    {
        if ($iblockId <= 0) {
            return [];
        }

        $stores = [];

        $rs = CIBlockElement::GetList(
            ['SORT' => 'ASC', 'NAME' => 'ASC'],
            ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y'],
            false,
            false
        );

        while ($block = $rs->GetNextElement()) {
            $fields = $block->GetFields();
            $props = $block->GetProperties();
            $coords = self::parseMapCoords((string)($props['MAP']['VALUE'] ?? ''));

            $stores[] = [
                'id' => (int)$fields['ID'],
                'name' => trim((string)$fields['NAME']),
                'picture' => $this->resizePreviewPicture((int)$fields['PREVIEW_PICTURE']),
                'address' => self::normalizeIblockStringProperty($props['ADDRESS']['VALUE'] ?? ''),
                'phone' => self::normalizeIblockStringProperty($props['PHONE']['VALUE'] ?? ''),
                'schedule' => self::normalizeScheduleProperty($props['SCHEDULE'] ?? []),
                'lat' => $coords['lat'],
                'lon' => $coords['lon'],
            ];
        }

        return $stores;
    }

    /**
     * @return array{name: string, address: string, phone: string, schedule: string}|null
     */
    private function loadPickupStoreById(int $iblockId, int $elementId): ?array
    {
        if ($iblockId <= 0 || $elementId <= 0) {
            return null;
        }

        $rs = CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => $iblockId, 'ID' => $elementId, 'ACTIVE' => 'Y'],
            false,
            false
        );

        if (!$block = $rs->GetNextElement()) {
            return null;
        }

        $fields = $block->GetFields();
        $props = $block->GetProperties();

        return [
            'name' => trim((string)$fields['NAME']),
            'address' => self::normalizeIblockStringProperty($props['ADDRESS']['VALUE'] ?? ''),
            'phone' => self::normalizeIblockStringProperty($props['PHONE']['VALUE'] ?? ''),
            'schedule' => self::normalizeScheduleProperty($props['SCHEDULE'] ?? []),
        ];
    }

    /**
     * @param mixed $value
     */
    private static function normalizeIblockStringProperty($value): string
    {
        if (is_array($value)) {
            $first = reset($value);

            return trim((string)($first !== false ? $first : ''));
        }

        return trim((string)$value);
    }

    /**
     * @param array<string, mixed> $scheduleProp
     */
    private static function normalizeScheduleProperty(array $scheduleProp): string
    {
        $value = $scheduleProp['~VALUE'] ?? $scheduleProp['VALUE'] ?? '';
        if (is_array($value)) {
            $text = (string)($value['TEXT'] ?? '');
            if ($text !== '') {
                return trim(strip_tags($text));
            }

            return '';
        }

        return trim(strip_tags((string)$value));
    }

    /**
     * @return array{lat: float|null, lon: float|null}
     */
    private static function parseMapCoords(string $mapValue): array
    {
        $mapValue = trim($mapValue);
        if ($mapValue === '') {
            return ['lat' => null, 'lon' => null];
        }

        // MAP в проекте: "широта,долгота" с точкой как десятичным разделителем (см. news/stores).
        if (!preg_match('/^\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*$/u', $mapValue, $m)) {
            return ['lat' => null, 'lon' => null];
        }

        $lat = (float)$m[1];
        $lon = (float)$m[2];
        if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
            return ['lat' => null, 'lon' => null];
        }

        return ['lat' => $lat, 'lon' => $lon];
    }

    private function resizePreviewPicture(int $fileId): string
    {
        if ($fileId <= 0) {
            return '';
        }

        $resized = \CFile::ResizeImageGet(
            $fileId,
            ['width' => 80, 'height' => 80],
            BX_RESIZE_IMAGE_PROPORTIONAL,
            true
        );

        return is_array($resized) ? (string)($resized['src'] ?? '') : '';
    }
}
