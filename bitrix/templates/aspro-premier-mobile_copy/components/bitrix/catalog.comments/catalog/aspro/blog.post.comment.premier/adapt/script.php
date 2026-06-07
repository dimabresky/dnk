<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
?>
<script>
var reviews_count = <?=CUtil::PhpToJSObject($arResult['REVIEWS_COUNT']);?>;
var reviews_count_text = <?=CUtil::PhpToJSObject($arResult['REVIEWS_COUNT_TEXT']);?>;

if (reviews_count && !isNaN(reviews_count) && reviews_count_text) {
    $('.EXTENDED .element-count-wrapper .element-count').text(reviews_count_text).removeClass('hidden');
}

<?if ($arResult['REVIEWS_COUNT']):?>
    var tabReviews = $('a[href="#catalog_comments"]:not(.rating__static-block)');
    tabReviews.each(function() {
        if (!$(this).hasClass('counted')) {
            $(this).html($(this).text() + ' <span class="icon-count"><?=$arResult['REVIEWS_COUNT'];?></span>').addClass('counted');
        }
    });

    if (tabReviews.parent().hasClass('active')) {
        $('.reviews-gallery-block .slider-solution').removeClass('appear-block');
    }
<?endif;?>

function onLightEditorShow(content) {
    if (!window.oBlogComLHE) {
        return BX.addCustomEvent(window, 'LHE_OnInit', function() {
            setTimeout(function() {
                onLightEditorShow(content);
            }, 500);
        });
    }

    oBlogComLHE.SetContent(content || '');
    oBlogComLHE.CreateFrame(); // We need to recreate editable frame after reappending editor container
    oBlogComLHE.SetEditorContent(oBlogComLHE.content);
    oBlogComLHE.SetFocus();
}

function commentAction(key, el, type) {
    const $this = $(el);
    const $comment = $('#form_comment_' + key);
    const $commentsContainer = $('.blog-comments');
    const currentCommentType = $comment.data('type');
    const currentKey = $commentsContainer.data('key');

    $('.js-form-comment').hide();
    $('.blog-comment-action__link').not($this).removeClass('clicked');

    if (!currentKey) {
        $comment.data('key', key);
    }

    if ((type !== currentCommentType && typeof window[type] !== 'undefined') || currentKey !== key) {
        window[type](key);
        $comment.data('type', type);
        $comment.data('key', key);
    }

    if ((type !== currentCommentType && !$this.hasClass('clicked')) || type === currentCommentType) {
        toggleComment($this, $comment);
    }

}

function toggleComment($el, $comment,) {
    if ($el.hasClass('clicked')) {
        $comment.slideUp();
        $el.removeClass('clicked');
        $el.closest('.blog-comment-content__item').find('.blog-comment-action__link').removeClass('clicked');
    } else {
        $comment.slideDown();
        $el.addClass('clicked');
    }
}

function showComment(key, error, userName, userEmail, needData) {
    subject = '';
    comment = '';

    if (needData == "Y") {
        subject = window["title" + key];
        comment = window["text" + key];
    }

    var pFormCont = BX('form_c_del');
    if (!pFormCont) {
        return;
    }

    clearForm(pFormCont);

    if (BX.hasClass(pFormCont, 'blog-comment__edit-form')) {
        BX.removeClass(pFormCont, 'blog-comment__edit-form');
    }
    BX('form_comment_' + key).appendChild(pFormCont); // Move form
    fileInputInit("<?=GetMessage('INPUT_FILE_DEFAULT');?>", 'Y');

    pFormCont.style.display = "block";

    form_comment.parentId.value = key;
    form_comment.edit_id.value = '';
    form_comment.act.value = 'add';
    form_comment.post.value = '<?=GetMessageJS('B_B_MS_SEND');?>';
    form_comment.action = form_comment.action + "#" + key;

    if (error == "Y") {
        if (comment.length > 0) {
            comment = comment.replace(/\/</gi, '<');
            comment = comment.replace(/\/>/gi, '>');
        }

        if (userName.length > 0) {
            userName = userName.replace(/\/</gi, '<');
            userName = userName.replace(/\/>/gi, '>');
            form_comment.user_name.value = userName;
        }

        if (userEmail.length > 0) {
            userEmail = userEmail.replace(/\/</gi, '<');
            userEmail = userEmail.replace(/\/>/gi, '>');
            form_comment.user_email.value = userEmail;
        }

        if (subject && subject.length > 0 && form_comment.subject) {
            subject = subject.replace(/\/</gi, '<');
            subject = subject.replace(/\/>/gi, '>');
            form_comment.subject.value = subject;
        }
    }

    files = BX('form_comment')["UF_BLOG_COMMENT_DOC[]"];
    if (files !== null && typeof files != 'undefined') {
        if (!files.length) {
            BX.remove(files);
        } else {
            for (i = 0; i < files.length; i++) {
                BX.remove(BX(files[i]));
            }
        }
    }

    filesForm = BX.findChild(BX('blog-comment-user-fields-UF_BLOG_COMMENT_DOC'), {
        'className': 'file-placeholder-tbody'
    }, true, false);
    if (filesForm !== null && typeof filesForm != 'undefined') {
        BX.cleanNode(filesForm, false);
    }

    filesForm = BX.findChild(BX('blog-comment-user-fields-UF_BLOG_COMMENT_DOC'), {
        'className': 'feed-add-photo-block'
    }, true, true);
    if (filesForm !== null && typeof filesForm != 'undefined') {
        for (i = 0; i < filesForm.length; i++) {
            if (BX(filesForm[i]).parentNode.id != 'file-image-template') {
                BX.remove(BX(filesForm[i]));
            }
        }
    }

    filesForm = BX.findChild(BX('blog-comment-user-fields-UF_BLOG_COMMENT_DOC'), {
        'className': 'file-selectdialog'
    }, true, false);
    if (filesForm !== null && typeof filesForm != 'undefined') {
        BX.hide(BX.findChild(BX('blog-comment-user-fields-UF_BLOG_COMMENT_DOC'), {
            'className': 'file-selectdialog'
        }, true, false));
        BX.show(BX('blog-upload-file'));
    }

    onLightEditorShow(comment);
}

function editComment(key) {
    subject = window["title" + key];
    comment = window["text" + key];

    if (comment.length > 0) {
        comment = comment.replace(/\/</gi, '<');
        comment = comment.replace(/\/>/gi, '>');
    }

    var pFormCont = BX('form_c_del');
    var parent = BX.findParent(BX('form_comment_' + key), {
        "class": "blog-comment"
    });
    var commentBlock = BX('blg-comment-' + key);

    if (commentBlock === null) {
        if (BX('blg-comment-' + key + 'old') !== null) {
            $('#blg-comment-' + key + 'old').attr('id', 'blg-comment-' + key);
            commentBlock = BX('blg-comment-' + key);
        }
    }

    if (BX.hasClass(parent, 'parent')) {
        BX.addClass(pFormCont, 'blog-comment__edit-form');
        updateEditForm(commentBlock, pFormCont, true);
    } else if (BX.hasClass(pFormCont, 'blog-comment__edit-form')) {
        BX.removeClass(pFormCont, 'blog-comment__edit-form');
        updateEditForm(commentBlock, pFormCont);
    } else {
        updateEditForm(commentBlock, pFormCont);
    }

    BX('form_comment_' + key).appendChild(pFormCont); // Move form

    typeof dropZone === 'object' && dropZone.drawImagesFromColection(document.querySelectorAll(`[rel="reviews-gallery_${key}"] img`));

    pFormCont.style.display = "block";

    files = BX('form_comment')["UF_BLOG_COMMENT_DOC[]"];
    if (files !== null && typeof files != 'undefined') {
        if (!files.length) {
            BX.remove(files);
        } else {
            for (i = 0; i < files.length; i++) {
                BX.remove(BX(files[i]));
            }
        }
    }
    filesForm = BX.findChild(BX('blog-comment-user-fields-UF_BLOG_COMMENT_DOC'), {
        'className': 'file-placeholder-tbody'
    }, true, false);
    if (filesForm !== null && typeof filesForm != 'undefined') {
        BX.cleanNode(filesForm, false);
    }

    filesForm = BX.findChild(BX('blog-comment-user-fields-UF_BLOG_COMMENT_DOC'), {
        'className': 'feed-add-photo-block'
    }, true, true);
    if (filesForm !== null && typeof filesForm != 'undefined') {
        for (i = 0; i < filesForm.length; i++) {
            if (BX(filesForm[i]).parentNode.id != 'file-image-template') {
                BX.remove(BX(filesForm[i]));
            }
        }
    }

    filesForm = BX.findChild(BX('blog-comment-user-fields-UF_BLOG_COMMENT_DOC'), {
        'className': 'file-selectdialog'
    }, true, false);
    if (filesForm !== null && typeof filesForm != 'undefined') {
        BX.hide(BX.findChild(BX('blog-comment-user-fields-UF_BLOG_COMMENT_DOC'), {
            'className': 'file-selectdialog'
        }, true, false));
        BX.show(BX('blog-upload-file'));
    }

    onLightEditorShow(comment);

    form_comment.parentId.value = '';
    form_comment.edit_id.value = key;
    form_comment.act.value = 'edit';
    form_comment.post.value = '<?=GetMessageJS('B_B_MS_SAVE');?>';
    form_comment.action = form_comment.action + "#" + key;

    if (subject && subject.length > 0 && form_comment.subject) {
        subject = subject.replace(/\/</gi, '<');
        subject = subject.replace(/\/>/gi, '>');
        form_comment.subject.value = subject;
    }
}

function waitResult(id) {
    id = parseInt(id);
    r = 'new_comment_' + id;
    ob = BX(r);
    const eventdata = {
        id: id,
    };

    if (ob.innerHTML.length > 0) {
        var obNew = BX.processHTML(ob.innerHTML, true);
        scripts = obNew.SCRIPT;
        BX.ajax.processScripts(scripts, true);
        if (window.commentEr && window.commentEr == "Y") {
            BX('err_comment_' + id).innerHTML = ob.innerHTML;
            ob.innerHTML = '';
        } else {
            if (BX('edit_id').value > 0) {
                if (BX('blg-comment-' + id)) {
                    BX('blg-comment-' + id + 'old').innerHTML = BX('blg-comment-' + id).innerHTML;
                    BX('blg-comment-' + id + 'old').id = 'blg-comment-' + id;
                } else {
                    BX('blg-comment-' + id + 'old').innerHTML = ob.innerHTML;
                }

                // clean deleted images
                document.querySelector('.blog-comment-form__existing-files').innerHTML = '';
            } else {
                if (id) {
                    BX('new_comment_cont_' + id).innerHTML += ob.innerHTML;
                    BX('new_comment_cont_' + id).classList.remove('hidden');
                } else {
                    $('.blog-comment__container').prepend($(ob.innerHTML));
                }

                // adjust first comment's html
                const nodeAlertEmpty = document.querySelector('.alert-empty')
                if (nodeAlertEmpty) {
                    // remove empty reviews block
                    nodeAlertEmpty.remove();

                    // show raging block
                    const nodeRatingBlock = document.querySelector('.comments-block__reviews-info-inner');
                    if (nodeRatingBlock) {
                        nodeRatingBlock.classList.remove('hidden');
                    }

                    // show sort
                    const nodeSort = document.querySelector('.reviews_sort');
                    if (nodeSort) {
                        nodeSort.classList.remove('hidden');
                    }

                    // add first rating
                    const nodeRateBar = ob.querySelector('.rate__bar');
                    if (nodeRateBar) {
                        nodeRatingBlock.querySelector('.rating-value .count').innerText = nodeRateBar.value;
                    }
                }
            }
            ob.innerHTML = '';
        }
        window.commentEr = false;

        $('.blog-comment-form').removeClass('sending');

        BX.closeWait();
        BX('post-button').disabled = false;

        if (id == 0) {
            var comments = document.querySelector('.EXTENDED .blog-comments');
            if (comments !== null) {
                comments.classList.remove('empty-reviews');
            }

            var bError = $(comments).find('[id^=err_comment] *').length > 0;
            if (!bError) {
                $('#form_comment_0').slideUp();
                clearForm(BX('form_c_del'));
                eventdata.status = 'success';
            } else {
                $('#form_comment_0 [name=comment]').val(window.commentOr);
                eventdata.status = 'error';
                eventdata.message = $(comments).find('[id^=err_comment]').text().trim();
            }

            BX.onCustomEvent("onIblockCatalogCommentSubmit", [eventdata]);
        } else {
            BX('form_c_del').style.display = "none";
        }
    } else {
        setTimeout("waitResult('" + id + "')", 500);
    }

    $('.reviews-gallery-block .slider-solution').removeClass('appear-block');
}

function submitComment() {
    //oBlogComLHE.SaveContent();
    BX('post-button').focus();
    BX('post-button').disabled = true;
    obForm = BX('form_comment');
    const bCommentRequired = <?=isset($arParams['REVIEW_COMMENT_REQUIRED']) && $arParams['REVIEW_COMMENT_REQUIRED'] === 'N' ? 'false' : 'true';?>;
    <?if ($arParams['AJAX_POST'] == 'Y'):?>
        if (BX('edit_id').value > 0) {
            val = BX('edit_id').value;
            BX('blg-comment-' + val).id = 'blg-comment-' + val + 'old';
        } else {
            val = BX('parentId').value;
        }
        id = 'new_comment_' + val;

        if (BX('err_comment_' + val)) {
            BX('err_comment_' + val).innerHTML = '';
        }

        BX.onCustomEvent('comment-send-aspro');

        if (!prepareFormInfo(obForm, bCommentRequired)) {
            BX('post-button').disabled = false;
            return false;
        }

        BX.ajax.submitComponentForm(obForm, id);
        var comment = $(obForm).find('[name=comment]');
        setTimeout(function() {
            comment.css('color', '');
        }, 1500);

        setTimeout("waitResult('" + val + "')", 100);
    <?endif;?>

    var eventdata = {
        type: 'form_submit',
        form: $(obForm)
    };
    BX.onCustomEvent('onSubmitForm', [eventdata]);
}

function prepareFormInfo(obForm, bCommentRequired) {
    let isValid = true;

    var form = $(obForm);

    // remove hidden comment field
    form.find('input[type=hidden][name=comment]').remove();

    var comment = form.find('[name=comment]');
    var limitations = form.find('[name=limitations]');
    var virtues = form.find('[name=virtues]');
    var rating = form.find('[name=rating]');
    var edit_id = form.find('[name=edit_id]') ?
        parseInt(form.find('[name=edit_id]').val()) :
        false;
    var parent_id = form.find('[name=parentId]') ?
        parseInt(form.find('[name=parentId]').val()) :
        false;
    var oid = form.find('[name=OFFER_ID]').val();
    oid = String(oid).length ? oid : '';

    var ratingVal = rating.closest('.votes_block').data('rating');
    if (ratingVal) {
        rating.val(ratingVal);
    }

    var resultCommentText = '';
    if (virtues.val()) {
        resultCommentText += `<virtues>${virtues.val().replace(/(<([^>]+)>)/gi, "")}</virtues>\n`;
    }
    if (limitations.val()) {
        resultCommentText += `<limitations>${limitations.val().replace(/(<([^>]+)>)/gi, "")}</limitations>\n`;
    }
    if (comment.val()) {
        resultCommentText += `<comment>${comment.val().replace(/(<([^>]+)>)/gi, "")}</comment>`;
    } else if ((!bCommentRequired || edit_id) && !parent_id) {
        resultCommentText += `<uniqid>${oid}</uniqid>`;
    }

    if (bCommentRequired && !resultCommentText) {
        const $label = form.find('.form__text-field:visible:first label:not(.error)');
        const $error = form.find('.comments-error');
        if (!$error.length) {
            const error = BX.create({
                tag: 'label',
                text: "<?=GetMessage('NO_COMMENT_TEXT');?>",
                attrs: {
                    class: 'error comments-error',
                    for: 'virtues'
                },
            });
            BX.insertAfter(error, $label[0]);
        }
        isValid = false;
    }

    isValid = isValid && $(obForm).valid();

    if (!isValid) {
        return false;
    }

    window.commentOr = comment.val();

    // add hidden comment field
    if (resultCommentText) {
        let commentHidden = BX.create({
            tag: 'input',
            attrs: {
                type: 'hidden',
                name: comment.attr('name'),
                value: resultCommentText,
            },
        });

        BX.insertAfter(commentHidden, comment[0]);
    }

    return isValid;
}

function hideShowComment(url, id) {
    var siteID = '<?=SITE_ID;?>';
    var bcn = BX('blg-comment-' + id);
    BX.showWait(bcn);
    bcn.id = 'blg-comment-' + id + 'old';
    BX('err_comment_' + id).innerHTML = '';
    url += `&SITE_ID=` + siteID;
    BX.ajax.get(url, function(data) {
        var obNew = BX.processHTML(data, true);
        scripts = obNew.SCRIPT;
        BX.ajax.processScripts(scripts, true);
        var nc = BX('new_comment_' + id);
        var bc = BX('blg-comment-' + id + 'old');
        nc.style.display = "none";
        nc.innerHTML = data;
        $('.reviews-gallery-block .slider-solution').removeClass('appear-block');
        if (BX('blg-comment-' + id)) {
            bc.innerHTML = BX('blg-comment-' + id).innerHTML;
        } else {
            BX('err_comment_' + id).innerHTML = nc.innerHTML;
        }
        BX('blg-comment-' + id + 'old').id = 'blg-comment-' + id;

        BX.closeWait();
    });

    return false;
}

function deleteComment(url, id) {
    var siteID = '<?=SITE_ID;?>';
    BX.showWait(BX('blg-comment-' + id));
    url += '&SITE_ID=' + siteID;
    BX.ajax.get(url, function(data) {
        var obNew = BX.processHTML(data, true);
        scripts = obNew.SCRIPT;
        BX.ajax.processScripts(scripts, true);

        var nc = BX('new_comment_' + id);
        nc.style.display = "none";
        nc.innerHTML = data;

        if (BX('blg-com-err')) {
            BX('err_comment_' + id).innerHTML = nc.innerHTML;
        } else {
            BX('blg-comment-' + id).innerHTML = nc.innerHTML;
        }
        nc.innerHTML = '';

        BX.closeWait();
    });

    return false;
}

function updateEditForm(commentBlock, pFormCont, parent) {
    clearForm(pFormCont);
    if (parent) {
        var rating = BX.findChild(commentBlock, {
            "class": 'rate__bar'
        }, true, true);
        rating = rating === undefined ? 0 : rating[0].value;

        var stars = BX.findChild(pFormCont, {
            "class": 'item-rating rating__star-svg'
        }, true, true);
        var _this = $(stars[rating - 1]),
            index = rating,
            ratingMessage = _this.data('message');

        _this.closest('.votes_block').data('rating', index);
        var ratingInput = _this.closest('.votes_block').find('input[name=RATING]');
        if (ratingInput.length) {
            ratingInput.val(index);
        } else {
            _this.closest('.votes_block').find('input[data-sid=RATING]').val(index);
        }
        _this.closest('.votes_block').find('.rating_message').data('message', ratingMessage).text(ratingMessage);;

        stars.forEach(function(star, index) {
            if (index < rating) {
                $(star).addClass('rating__star-svg--filled');
            } else {
                $(star).removeClass('rating__star-svg--filled');
            }
        });

        var virtues = BX.findChild(commentBlock, {
            "class": 'comment-text__text VIRTUES'
        }, true);
        if (virtues !== null) {
            virtues = virtues.innerHTML.trim();
        }
        var limitations = BX.findChild(commentBlock, {
            "class": 'comment-text__text LIMITATIONS'
        }, true);
        if (limitations !== null) {
            limitations = limitations.innerHTML.trim();
        }
        var comment = BX.findChild(commentBlock, {
            "class": 'comment-text__text COMMENT'
        }, true);
        if (comment !== null) {
            comment = comment.innerHTML.trim();
        }
        var approveText = BX.findChild(commentBlock, {
            "class": 'comment-text__text APPROVE_TEXT'
        }, true);
        if (approveText !== null) {
            approveText = approveText.innerHTML.trim();
        }

        $(pFormCont).find('.form.virtues textarea').val(virtues);
        $(pFormCont).find('.form.limitations textarea').val(limitations);
        $(pFormCont).find('.form.comment textarea').val(comment);
        $(pFormCont).find('.form.approve-text input').val(approveText);
    } else {
        var comment = BX.findChild(commentBlock, {
            "class": 'comment-text__text COMMENT'
        }, true).innerHTML.trim();
        $(pFormCont).find('.form.comment textarea').val(comment);
    }


}

function clearForm(pFormCont) {
    var stars = BX.findChild(pFormCont, {
        "class": 'item-rating'
    }, true, true);
    stars.forEach(function(star, index) {
        $(stars).removeClass('rating__star-svg--filled');
    });

    var votesBlock = $(pFormCont).find('.votes_block');

    votesBlock.data('rating', '');

    if (votesBlock.find('input[name=RATING]').length) {
        votesBlock.find('input[name=RATING]').val('');
    } else {
        votesBlock.find('input[data-sid=RATING]').val('');
    }
    votesBlock.find('.rating_message').data('message', "<?=GetMessage('RATING_MESSAGE_0');?>").text("<?=GetMessage('RATING_MESSAGE_0');?>");

    $(pFormCont).find('.form.virtues textarea').val('');
    $(pFormCont).find('.form.limitations textarea').val('');
    $(pFormCont).find('.form.comment textarea').val('');
    $(pFormCont).find('input[type=file]').val('');
    $(pFormCont).find('input[name=rating]').val('');
    $(pFormCont).find("input[type=file]").uniform.update();

    typeof dropZone === 'object' && dropZone.removeAllFiles();
}

<?if ($arResult['NEED_NAV'] == 'Y'):?>
    function bcNav(page, th) {
        const container = $(th).closest('.bottom_nav');
        const pageCount = parseInt(<?=$arResult['PAGE_COUNT'];?>);
        page = parseInt(page);

        container.addClass('loadings');
        setTimeout(function() {
            for (i = 1; i <= pageCount; i++) {
                if (i == page) {
                    BX.addClass(BX('blog-comment-nav-t' + i), 'blog-comment-nav-item-sel colored_theme_bg');
                    BX.addClass(BX('blog-comment-nav-b' + i), 'blog-comment-nav-item-sel colored_theme_bg');

                    BX('blog-comment-page-' + i).classList.remove('hidden');
                } else {
                    BX.removeClass(BX('blog-comment-nav-t' + i), 'blog-comment-nav-item-sel colored_theme_bg');
                    BX.removeClass(BX('blog-comment-nav-b' + i), 'blog-comment-nav-item-sel colored_theme_bg');

                    // BX('blog-comment-page-' + i).classList.add('hidden');
                }
            }
            if (page === pageCount) {
                container.remove();
            } else {
                container.removeClass('loadings');
            }

            if (typeof window['stickySidebar'] !== 'undefined') {
                window['stickySidebar'].updateSticky();
            }
            //BX.closeWait();
        }, 300);
        return false;
    }
<?endif;?>

function blogShowFile() {
    el = BX('blog-upload-file');
    if (el.style.display != 'none') {
        BX.hide(el);
    } else {
        BX.show(el);
    }

    BX.onCustomEvent(BX('blog-comment-user-fields-UF_BLOG_COMMENT_DOC'), "BFileDLoadFormController");
}

BX.Aspro.Utils.readyDOM(() => {
    BX.Aspro.Loader.addExt('file_input', 'validate.script').then(() => {
        $('form[name="form_comment"]').validate({
            highlight: function(element) {
                $(element).parent().addClass('error');
            },
            unhighlight: function(element) {
                $(element).parent().removeClass('error');
            },
            submitHandler: function(form) {
                if ($('form[name="form_comment"]').valid()) {
                    setTimeout(function() {
                        $(form).find('button[type="submit"]').attr("disabled", "disabled");
                    }, 300);
                    var eventdata = {type: 'form_submit', form: form, form_name: 'form_comment'};
                    BX.onCustomEvent('onSubmitForm', [eventdata]);
                }
            },
            errorPlacement: function(error, element) {
                let uploader = element.closest('.uploader');
                if (uploader.length) {
                    error.insertAfter(uploader);
                } else {
                    error.insertAfter(element);
                }
            },
        });
    })
});

$('.blog-add-comment .btn').on('click', function() {
    if (!$(this).hasClass('clicked')) {
        showComment('0');
        $(this).addClass('clicked');
    } else {
        $('#form_comment_0').slideToggle();
    }
});

<?if (isset($arParams['REVIEW_COMMENT_REQUIRED']) && $arParams['REVIEW_COMMENT_REQUIRED'] === 'Y'):?>
    $(document).on('paste, change, keyup', '.form.blog-comment-fields textarea', function() {
        let value = $(this).val();
        if (value.length) {
            $(this).closest('.blog-comment__form').find('.comments-error').remove();
        }
    });
<?endif;?>

<?if (!$arResult['CanUserComment']):?>
    $('.show-comment.btn').on('click', function() {
        $('.blog-note-error').remove();
        $('<div class="alert alert-danger blog-note-box blog-note-error"><div class="blog-error-text"><?=GetMessage('BPC_ERROR_NO_COMMENT_PERM');?></div></div>').insertBefore('#reviews_sort_continer');
    });
<?endif;?>

BX.Aspro?.Comments?.showCurrentOfferInfo();
</script>
