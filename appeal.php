<?php
session_start();
if(isset($_POST['appeal'])){

        $username = $_POST['username'];

        $email = $_POST['email'];

        $handle = $_POST['handle'];

        $xml = new SimpleXMLElement ('<user></user>');

        $xml->addChild('username', $username);

        $xml->addChild('email', $email);

        $xml->addChild('handle', $handle);

        $xml->addChild('type', 'appeal');

        $xml->asXml('com/report/' . '(user)' . $username . '.xml');

        echo('Appealed!');
            
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo basename($_SERVER['QUERY_STRING']) ?></title>
    <link rel="stylesheet" href="../w3.css">
    <link rel="stylesheet" href="../theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <meta charset="UTF-8">
    <meta name="description" content="Gr8brik is a block building browser game. No download required">
    <meta name="keywords" content="legos, online block builder, gr8brik, online lego modeler, barbies-legos8885 balteam, lego digital designer, churts, anti-coppa, anti-kosa, churtsontime, sussteve226, manofmenx">
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

<?php include('navbar.php') ?>

        <div class='w3-container'>
                <p>Want to build your own model? <a href='../modeler.php' class='w3-btn w3-round'>MODELER</a></p>
        </div>
        <br />
        <center>
            <h1>Appeal</h1>
            <form method="post" action="">
                <div class="w3-row-padding">
                    <p><input type="text" class="w3-input w3-border w3-hover-green w3-third" placeholder="Your username" name="username" /></p>
                    <p><input type="text" class="w3-input w3-border w3-hover-green w3-third" placeholder="Your handle" name="handle" /></p>
                    <p><input type="text" class="w3-input w3-border w3-hover-green w3-third" placeholder="Your email" name="email" /></p>
                </div>
                <p><textarea class="w3-border w3-hover-red" name="appealbox" placeholder="What did you do wrong and why should we reinstate you're account?" rows="4" cols="50"></textarea></p>
                <p><input type="submit" value="APPEAL" name="appeal" class="w3-btn w3-round" /></p>
            </form>
            <a href="report">Report</a>
        </center>
        <br />
        <br />
        <br />

    <?php include('linkbar.php') ?>


</body>
</html>