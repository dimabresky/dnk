<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main,
	Bitrix\Main\Localization\Loc,
	Bitrix\Sale,
	Bitrix\Main\Loader,
	CPremier as Solution;

Loc::loadMessages(__FILE__);

class PersonalSection extends CBitrixComponent {
	public static function getSettingsScript($componentPath, $settingsName) {
		$path = $componentPath.'/settings/'.$settingsName.'/script.js';
		$file = new Main\IO\File(Main\Application::getDocumentRoot().$path);

		return $path.'?'.$file->getModificationTime();
	}

	public function onPrepareComponentParams($arParams) {
		$arParams['SHOW_PRIVATE_PAGE'] = ($arParams['SHOW_PRIVATE_PAGE'] ?? 'Y') === 'Y' ? 'Y' : 'N';
		$arParams['SHOW_ACCOUNT_PAGE'] = ($arParams['SHOW_ACCOUNT_PAGE'] ?? 'Y') === 'Y' ? 'Y' : 'N';
		$arParams['SHOW_PROFILE_PAGE'] = ($arParams['SHOW_PROFILE_PAGE'] ?? 'Y') === 'Y' ? 'Y' : 'N';
		$arParams['SHOW_ORDER_PAGE'] = ($arParams['SHOW_ORDER_PAGE'] ?? 'Y') === 'Y' ? 'Y' : 'N';
		$arParams['SHOW_SUBSCRIBE_PAGE'] = ($arParams['SHOW_SUBSCRIBE_PAGE'] ?? 'Y') === 'Y' ? 'Y' : 'N';
		$arParams['SHOW_FAVORITE_PAGE'] = ($arParams['SHOW_FAVORITE_PAGE'] ?? 'Y') === 'Y' ? 'Y' : 'N';
		$arParams['CUSTOM_PAGES'] = $arParams['CUSTOM_PAGES'] ?? '';
		
		$arParams['BANNERS_HIDDEN_SM'] = ($arParams['BANNERS_HIDDEN_SM'] ?? 'N') === 'Y' ? 'Y' : 'N';
		$arParams['BANNERS_HIDDEN_XS'] = ($arParams['BANNERS_HIDDEN_XS'] ?? 'N') === 'Y' ? 'Y' : 'N';

		$arParams['PATH_TO_HELP'] = $arParams['PATH_TO_HELP'] ?? SITE_DIR.'help/faq/';
		$arParams['PATH_TO_BASKET'] = $arParams['PATH_TO_BASKET'] ?? Solution::GetBasketPage() ?? SITE_DIR.'basket/';
		$arParams['PATH_TO_CATALOG'] = $arParams['PATH_TO_CATALOG'] ?? Solution::GetCatalogPage() ?? SITE_DIR.'catalog/';
		
		$arParams['MAIN_BLOCKS_ORDER'] = preg_replace('/[^_\-,a-z0-9]/i', '', $arParams['MAIN_BLOCKS_ORDER'] ?? 'banners,private,account,links,orders,votes,recoms');
		$arParams['CUSTOM_MAIN_BLOCKS'] = $arParams['CUSTOM_MAIN_BLOCKS'] ?? '';
		$arParams['MAIN_LINKS_ORDER'] = preg_replace('/[^_\-,a-z0-9]/i', '', $arParams['MAIN_LINKS_ORDER'] ?? 'favorites,orders,subscribes,profiles,help');
		$arParams['CUSTOM_MAIN_LINKS'] = $arParams['CUSTOM_MAIN_LINKS'] ?? '';
		
		$arParams['SEND_INFO_PRIVATE'] = ($arParams['SEND_INFO_PRIVATE'] ?? 'N') === 'Y' ? 'Y' : 'N';
		$arParams['CHECK_RIGHTS_PRIVATE'] = ($arParams['CHECK_RIGHTS_PRIVATE'] ?? 'N') === 'Y' ? 'Y' : 'N';
		$arParams['SHOW_ACCOUNT_COMPONENT'] = ($arParams['SHOW_ACCOUNT_COMPONENT'] ?? 'Y') === 'Y' ? 'Y' : 'N';
		$arParams['SHOW_ACCOUNT_PAY_COMPONENT'] = ($arParams['SHOW_ACCOUNT_PAY_COMPONENT'] ?? 'Y') === 'Y' ? 'Y' : 'N';
		$arParams['ACCOUNT_PAYMENT_PERSON_TYPE'] = $arParams['ACCOUNT_PAYMENT_PERSON_TYPE'] ?? '1';
		$arParams['ACCOUNT_PAYMENT_ELIMINATED_PAY_SYSTEMS'] = (array)($arParams['ACCOUNT_PAYMENT_ELIMINATED_PAY_SYSTEMS'] ?? ['0']);
		$arParams['ACCOUNT_PAYMENT_SELL_SHOW_FIXED_VALUES'] = ($arParams['ACCOUNT_PAYMENT_SELL_SHOW_FIXED_VALUES'] ?? 'Y') === 'Y' ? 'Y' : 'N';
		$arParams['ACCOUNT_PAYMENT_SELL_TOTAL'] = $arParams['ACCOUNT_PAYMENT_SELL_TOTAL'] ?? '';
		$arParams['ACCOUNT_PAYMENT_SELL_USER_INPUT'] = ($arParams['ACCOUNT_PAYMENT_SELL_USER_INPUT'] ?? 'Y') === 'Y' ? 'Y' : 'N';
		$arParams['CUSTOM_SELECT_PROPS'] = (array)($arParams['CUSTOM_SELECT_PROPS'] ?? []);
		$arParams['ORDER_HIDE_USER_INFO'] = (array)($arParams['ORDER_HIDE_USER_INFO'] ?? [0]);
		$arParams['ORDER_HISTORIC_STATUSES'] = (array)($arParams['ORDER_HISTORIC_STATUSES'] ?? ['F']);
		$arParams['ORDER_HIDE_STATUSES'] = (array)($arParams['ORDER_HIDE_STATUSES'] ?? []);
		$arParams['ORDER_RESTRICT_CHANGE_PAYSYSTEM'] = (array)($arParams['ORDER_RESTRICT_CHANGE_PAYSYSTEM'] ?? []);
		$arParams['ORDER_DEFAULT_SORT'] = $arParams['ORDER_DEFAULT_SORT'] ?? 'DATE_INSERT';
		$arParams['ORDER_REFRESH_PRICES'] = ($arParams['ORDER_REFRESH_PRICES'] ?? 'N') === 'Y' ? 'Y' : 'N';
		$arParams['ACCOUNT_PAYMENT_SELL_USER_INPUT'] = ($arParams['ACCOUNT_PAYMENT_SELL_USER_INPUT'] ?? 'Y') === 'Y' ? 'Y' : 'N';
		$arParams['ORDER_DISALLOW_CANCEL'] = ($arParams['ORDER_DISALLOW_CANCEL'] ?? 'N') === 'Y' ? 'Y' : 'N';
		$arParams['ORDER_CANCEL_REASON_REQUIRED'] = ($arParams['ORDER_CANCEL_REASON_REQUIRED'] ?? 'N') === 'Y' ? 'Y' : 'N';
		$arParams['ORDER_CANCEL_REASONS'] = $arParams['ORDER_CANCEL_REASONS'] ?? '';
		$arParams['ALLOW_INNER'] = ($arParams['ALLOW_INNER'] ?? 'N') === 'Y' ? 'Y' : 'N';
		$arParams['ONLY_INNER_FULL'] = ($arParams['ONLY_INNER_FULL'] ?? 'N') === 'Y' ? 'Y' : 'N';
		$arParams['NAV_TEMPLATE'] = $arParams['NAV_TEMPLATE'] ?? '';
		$arParams['ORDERS_PER_MAIN_PAGE'] = $arParams['ORDERS_PER_MAIN_PAGE'] ?? '10';
		$arParams['ORDERS_PER_PAGE'] = $arParams['ORDERS_PER_PAGE'] ?? '20';
		$arParams['USE_AJAX_LOCATIONS_PROFILE'] = ($arParams['USE_AJAX_LOCATIONS_PROFILE'] ?? 'N') === 'Y' ? 'Y' : 'N';
		$arParams['PROFILES_PER_PAGE'] = $arParams['PROFILES_PER_PAGE'] ?? '20';
		$arParams['VOTE_PRODUCTS_PER_MAIN_PAGE'] = $arParams['VOTE_PRODUCTS_PER_MAIN_PAGE'] ?? '10';
		$arParams['VOTE_ORDER_STATUSES'] = (array)($arParams['VOTE_ORDER_STATUSES'] ?? ['F']);
		$arParams['VOTE_BLOG_URL'] = $arParams['VOTE_BLOG_URL'] ?? 'catalog_comments';
		$arParams['RCM_ELEMENTS_COUNT'] = $arParams['RCM_ELEMENTS_COUNT'] ?? '10';
		$arParams['RCM_TYPE'] = $arParams['RCM_TYPE'] ?? 'any_personal';
		
		$arParams['USE_PRIVATE_PAGE_TO_AUTH'] = ($arParams['USE_PRIVATE_PAGE_TO_AUTH'] ?? 'N') === 'Y' ? 'Y' : 'N';
		$arParams['SEF_URL_TEMPLATES'] = isset($arParams['SEF_URL_TEMPLATES']) && is_array($arParams['SEF_URL_TEMPLATES']) ? $arParams['SEF_URL_TEMPLATES'] : [];

		$arParams['DATE_FORMAT'] = $arParams['DATE_FORMAT'] ?? 'j F Y';
	
		return $arParams;
	}

	public function executeComponent() {
		$this->setFrameMode(false);
		
		$sectionsList = [];
		$variables = [];

		if (!Loader::includeModule('aspro.premier')) {
			ShowError(Loc::getMessage('SOLUTION_MODULE_NOT_INSTALLED'));
			return;
		}

		$request = Bitrix\Main\Application::getInstance()->getContext()->getRequest();

		$bSaleMode = Solution::isSaleMode();
		$bSaleAccounts = $bSaleMode && CBXFeatures::IsFeatureEnabled('SaleAccounts');
		$bBlog = Loader::includeModule('blog');
		$bSubscribe = Loader::includeModule('subscribe');

		$this->arResult = [
			'USER_ID' => $GLOBALS['USER']->GetID(),
			'SALE_MODE' => $bSaleMode,
			'SALE_ACCOUNTS' => $bSaleAccounts,
			'BLOG' => $bBlog,
			'SUBSCRIBE' => $bSubscribe,
			'AJAX_NAV' => ($bAjaxNav = $request->get('AJAX_REQUEST') === 'Y'),
			'AJAX_POST' => $bAjaxNav || $request->get('AJAX_POST') === 'Y',
		];

		$defaultUrlTemplates = array(
			'index' => 'index.php', // it`s const value and hidden component parameter
			'private' => 'private/',
			'password_change' => 'change-password/',
			'account' => 'account/',
			'profiles' => 'profiles/',
			'profile' => 'profiles/#ID#',
			'orders' => 'orders/',
			'order' => 'orders/#ID#',
			'order_old' => 'order/detail/#ID#/',
			'order_cancel' => 'orders/cancel/#ID#',
			'order_cancel_old' => 'order/cancel/#ID#',
			'order_cancel_old2' => 'cancel/#ID#',
			'order_cancel_old3' => 'orders/order_cancel.php?ID=#ID#',
			'order_cancel_old4' => 'order/order_cancel.php?ID=#ID#',
			'payment' => 'payment/',
			'subscribe' => 'subscribe/',
			'subscribe_old' => 'subscribe/subscr_edit.php',
			'unsubscribe' => 'unsubscribe/',
			'unsubscribe_old' => 'subscribe/unsubscribe.php',
			'favorite' => 'favorite/',
		);

		if (!$bSaleMode) {
			$this->arParams['SHOW_ACCOUNT_PAGE'] = 'N';
			$this->arParams['SHOW_PROFILE_PAGE'] = 'N';
			$this->arParams['SHOW_ORDER_PAGE'] = 'N';
			$this->arParams['MAIN_BLOCKS_ORDER'] = preg_replace('/[-]*(account|orders|recoms)/', '-$1', $this->arParams['MAIN_BLOCKS_ORDER']);
			$this->arParams['MAIN_LINKS_ORDER'] = preg_replace('/[-]*(profiles|orders)/', '-$1', $this->arParams['MAIN_LINKS_ORDER']);
			$this->arParams['SHOW_ACCOUNT_COMPONENT'] = 'N';
			$this->arParams['SHOW_ACCOUNT_PAY_COMPONENT'] = 'N';
		}
		else {
			if ($this->arParams['SHOW_ORDER_PAGE'] === 'N') {
				$this->arParams['MAIN_BLOCKS_ORDER'] = preg_replace('/[-]*(orders)/', '-$1', $this->arParams['MAIN_BLOCKS_ORDER']);
				$this->arParams['MAIN_LINKS_ORDER'] = preg_replace('/[-]*(orders)/', '-$1', $this->arParams['MAIN_LINKS_ORDER']);
			}
		}

		$sectionsList[] = 'account';
		if (
			!$bSaleAccounts ||
			(
				$this->arParams['SHOW_ACCOUNT_COMPONENT'] === 'N' &&
				$this->arParams['SHOW_ACCOUNT_PAY_COMPONENT'] === 'N'
			)
		) {
			$this->arParams['SHOW_ACCOUNT_PAGE'] = 'N';
			$this->arParams['MAIN_BLOCKS_ORDER'] = preg_replace('/[-]*(account)/', '-$1', $this->arParams['MAIN_BLOCKS_ORDER']);
		}

		$sectionsList[] = 'subscribe';
		$sectionsList[] = 'unsubscribe';
		if (
			!$bSubscribe &&
			!$bSaleMode
		) {
			$this->arParams['SHOW_SUBSCRIBE_PAGE'] = 'N';
		}
		// if (!\CComponentUtil::isComponent('/bitrix/components/bitrix/catalog.product.subscribe.list')) {
		// 	$this->arParams['SHOW_SUBSCRIBE_PAGE'] = 'N';
		// 	unset($this->arParams["SEF_URL_TEMPLATES"]['subscribe']);
		// }

		if (
			!$bSaleMode ||
			!$bBlog
		) {
			$this->arParams['MAIN_BLOCKS_ORDER'] = preg_replace('/[-]*(votes)/', '-$1', $this->arParams['MAIN_BLOCKS_ORDER']);
		}

		$componentVariables = [
			'CANCEL',
			'COPY_ORDER',
			'PAYMENT',
			'ID',
		];

		$customPagesList = CUtil::JsObjectToPhp($this->arParams['~CUSTOM_PAGES'] ?? '[]', true);
		$customPagesList = is_array($customPagesList) ? $customPagesList : [];
		foreach ($customPagesList as $i => $arCustomPage) {
			if (!is_array($arCustomPage)) {
				unset($customPagesList[$i]);
				continue;
			}

			$bActive = boolval($arCustomPage['active'] ?? false);
			if (!$bActive) {
				unset($customPagesList[$i]);
				continue;
			}

			$name = $customPagesList[$i]['name'] = trim($arCustomPage['name']);
			$path = $customPagesList[$i]['path'] = trim($arCustomPage['path']);
			$page = $customPagesList[$i]['page'] = trim($arCustomPage['page']);

			if (
				!strlen($name) ||
				!strlen($path) ||
				!strlen($page)
			) {
				unset($customPagesList[$i]);
				continue;
			}
			
			$defaultUrlTemplates[$page] = $path;
		}

		$this->arResult['PATH_TO_HELP'] = $this->arParams['PATH_TO_HELP'];
		$this->arResult['PATH_TO_CATALOG'] = $this->arParams['PATH_TO_CATALOG'];
		$this->arResult['PATH_TO_BASKET'] = $this->arParams['PATH_TO_BASKET'];

		if ($this->arParams['SEF_MODE'] === 'Y') {
			$templatesUrls = CComponentEngine::makeComponentUrlTemplates($defaultUrlTemplates, $this->arParams['SEF_URL_TEMPLATES']);

			foreach ($templatesUrls as $url => $value) {
				$this->arResult['PATH_TO_'.ToUpper($url)] = $this->arParams['SEF_FOLDER'].$value;
			}

			$this->arResult['PATH_TO_ORDER_COPY'] = $this->arResult['PATH_TO_ORDERS'].'?COPY_ORDER=Y&ID=#ID#';

			$variableAliases = CComponentEngine::makeComponentVariableAliases([], $this->arParams['VARIABLE_ALIASES']);

			$componentPage = CComponentEngine::parseComponentPath(
				$this->arParams['SEF_FOLDER'],
				$templatesUrls,
				$variables
			);

			if ($componentPage === 'profile_detail') {
				$componentPage = 'profile';
			}

			if ($componentPage === 'subscribe_old') {
				$componentPage = 'subscribe';
			}

			if ($componentPage === 'unsubscribe_old') {
				$componentPage = 'unsubscribe';
			}

			if (
				$componentPage === 'order_detail' ||
				$componentPage === 'order_old'
			) {
				$componentPage = 'order';
			}

			if (
				$componentPage === 'order_cancel_old' || 
				$componentPage === 'order_cancel_old2' ||
				$componentPage === 'order_cancel_old3' ||
				$componentPage === 'order_cancel_old4'
			) {
				$componentPage = 'order_cancel';
			}

			CComponentEngine::initComponentVariables($componentPage, $componentVariables, $variableAliases, $variables);

			if (empty($componentPage)) {
				LocalRedirect($this->arParams['SEF_FOLDER']);
			}

			$this->arResult = array_merge(
				$this->arResult,
				[
					'SEF_FOLDER' => $this->arParams['SEF_FOLDER'],
					'VARIABLES' => $variables,
					'ALIASES' => $variableAliases,
				],
			);
		}
		else {
			$variableAliases = CComponentEngine::makeComponentVariableAliases([], $this->arParams['VARIABLE_ALIASES']);
			CComponentEngine::initComponentVariables(false, $componentVariables, $variableAliases, $variables);

			$componentPage = $request->get('SECTION');

			if (
				$componentPage === 'orders'
				&& $request->get('ID')
				&& !$request->get('COPY_ORDER')
			) {
				if ($request->get('CANCEL') === 'Y') {
					$componentPage = 'order_cancel';
				}
				elseif ($request->get('PAYMENT') === 'Y') {
					$componentPage = 'payment';
				}
				else {
					$componentPage = 'order';
				}
			}

			if (empty($componentPage)) {
				if (
					$request->get('ID') && 
					$request->get('COPY_ORDER') === 'Y'
				) {
					$componentPage = 'orders';
				}
				else {
					$componentPage = 'index';
				}
			}

			$currentPage = $request->getRequestedPage();

			$this->arResult = array(
				'VARIABLES' => $variables,
				'ALIASES' => $variableAliases,
				'SEF_FOLDER' => $currentPage,
				''
			);

			$sectionsList = array_merge($sectionsList, array('order', 'profile', 'private', 'favorite'));

			if ($this->arParams['USE_PRIVATE_PAGE_TO_AUTH'] === 'Y') {
				$sectionsList = array_merge($sectionsList, ['password_change', 'password_restore', 'login']);
			}

			foreach ($sectionsList as $sectionName) {
				if ($sectionName === 'order') {
					$this->arResult['PATH_TO_ORDERS'] = $currentPage.'?SECTION='.$sectionName;
					$this->arResult['PATH_TO_ORDER'] = $this->arResult['PATH_TO_ORDERS'].'&ID=#ID#';
					$this->arResult['PATH_TO_ORDER_CANCEL'] = $this->arResult['PATH_TO_ORDERS'].'&ID=#ID#&CANCEL=Y';
					$this->arResult['PATH_TO_ORDER_COPY'] = $currentPage.'?COPY_ORDER=Y&ID=#ID#&SECTION='.$sectionName;
					$this->arResult['PATH_TO_PAYMENT'] = $this->arResult['PATH_TO_ORDERS'].'&PAYMENT=Y';
				}
				elseif ($sectionName === 'profile') {
					$this->arResult['PATH_TO_PROFILES'] = $currentPage.'?SECTION='.$sectionName;
					$this->arResult['PATH_TO_PROFILE'] = $this->arResult['PATH_TO_PROFILE'].'&ID=#ID#';
					$this->arResult['PATH_TO_PROFILE_DELETE'] = $this->arResult['PATH_TO_PROFILE'].'&del_id=#ID#';
				}
				else {
					$this->arResult['PATH_TO_'.ToUpper($sectionName)] = $currentPage.'?SECTION='.$sectionName;
				}
			}
		}

		// for bitrix components compatibility
		$this->arResult['PATH_TO_PROFILE_DETAIL'] =& $this->arResult['PATH_TO_PROFILE'];
		if ($this->arParams['SEF_URL_TEMPLATES']['order_detail']) {
			$this->arResult['PATH_TO_ORDER'] =& $this->arResult['PATH_TO_ORDER_DETAIL'];
		}
		else {
			$this->arResult['PATH_TO_ORDER_DETAIL'] =& $this->arResult['PATH_TO_ORDER'];
		}

		if (
			$componentPage === 'order_cancel' &&
			$this->arParams['ORDER_DISALLOW_CANCEL'] === 'Y'
		) {
			$componentPage = 'order';
		}

		if ($componentPage === 'order') {
			Loader::includeModule('sale');
			$id = urldecode(urldecode($variables['ID']));
			$registry = Sale\Registry::getInstance(Sale\Registry::REGISTRY_TYPE_ORDER);
			$orderClassName = $registry->getOrderClassName();

			$order = $orderClassName::loadByAccountNumber($id);
			if (!$order) {
				$order = $orderClassName::load((int)$id);
			}

			/** @var Sale\Order $order */
			if ($order) {
				if (
					(is_array($this->arParams['ORDER_HISTORIC_STATUSES']) && in_array($order->getField('STATUS_ID'), $this->arParams['ORDER_HISTORIC_STATUSES']))
					|| $order->isCanceled()
				) {
					$this->arResult['PATH_TO_ORDERS'] = \CHTTP::urlAddParams(
						CComponentEngine::makePathFromTemplate($this->arResult['PATH_TO_ORDERS']),
						['filter_history' => 'Y']
					);

					if ($order->isCanceled()) {
						$this->arResult['PATH_TO_ORDERS'] = \CHTTP::urlAddParams(
							CComponentEngine::makePathFromTemplate($this->arResult['PATH_TO_ORDERS']),
							['show_canceled' => 'Y']
						);
					}
				}
			}
		}
		elseif ($componentPage === 'password_restore') {
			$this->arResult['SHOW_FORGOT_PASSWORD_FORM'] = 'Y';
			$componentPage = 'private';
		}
		elseif ($componentPage === 'password_change') {
			$this->arResult['SHOW_CHANGE_PASSWORD_FORM'] = 'Y';
			$componentPage = 'private';
		}
		elseif ($componentPage === 'login') {
			$this->arResult['SHOW_LOGIN_FORM'] = 'Y';
			$componentPage = 'private';
		}

		if ($this->arParams['USE_PRIVATE_PAGE_TO_AUTH'] === 'Y') {
			$this->arResult['AUTH_SUCCESS_URL'] = $this->arResult['PATH_TO_LOGIN'];
			$backUrl = $this->request->get('backurl');

			if (!empty($backUrl) && mb_strpos($backUrl, '/') === 0) {
				$this->arResult['AUTH_SUCCESS_URL'] = $backUrl;
			}

			$this->arResult['PATH_TO_AUTH_PAGE'] = \CHTTP::urlAddParams(
				$this->arResult['PATH_TO_PRIVATE'],
				['backurl' => urlencode($request->getRequestUri())],
				true
			);
		}

		if (
			!Solution::isCabinetAvailable() ||
			!Solution::isPersonalSectionAvailable()
		) {
			if (!in_array($componentPage, ['subscribe', 'favorite'])) {
				LocalRedirect(SITE_DIR.'auth/');
			}
		}

		if (Solution::isPersonalSaleSectionAvailable()) {
		}
		else {
			if (in_array($componentPage, ['account', 'profiles', 'profile', 'orders', 'order', 'order_cancel'])) {
				LocalRedirect($this->arParams['SEF_FOLDER']);
			}
		}

		if (
			(
				$componentPage === 'private' &&
				$this->arParams['SHOW_PRIVATE_PAGE'] === 'N'
			) ||
			(
				$componentPage === 'account' &&
				$this->arParams['SHOW_ACCOUNT_PAGE'] === 'N'
			) ||
			(
				(
					$componentPage === 'profiles' ||
					$componentPage === 'profile'
				) &&
				$this->arParams['SHOW_PROFILE_PAGE'] === 'N'
			) ||
			(
				(
					$componentPage === 'orders' ||
					$componentPage === 'order' ||
					$componentPage === 'order_cancel' ||
					$componentPage === 'payment'
				) &&
				$this->arParams['SHOW_ORDER_PAGE'] === 'N'
			) ||
			(
				(
					$componentPage === 'subscribe' ||
					$componentPage === 'unsubscribe'
				) &&
				$this->arParams['SHOW_SUBSCRIBE_PAGE'] === 'N'
			) ||
			(
				$componentPage === 'unsubscribe' &&
				!$bSubscribe
			) ||
			(
				$componentPage === 'favorite' &&
				$this->arParams['SHOW_FAVORITE_PAGE'] === 'N'
			)
		) {
			LocalRedirect($this->arParams['SEF_FOLDER']);
		}

		foreach ($customPagesList as $arCustomPage) {
			if ($componentPage === $arCustomPage['page']) {
				$GLOBALS['APPLICATION']->SetTitle(htmlspecialcharsbx($arCustomPage['name']));
				$GLOBALS['APPLICATION']->AddChainItem($arCustomPage['name'], ($this->arParams['SEF_MODE'] === 'Y' ? $this->arParams['SEF_FOLDER'] : '').$arCustomPage['path']);
			}
		}

		$this->includeComponentTemplate($componentPage);
	}

	public function correctUserPhones() {
		$mask = Solution::GetFrontParametrValue('PHONE_MASK');
		if (strpos($mask, '+') === false) {
			return;
		}

		if (
			!isset($this->arResult['USER_ID']) ||
			!$this->arResult['USER_ID']
		) {
			return;
		}

		$user = \Bitrix\Main\UserTable::getByPrimary($this->arResult['USER_ID'])->fetchObject();
		$phone = $user->getPersonalPhone();

		if (strlen($phone)) {
			$newPhone = trim(preg_replace('/^\++([^\+]*)/', '$1', trim($phone)));
			$newPhone = strlen($newPhone) ? '+'.$newPhone : '';

			if ($newPhone != $phone) {
				$user = new CUser;
				$user->Update(
					$this->arResult['USER_ID'],
					[
						'PERSONAL_PHONE' => $newPhone,
					]
				);
			}
		}
	}

	public function correctUserProfilesPhones() {
		$mask = Solution::GetFrontParametrValue('PHONE_MASK');
		if (strpos($mask, '+') === false) {
			return;
		}

		if (
			!Loader::includeModule('sale') ||
			!isset($this->arResult['USER_ID']) ||
			!$this->arResult['USER_ID']
		) {
			return;
		}

		$arProfilesIDs = $arOrderPropertyPhonesIDs = $arProfilesPhones = [];

		$dbRes = \CSaleOrderProps::GetList(
			[], 
			[
				'IS_PHONE' => 'Y',
			]
		);
		while ($arProperty = $dbRes->Fetch()) {
			$arOrderPropertyPhonesIDs[] = $arProperty['ID'];
		}

		if ($arOrderPropertyPhonesIDs) {
			$dbRes = \CSaleOrderUserProps::GetList(
				[],
				[
					'USER_ID' => $this->arResult['USER_ID'],
				],
				false,
				false,
				['ID']
			);
			while ($arProfile = $dbRes->Fetch()) {
				$arProfilesIDs[] = $arProfile['ID'];
			}

			if ($arProfilesIDs) {
				$dbRes = \CSaleOrderUserPropsValue::GetList(
					[], 
					[
						'USER_PROPS_ID' => $arProfilesIDs,
						'ORDER_PROPS_ID' => $arOrderPropertyPhonesIDs,
					]
				);
				while ($arProfilePhone = $dbRes->Fetch()) {
					if (strlen($arProfilePhone['VALUE'])) {
						$newPhone = trim(preg_replace('/^\++([^\+]*)/', '$1', trim($arProfilePhone['VALUE'])));
						$newPhone = strlen($newPhone) ? '+'.$newPhone : '';

						if ($newPhone != $arProfilePhone['VALUE']) {
							\CSaleOrderUserPropsValue::Update(
								$arProfilePhone['ID'],
								[
									'VALUE' => $newPhone,
								]
							);
						}
					}
				}
			}
		}
	}
}
