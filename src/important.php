<!DOCTYPE html>
<?php
    require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';

?>
<head>
    <title>Privacy Policy</title>
    <?php include 'header.php' ?>
</head>
<body class="w3-light-blue w3-container">
<?php include('navbar.php') ?>
<center><br /><br />
    <style>
        #very-important-modal {
            display: none !important;
        }
        #aprilfools {
            display: none;
        }
    </style>
    <script>
        setTimeout(function() {
            const element = document.getElementById("aprilfools");
            element.style.display = "block";
        }, 10000);
    </script>
    <h2>Important Notice</h2>
    <p>Gr8brik is being sold to XAI, a small company of only a billion dollars, made by Elon Musk.</p>
    <p>This means inorder to use Gr8brik services, you will need an X account.</p>
    <p>Now this may be very shocking, but...</p>
    <h1 id="aprilfools" class="w3-animate-zoom">April fools!!!</h1>
</center><br /><br />
<?php include('linkbar.php') ?>
</body>
</html>