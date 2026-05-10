<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc,
	CPremier as Solution,
	Aspro\Premier\PhoneAuth;

$url = htmlspecialcharsbx($arResult['PATH_TO_PRIVATE']);
$bShowDetailLink = $arParams['SHOW_PRIVATE_PAGE'] !== 'N';

$currentUser = \Bitrix\Main\Engine\CurrentUser::get();
$arFullName = [
	trim($currentUser->getLastName()),
	trim($currentUser->getFirstName()),
	trim($currentUser->getSecondName()),
];
$fullName = preg_replace('/\s{2,}/', ' ', trim(implode(' ', $arFullName)));

$user = \Bitrix\Main\UserTable::getByPrimary($arResult['USER_ID'])->fetchObject();
$phone = $user->getPersonalPhone();

list($bPhoneAuthSupported, $bPhoneAuthShow, $bPhoneAuthRequired, $bPhoneAuthUse) = PhoneAuth::getOptions();
if ($bPhoneAuthShow) {
	if ($userPhoneAuth = \Bitrix\Main\UserPhoneAuthTable::getRowById($arResult['USER_ID'])) {
		$phone = strlen($userPhoneAuth['PHONE_NUMBER']) ? htmlspecialcharsbx($userPhoneAuth['PHONE_NUMBER']) : $phone;
	}
}
?>
<div class="personal__main-private__wrapper grid-list__item grid-list-border-outer">
	<div class="personal__main-private p p--24 stroke-grey-parent bordered outer-rounded-x shadow-hovered shadow-hovered-f600 shadow-no-border-hovered stroke-grey-parent">
		<?if ($bShowDetailLink):?>
			<a class="item-link-absolute" href="<?=$url?>" title="<?=htmlspecialcharsbx(Loc::getMessage('SPS_MAIN_BLOCK_TITLE_PRIVATE'))?>"></a>
		<?endif;?>

		<div class="personal__main-private__inner">
			<div class="personal__main-private__top">
				<?if ($bShowDetailLink):?>
					<span class="main-block__link">
						<span class="main-block__arrow">
							<?=Solution::showSpriteIconSvg(SITE_TEMPLATE_PATH.'/images/svg/arrows.svg#right-hollow', 'stroke-grey-target', [
								'WIDTH' => 6, 
								'HEIGHT' => 12
							]);?>
						</span>
					</span>
				<?endif;?>

				<div class="personal__main-private__title font_13 secondary-color"><?=Loc::getMessage('SPS_MAIN_BLOCK_TITLE_PRIVATE')?></div>
				<div class="personal__main-private__value switcher-title color_dark font_24 mt mt--4"><?=htmlspecialcharsbx(strlen($fullName) ? $fullName : $GLOBALS['USER']->GetLogin())?></div>
			</div>
			<div class="personal__main-private__bottom font_14 mt mt--88">
				<div class="personal__main-private__bottom-left">
					<div class="personal__main-private__email"><?=$currentUser->getEmail()?></div>
					<div class="personal__main-private__phone">
						<span><?=$phone?></span>
						<?if ($bPhoneAuthShow && $userPhoneAuth && strlen($phone)):?>
							<?$bConfirmed = $userPhoneAuth['CONFIRMED'] == 'Y';?>
							<span class="phone-confirm personal-color--<?=($bConfirmed ? 'green' : 'red')?> font_13"><?=Loc::getMessage($bConfirmed ? 'SPS_AUTH_PHONE_CONFIRMED' : 'SPS_AUTH_PHONE_NOTCONFIRMED')?></span>
						<?endif;?>
					</div>
				</div>

				<?if ($bShowDetailLink):?>
					<a class="personal__main-private__change-password btn btn-xs btn-default btn-transparent-bg" href="<?=$url?>#change-password"><?=Loc::getMessage('SPS_CHANGE_PASSWORD')?></a>
				<?endif;?>
			</div>
		</div>
	</div>
</div>
