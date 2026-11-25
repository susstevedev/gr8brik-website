<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/config.php';

$__url = [
    'protocol' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http',
    'host' => $_SERVER['HTTP_HOST'],
    'path' => strtok($_SERVER['REQUEST_URI'] ?? '/', '?'),
];

$pageURL = $__url['protocol'] . '://' . $__url['host'] . $__url['path'];

$__page = [
    'title' => $pageTitle ?? '[unknown page]',
    'description' => $pageDesc ?? null,
    'canonical' => $pageCanonical ?? $pageURL
];

$og = array_merge([
    'title' => $__page['title'],
    'description' => $__page['description'],
    'url' => $__page['canonical'],
], $pageOg ?? []);
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <title><?= "{$__page['title']} - Gr8Brik (beta)" ?></title>

        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta name="robots" content="index, follow" />

        <meta name="description" content="<?= $__page['description'] ?>" />
        <!-- holy fucking tag spam vro -->
        <meta name="keywords" content="online block builder, gr8brik, online lego modeler, lego digital designer, ldd, lego modeler, lego modeler online, account, awesome, best, beta, block, blog, brick, bricks, browser, browsers, building, builds, castle, cloud, community, conditions, configurations, create, creation, creations, no download, featured, file format, forum, free, greenery, guidelines, instructions, json, knob, ldraw, lego, library, mini, minifigure, model, modeler, modern, parts, policy, privacy, public, rules, sceenery, service, sites, terms, theme, threejs, tools, upload, view">
        <meta name="author" content="susstevedev" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no" />

        <link rel="canonical" href="<?php echo $__page['canonical'] ?>" />
        <link rel="manifest" href="https://susstevedev.github.io/gr8brik/manifest.json" />

        <link rel="stylesheet" href="/lib/w3_new.css">
        <link rel="stylesheet" href="/lib/theme.css?v=04-15-26">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">

        <script type="text/javascript" src="/lib/jquery-3.7.1.min.js"></script>
        <script type="text/javascript" src="/lib/jquery-migrate-1.4.1.min.js"></script>
        <script type="text/javascript" src="/lib/jquery.cookie-1.4.1.min.js"></script>
        <script type="text/javascript" src="/lib/jquery.ihavecookies.min.js"></script>
        <script type="text/javascript" src="/lib/preload.js?v=08-05-26"></script>
        <script type="text/javascript" src="/lib/main.js?v=08-05-26"></script>
        <script type="text/javascript" src="https://unpkg.com/twemoji@latest/dist/twemoji.min.js" crossorigin="anonymous"></script>

        <meta property="og:title" content="<?= $og['title'] ?>">
        <meta property="og:description" content="<?= $og['description'] ?>">
        <meta property="og:url" content="<?= $og['url'] ?>">
        <meta property="og:site_name" content="Gr8Brik">

        <meta name="twitter:title" content="<?= $og['title'] ?>">
        <meta name="twitter:description" content="<?= $og['description'] ?>">
        <meta name="twitter:url" content="<?= $og['url'] ?>">
        <meta name="twitter:card" content="<?= $og['description'] ?>">

        <link rel="apple-touch-icon" href="/img/logo.jpg" />
        <meta name="apple-mobile-web-app-status-bar" content="#f1f1f1" />
        <meta name="theme-color" content="#f1f1f1" />
    </head>
    <body class="w3-light-blue w3-container">