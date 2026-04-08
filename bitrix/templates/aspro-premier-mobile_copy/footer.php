<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?use \Aspro\Premier\Mobile\General as MSolution?>
        <?if(!$isIndex):?>
            <?TSolution::checkRestartBuffer();?>
            <?TSolution::get_banners_position('CONTENT_BOTTOM');?>
            <?if($APPLICATION->GetProperty("FULLWIDTH")!=='Y'):?>
                </div>
            <?endif;?>
        <?else:?>
            <?TSolution::ShowPageType('indexblocks');?>
        <?endif;?>
    </main>
</layout>
<?TSolution::showPageType('footer');?>
<?MSolution::showPageTypeFromSolution('footer');?>
	<?@include_once(str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'].'/'.SITE_DIR.'include/footer/bottom_footer.php'));?>
<script>
        (function(w,d,u){
                var s=d.createElement('script');s.async=true;s.src=u+'?'+(Date.now()/60000|0);
                var h=d.getElementsByTagName('script')[0];h.parentNode.insertBefore(s,h);
        })(window,document,'https://cdn-ru.bitrix24.by/b30800294/crm/site_button/loader_2_msdl8k.js');
</script>
</body>
</html>