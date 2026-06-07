<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc,
    Bitrix\Main\Web\Json;

/** @global CMain $APPLICATION */
CJSCore::Init(array("image"));
if (!include_once($_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/vendor/php/solution.php')) {
    throw new \Exception('Error include solution contants');
}

$APPLICATION->ShowAjaxHead();

$application = \Bitrix\Main\Application::getInstance();
$request = $application->getContext()->getRequest();
$post = $request->getPostList();
$session = $application->getSession();

$bAjaxPost = $arResult["is_ajax_post"] === 'Y';

global $pathForAjax;
$pathForAjax = $templateFolder;
?>

<div class="comments-block__inner-wrapper">
    <?ob_start();?>
    <?if ($arResult['IMAGES']):?>
        <div class="reviews-gallery-block reviews-gallery-block--top mb mb--32" >
            <?=TSolution\Functions::showGallery($arResult['IMAGES'], [
                'BREAKPOINTS' => [
                    '320' => 3,
                    '374' => 4,
                    '420' => 5,
                    '599' => 7,
                    '767' => 8,
                    '991' => 10,
                ],
                'CONTAINER_CLASS' => 'gallery-review',
            ]);?>
        </div>
    <?endif;?>
    <?$topImages = trim(ob_get_clean());?>

    <script>
        BX.ready(function() {
            if (BX.viewImageBind) {
                BX.viewImageBind('blg-comment-<?=$arParams["ID"];?>', false, {
                    tag:'IMG',
                    attr: 'data-bx-image'
                });
            }
        });
        BX.message({
            'BPC_MES_DELETE': '<?=GetMessage("BPC_MES_DELETE");?>',
        });
    </script>
    <div id="reviews_sort_continer" class="hidden"></div>
    <div class="blog-comments" id="blg-comment-<?=$arParams["ID"];?>">
    <a name="comments" class="hidden"></a>

    <?if (!$bAjaxPost):?>
        <?include($_SERVER["DOCUMENT_ROOT"].$templateFolder."/script.php");?>
        <?if ($arResult['IMAGES']):?>
            <script>
                InitFancyBox();
            </script>
        <?endif;?>
    <?else:?>
        <?$APPLICATION->RestartBuffer();?>
        <script>
            window.BX = top.BX;
            <?if ($arResult["use_captcha"]):?>
                var cc ='<?=$arResult["CaptchaCode"];?>';
                if (BX('captcha')) {
                    BX('captcha').src='/bitrix/tools/captcha.php?captcha_code='+cc;
                }

                if (BX('captcha_code')) {
                    BX('captcha_code').value = cc;
                    BX.Aspro?.Captcha.reset();
                }

                if (BX('captcha_word')) {
                    BX('captcha_word').value = "";
                }
            <?endif;?>
            if (!top.arImages) {
                top.arImages = [];
            }
            if (!top.arImagesId) {
                top.arImagesId = [];
            }
            <?if ($arResult["Images"]):?>
                <?foreach($arResult["Images"] as $aImg):?>
                    top.arImages.push('<?=CUtil::JSEscape($aImg["SRC"]);?>');
                    top.arImagesId.push('<?=$aImg["ID"];?>');
                <?endforeach;?>
            <?endif;?>
        </script>
        <?if (strlen($arResult["COMMENT_ERROR"])):?>
            <script>top.commentEr = 'Y';</script>
            <div class="alert alert-danger blog-note-box blog-note-error">
                <div class="blog-error-text">
                    <?=$arResult["COMMENT_ERROR"];?>
                </div>
            </div>
        <?endif;?>
    <?endif;?>

    <?if (strlen($arResult["MESSAGE"]) > 0):?>
        <div class="blog-textinfo blog-note-box">
            <div class="blog-textinfo-text">
                <?=$arResult["MESSAGE"];?>
            </div>
        </div>
    <?endif;?>

    <?if (strlen($arResult["ERROR_MESSAGE"]) > 0):?>
        <div class="alert alert-danger blog-note-box blog-note-error">
            <div class="blog-error-text" id="blg-com-err">
                <?=$arResult["ERROR_MESSAGE"];?>
            </div>
        </div>
    <?endif;?>

    <?if (strlen($arResult["FATAL_MESSAGE"]) > 0):?>
        <div class="alert alert-danger blog-note-box blog-note-error">
            <div class="blog-error-text">
                <?=$arResult["FATAL_MESSAGE"];?>
            </div>
        </div>
    <?else:?>
        <?if ($arResult["imageUploadFrame"] == "Y"):?>
            <script>
                <?if (!empty($arResult["Image"])):?>
                    top.bxBlogImageId = top.arImagesId.push('<?=$arResult["Image"]["ID"];?>');
                    top.arImages.push('<?=CUtil::JSEscape($arResult["Image"]["SRC"]);?>');
                    top.bxBlogImageIdWidth = '<?=CUtil::JSEscape($arResult["Image"]["WIDTH"]);?>';
                <?elseif (strlen($arResult["ERROR_MESSAGE"]) > 0):?>
                    top.bxBlogImageError = '<?=CUtil::JSEscape($arResult["ERROR_MESSAGE"]);?>';
                <?endif;?>
            </script>
            <?die();?>
        <?else:?>
            <?if (!$bAjaxPost && $arResult["CanUserComment"]):?>
                <?$ajaxPath = $templateFolder.'/ajax.php';?>
                <div class="js-form-comment" id="form_comment_" style="display:none;">
                    <div id="form_c_del" style="display:none;">
                        <div class="blog-comment__form">
                            <form enctype="multipart/form-data" method="POST" name="form_comment" id="form_comment" action="<?=$ajaxPath;?>">
                                <input type="hidden" name="parentId" id="parentId" value="">
                                <input type="hidden" name="edit_id" id="edit_id" value="">
                                <input type="hidden" name="act" id="act" value="add">
                                <input type="hidden" name="post" value="Y">

                                <?if (isset($request["IBLOCK_ID"])):?>
                                    <input type="hidden" name="IBLOCK_ID" value="<?=(int)$request["IBLOCK_ID"];?>">
                                <?endif;?>

                                <?if (isset($request["ELEMENT_ID"])):?>
                                    <input type="hidden" name="ELEMENT_ID" value="<?=(int)$request["ELEMENT_ID"];?>">
                                <?endif;?>

                                <?if (isset($arParams["OFFER_ID"])):?>
                                    <input type="hidden" name="OFFER_ID" value="<?=(int)$arParams["OFFER_ID"];?>">
                                <?endif;?>

                                <?if (isset($request["XML_ID"])):?>
                                    <input type="hidden" name="XML_ID" value="<?=htmlspecialcharsbx($request["XML_ID"]);?>">
                                <?endif;?>

                                <?if (isset($request["SITE_ID"])):?>
                                    <input type="hidden" name="SITE_ID" value="<?=htmlspecialcharsbx($request["SITE_ID"]);?>">
                                <?endif;?>

                                <?=makeInputsFromParams($arParams["PARENT_PARAMS"]);?>
                                <?=bitrix_sessid_post();?>

                                <div class="form popup blog-comment-fields outer-rounded-x bordered mb mb--24">
                                    <div class="form-header">
                                        <?if (empty($arResult["User"])):?>
                                            <div class="blog-comment-field blog-comment-field-user">
                                                <div class="row form">
                                                    <div class="col-md-6 col-sm-6">
                                                        <div class="form-group <?=($_SESSION["blog_user_name"] ? 'input-filed' : '');?>">
                                                            <label for="user_name"><?=GetMessage("B_B_MS_NAME");?> <span class="required-star">*</span></label>
                                                            <div class="input">
                                                            <input maxlength="255" size="30" class="form-control" required tabindex="3" type="text" name="user_name" id="user_name" value="<?=htmlspecialcharsEx($_SESSION["blog_user_name"]);?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6 col-sm-6">
                                                        <div class="form-group <?=($_SESSION["blog_user_email"] ? 'input-filed' : '');?>">
                                                            <label for="user_email">E-mail</label>
                                                            <div class="input">
                                                            <input maxlength="255" size="30" class="form-control" tabindex="4" type="text" name="user_email" id="user_email" value="<?=htmlspecialcharsEx($_SESSION["blog_user_email"]);?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?endif;?>

                                        <?if ($arParams["NOT_USE_COMMENT_TITLE"] != "Y"):?>
                                            <div class="row form">
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <label for="user_sbj"><?=GetMessage("BPC_SUBJECT");?></label>
                                                        <div class="input">
                                                            <input maxlength="255" size="70" class="form-control" tabindex="3" type="text" name="subject" id="user_sbj" value="">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?endif;?>

                                        <div class="row form">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label class="rating_label" data-hide><?=GetMessage("BPC_RATING");?> <span class="required-star">*</span></label>
                                                    <div class="votes_block nstar big with-text" data-hide>
                                                        <div class="ratings">
                                                            <div class="inner_rating rating__star-svg">
                                                                <?for ($i=1; $i<=5; $i++):?>
                                                                    <div class="item-rating rating__star-svg" data-message="<?=GetMessage('RATING_MESSAGE_'.$i);?>">
                                                                        <?=TSolution::showSpriteIconSvg(SITE_TEMPLATE_PATH."/images/svg/catalog/item_icons.svg#star-13-13", '', [
                                                                            'WIDTH' => 16,
                                                                            'HEIGHT' => 16,
                                                                        ]);?>
                                                                    </div>
                                                                <?endfor;?>
                                                            </div>
                                                        </div>
                                                        <div class="rating_message secondary-color" data-message="<?=GetMessage('RATING_MESSAGE_0');?>"><?=GetMessage('RATING_MESSAGE_0');?></div>
                                                        <input class="hidden" name="rating" required>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row form virtues" data-hide>
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="virtues"><?=GetMessage("BPC_VIRTUES");?></label>
                                                    <div class="input">
                                                    <textarea rows="3" class="form-control" tabindex="3" name="virtues" id="virtues" value=""></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row form limitations" data-hide>
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="limitations"><?=GetMessage("BPC_LIMITATIONS");?></label>
                                                    <div class="input">
                                                    <textarea rows="3" class="form-control" tabindex="3" name="limitations" id="limitations" value=""></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row form comment">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="comment"><?=GetMessage("BPC_MESSAGE");?></label>
                                                    <div class="input">
                                                        <textarea rows="3" class="form-control" tabindex="3" name="comment" id="comment" value=""></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row form">
                                            <div class="col-md-12 blog-comment-form__existing-files">

                                            </div>
                                        </div>

                                        <?if ($arParams['NO_USE_IMAGE'] == 'N' && !empty($arParams['MAX_IMAGE_COUNT'])):?>
                                            <div class="drop-zone bordered button-rounded-x mb mb--20" data-hide>
                                                <div class ="drop-zone__wrapper button-rounded-x">
                                                    <input type="file" id="comment_images" multiple="multiple" name="comment_images[]" accept="image/*" title="" class="drop-zone__wrapper-input uniform-ignore">
                                                </div>
                                            </div>

                                            <script>var dropZone = new DropZone('.drop-zone', {maxImageCount:<?=$arParams['MAX_IMAGE_COUNT']?>});</script>
                                        <?endif;?>

                                        <?if ($arResult["COMMENT_PROPERTIES"]["SHOW"] == "Y"):?>
                                            <br />
                                            <?
                                            $eventHandlerID = false;
                                            $eventHandlerID = AddEventHandler('main', 'system.field.edit.file', array('CBlogTools', 'blogUFfileEdit'));
                                            ?>
                                            <?foreach ($arResult["COMMENT_PROPERTIES"]["DATA"] as $FIELD_NAME => $arPostField):?>
                                                <?if ($FIELD_NAME=='UF_BLOG_COMMENT_DOC'):?>
                                                    <a id="blog-upload-file" href="javascript:blogShowFile()"><?=GetMessage("BLOG_ADD_FILES");?></a>
                                                <?endif;?>

                                                <div id="blog-comment-user-fields-<?=$FIELD_NAME?>"><?=($FIELD_NAME=='UF_BLOG_COMMENT_DOC' ? "" : $arPostField["EDIT_FORM_LABEL"].":");?>
                                                    <?$APPLICATION->IncludeComponent(
                                                        "bitrix:system.field.edit",
                                                        $arPostField["USER_TYPE"]["USER_TYPE_ID"],
                                                        array("arUserField" => $arPostField),
                                                        null,
                                                        array("HIDE_ICONS"=>"Y")
                                                    );?>
                                                </div>
                                            <?endforeach;?>
                                            <?
                                            if ($eventHandlerID !== false && (intval($eventHandlerID) > 0 ))
                                                RemoveEventHandler('main', 'system.field.edit.file', $eventHandlerID);
                                            ?>
                                        <?endif;?>

                                        <?if (strlen($arResult["NoCommentReason"]) > 0):?>
                                            <div id="nocommentreason" style="display:none;"><?=$arResult["NoCommentReason"];?></div>
                                        <?endif;?>

                                        <?if ($arResult["use_captcha"]):?>
                                            <div class="captcha-row clearfix fill-animate">
                                                <label for="captcha_word"><span><?=Loc::getMessage("B_B_MS_CAPTCHA_SYM")?>&nbsp;<span class="required-star">*</span></span></label>
                                                <div class="captcha_image">
                                                    <img data-src="" src="/bitrix/tools/captcha.php?captcha_code=<?=htmlspecialcharsbx($arResult["CaptchaCode"])?>" width="180" height="40" id="captcha" border="0" class="captcha_img" />
                                                    <input type="hidden" id="captcha_code" name="captcha_code" class="captcha_sid" value="<?=htmlspecialcharsbx($arResult["CaptchaCode"])?>" />
                                                    <div class="captcha_reload"></div>
                                                    <span class="refresh"><a href="javascript:;" rel="nofollow"><?=Loc::getMessage("REFRESH")?></a></span>
                                                </div>
                                                <div class="captcha_input">
                                                    <input type="text" class="inputtext form-control captcha" name="captcha_word" size="30" maxlength="50" value="" required />
                                                </div>
                                            </div>
                                        <?endif;?>

                                        <?$showLicence = $arParams['SHOW_LICENCE'] ?: TSolution::GetFrontParametrValue('SHOW_LICENCE');?>
                                        <?if ($showLicence === 'Y'):?>
                                            <?TSolution\Functions::showBlockHtml([
                                                'FILE' => 'consent/userconsent.php',
                                                'PARAMS' => [
                                                    'OPTION_CODE' => 'AGREEMENT_COMMENT',
                                                    'SUBMIT_TEXT' => GetMessage('B_B_MS_SEND'),
                                                    'REPLACE_FIELDS' => [],
                                                    'INPUT_NAME' => 'licenses_popup',
                                                    'INPUT_ID' => "licenses_comment",
                                                    'SUBMIT_EVENT_NAME' => 'comment-send-aspro',
                                                    'PARENT_COMPONENT' => $this->__component,
                                                ]
                                            ]);?>
                                            <script>
                                                BX.Aspro.Utils.readyDOM(() => {
                                                    BX.onCustomEvent('onUserConsentReload');
                                                });
                                            </script>
                                        <?endif;?>

                                        <div class="blog-comment-buttons-wrapper font_15 mt mt--32">
                                            <input tabindex="10" class="btn btn-default" value="<?=GetMessage("B_B_MS_SEND");?>" type="button" name="sub-post" id="post-button" onclick="submitComment()">
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="blog_upload_cid" id="upload-cid" value="">
                            </form>
                        </div>
                    </div>
                </div>
            <?endif;?>
            <?
            $prevTab = 0;
            function ShowComment($comment, $tabCount=0, $tabSize=2.5, $canModerate=false, $User=Array(), $use_captcha=false, $bCanUserComment=false, $errorComment=false, $arParams = array()) {
                if (!isset($application) && !isset($request)) {
                    $application = \Bitrix\Main\Application::getInstance();
                    $request = $application->getContext()->getRequest();
                }

                $comment["urlToAuthor"] = "";
                $comment["urlToBlog"] = "";
                $comment["urlToApprove"] = "";

                if ($canModerate && !$comment['PARENT_ID']) {
                    $approveParam = isset($comment['UF_ASPRO_COM_APPROVE']) && $comment['UF_ASPRO_COM_APPROVE'] ? "unapprove_comment_id" : "approve_comment_id";
                    $comment["urlToApprove"] = htmlspecialcharsbx($GLOBALS['APPLICATION']->GetCurPageParam($approveParam."=".$comment["ID"], ["sessid", "delete_comment_id", "hide_comment_id", "success", "show_comment_id", "commentId", "approve_comment_id", "unapprove_comment_id"]));
                }

                if ($comment["SHOW_AS_HIDDEN"] == "Y" || $comment["PUBLISH_STATUS"] == BLOG_PUBLISH_STATUS_PUBLISH || $comment["SHOW_SCREENNED"] == "Y" || $comment["ID"] == "preview") {
                    global $prevTab;
                    $tabCount = IntVal($tabCount);
                    $startVal = $comment['PARENT_ID'] ? 25 : 0;
                    if ($tabCount <= 5)
                        $paddingSize = 26 * $tabCount;
                    elseif ($tabCount > 5 && $tabCount <= 10)
                        $paddingSize = 26 * 5 + ($tabCount - 5) * 1.5;
                    elseif ($tabCount > 10)
                        $paddingSize = 26 * 5 + 1.5 * 5 + ($tabCount-10) * 1;

                    if (($tabCount+1) <= 5)
                        $paddingSizeNew = 26 * ($tabCount+1);
                    elseif (($tabCount+1) > 5 && ($tabCount+1) <= 10)
                        $paddingSizeNew = 26 * 5 + (($tabCount+1) - 5) * 1.5;
                    elseif (($tabCount+1) > 10)
                        $paddingSizeNew = 26 * 5 + 1.5 * 5 + (($tabCount+1)-10) * 1;
                    $paddingSizeNew -= $paddingSize;

                    if ($prevTab > $tabCount)
                        $prevTab = $tabCount;
                    if ($prevTab <= 5)
                        $prevPaddingSize = 26 * $prevTab;
                    elseif ($prevTab > 5 && $prevTab <= 10)
                        $prevPaddingSize = 26 * 5 + ($prevTab - 5) * 1.5;
                    elseif ($prevTab > 10)
                        $prevPaddingSize = 26 * 5 + 1.5 * 5 + ($prevTab-10) * 1;

                        $prevTab = $tabCount;

                    $bCommentChild = $tabCount > 0 || $comment['PARENT_ID'];
                    ?>
                    <div class="blog-comment <?=$bCommentChild ? 'blog-comment--child pt pt--20' : 'p-block p-block--24 border-bottom parent'?>"
                        <?if ($bCommentChild):?>
                            style="--blog_comment_padding: <?=$tabCount ? $tabCount-1 : 1;?>"
                        <?endif;?>
                        data-oid="<?=$comment['UF_ASPRO_COM_OFFER_ID'];?>"
                    >
                    <a name="<?=$comment["ID"];?>"></a>
                    <div id="blg-comment-<?=$comment["ID"];?>" class="blog-comment__content">
                        <?if (
                            isset($_SESSION['NOT_ADDED_FILES'])
                            && $_SESSION['NOT_ADDED_FILES']['FILES']
                            && !empty(reset($_SESSION['NOT_ADDED_FILES']['FILES']))
                            && $_SESSION['NOT_ADDED_FILES']['ID'] == $comment["ID"]
                        ):?>
                            <div class="alert alert-danger">
                                <?
                                echo GetMessage('NOT_ADDED_FILES').'<br />';
                                foreach ($_SESSION['NOT_ADDED_FILES']['FILES'] as $fileName) {
                                    echo $fileName.'<br />';
                                }
                                unset($_SESSION['NOT_ADDED_FILES']);
                                ?>
                            </div>
                        <?endif;?>

                        <div class="line-block--align-flex-start line-block line-block--gap line-block--gap-16">
                            <?if ($bCommentChild):?>
                                <div class="blog-comment__icon-answer flexbox">
                                    <div class="icon-block">
                                        <?=TSolution::showSpriteIconSvg(SITE_TEMPLATE_PATH.'/images/svg/catalog/item_icons.svg#reply', 'icon-block__icon icon-block__icon--sm mt mt--6 stroke-dark-light opacity_5', [
                                            'WIDTH' => 10,
                                            'HEIGHT' => 10,
                                        ]);?>
                                    </div>
                                </div>
                            <?endif;?>

                            <?if ($comment["PUBLISH_STATUS"] == BLOG_PUBLISH_STATUS_PUBLISH || $comment["SHOW_SCREENNED"] == "Y" || $comment["ID"] == "preview"):?>
                                <?
                                $extraStyle = "";
                                if ($arParams["is_ajax_post"] == "Y" || $comment["NEW"] == "Y")
                                    $extraStyle .= " blog-comment-new";
                                if ($comment["AuthorIsAdmin"] == "Y")
                                    $extraStyle = " blog-comment-admin";
                                if (IntVal($comment["AUTHOR_ID"]) > 0)
                                    $extraStyle .= " blog-comment-user-".IntVal($comment["AUTHOR_ID"]);
                                if ($comment["AuthorIsPostAuthor"] == "Y")
                                    $extraStyle .= " blog-comment-author";
                                if ($comment["PUBLISH_STATUS"] != BLOG_PUBLISH_STATUS_PUBLISH && $comment["ID"] != "preview")
                                    $extraStyle .= " blog-comment-hidden";
                                if ($comment["ID"] == "preview")
                                    $extraStyle .= " blog-comment-preview";
                                ?>
                                <div class="blog-comment-cont table-full-width colored_theme_bg_before<?=$extraStyle;?>">
                                    <div class="blog-comment-cont-white flexbox gap gap--20">
                                        <div class="blog-comment-info line-block line-block--gap line-block--gap-16">
                                            <?
                                            $authorFirstName = dnkGetReviewAuthorFirstName($comment);
                                            ?>
                                            <?if (!$comment['PARENT_ID'] && strlen($authorFirstName)):?>
                                                <div class="block-comment-info__image color_light rounded line-block line-block--gap line-block--justify-center font_28" title="<?=htmlspecialcharsbx($authorFirstName);?>">
                                                    <?=mb_substr($authorFirstName, 0, 1);?>
                                                </div>
                                            <?endif;?>

                                            <div class="line-block__item flexbox gap gap--6">
                                                <div class="block-comment-info__user line-block line-block--gap line-block--gap-12 line-block--flex-wrap">
                                                    <?
                                                    if (
                                                        COption::GetOptionString("blog", "allow_alias", "Y") == "Y"
                                                        && (
                                                            strlen($comment["urlToBlog"]) > 0
                                                            || strlen($comment["urlToAuthor"]) > 0
                                                        )
                                                        && array_key_exists("ALIAS", $comment["BlogUser"])
                                                        && strlen($comment["BlogUser"]["ALIAS"]) > 0
                                                    ) {
                                                        $arTmpUser = array(
                                                            "NAME" => "",
                                                            "LAST_NAME" => "",
                                                            "SECOND_NAME" => "",
                                                            "LOGIN" => "",
                                                            "NAME_LIST_FORMATTED" => $authorFirstName,
                                                        );
                                                    } elseif (
                                                        strlen($comment["urlToBlog"]) > 0
                                                        || strlen($comment["urlToAuthor"]) > 0
                                                    ) {
                                                        $arTmpUser = array(
                                                            "NAME" => $authorFirstName,
                                                            "LAST_NAME" => "",
                                                            "SECOND_NAME" => "",
                                                            "LOGIN" => "",
                                                            "NAME_LIST_FORMATTED" => "",
                                                        );
                                                    }
                                                    ?>
                                                    <div class="blog-comment__author fw-500 color_222 font_15">
                                                        <?if (strlen($comment["urlToBlog"])):?>
                                                            <?$GLOBALS["APPLICATION"]->IncludeComponent("bitrix:main.user.link",
                                                                '',
                                                                array(
                                                                    "ID" => $comment["arUser"]["ID"],
                                                                    "HTML_ID" => "blog_post_comment_".$comment["arUser"]["ID"],
                                                                    "NAME" => $arTmpUser["NAME"],
                                                                    "LAST_NAME" => $arTmpUser["LAST_NAME"],
                                                                    "SECOND_NAME" => $arTmpUser["SECOND_NAME"],
                                                                    "LOGIN" => $arTmpUser["LOGIN"],
                                                                    "NAME_LIST_FORMATTED" => $arTmpUser["NAME_LIST_FORMATTED"],
                                                                    "USE_THUMBNAIL_LIST" => "N",
                                                                    "PROFILE_URL" => $comment["urlToAuthor"],
                                                                    "PROFILE_URL_LIST" => $comment["urlToBlog"],
                                                                    "PATH_TO_SONET_MESSAGES_CHAT" => $arParams["~PATH_TO_MESSAGES_CHAT"],
                                                                    "PATH_TO_VIDEO_CALL" => $arParams["~PATH_TO_VIDEO_CALL"],
                                                                    "DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
                                                                    "SHOW_YEAR" => $arParams["SHOW_YEAR"],
                                                                    "CACHE_TYPE" => $arParams["CACHE_TYPE"],
                                                                    "CACHE_TIME" => $arParams["CACHE_TIME"],
                                                                    "NAME_TEMPLATE" => "#NAME#",
                                                                    "SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
                                                                    "PATH_TO_CONPANY_DEPARTMENT" => $arParams["~PATH_TO_CONPANY_DEPARTMENT"],
                                                                    "PATH_TO_SONET_USER_PROFILE" => ($arParams["USE_SOCNET"] == "Y" ? $comment["urlToAuthor"] : $arParams["~PATH_TO_SONET_USER_PROFILE"]),
                                                                    "INLINE" => "Y",
                                                                    "SEO_USER" => $arParams["SEO_USER"],
                                                                ),
                                                                false,
                                                                array("HIDE_ICONS" => "Y")
                                                            );?>
                                                        <?elseif (strlen($comment["urlToAuthor"])):?>
                                                            <?if ($arParams["SEO_USER"] == "Y"):?>
                                                            <noindex>
                                                            <?endif;?>

                                                                <?$GLOBALS["APPLICATION"]->IncludeComponent("bitrix:main.user.link",
                                                                    '',
                                                                    array(
                                                                        "ID" => $comment["arUser"]["ID"],
                                                                        "HTML_ID" => "blog_post_comment_".$comment["arUser"]["ID"],
                                                                        "NAME" => $arTmpUser["NAME"],
                                                                        "LAST_NAME" => $arTmpUser["LAST_NAME"],
                                                                        "SECOND_NAME" => $arTmpUser["SECOND_NAME"],
                                                                        "LOGIN" => $arTmpUser["LOGIN"],
                                                                        "NAME_LIST_FORMATTED" => $arTmpUser["NAME_LIST_FORMATTED"],
                                                                        "USE_THUMBNAIL_LIST" => "N",
                                                                        "PROFILE_URL" => $comment["urlToAuthor"],
                                                                        "PATH_TO_SONET_MESSAGES_CHAT" => $arParams["~PATH_TO_MESSAGES_CHAT"],
                                                                        "PATH_TO_VIDEO_CALL" => $arParams["~PATH_TO_VIDEO_CALL"],
                                                                        "DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
                                                                        "SHOW_YEAR" => $arParams["SHOW_YEAR"],
                                                                        "CACHE_TYPE" => $arParams["CACHE_TYPE"],
                                                                        "CACHE_TIME" => $arParams["CACHE_TIME"],
                                                                        "NAME_TEMPLATE" => "#NAME#",
                                                                        "SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
                                                                        "PATH_TO_CONPANY_DEPARTMENT" => $arParams["~PATH_TO_CONPANY_DEPARTMENT"],
                                                                        "PATH_TO_SONET_USER_PROFILE" => ($arParams["USE_SOCNET"] == "Y" ? $comment["urlToAuthor"] : $arParams["~PATH_TO_SONET_USER_PROFILE"]),
                                                                        "INLINE" => "Y",
                                                                        "SEO_USER" => $arParams["SEO_USER"],
                                                                    ),
                                                                    false,
                                                                    array("HIDE_ICONS" => "Y")
                                                                );?>

                                                            <?if ($arParams["SEO_USER"] == "Y"):?>
                                                            </noindex>
                                                            <?endif;?>
                                                        <?else:?>
                                                            <?=htmlspecialcharsbx($authorFirstName);?>
                                                        <?endif;?>
                                                    </div>

                                                    <?if (strlen($comment["urlToDelete"]) && strlen($comment["AuthorEmail"])):?>
                                                        <a href="mailto:<?=$comment["AuthorEmail"];?>" class="no-decoration">(<?=$comment["AuthorEmail"];?>)</a>
                                                    <?endif;?>

                                                    <div class="blog-comment__date secondary-color font_13">
                                                        <?=FormatDate('d F Y, H:i', MakeTimeStamp($comment["DateFormated"]));?>
                                                    </div>
                                                </div>

                                                <div class="blog-info__rating line-block line-block--gap line-block--gap-12">
                                                    <div class="line-block__item">
                                                        <div class="line-block line-block--gap line-block--gap-4">
                                                            <?TSolution\Functions::showBlockHtml([
                                                                'FILE' => 'ui/rating-progressbar.php',
                                                                'PARAMS' => [
                                                                    'PROGRESSBAR_CLASS' => 'mb mb--2',
                                                                    'RATING' => $comment['UF_ASPRO_COM_RATING'],
                                                                    'STATIC' => true,
                                                                    'VALUE_CLASS' => 'font_15 fw-500',
                                                                    'WRAPPER_CLASS' => 'line-block--gap-4',
                                                                ]
                                                            ]);?>
                                                        </div>
                                                    </div>

                                                    <?if (isset($comment['UF_ASPRO_COM_APPROVE']) && $comment['UF_ASPRO_COM_APPROVE']):?>
                                                        <div class="line-block__item">
                                                            <div class="blog-comment-approve-text font_13">
                                                                <?=isset($arParams["REAL_CUSTOMER_TEXT"]) && strlen($arParams["REAL_CUSTOMER_TEXT"]) ? $arParams["REAL_CUSTOMER_TEXT"] : GetMessage('T_REAL_CUSTOMER_TEXT_DEFAULT');?>
                                                            </div>
                                                        </div>
                                                    <?endif;?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="blog-comment-post flexbox gap gap--12">
                                            <?if (!!strlen($comment["TitleFormated"])):?>
                                                <b><?=$comment["TitleFormated"];?></b><br />
                                            <?endif;?>

                                            <?if (isset($comment["TEXT"]['TYPE']) && $comment["TEXT"]['TYPE'] == 'PARENT'):?>
                                                <?if ($comment["TEXT"]['VIRTUES']):?>
                                                    <div class="blog-comment-post__item comment-text__text VIRTUES font_15" data-label="<?=GetMessage('BPC_VIRTUES');?>">
                                                        <?=$comment["TEXT"]['VIRTUES'];?>
                                                    </div>
                                                <?endif;?>

                                                <?if ($comment["TEXT"]['LIMITATIONS']):?>
                                                    <div class="blog-comment-post__item comment-text__text LIMITATIONS font_15" data-label="<?=GetMessage('BPC_LIMITATIONS');?>">
                                                        <?=$comment["TEXT"]['LIMITATIONS'];?>
                                                    </div>
                                                <?endif;?>

                                                <?if ($comment["TEXT"]['COMMENT']):?>
                                                    <div class="<?=!$tabCount ? 'blog-comment-post__item ' : '';?>comment-text__text COMMENT font_15"
                                                        <?=!$tabCount ? ' data-label="'.GetMessage('BPC_MESSAGE').'"' : '';?>
                                                    >
                                                        <?=$comment["TEXT"]['COMMENT'];?>
                                                    </div>
                                                <?endif;?>
                                            <?else:?>
                                                <?if ($comment["~POST_TEXT"]):?>
                                                    <?
                                                    $pattern = '/<comment>(.*?)<\/comment>/s';
                                                    preg_match($pattern, $comment["~POST_TEXT"], $matches);
                                                    $commentText = $matches[1];
                                                    ?>
                                                    <div class="blog-comment-post__item comment-text__text COMMENT">
                                                        <?=$commentText;?>
                                                    </div>
                                                <?endif;?>
                                            <?endif;?>

                                            <?if ($comment['IMAGES']):?>
                                                <?$commentSliderConfig = [
                                                    'allowSlideNext' => false,
                                                    'allowSlidePrev' => false,
                                                    'allowTouchMove' => false,
                                                    'init' => false,
                                                    'slidesPerView' => 4,
                                                    'type' => 'comment_block_slider_main',
                                                    'breakpoints' => [
                                                        601 => [
                                                            'slidesPerView' => 6,
                                                        ],
                                                        768 => [
                                                            'slidesPerView' => 8,
                                                        ],
                                                        1200 =>  [
                                                            'slidesPerView' => 12,
                                                        ],
                                                    ]
                                                ];?>
                                                <div class="blog-comment-content__item comment-image__wrapper pb pb--8">
                                                    <div class="reviews-gallery-block" >
                                                        <?=TSolution\Functions::showGallery($comment['IMAGES'], [
                                                            'BREAKPOINTS' => [
                                                                '320' => 3,
                                                                '374' => 4,
                                                                '420' => 5,
                                                                '599' => 7,
                                                                '767' => 8,
                                                                '991' => 10,
                                                            ],
                                                            'CONTAINER_CLASS' => 'gallery-review',
                                                            'ID' => $comment['ID'],
                                                        ]);?>
                                                    </div>
                                                </div>
                                            <?endif;?>

                                            <?if (!empty($arParams["arImages"][$comment["ID"]])):?>
                                                <div class="feed-com-files">
                                                    <div class="feed-com-files-title"><?=GetMessage("BLOG_PHOTO");?></div>
                                                    <div class="feed-com-files-cont">
                                                        <?foreach ($arParams["arImages"][$comment["ID"]] as $val):?>
                                                            <span class="feed-com-files-photo"><img src="<?=$val["small"];?>" alt="" data-bx-image="<?=$val["full"];?>"></span>
                                                        <?endforeach;?>
                                                    </div>
                                                </div>
                                            <?endif;?>

                                            <?if ($comment["COMMENT_PROPERTIES"]["SHOW"] == "Y"):?>
                                                <div>
                                                    <?
                                                    $eventHandlerID = AddEventHandler('main', 'system.field.view.file', Array('CBlogTools', 'blogUFfileShow'));

                                                    foreach ($comment["COMMENT_PROPERTIES"]["DATA"] as $FIELD_NAME => $arPostField) {
                                                        if (!empty($arPostField["VALUE"])) {
                                                            $GLOBALS["APPLICATION"]->IncludeComponent(
                                                                "bitrix:system.field.view",
                                                                $arPostField["USER_TYPE"]["USER_TYPE_ID"],
                                                                array("arUserField" => $arPostField),
                                                                null,
                                                                array("HIDE_ICONS"=>"Y")
                                                            );
                                                        }
                                                    }
                                                    ?>
                                                </div>
                                                <?if ($eventHandlerID !== false && (intval($eventHandlerID) > 0 )) {
                                                    RemoveEventHandler('main', 'system.field.view.file', $eventHandlerID);
                                                }?>
                                            <?endif;?>

                                            <div class="blog-comment-post__item blog-comment-meta pt pt--4">
                                                <?// like buttons?>
                                                <?if ($arParams["SHOW_RATING"] == "Y") {
                                                    include('like.php');
                                                }?>

                                                <?// answer button?>
                                                <?if ($bCanUserComment === true):?>
                                                    <span class="blog-comment-answer blog-comment-action color_222">
                                                        <button type="button"
                                                            class="btn--no-btn-appearance blog-comment-action__link dotted font_14"
                                                            onclick="commentAction('<?=$comment['ID'];?>', this, 'showComment');"
                                                            data-type='showComment'
                                                        ><?=GetMessage("B_B_MS_REPLY");?></button>
                                                    </span>
                                                <?endif;?>

                                                <?// edit comment button?>
                                                <?if ($comment["CAN_EDIT"] == "Y"):?>
                                                    <script>
                                                        top.text<?=$comment["ID"];?> = text<?=$comment["ID"];?> = '<?=CUtil::JSEscape($comment["~POST_TEXT"]);?>';
                                                        top.title<?=$comment["ID"];?> = title<?=$comment["ID"];?> = '<?=CUtil::JSEscape($comment["TITLE"]);?>';
                                                    </script>
                                                    <span class="blog-comment-edit blog-comment-action color_222">
                                                        <button type="button"
                                                            class="btn--no-btn-appearance blog-comment-action__link dotted font_14"
                                                            onclick="commentAction('<?=$comment['ID'];?>', this, 'editComment');"
                                                            data-type='editComment'
                                                        ><?=GetMessage("BPC_MES_EDIT");?></button>
                                                    </span>
                                                <?endif;?>

                                                <?// hide comment button?>
                                                <?if (strlen($comment["urlToShow"])):?>
                                                    <span class="blog-comment-show blog-comment-action color_222">
                                                        <?if ($arParams['AJAX_POST'] === 'Y'):?>
                                                            <button type="button"
                                                                class="btn--no-btn-appearance blog-comment-action__link dotted font_14"
                                                                title="<?=GetMessage('BPC_MES_SHOW');?>"
                                                                onclick="return hideShowComment('<?=$comment['urlToShow'].'&'.bitrix_sessid_get();?>', '<?=$comment['ID'];?>');"
                                                            ><?=GetMessage("BPC_MES_SHOW");?></button>
                                                        <?else:?>
                                                            <a class="blog-comment-action__link dotted dark_link font_14"
                                                                title="<?=GetMessage('BPC_MES_SHOW');?>"
                                                                href="<?=$comment["urlToShow"]."&".bitrix_sessid_get();?>"
                                                            ><?=GetMessage("BPC_MES_SHOW");?></a>
                                                        <?endif;?>
                                                    </span>
                                                <?endif;?>

                                                <?// show comment button?>
                                                <?if (strlen($comment["urlToHide"])):?>
                                                    <?$targetURL = $comment['urlToHide'].'&'.bitrix_sessid_get().'&IBLOCK_ID='.htmlspecialcharsbx($request['IBLOCK_ID']).'&ELEMENT_ID='.htmlspecialcharsbx($request['ELEMENT_ID']);?>
                                                    <span class="blog-comment-show blog-comment-action color_222">
                                                        <?if ($arParams['AJAX_POST'] === 'Y'):?>
                                                            <button type="button"
                                                                class="btn--no-btn-appearance blog-comment-action__link dotted font_14"
                                                                title="<?=GetMessage('BPC_MES_HIDE');?>"
                                                                onclick="return hideShowComment('<?=$targetURL;?>', '<?=$comment['ID'];?>');"
                                                            ><?=GetMessage("BPC_MES_HIDE");?></button>
                                                        <?else:?>
                                                            <a class="blog-comment-action__link dotted dark_link font_14"
                                                                title="<?=GetMessage('BPC_MES_HIDE');?>"
                                                                href="<?=$targetURL;?>"
                                                            ><?=GetMessage("BPC_MES_HIDE");?></a>
                                                        <?endif;?>
                                                    </span>
                                                <?endif;?>

                                                <?// approve comment button?>
                                                <?if (strlen($comment["urlToApprove"])):?>
                                                    <?
                                                    $bpcMessage = $comment['UF_ASPRO_COM_APPROVE'] ? "BPC_MES_UNAPPROVE" : "BPC_MES_APPROVE";
                                                    $targetURL = $comment['urlToApprove'].'&'.bitrix_sessid_get().'&IBLOCK_ID='.htmlspecialcharsbx($request['IBLOCK_ID']).'&ELEMENT_ID='.htmlspecialcharsbx($request['ELEMENT_ID']);
                                                    ?>
                                                    <span class="blog-comment-approve blog-comment-action color_222">
                                                        <?if ($arParams['AJAX_POST'] === 'Y'):?>
                                                            <button type="button"
                                                                class="btn--no-btn-appearance blog-comment-action__link dotted font_14"
                                                                title="<?=GetMessage($bpcMessage);?>"
                                                                onclick="hideShowComment('<?=$targetURL;?>', '<?=$comment['ID'];?>');"
                                                            ><?=GetMessage($bpcMessage);?></button>
                                                        <?else:?>
                                                            <a class="blog-comment-action__link dotted dark_link font_14"
                                                                title="<?=GetMessage($bpcMessage);?>"
                                                                href="<?=$targetURL;?>"
                                                            ><?=GetMessage($bpcMessage);?></a>
                                                        <?endif;?>
                                                    </span>
                                                <?endif;?>

                                                <?// delete comment button?>
                                                <?if (strlen($comment["urlToDelete"])):?>
                                                    <span class="blog-comment-delete blog-comment-action color_222">
                                                        <?if ($arParams["AJAX_POST"] == "Y"):?>
                                                            <button type="button"
                                                                class="btn--no-btn-appearance blog-comment-action__link dotted font_14"
                                                                onclick="if (confirm('<?=GetMessage('BPC_MES_DELETE_POST_CONFIRM');?>')) deleteComment('<?=$comment['urlToDelete'].'&'.bitrix_sessid_get();?>&IBLOCK_ID=<?=htmlspecialcharsbx($request['IBLOCK_ID']);?>&ELEMENT_ID=<?=htmlspecialcharsbx($request['ELEMENT_ID']);?>', '<?=$comment['ID'];?>');"
                                                                title="<?=GetMessage("BPC_MES_DELETE");?>"
                                                            ><?=GetMessage("BPC_MES_DELETE");?></button>
                                                        <?else:?>
                                                            <a href="javascript:if (confirm('<?=GetMessage("BPC_MES_DELETE_POST_CONFIRM");?>')) window.location='<?=$comment["urlToDelete"]."&".bitrix_sessid_get();?>&IBLOCK_ID=<?=htmlspecialcharsbx($request["IBLOCK_ID"]);?>&ELEMENT_ID=<?=htmlspecialcharsbx($request["ELEMENT_ID"]);?>'"
                                                                class="blog-comment-action__link dotted dark_link font_14"
                                                                title="<?=GetMessage("BPC_MES_DELETE");?>"
                                                            ><?=GetMessage("BPC_MES_DELETE");?></a>
                                                        <?endif;?>
                                                    </span>
                                                <?endif;?>

                                                <?// mark comment as spam button?>
                                                <?if (strlen($comment["urlToSpam"])):?>
                                                    <span class="blog-comment-delete blog-comment-action blog-comment-spam color_222">
                                                        <button type="button"
                                                            class="btn--no-btn-appearance blog-comment-action__link dotted font_14"
                                                            onclick="window.location.href = '<?=$comment['urlToSpam'];?>'"
                                                            title="<?=GetMessage("BPC_MES_SPAM_TITLE");?>"><?=GetMessage("BPC_MES_SPAM");?></button>
                                                    </span>
                                                <?endif;?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?
                                if (
                                    strlen($errorComment) <= 0
                                    && (strlen($post["preview"]) > 0 && $post["show_preview"] != "N")
                                    && (IntVal($post["parentId"]) > 0 || IntVal($post["edit_id"]) > 0)
                                    && (
                                        (IntVal($post["parentId"]) == $comment["ID"] && IntVal($post["edit_id"]) <= 0)
                                        || (IntVal($post["edit_id"]) > 0 && IntVal($post["edit_id"]) == $comment["ID"] && $comment["CAN_EDIT"] == "Y")
                                    )
                                ) {
                                    $level = 0;
                                    $commentPreview = array(
                                        "ID" => "preview",
                                        "TitleFormated" => htmlspecialcharsEx($post["subject"]),
                                        "TextFormated" => $post["commentFormated"],
                                        "AuthorName" => $User["NAME"],
                                        "DATE_CREATE" => GetMessage("B_B_MS_PREVIEW_TITLE"),
                                    );
                                    ShowComment($commentPreview, (IntVal($post["edit_id"]) == $comment["ID"] && $comment["CAN_EDIT"] == "Y") ? $level : ($level+1), 2.5, false, Array(), false, false, false, $arParams);
                                }

                                if (
                                    strlen($errorComment) && $bCanUserComment === true
                                    && (IntVal($post["parentId"])==$comment["ID"] || IntVal($post["edit_id"]) == $comment["ID"])
                                ) {
                                    ?>
                                    <div class="alert alert-danger blog-note-box blog-note-error">
                                        <div class="blog-error-text">
                                            <?=$errorComment?>
                                        </div>
                                    </div>
                                    <?
                                }
                                ?>
                                </div>

                                <div id="err_comment_<?=$comment['ID'];?>"></div>
                                <div id="form_comment_<?=$comment['ID'];?>" class="js-form-comment blog-comment__form-container" style="display: none"></div>
                                <div id="new_comment_cont_<?=$comment['ID'];?>"></div>
                                <div id="new_comment_<?=$comment['ID'];?>" style="display:none;"></div>
                                <?if (
                                    (strlen($errorComment) > 0 || strlen($post["preview"]) > 0)
                                    && (IntVal($post["parentId"]) == $comment["ID"] || IntVal($post["edit_id"]) == $comment["ID"])
                                    && $bCanUserComment === true
                                ):?>
                                    <script>
                                        top.text<?=$comment["ID"];?> = text<?=$comment["ID"];?> = '<?=CUtil::JSEscape($post["comment"]);?>';
                                        top.title<?=$comment["ID"];?> = title<?=$comment["ID"];?> = '<?=CUtil::JSEscape($post["subject"]);?>';
                                        <?if (IntVal($post["edit_id"]) == $comment["ID"]):?>
                                            editComment('<?=$comment["ID"];?>');
                                        <?else:?>
                                            showComment('<?=$comment["ID"];?>', 'Y', '<?=CUtil::JSEscape($post["user_name"]);?>', '<?=CUtil::JSEscape($post["user_email"]);?>', 'Y');
                                        <?endif;?>
                                    </script>
                                <?endif;?>

                            <?elseif ($comment["SHOW_AS_HIDDEN"] == "Y"):?>
                                <b><?=GetMessage("BPC_HIDDEN_COMMENT");?></b>
                            <?endif;?>
                        </div>

                    <?if ($tabCount > 0):?>
                        </div>
                    <?endif;?>
                <?}
            }

            function RecursiveComments($sArray, $key, $level=0, $first=false, $canModerate=false, $User, $use_captcha, $bCanUserComment, $errorComment, $arSumComments, $arParams) {
                if (!empty($sArray[$key])) {
                    foreach ($sArray[$key] as $comment) {
                        if (!empty($arSumComments[$comment["ID"]])) {
                            $comment["CAN_EDIT"] = $arSumComments[$comment["ID"]]["CAN_EDIT"];
                            $comment["SHOW_AS_HIDDEN"] = $arSumComments[$comment["ID"]]["SHOW_AS_HIDDEN"];
                            $comment["SHOW_SCREENNED"] = $arSumComments[$comment["ID"]]["SHOW_SCREENNED"];
                            $comment["NEW"] = $arSumComments[$comment["ID"]]["NEW"];
                        }
                        ShowComment($comment, $level, 2.5, $canModerate, $User, $use_captcha, $bCanUserComment, $errorComment, $arParams);

                        if (!empty($sArray[$comment["ID"]])) {
                            foreach ($sArray[$comment["ID"]] as $key1) {
                                if (!empty($arSumComments[$key1["ID"]])) {
                                    $key1["CAN_EDIT"] = $arSumComments[$key1["ID"]]["CAN_EDIT"];
                                    $key1["SHOW_AS_HIDDEN"] = $arSumComments[$key1["ID"]]["SHOW_AS_HIDDEN"];
                                    $key1["SHOW_SCREENNED"] = $arSumComments[$key1["ID"]]["SHOW_SCREENNED"];
                                    $key1["NEW"] = $arSumComments[$key1["ID"]]["NEW"];
                                }

                                ShowComment($key1, ($level+1), 2.5, $canModerate, $User, $use_captcha, $bCanUserComment, $errorComment, $arParams);

                                if (!empty($sArray[$key1["ID"]])) {
                                    RecursiveComments($sArray, $key1["ID"], ($level+2), false, $canModerate, $User, $use_captcha, $bCanUserComment, $errorComment, $arSumComments, $arParams);
                                }
                            }
                        }
                        if ($first)
                            $level=0;

                        if ($level == 0):?>
                            </div>
                        <?endif;
                    }?>
                    <?
                }
            }
            ?>

            <?if (!$bAjaxPost):?>
                <?if ($arResult["CanUserComment"]):?>
                    <?
                    $postTitle = "";
                    if ($arParams["NOT_USE_COMMENT_TITLE"] != "Y") {
                        $postTitle = "RE: ".CUtil::JSEscape($arResult["Post"]["TITLE"]);
                    }
                    ?>

                    <?if (
                        strlen($arResult["COMMENT_ERROR"]) > 0
                        && strlen($post["parentId"]) < 2
                        && IntVal($post["parentId"])==0
                        && IntVal($post["edit_id"]) <= 0
                    ):?>
                        <div class="alert alert-danger blog-note-box blog-note-error">
                            <div class="blog-error-text"><?=$arResult["COMMENT_ERROR"];?></div>
                        </div>
                    <?endif;?>
                <?endif;?>

                <?if (!$arResult["CommentsResult"][0] && !$arResult["ajax_comment"] && !strlen($arResult["COMMENT_ERROR"])):?>
                    <div class="outer-rounded-x bordered alert-empty p p--32">
                        <div class="no-margin-p line-block line-block--gap line-block--flex-wrap line-block--justify-between line-block--align-flex-start">
                            <div>
                                <?=GetMessage('EMPTY_REVIEWS');?>
                            </div>

                            <button type="button" class="btn btn-default show-comment blog-comment-action__link">
                                <?=GetMessage('ADD_REVIEW');?>
                            </button>
                        </div>
                    </div>
                    <script>
                        var comments = $('.EXTENDED .blog-comments');
                        if (comments.length) {
                            comments.addClass('empty-reviews');
                        }
                    </script>
                <?endif;?>

                <?if ($arResult["CanUserComment"]):?>
                    <div class="js-form-comment" id="form_comment_0" style="display: none;">
                        <div id="err_comment_0"></div>
                        <div class="js-form-comment" id="form_comment_0"></div>
                        <div id="new_comment_0" style="display:none;"></div>
                    </div>

                    <div id="new_comment_cont_0" class="hidden"></div>

                    <?if (
                        (strlen($arResult["COMMENT_ERROR"]) || strlen($post["preview"]) > 0)
                        && $arResult['COMMENT_ERROR_TYPE'] !== 'FILTER'
                        && IntVal($post["parentId"]) == 0
                        && strlen($post["parentId"]) < 2
                        && IntVal($post["edit_id"]) <= 0
                    ):?>
                        <script>
                            top.text0 = text0 = '<?=CUtil::JSEscape($post["comment"]);?>';
                            top.title0 = title0 = '<?=CUtil::JSEscape($post["subject"]);?>';
                            showComment('0', 'Y', '<?=CUtil::JSEscape($post["user_name"]);?>', '<?=CUtil::JSEscape($post["user_email"]);?>', 'Y');
                        </script>
                    <?endif;?>
                <?endif;?>

                <?include_once('sort.php');?>
            <?endif;?>

            <?
            $arParams["RATING"] = $arResult["RATING"];
            $arParams["component"] = $component;
            $arParams["arImages"] = $arResult["arImages"];

            if ($bAjaxPost)
                $arParams["is_ajax_post"] = "Y";
            ?>

            <?if (!$bAjaxPost && $arResult["NEED_NAV"] == "Y"):?>
                <div class="blog-comment__container">
                    <?for ($i = 1; $i <= $arResult["PAGE_COUNT"]; $i++):?>
                        <?
                        $tmp = $arResult["CommentsResult"];
                        $tmp[0] = $arResult["PagesComment"][$i];
                        ?>
                        <div id="blog-comment-page-<?=$i?>" class="<?=$arResult["PAGE"] != $i ? "hidden" : '';?>">
                            <?RecursiveComments($tmp, $arResult["firstLevel"], 0, true, $arResult["canModerate"], $arResult["User"], $arResult["use_captcha"], $arResult["CanUserComment"], $arResult["COMMENT_ERROR"], $arResult["Comments"], $arParams);?>
                        </div>
                    <?endfor;?>
                </div>
            <?else:?>
                <?if (!$bAjaxPost):?>
                    <div class="blog-comment__container">
                <?endif; ?>
                    <?RecursiveComments($arResult["CommentsResult"], $arResult["firstLevel"], 0, true, $arResult["canModerate"], $arResult["User"], $arResult["use_captcha"], $arResult["CanUserComment"], $arResult["COMMENT_ERROR"], $arResult["Comments"], $arParams);?>
                <?if (!$bAjaxPost):?>
                    </div>
                <?endif;?>
            <?endif;?>

            <?if (!$bAjaxPost && $arResult["NEED_NAV"] == "Y"):?>
                <div class="bottom_nav">
                    <div class="blog-comment-nav hidden">
                        <?for ($i = 1; $i <= $arResult["PAGE_COUNT"]; $i++):?>
                            <?
                            $style = "blog-comment-nav-item";
                            if ($i == $arResult["PAGE"]) {
                                $style .= " blog-comment-nav-item-sel colored_theme_bg";
                            }
                            ?>
                            <a class="<?=$style;?>"
                                href="<?=$arResult["NEW_PAGES"][$i];?>"
                                onclick="return bcNav('<?=$i?>', this)"
                                id="blog-comment-nav-b<?=$i;?>"
                            ><?=$i;?></a>
                        <?endfor;?>
                    </div>

                    <div class="more_text_ajax btn btn-transparent blog-comment__load_more">
                        <?=GetMessage('PAGER_SHOW_MORE');?>
                    </div>
                </div>
            <?endif;?>
        <?endif;?>
    <?endif;?>
    </div>
    <?
    if ($bAjaxPost) die();

    function makeInputsFromParams($arParams, $name="PARAMS") {
        $result = "";

        if (is_array($arParams)) {
            foreach ($arParams as $key => $value) {
                if (substr($key, 0, 1) != "~") {
                    $inputName = $name.'['.$key.']';

                    $result .= is_array($value)
                        ? makeInputsFromParams($value, $inputName)
                        : '<input type="hidden" name="'.$inputName.'" value="'.$value.'">'.PHP_EOL;
                }
            }
        }

        return $result;
    }
    ?>
</div>
