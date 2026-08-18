<style>
    .dnk__seo-area {
        padding-bottom: 5rem;
    }
</style>
<div class="maxwidth-theme dnk__seo-area">
    <h1 class="dnk__seo-h1">
        <?php
        $APPLICATION->IncludeFile(
                SITE_DIR . "include/mainpage/components/seo/h1.php",
                [],
                [
                    "MODE" => "html",
                    "NAME" => "SEO заголовок",
                ]
        );
        ?>
    </h1>
    <div class="dnk__seo-text">
        <?php
        $APPLICATION->IncludeFile(
                SITE_DIR . "include/mainpage/components/seo/text.php",
                [],
                [
                    "MODE" => "html",
                    "NAME" => "SEO текст",
                ]
        );
        ?>
    </div>
</div>