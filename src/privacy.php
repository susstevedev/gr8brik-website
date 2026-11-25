<?php
    require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/const/Autoloader.php';

    $pageTitle = "Privacy Policy";
    $pageDesc = 'Read the privacy policy of Gr8Brik';
    $pageCanonical = appSpace->__urls->app . '/privacy';

    require_once $_SERVER['DOCUMENT_ROOT'] . '/const/includes/header.php';
    include 'navbar.php';
?>
<br /><br /><div class="w3-center">

<?php
$filen = $_SERVER['DOCUMENT_ROOT'] . "/lib/static/legal/privacy.md";

if (file_exists($filen)) {
    $content = file_get_contents($filen);

    $vars = [
        ':date'  => "Nov 25, 2026",
        ':url' => "http://gr8brik.rf.gd",
        ':url_acc' => "http://gr8brik.rf.gd/acc/",
        ':mailto' => "mailto:" . DB_MAIL
    ];

    echo Slimdown::render(strtr($content, $vars));
} else {
    echo "Privacy policy file not found.";
}
?>

</div><br /><br />
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/const/includes/linkbar.php'; ?>