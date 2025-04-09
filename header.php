<?php
    $head = ob_get_clean();

    $head = preg_replace_callback('/<title>(.*?)<\/title>/i', function ($match) {
        $title = trim($match[1]);
        return "<title>{$title} | Gr8brik</title>";
    }, $head);

    echo $head;
?>

<link rel="canonical" href="http://www.gr8brik.rf.gd<?php echo htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8'); ?>" />
<link rel="manifest" href="https://susstevedev.github.io/gr8brik/manifest.json">

<link rel="stylesheet" href="/lib/w3.css">
<link rel="stylesheet" href="/lib/theme.css">
<link rel="stylesheet" href="/lib/xpull.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
<link rel='stylesheet' href='https://fonts.googleapis.com/css?family=Inter'>

<script  type="text/javascript" src="/lib/jquery-3.7.1.min.js"></script>
<script type="text/javascript" src="/lib/jquery-migrate-1.4.1.min.js"></script>
<script type="text/javascript" src="/lib/jquery.cookie-1.4.1.min.js"></script>
<script type="text/javascript" src="/lib/jquery.ihavecookies.min.js"></script>
<script type="text/javascript" src="/lib/xpull.js"></script>
<script type="text/javascript" src="/lib/main.js?v=2"></script>
<script async defer src="https://cdn.jsdelivr.net/npm/altcha/dist/altcha.min.js" type="module"></script>
<script src="https://unpkg.com/twemoji@latest/dist/twemoji.min.js" crossorigin="anonymous"></script>

<meta charset="UTF-8">
<meta name="description" content="Gr8brik is a block building browser game. No download required">
<meta name="keywords" content="legos, online block builder, gr8brik, online lego modeler, barbies-legos8885 balteam, lego digital designer, churts, anti-coppa, anti-kosa, churtsontime, sussteve226, manofmenx">
<meta name="author" content="The Gr8brik Team">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">

<link rel="apple-touch-icon" href="/img/logo.jpg" />
<meta name="apple-mobile-web-app-status-bar" content="#f1f1f1" />
<meta name="theme-color" content="#f1f1f1" />