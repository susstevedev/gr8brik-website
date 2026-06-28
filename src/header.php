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

<link rel="stylesheet" href="/lib/w3_new.css">
<link rel="stylesheet" href="/lib/theme.css?v=04-15-26">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">

<script type="text/javascript" src="/lib/jquery-3.7.1.min.js"></script>
<script type="text/javascript" src="/lib/jquery-migrate-1.4.1.min.js"></script>
<script type="text/javascript" src="/lib/jquery.cookie-1.4.1.min.js"></script>
<script type="text/javascript" src="/lib/jquery.ihavecookies.min.js"></script>
<script type="text/javascript" src="/lib/main.js?v=04-15-26"></script>
<!-- <script type="module" async defer src="https://cdn.jsdelivr.net/npm/altcha/dist/altcha.min.js?v=<?php echo time(); ?>"></script> -->
<script type="text/javascript" src="https://unpkg.com/twemoji@latest/dist/twemoji.min.js?v=<?php echo time(); ?>" crossorigin="anonymous"></script>

<meta charset="UTF-8">
<!-- <meta name="description" content="GR8BRIK is an online lego builder written in ThreeJS and uses the LDraw parts library, made for modern web browsers. No download needed. The GR8BRIK community is a forum of Lego lovers like you that use GR8BRIK and other tools. You can view posts of other and post on there. On GR8BRIK you can scroll view others public creation's for free, and download them in the GR8BRIK .gr8 or .json file format. GR8BRIK's modeler may be one of the best part of our service; where you can model your own creation using Ldraw bricks."> -->
<meta name="description" content="LDraw and WebGL based LEGO building webapp with social features in your web browser.">
<meta name="keywords" content="online block builder, gr8brik, online lego modeler, lego digital designer, ldd, lego modeler, lego modeler online, account, awesome, best, beta, block, blog, brick, bricks, browser, browsers, building, builds, castle, cloud, community, conditions, configurations, create, creation, creations, no download, featured, file format, forum, free, greenery, guidelines, instructions, json, knob, ldraw, lego, library, mini, minifigure, model, modeler, modern, parts, policy, privacy, public, rules, sceenery, service, sites, terms, theme, threejs, tools, upload, view">
<meta name="author" content="The GR8BRIK Team">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">

<meta property="og:title" content="GR8BRIK">
<meta property="og:description" content="GR8BRIK is an online lego builder written in ThreeJS and uses the LDraw parts library, made for modern web browsers.">
<meta property="og:image" content="/img/logo.jpg">
<meta property="og:url" content="https://<?php echo htmlspecialchars($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>">

<meta name="twitter:title" content="GR8BRIK">
<meta name="twitter:description" content="GR8BRIK is an online lego builder written in ThreeJS and uses the LDraw parts library, made for modern web browsers.">
<meta name="twitter:url" content="https://<?php echo htmlspecialchars($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>">
<meta name="twitter:card" content="GR8BRIK is an online lego builder written in ThreeJS and uses the LDraw parts library, made for modern web browsers.">

<link rel="apple-touch-icon" href="/img/logo.jpg" />
<meta name="apple-mobile-web-app-status-bar" content="#f1f1f1" />
<meta name="theme-color" content="#f1f1f1" />