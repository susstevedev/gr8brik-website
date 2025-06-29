<?php
    $head = ob_get_clean();

    $head = preg_replace_callback('/<title>(.*?)<\/title>/i', function ($match) {
        $title = trim($match[1]);
        return "<title>{$title} | GR8BRIK</title>";
    }, $head);

    echo $head;
?>

<link rel="canonical" href="https://gr8brik.rf.gd<?php echo htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8'); ?>" />
<link rel="manifest" href="https://susstevedev.github.io/gr8brik/manifest.json">

<?php
    if (!isset($_COOKIE['new_ui']) || $_COOKIE['new_ui'] === (string)2) {
        $variant = (string)mt_rand(0, 1);
        setcookie('new_ui', $variant, time() + (86400 * 30), "/");
        $_COOKIE['new_ui'] = $variant;
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }

    if($_COOKIE['new_ui'] === (string)1 || $_COOKIE['new_ui'] === (string)2) {
        echo '<!-- user is enrolled in new ui --><link rel="stylesheet" href="/lib/w3_new.css">';
    } else {
        echo '<!-- user is not enrolled in new ui --><link rel="stylesheet" href="/lib/w3.css">';
    }
?>
<link rel="stylesheet" href="/lib/theme.css?v=4-26-2025">
<link rel="stylesheet" href="/lib/xpull.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">

<script type="text/javascript" src="/lib/jquery-3.7.1.min.js"></script>
<script type="text/javascript" src="/lib/jquery-migrate-1.4.1.min.js"></script>
<script type="text/javascript" src="/lib/jquery.cookie-1.4.1.min.js"></script>
<script type="text/javascript" src="/lib/jquery.ihavecookies.min.js"></script>
<script type="text/javascript" src="/lib/xpull.js"></script>
<script type="text/javascript" src="/lib/main.js?v=4-26-2025"></script>
<script type="module" async defer src="https://cdn.jsdelivr.net/npm/altcha/dist/altcha.min.js"></script>
<script type="text/javascript" src="https://unpkg.com/twemoji@latest/dist/twemoji.min.js?v=<?php echo time(); ?>" crossorigin="anonymous"></script>

<meta charset="UTF-8">
<meta name="description" content="Gr8brik is a block building browser game. No download required">
<meta name="keywords" content="legos, online block builder, gr8brik, online lego modeler, barbies-legos8885 balteam, lego digital designer, churts, anti-coppa, anti-kosa, churtsontime, sussteve226, manofmenx">
<meta name="author" content="The Gr8brik Team">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">

<link rel="apple-touch-icon" href="/img/logo.jpg" />
<meta name="apple-mobile-web-app-status-bar" content="#f1f1f1" />
<meta name="theme-color" content="#f1f1f1" />