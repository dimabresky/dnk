<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

$APPLICATION->RestartBuffer();

$application = Bitrix\Main\Application::getInstance();
$request = $application->getContext()->getRequest();
$post = $request->getPostList();
$session = $application->getSession();

if ($request['sort']) {
    [
        $sort,
        $order
    ] = explode(':', $request['sort']);

    $session->set('REVIEW_SORT_PROP', $sort);
    $session->set('REVIEW_SORT_ORDER', $order);
}

if ($post && isset($post['reviews_filter'])) {
    $session->set('REVIEW_FILTER', $post['filter']);
}

$ajaxData = [
    'IBLOCK_ID' => $arParams['IBLOCK_ID'],
    'ELEMENT_ID' => $arElement['ID'],
    'SITE_ID' => SITE_ID,
];

if (isset($request['OFFER_ID'])) {
    $ajaxData['OFFER_ID'] = $request['OFFER_ID'];
}

if (isset($request['reviewsVariantMode'])) {
    $ajaxData['reviewsVariantMode'] = $request['reviewsVariantMode'] !== 'offer' ? 'all' : 'offer';
}

// remove external links
$url = preg_replace('/^(https?:)?\/\//', '', trim($request['ajax_url'] ?: ''));
?>
<script>
	$.ajax({
		url: <?var_export($url); ?> + '?' + <?var_export(bitrix_sessid_get()); ?>,
		type: 'post',
		data:  <?= CUtil::PhpToJSObject($ajaxData); ?>,
		success: function(html){
			$(<?var_export('#'.$request['containerId']); ?>).html(
				$(html).find(<?var_export('#'.$request['containerId']); ?>).html()
			);
		}
	});
</script>
