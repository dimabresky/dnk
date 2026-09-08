<?php
//<title>Diginetica AnyQuery</title>
/**
 * Профиль экспорта глобального фида AnyQuery / Diginetica.
 * Пишет статический UTF-8 XML (не PHP-обёртку).
 *
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @global string $SETUP_FILE_NAME
 * @global string $SETUP_SERVER_NAME
 * @global string $USE_HTTPS
 * @global array $YANDEX_EXPORT
 * @global string $strExportErrorMessage
 */

use Dnk\PhpInterface\DigineticaGlobalFeedExporter;

IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/catalog/export_setup_templ.php');
set_time_limit(0);

global $USER, $APPLICATION;
$bTmpUserCreated = false;
if (!CCatalog::IsUserExists())
{
	$bTmpUserCreated = true;
	if (isset($USER))
		$USER_TMP = $USER;
	$USER = new CUser();
}

CCatalogDiscountSave::Disable();
/** @noinspection PhpDeprecationInspection */
CCatalogDiscountCoupon::ClearCoupon();
if ($USER->IsAuthorized())
{
	/** @noinspection PhpDeprecationInspection */
	CCatalogDiscountCoupon::ClearCouponsByManage($USER->GetID());
}

$strExportErrorMessage = '';

$usedProtocol = (isset($USE_HTTPS) && $USE_HTTPS == 'Y' ? 'https://' : 'http://');
$SETUP_SERVER_NAME = isset($SETUP_SERVER_NAME) ? trim((string)$SETUP_SERVER_NAME) : '';

if (!isset($SETUP_FILE_NAME) || $SETUP_FILE_NAME == '')
{
	$strExportErrorMessage .= GetMessage("CET_ERROR_NO_FILENAME")."<br>";
}
elseif (preg_match(BX_CATALOG_FILENAME_REG, $SETUP_FILE_NAME))
{
	$strExportErrorMessage .= GetMessage("CES_ERROR_BAD_EXPORT_FILENAME")."<br>";
}

if ($strExportErrorMessage == '')
{
	$SETUP_FILE_NAME = Rel2Abs("/", $SETUP_FILE_NAME);
}

if ($strExportErrorMessage == '' && (empty($YANDEX_EXPORT) || !is_array($YANDEX_EXPORT)))
{
	$strExportErrorMessage .= GetMessage("CET_ERROR_NO_IBLOCKS")."<br>";
}

if ($strExportErrorMessage == '')
{
	$serverName = $SETUP_SERVER_NAME;
	if ($serverName === '')
	{
		if (defined('DNK_SITE_URL'))
		{
			$serverName = (string)preg_replace('#^https?://#i', '', (string)DNK_SITE_URL);
		}
		if ($serverName === '')
		{
			$serverName = (string)COption::GetOptionString('main', 'server_name', '');
		}
	}

	$siteUrl = defined('DNK_SITE_URL') && $SETUP_SERVER_NAME === ''
		? rtrim((string)DNK_SITE_URL, '/')
		: $usedProtocol.rtrim($serverName, '/');

	$shopName = defined('DNK_PRODUCT_FEED_CHANNEL_TITLE')
		? trim((string)DNK_PRODUCT_FEED_CHANNEL_TITLE)
		: '';
	if ($shopName === '')
	{
		$shopName = (string)COption::GetOptionString('main', 'site_name', '');
	}

	$absoluteFilePath = rtrim(str_replace('\\', '/', (string)$_SERVER['DOCUMENT_ROOT']), '/').$SETUP_FILE_NAME;

	try
	{
		DigineticaGlobalFeedExporter::export(
			array_values($YANDEX_EXPORT),
			$absoluteFilePath,
			$siteUrl,
			$shopName
		);
	}
	catch (Throwable $e)
	{
		$strExportErrorMessage .= $e->getMessage()."\n";
	}
}

CCatalogDiscountSave::Enable();

if ($bTmpUserCreated)
{
	if (isset($USER_TMP))
	{
		$USER = $USER_TMP;
		unset($USER_TMP);
	}
}
