<?php
    require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/const/Autoloader.php';

    $pageTitle = "Terms and Conditions";
    $pageDesc = 'Read the terms and conditions of Gr8Brik';
    $pageCanonical = appSpace->__urls->app . '/terms';

    require_once $_SERVER['DOCUMENT_ROOT'] . '/const/includes/header.php';
    include 'navbar.php';
?>
<br /><br /><div class="w3-center">

<?php
$filen = $_SERVER['DOCUMENT_ROOT'] . "/lib/static/legal/terms.md";

if (file_exists($filen)) {
    $content = file_get_contents($filen);

    $vars = [
        ':date'  => "Nov 25, 2026",
        ':url' => "http://gr8brik.rf.gd",
    ];

    echo Slimdown::render(strtr($content, $vars));
} else {
    echo "Terms file not found.";
}
?>

</div><br /><br />
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/const/includes/linkbar.php'; ?>