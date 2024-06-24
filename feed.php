<?php
session_start();
if(!file_exists('acc/users/' . $_SESSION['username'] . '.xml')){

    header('Location: acc/login.php');

    die;

}
$xml = new SimpleXMLElement('acc/users/' . $_SESSION['username'] . '.xml', 0, true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>@<?php echo $xml->username ?>'s GR8BRIK feed</title>
    <link rel="stylesheet" href="w3.css">
    <link rel="stylesheet" href="theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <meta charset="UTF-8">
    <meta name="description" content="Gr8brik is a block building browser game. No download required">
    <meta name="keywords" content="legos, online block builder, gr8brik, online lego modeler, barbies-legos8885 balteam, lego digital designer, churts, anti-coppa, anti-kosa, churtsontime, sussteve226, manofmenx">
    <meta name="author" content="sussteve226">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="w3-light-blue w3-container">

<?php 

    include('navbar.php');
    
?>
        <div class="w3-container">
                <p>Want to build your own model? <a href="modeler.php" class="w3-btn w3-round">MODELER</a></p>
        </div>

        <div class="w3-green w3-card-8 w3-center">
            <h1>Welcome to your feed, <?php echo $_SESSION['username']; ?></h1>
            <img src=<?php echo 'acc/users/pfps/' . $_SESSION['username'] . '..jpg' ?> style='width: 250px; height: 250px; border-radius: 50%;cursor:zoom-in' class="w3-hover-shadow" onclick="document.getElementById('im').style.display='block'" />
            <h3>or @<?php echo $xml->username ?><a href="acc/index.php#handle"><i class="fa fa-pencil" aria-hidden="true"></i></a><br />
            <a href="/acc/index.php" class="w3-btn w3-large w3-white w3-hover-green">MANAGE <i class="fa fa-wrench" aria-hidden="true"></i>
</a>
            <a href=profile?<?php echo $_SESSION['username']; ?> class="w3-btn w3-large w3-white w3-hover-green">PROFILE <li class="fa fa-user" aria-hidden="true"></li></a>
        </div>

        <h3 class="w3-center">Here are some things you might be interested in</h3>

        <table class="w3-table-all" style="color:black;">
        
            <?php
                $i = 0;
                foreach (glob("cre/*json") as $_FF) {
                    echo "<div class='w3-container w3-left'>";
                    echo "<a href='creation.php?" . $_FF . "'><img src='img/blured_model.jpg' width='320' height='240' class='w3-hover-opacity w3-round'></a><br />";
                    echo "<b>" . $_FF . "</b><br /></div>";
                    if (++$i == 6) break;
                };

            ?>
            <div class="w3-half w3-center"><hr /><h3>Like these?</h3><a href='list.php' class='w3-btn w3-round'>MORE</a><br /><br /><br /></div>
            
        </table>

    <?php include('linkbar.php'); ?>


</body>
</html>