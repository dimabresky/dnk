<?
namespace Dnk\Components;

use Bitrix\Main\Loader,
	Bitrix\Main\Localization\Loc,
	Bitrix\Sale,
	Bitrix\Main\SystemException,
	CPremier as Solution,
	Aspro\Premier\VoteIgnoreTable;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

require_once $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/vendor/php/solution.php';

$lang = isset($_REQUEST['lang']) ? trim($_REQUEST['lang']) : LANGUAGE_ID;
Loc::setCurrentLang($lang);
Loc::loadMessages(__FILE__);

class VoteProductsController extends \Bitrix\Main\Engine\Controller {
	public function configureActions() {
		return [
			'ignore' => [
				'prefilters' => [],
			],
			'clear' => [
				'prefilters' => [],
			],
			'refresh' => [
				'prefilters' => [],
			],
		];
	}

	public function ignoreAction($productId, $siteId, $lang, $sessid) {
		$userId = $GLOBALS['USER']->GetID();

		$this->includeModules();
		$this->checkSession($sessid);
		$this->checkSite($siteId);
		$this->checkUser($userId);
		$this->checkProduct($productId, $userId, $siteId);

		$result = VoteIgnoreTable::getList([
			'filter' => [
				'=USER_ID' => $userId,
				'=SITE_ID' => $siteId,
				'=PRODUCT_ID' => $productId,
			],
			'limit' => 1,
			'select' => [
				'ID',
			],
		]);
		if ($item = $result->fetchObject()) {
			return $item->getId();
		}

		$fields = [
			'USER_ID' => $userId,
			'SITE_ID' => $siteId,
			'PRODUCT_ID' => $productId,
		];

		$result = VoteIgnoreTable::add($fields);
		if ($result->isSuccess()) {
			return $result->getId();
		}
		else {
			$errors = $result->getErrorMessages();
			throw new SystemException(reset($errors));
		}
	}

	public function clearAction($siteId, $lang, $sessid) {
		$userId = $GLOBALS['USER']->GetID();

		$this->includeModules();
		$this->checkSession($sessid);
		$this->checkSite($siteId);
		$this->checkUser($userId);

		$productsIds = static::getAllProducts($siteId, $userId);
		if ($productsIds) {
			$result = VoteIgnoreTable::getList([
				'filter' => [
					'=USER_ID' => $userId,
					'=SITE_ID' => $siteId,
					'=PRODUCT_ID' => $productsIds,
				],
				'select' => [
					'ID',
					'PRODUCT_ID',
				],
			]);
			while ($item = $result->fetchObject()) {
				unset($productsIds[$item->getProductId()]);
			}

			if ($productsIds) {
				foreach ($productsIds as $productId) {
					$fields = [
						'USER_ID' => $userId,
						'SITE_ID' => $siteId,
						'PRODUCT_ID' => $productId,
					];
			
					$result = VoteIgnoreTable::add($fields);
					if (!$result->isSuccess()) {
						$errors = $result->getErrorMessages();
						throw new SystemException(reset($errors));
					}
				}
			}
		}
	}

	public function refreshAction($template, $signedParameters, $siteId, $lang, $sessid) {
		$this->includeModules();		
		$this->checkSession($sessid);
		$this->checkSite($siteId);

		$componentName = 'dnk:vote.products.premier';

		$signer = new \Bitrix\Main\Component\ParameterSigner;
		$arParams = $signer->unsignParameters(str_replace(':', '.', $componentName), $signedParameters);

		$template = $arParams['COMPONENT_TEMPLATE'] ?: $template;
		$arParams['CUSTOM_LANGUAGE_ID'] = $lang;
		$arParams['CUSTOM_SITE_ID'] = $siteId;

		$tmp = [
			'componentName' => $componentName,
			'template' => $template,
			'arParams' => $arParams,
		];

		$GLOBALS['APPLICATION']->RestartBuffer();

		ob_start();
		$result = $GLOBALS['APPLICATION']->IncludeComponent(
			$tmp['componentName'],
			$tmp['template'],
			$tmp['arParams']
		);
		$content = trim(ob_get_clean());

		return [
			'result' => $result,
			'content' => $content,
		];
	}

	protected function includeModules() {
		if (!Loader::includeModule(Solution::moduleID)) {
			throw new SystemException(Loc::getMessage('VP_C_ERROR_MODULE_NOT_INSTALLED'));
		}

		if (!Loader::includeModule('sale')) {
			throw new SystemException(Loc::getMessage('VP_C_ERROR_MODULE_SALE_NOT_INSTALLED'));
		}
	}

	protected function checkSession($sessid) {
		if ($sessid !== bitrix_sessid()) {
			throw new SystemException(Loc::getMessage('VP_C_ERROR_BAD_SESSID'));
		}
	}

	protected function checkSite($siteId) {
		if (!$siteId) {
			throw new SystemException(Loc::getMessage('VP_C_ERROR_BAD_SITE'));
		}
		else {
			$arSite = \CSite::GetByID($siteId)->Fetch();
			if (!$arSite) {
				throw new SystemException(Loc::getMessage('VP_C_ERROR_BAD_SITE'));
			}
		}
	}

	protected function checkUser($userId) {
		if (!$userId) {
			throw new SystemException(Loc::getMessage('VP_C_ERROR_BAD_USER'));
		}		
	}

	protected function checkProduct($productId, $userId, $siteId) {
		if (!$productId) {
			throw new SystemException(Loc::getMessage('VP_C_ERROR_BAD_PRODUCT'));
		}
		else {
			$registry = Sale\Registry::getInstance(Sale\Registry::REGISTRY_TYPE_ORDER);
			$basketClassName = $registry->getBasketClassName();

			if (!$basketClassName::getList([
				'filter' => [
					'=LID' => $siteId,
					'=PRODUCT_ID' => $productId,
					'>ORDER_ID' => 0,
					'ORDER.USER_ID' => $userId,
					'ORDER.CANCELED' => 'N',
				],
				'limit' => 1,
				'select' => [
					'ID',
				],
			])->fetch()) {
				$arProducts = \CCatalogSKU::getOffersList(
					$productId,
					0,
					[],
					[],
					[]
				);

				if (
					$arProducts &&
					is_array($arProducts) &&
					isset($arProducts[$productId])
				) {
					$arOffersIds = array_keys($arProducts[$productId]);

					if ($arOffersIds) {
						if ($basketClassName::getList([
							'filter' => [
								'=LID' => $siteId,
								'PRODUCT_ID' => $arOffersIds,
								'>ORDER_ID' => 0,
								'ORDER.USER_ID' => $userId,
								'ORDER.CANCELED' => 'N',
							],
							'limit' => 1,
							'select' => [
								'ID',
							],
						])->fetch()) {
							return;
						}
					}
				}

				throw new SystemException(Loc::getMessage('VP_C_ERROR_BAD_PRODUCT'));
			}
		}
	}

	protected function getAllProducts($siteId, $userId) {
		$productsIds = [];

		$registry = Sale\Registry::getInstance(Sale\Registry::REGISTRY_TYPE_ORDER);
		$basketClassName = $registry->getBasketClassName();

		$basketItems = $basketClassName::getList([
			'filter' => [
				'=LID' => $siteId,
				'>ORDER_ID' => 0,
				'ORDER.USER_ID' => $userId,
				'ORDER.CANCELED' => 'N',
			],
			'select' => [
				'ID',
				'PRODUCT_ID',
				'TYPE',
				'SET_PARENT_ID',
				'MODULE',
			],
		]);
		while ($basketItem = $basketItems->fetch()) {
			if (\CSaleBasketHelper::isSetItem($basketItem)) {
				continue;
			}

			if ($basketItem['MODULE'] == 'sale') {
				// inner payment
				continue;
			}

			$productId = $basketItem['PRODUCT_ID'];
			$productsIds[$productId] = $productId;
		}

		if ($productsIds) {
			$arProductsByOffersId = \CCatalogSKU::getProductList($productsIds);
			if ($arProductsByOffersId) {
				foreach ($arProductsByOffersId as $offerId => $arProduct) {
					if ($arProduct && $arProduct['ID']) {
						$productsIds[$arProduct['ID']] = $arProduct['ID'];
						unset($productsIds[$offerId]);
					}
				}
			}
		}

		return $productsIds;
	}
}