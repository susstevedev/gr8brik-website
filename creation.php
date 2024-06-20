<?php

session_start();

$p = "com/comments/" . urldecode(basename($_SERVER['QUERY_STRING']));

if(!file_exists(urldecode($_SERVER['QUERY_STRING']))){

    header('Location: index.php?dc');

    die;

}

if(isset($_POST['report'])){

    $model = basename($_SERVER['QUERY_STRING']);

    $reportxml = new SimpleXMLElement ('<creation></creation>');

    $reportxml->addChild('name', $model);

    $reportxml->asXml('../reported/' . '(creation)' . $model . '.xml');

    echo('Creation reported. View reported creations and users <a href="../reported/index.php">here</a>.');
            
}

if($_POST['comment']) {

    if (!isset($_SESSION['username']) || $_SESSION['username'] == '') {

        $u = 'Anonymous User';

        $a = 'An Anonymous User';

    } else {

        $xml = new SimpleXMLElement('acc/users/' . $_SESSION['username'] . '.xml', 0, true);

        $u = $_SESSION['username'];

        $a = "@" . $xml->username;

    }

    $c = htmlspecialchars($_POST['commentbox']);

    $pfp = 'acc/users/pfps/' . $u . '..jpg';

    $id = rand(10, 10000);

    $handle = fopen($p, "a");

    fwrite($handle, "<table id='" . $id . "' class='w3-table-all w3-card-4' style='color:black;'><td><a href='profile.php?" . $u . "'><img src='" . $pfp . "' width='50px' height='50px' style='border-radius:50px'>" . $a . "</a> <b>said:</b><b style='float:right'><a id='" . $id . "' href='#" . $id . "'>" . date('F d Y H:i:s') . "</a></b><br /><p>" . $c . "</p></td></table><br />");

    fclose($handle);

}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo urldecode($_SERVER['QUERY_STRING']) ?></title>
    <link rel="stylesheet" href="../w3.css">
    <link rel="stylesheet" href="../theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <meta charset="UTF-8">
    <meta name="author" content="sussteve226">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
<body class="w3-light-blue w3-container">

<script> 
    var $buoop = {required:{e:-4,f:-3,o:-3,s:-1,c:-3},insecure:true,api:2023.10 }; 
    function $buo_f(){ 
    var e = document.createElement("script"); 
    e.src = "//browser-update.org/update.min.js"; 
    document.body.appendChild(e);
    };
    try {document.addEventListener("DOMContentLoaded", $buo_f,false)}
    catch(e){window.attachEvent("onload", $buo_f)}
</script>

<?php include('navbar.php'); ?>
<br />

    <h1><?php urldecode($_SERVER['QUERY_STRING']) ?></h1>

    <div class='w3-container'>
        <p>Want to build your own model? <a href='../modeler.php' class='w3-btn w3-round'>MODELER</a></p>
    </div>

    <?php
        $file = urldecode($_SERVER['QUERY_STRING']);
    ?>

    <form method="post" action=""><input type="submit" value="REPORT" name="report" class="w3-btn w3-round w3-red w3-hover-white" />
        <a href="<?PHP echo $file ?>" class='w3-btn w3-round' download>DOWNLOAD</a>
        <br /><b>EMBED:<input type="text" class="w3-input" value=<?php echo "http://gr8brik.rf.gd/embed?cre/" . basename($_SERVER['QUERY_STRING']) ?> style="width:30%"></b>
    </form>

    <br /><form method='post' action=''><textarea name='commentbox' placeholder='Add a comment...' rows='4' cols='50'></textarea>
    <br /><input type='submit' value='COMMENT' name='comment' class='w3-btn w3-round' /></form>

        <?php
            if(file_exists('com/comments/' . urldecode(basename($_SERVER['QUERY_STRING'])))){
                include('com/comments/' . urldecode(basename($_SERVER['QUERY_STRING'])));
            } else {
                echo '<h2>No comments</h2><br />';
            }
        ?>

        <?php include('linkbar.php'); ?>

</div>

</body>
</html>