<?if( !defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true ) die();?>
<?if( $arParams["POPUP_AUTH"] == "Y"){ ?>
	<?if( $arResult["AUTH_SERVICES"] ){?>
		<div class="auth__services">
			<?$APPLICATION->IncludeComponent(
				"bitrix:socserv.auth.form",
				"auth",
				array(
					"AUTH_SERVICES" => $arResult["AUTH_SERVICES"],
					"AUTH_URL" => SITE_DIR."auth/?login=yes",
					"POST" => $arResult["POST"],
					"POPUP" => "N",
					"SUFFIX" => "form_inline",
				),
				$component,
				array( "HIDE_ICONS" => "N" )
			);?>
		</div>
	<?}?>
<?}?>
