<?

use Awz\CookiesSett\App as CookieApp;

$metricsFile = $_SERVER['DOCUMENT_ROOT'] . '/include/header/google_metrics.php';
if (\Bitrix\Main\Loader::includeModule('awz.cookiessett')) {

    $app = CookieApp::getInstance();
    if ($app->isEmpty() || $app->check(CookieApp::MARKET_EXT)) {
        //разрешены маркетинговые
        include $metricsFile;
    }
} else {
    include $metricsFile;
}
?>
