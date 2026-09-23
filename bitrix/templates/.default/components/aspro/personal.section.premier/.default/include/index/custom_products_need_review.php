<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Dnk\PhpInterface\Utils;

$products = Utils::getProductsAwaitingReview((int)($arResult['USER_ID'] ?? 0));
if ($products === []) {
	return;
}

TSolution\Extensions::init(['swiper']);

$count = count($products);
$arOptions = [
	'preloadImages' => false,
	'lazy' => false,
	'keyboard' => true,
	'init' => false,
	'loop' => false,
	'countSlides' => $count,
	'slidesPerView' => 'auto',
	'freeMode' => [
		'enabled' => true,
		'momentum' => true,
		'sticky' => true,
	],
	'spaceBetween' => 12,
	'pagination' => false,
	'watchSlidesProgress' => true,
	'breakpoints' => [
		601 => [
			'slidesPerView' => 'auto',
			'spaceBetween' => 24,
		],
	],
];
?>
<div class="main-block__title-wrapper mt mt--40">
	<h3 class="main-block__title switcher-title">
		<div class="main-block__title-inner">
			<span><?=Loc::getMessage('SPS_MAIN_BLOCK_TITLE_PRODUCTS_NEED_REVIEW')?></span>
			<span class="main-block__title-count bordered rounded-x font_14"><?=$count?></span>
		</div>
	</h3>
</div>

<div class="products-need-review--slider__wrap swiper-nav-offset">
	<div class="swiper slider-solution mobile-offset mobile-offset--right products-need-review--slider outer-rounded-x" data-plugin-options='<?=json_encode($arOptions)?>'>
		<div class="swiper-wrapper">
			<?foreach ($products as $product):?>
				<?
				$name = (string)$product['name'];
				$url = (string)$product['url'];
				$picture = (string)$product['picture'];
				?>
				<div class="swiper-slide products-need-review__slide">
					<a class="products-need-review__card outer-rounded-x bordered shadow-hovered shadow-hovered-f600 shadow-no-border-hovered" href="<?=htmlspecialcharsbx($url)?>" title="<?=htmlspecialcharsbx($name)?>">
						<span class="products-need-review__image">
							<?if ($picture !== ''):?>
								<img src="<?=htmlspecialcharsbx($picture)?>" alt="<?=htmlspecialcharsbx($name)?>">
							<?endif;?>
						</span>
						<span class="products-need-review__name switcher-title font_clamp--16-14 color_dark"><?=htmlspecialcharsbx($name)?></span>
					</a>
				</div>
			<?endforeach;?>
		</div>
	</div>

	<?if ($count > 1):?>
		<?TSolution\Functions::showBlockHtml([
			'FILE' => 'ui/slider-navigation.php',
			'PARAMS' => [
				'CLASSES' => 'slider-nav slider-nav--shadow',
			],
		]);?>
	<?endif;?>
</div>
<script>
	document.addEventListener('DOMContentLoaded', function () {
		if (typeof initSwiperSlider === 'function') {
			initSwiperSlider();
		}
	});
</script>
<?
unset($products, $count, $arOptions, $product, $name, $url, $picture);
