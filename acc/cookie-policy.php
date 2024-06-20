<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Cookie Policy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <link rel="stylesheet" href="../w3.css">
    <link rel="stylesheet" href="../theme.css">
    <meta charset="UTF-8">
    <meta name="author" content="sussteve226">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .js_enabled #cookie-message {
            display: none;
        }
    </style>
</head>
<body class="w3-light-blue">
<script>
    // Detect JS support
    document.body.className = document.body.className + " js_enabled";
</script>

    <?php include('../navbar.php') ?>

    <div class="w3-container">

        <h1>Cookie Policy</h1>

<h2>Cookies</h2>

<p>A cookie is a small text file which is automatically saved on your computer when you visit our website and is used to
    help track activity across a website. GR8BRIK uses cookies on our website for the following purposes:</p>

<h3>Google Analytics</h3>

<p>We use Google Analytics to help provide anonymous web analytics for our website.</p>

<table class="w3-table-all w3-card-8" border="0">
    <thead>
    <tr>
        <th>Name</th>
        <th>Description</th>
        <th>Expiration</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td>_ga</td>
        <td>Used to distinguish users</td>
        <td>2 years</td>
    </tr>
    <tr>
        <td>_gat</td>
        <td>Used to throttle request rate</td>
        <td>30 minutes</td>
    </tr>
    </tbody>
</table>

<h3>Website</h3>

<p>We use cookies to help increase the user experience of this website.</p>

<table class="w3-table-all w3-card-8" border="0">
    <thead>
    <tr>
        <th>Name</th>
        <th>Description</th>
        <th>Expiration</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td>seen-cookie-message</td>
        <td>Used to indicate you have seen the cookie message</td>
        <td>30 days</td>
    </tr>
    <tr>
        <td>PHPSESSIONID</td>
        <td>Used to indicate if your logged in or not</td>
        <td>One hour (unless logout or ban)</td>
    </tr>
    </tbody>
</table>

<h2>Removing cookies</h2>
<p>If you have any concerns about cookies, or want to remove cookies from your computer, please visit <a href="http://www.allaboutcookies.org/">All About Cookies</a> which explains how to do this.</p>

<h2>Collection of personal information</h2>

<p>If you contact us via a form on our website your personal information will only be used for us to contact you.
    No personal data you send us will be disclosed to a third party without your permission.</p>

<p>If you wish us to remove any personal details we hold about you, please email us at <i class="fa fa-envelope">
</i><a href="mailto:evanrutledge226@gmail.com">evanrutledge226[at]gmail[dot]com</a></p>

    </div>

    <?php include('../linkbar.php') ?>

</div>
<script src="../cookie-message.js"></script>

</body>
</html>