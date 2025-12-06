<?php
    $head = ob_get_clean();

    $head = preg_replace_callback('/<title>(.*?)<\/title>/i', function ($match) {
        $title = trim($match[1]);
        return "<title>{$title} | Gr8Brik</title>";
    }, $head);

    echo $head;
?>

<link rel="canonical" href="https://<?php echo htmlspecialchars($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" />
<link rel="manifest" href="https://susstevedev.github.io/gr8brik/manifest.json">

<?php
    if(isset($_SESSION['settings'])) {
        echo "<style>body, html { font-size: " . $_SESSION['setting_font'] . " !important } #navbar.w3-large, h4, h5 { font-size: " . $_SESSION['setting_font'] . " !important } </style>";
    }
?>
<link rel="stylesheet" href="/lib/w3_new.css">
<link rel="stylesheet" href="/lib/theme.css?v=10-11-2025">
<link rel="stylesheet" href="/lib/xpull.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">

<script type="text/javascript" src="/lib/jquery-3.7.1.min.js"></script>
<script type="text/javascript" src="/lib/jquery-migrate-1.4.1.min.js"></script>
<script type="text/javascript" src="/lib/jquery.cookie-1.4.1.min.js"></script>
<script type="text/javascript" src="/lib/jquery.ihavecookies.min.js"></script>
<script type="text/javascript" src="/lib/xpull.js"></script>
<script type="text/javascript" src="/lib/main.js?v=11-04-25"></script>
<script type="module" async defer src="https://cdn.jsdelivr.net/npm/altcha/dist/altcha.min.js?v=<?php echo time(); ?>"></script>
<script type="text/javascript" src="https://unpkg.com/twemoji@latest/dist/twemoji.min.js?v=<?php echo time(); ?>" crossorigin="anonymous"></script>

<meta charset="UTF-8">
<meta name="description" content="Gr8Brik is a block building browser game written in ThreeJS and uses the LDraw parts library, made for modern web browsers. No download needed.">
<meta name="keywords" content="online block builder, gr8brik, online lego modeler, lego digital designer, ldd, lego modeler, lego modeler online">
<meta name="author" content="The Gr8Brik Team">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">

<link rel="apple-touch-icon" href="/img/logo.jpg" />
<meta name="apple-mobile-web-app-status-bar" content="#f1f1f1" />
<meta name="theme-color" content="#f1f1f1" />