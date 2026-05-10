<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc,
	CPremier as Solution;

Loc::loadMessages(__FILE__);
$this->setFrameMode(false);

if ($arResult['ERROR_MESSAGE']) {
	ShowMessage($arResult['ERROR_MESSAGE']);
}

$svgIconsSprite = $this->__folder.'/images/svg/icons.svg';

$arServices = $arResult['AUTH_SERVICES_ICONS'];
$userIdTwitter = $userIdOther = [];
$showDivTwitter = false;

$forSplit = 'Y';
$suffix = '';
?>
<?if ($arResult['AUTH_SERVICES']):?>
	<div class="soc-avt personal__top-form bordered outer-rounded-x p p--32">
		<h4><?=Loc::getMessage('SS_GET_COMPONENT_INFO')?></h4>

		<div class="socserv mt mt--24">
			<div class="social">
				<ul class="social__items social__items--merged social__items--type-color grid-list--items grid-list--items-4-from-1200 grid-list--items-3-from-992 grid-list--items-2-from-768 grid-list--items-2-from-601 mt mt--24">
					<?if (isset($arResult['DB_SOCSERV_USER']) && $arParams['SHOW_PROFILES'] != 'N'):?>
						<?foreach ($arResult['DB_SOCSERV_USER'] as $key => $arUser):?>
							<?
							$authID = ($arServices[$arUser['EXTERNAL_AUTH_ID']]['NAME']) ? $arServices[$arUser['EXTERNAL_AUTH_ID']]['NAME'] : $arUser['EXTERNAL_AUTH_ID'];

							$icon = $arResult['AUTH_SERVICES_ICONS'][$arUser['EXTERNAL_AUTH_ID']]['ICON'] ?: 'openid';

							if ($arUser['EXTERNAL_AUTH_ID'] == 'Twitter') {
								$showDivTwitter = true;
								$userIdTwitter[] = $arUser['ID'];
							}
							else {
								$userIdOther[] = $arUser['ID'];
							}
							?>
							<li class="social__item social__item--merged grid-list__item hover_blink <?=htmlspecialcharsbx($icon)?>">
								<div class="social__item--merged__icon"><div class="social__link bordered"></div></div>

								<div class="social__item--merged__info">
									<div class="social__item--merged__head">
										<div class="social__item--merged__title font_13 color_dark">
											<?if ($arUser['PERSONAL_LINK']):?>
												<a class="soc-serv-link dark_link" target="_blank" href="<?=$arUser['PERSONAL_LINK']?>"><?=$authID?></a>
											<?else:?>
												<?=$authID?>
											<?endif;?>
										</div>

										<div class="social__item--merged__checked"><?=Solution::showSpriteIconSvg($svgIconsSprite.'#check-16-16', 'fill-theme', ['WIDTH' => 16,'HEIGHT' => 16]);?></div>
									</div>

									<?if (in_array($arUser['ID'], $arResult['ALLOW_DELETE_ID'])):?>
										<div class="social__item--merged__actions font_14 fw-500">
											<a class="social__item--merged__action social__item--merged__action--delete no-decoration" href="?action=delete&user_id=<?=$arUser['ID']."&".bitrix_sessid_get()?>" onclick="return confirm('<?=Loc::getMessage('SS_PROFILE_DELETE_CONFIRM')?>')" title=<?=htmlspecialcharsbx(Loc::getMessage('SS_DELETE'))?>><?=Loc::getMessage('SS_DELETE')?></a>
										</div>
									<?endif;?>
								</div>
							</li>
						<?endforeach;?>
					<?endif;?>

					<?foreach ($arResult['AUTH_SERVICES'] as $service):?>
						<?
						if (is_array($service['FORM_HTML'])) {
							$onClickEvent = $service['FORM_HTML']['ON_CLICK'];
						}
						else {
							$onClickEvent = "onclick=\"BxShowAuthService('".$service['ID']."', '".$suffix."')\"";
						}
						?>
						<li class="social__item social__item--merged grid-list__item hover_blink <?=htmlspecialcharsbx($service['ICON'])?>">
							<div class="social__item--merged__icon"><div class="social__link bordered"></div></div>

							<div class="social__item--merged__info">
								<div class="social__item--merged__head">
									<div class="social__item--merged__title font_13 color_dark"><?=$service['NAME']?></div>
								</div>

								<div class="social__item--merged__actions font_14 fw-500">
									<a href="javascript:void(0)" <?=$onClickEvent?> class="social__item--merged__action social__item--merged__action--add no-decoration" type="<?=htmlspecialcharsbx($service['ICON'])?>" id="bx_auth_href_<?=$suffix?><?=$service['ID']?>"><?=Loc::getMessage('SS_ADD')?></a>
								</div>
							</div>
						</li>
					<?endforeach?>
				</ul>

				<?if (isset($arResult['DB_SOCSERV_USER']) && $arParams['SHOW_PROFILES'] != 'N'):?>
					<div class="soc-serv-accounts">
						<div class="soc-serv-my-actives">
							<input type="hidden" name="bEdit" value="N" />
						</div>

						<?if ($showDivTwitter):?>
							<div class="soc-serv-title-grey">
								<?if (COption::GetOptionString('socialservices', 'get_message_from_twitter', 'N') == 'Y'):?>
									<br>
									
									<?=str_replace('#hash#', $arResult['TWIT_HASH'], Loc::getMessage('SS_SEND_MESSAGE_TO'))."  "?><a href="javascript:void(0)" onclick="ShowTwitDiv()"><?=Loc::getMessage('SS_TO_RECIPIENTS')?></a>

									<div id="soc-serv-recipients">
										<?
										$APPLICATION->IncludeComponent(
											"bitrix:main.post.form",
											"",
											$formParams = Array(
												"FORM_ID" => "bx_user_profile_form",
												"SHOW_MORE" => "Y",
												"PARSER" => Array("Bold", "Italic", "Underline", "Strike", "ForeColor",
													"FontList", "FontSizeList", "RemoveFormat", "Quote", "Code",
													"MentionUser",
												),
												"BUTTONS" => Array(
													"MentionUser",
												),
												"DESTINATION" => array(
													"VALUE" => $arResult["PostToShow"]["FEED_DESTINATION"],
													"SHOW" => "Y"
												),
											),
											false,
											Array("HIDE_ICONS" => "Y")
										);
										?>
									</div>
								<?endif;?>

								<script>
								function ShowTwitDiv() {
									var obTwitterRecipients = document.getElementById('soc-serv-recipients');
									if(obTwitterRecipients.style.display == 'block') {
										obTwitterRecipients.style.display = 'none';
									}
									else {
										obTwitterRecipients.style.display = 'block'
									}
								}
								</script>
							</div>
						<?endif;?>

						<?
						if (!empty($userIdTwitter)) {
							foreach ($userIdTwitter as $value) {
								?><input type="hidden" name="USER_ID_TWITTER[<?=$value?>]" value="<?=$value?>" /><?
							}
						}

						if (!empty($userIdOther)) {
							foreach ($userIdOther as $value) {
								?><input type="hidden" name="USER_ID_OTHER[<?=$value?>]" value="<?=$value?>" /><?
							}
						}
						?>
					</div>
				<?endif;?>

				<div class="form">
					<form method="post" name="bx_auth_services<?=$suffix?>" target="_top" action="<?=$arResult['CURRENTURL']?>">
						<div id="bx_auth_serv<?=$suffix?>" style="display:none">
							<?foreach ($arResult['AUTH_SERVICES'] as $service):?>
								<?if (!is_array($service['FORM_HTML'])):?>
									<?
									$service['FORM_HTML'] = str_replace('"button"', '"btn btn-sm btn-default"', $service['FORM_HTML']);
									$service['FORM_HTML'] = str_replace('"required"', '"required form-control"', $service['FORM_HTML']);
									$service['FORM_HTML'] = '<div class="form-body">'.$service['FORM_HTML'];

									if (preg_match('/<input\s[^>]*?type=\"submit\"[^>]*?>/i', $service['FORM_HTML'], $arMatches)) {
										$service['FORM_HTML'] = preg_replace('/(<input\s[^>]*?type=\"submit\"[^>]*?>)/i', '</div>'.'<div class="form-footer">$1</div>', $service['FORM_HTML']);
									}
									else {
										$service['FORM_HTML'] .= '</div>';
									}
									?>								
									<div id="bx_auth_serv_<?=$suffix?><?=$service['ID']?>" style="display:none"><?=$service['FORM_HTML']?></div>
								<?endif;?>
							<?endforeach;?>
						</div>

						<?foreach ($arResult['POST'] as $key => $value):?>
							<?if (!preg_match("|OPENID_IDENTITY|", $key)):?>
								<input type="hidden" name="<?=$key?>" value="<?=$value?>" />
							<?endif;?>
						<?endforeach;?>
						
						<input type="hidden" name="auth_service_id" value="" />
					</form>

					<script>
					$("#bx_auth_serv<?=$suffix?> input[type=text]").each(function() {
						$(this).addClass("required form-control").attr("required", "true");
					});

					function BxShowAuthService(id, suffix) {
						var bxCurrentAuthId = '';
						if (window['bxCurrentAuthId' + suffix]) {
							bxCurrentAuthId = window['bxCurrentAuthId'+suffix];
						}

						BX('bx_auth_serv' + suffix).style.display = '';

						if (bxCurrentAuthId != '' && bxCurrentAuthId != id) {
							BX('bx_auth_serv_' + suffix + bxCurrentAuthId).style.display = 'none';
						}

						BX('bx_auth_href_' + suffix + id).blur();
						BX('bx_auth_serv_' + suffix + id).style.display = '';

						var el = BX.findChild(BX('bx_auth_serv_'+suffix+id), {'tag':'input', 'attribute':{'type':'text'}}, true);
						if (el) {
							try {
								el.focus();
							}
							catch(e) {
							}
						}

						window['bxCurrentAuthId'+suffix] = id;

						if (document.forms['bx_auth_services' + suffix]) {
							document.forms['bx_auth_services' + suffix].auth_service_id.value = id;
						}
						else if (document.forms['bx_user_profile_form' + suffix]) {
							document.forms['bx_user_profile_form' + suffix].auth_service_id.value = id;
						}
					}

					var bxAuthWnd = false;
					function BxShowAuthFloat(id, suffix) {
						var bCreated = false;

						if (!bxAuthWnd) {
							bxAuthWnd = new BX.CDialog({
								'content':'<div id="bx_auth_float_container"></div>',
								'width': 640,
								'height': 400,
								'resizable': false,
							});

							bCreated = true;
						}

						bxAuthWnd.Show();

						if (bCreated) {
							BX('bx_auth_float_container').appendChild(BX('bx_auth_float'));
						}

						BxShowAuthService(id, suffix);
					}
					</script>
				</div>
			</div>
		</div>
	</div>
<?endif;?>