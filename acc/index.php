<?php
session_start();
if(!file_exists('users/' . $_SESSION['username'] . '.xml')){
    header('Location: login.php');
}
$xml = new SimpleXMLElement('users/' . $_SESSION['username'] . '.xml', 0, true);
if(isset($_POST['upload'])){
    $target_dir = "../cre/";
    $target_file = $target_dir . '(' . $_SESSION['username'] . ')' . basename($_FILES['fileToUpload']['name']);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

    // Check if file already exists
    if (file_exists($target_file)) {
        echo "Sorry, file already exists.";
    $uploadOk = 0;
    }

    // Check file size
    if ($_FILES["fileToUpload"]["size"] > 5242880) {
        echo "Sorry, your file is too large.";
    $uploadOk = 0;
    }

    // Allow certain file formats
    if($imageFileType != "json" && $imageFileType != "js" ) {
        echo "Sorry, only Json and Js files are allowed.";
    $uploadOk = 0;
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        echo "Sorry, your file was not uploaded.";
    // if everything is ok, try to upload file
    } else {
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
        echo "The model has been uploaded. View it <a href='http://gr8brik.rf.gd/cre/view.php?". '(' . $_SESSION['username'] . ')' . basename($_FILES['fileToUpload']['name']) ."'>here</a>";
    } else {
        echo "Sorry, there was an error uploading your file.";
    }
    }

}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Account - GR8BRIK</title>
    <link rel="stylesheet" href="../w3.css">
    <link rel="stylesheet" href="../theme.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
</head>
<body class="w3-light-blue w3-container">

    <?php include('../navbar.php') ?>

    <div class="w3-center">
        <div class="w3-green w3-card-4">
            <h3>Welcome,</h3>
            <?php 
                if($xml->username == ''){
                    echo '<b style="color:red;">There was an error loading your username</b>';
                }
            ?>
            <h1><?php echo $_SESSION['username']; ?></h1>
            <h3>or @<?php echo $xml->username ?><a href="#handle"><i class="fa fa-pencil" aria-hidden="true"></i></a></h3>
            <h5><?php echo $xml->email ?><a href="#email"><i class="fa fa-pencil" aria-hidden="true"></i></a></h5>
            <h5><?php echo $xml->age ?><a href="#age"><i class="fa fa-pencil" aria-hidden="true"></i></a></h5>

            <a href="<?php echo '../profile.php?' . $_SESSION['username'] ?>" class="w3-btn w3-large w3-white w3-hover-green">PROFILE <li class="fa fa-user" aria-hidden="true"></li></a>
        </div>

        <center>

        <h2 id="upload">Upload model</h2>

        <form action="" method="post" enctype="multipart/form-data">

            <input type="file" name="fileToUpload" id="fileToUpload" class="w3-input" style="width:30%">

            <input type="submit" name="upload" value="UPLOAD" class="w3-btn w3-round">

        </form><br />

        </center>

        <div class="w3-light-grey w3-card-4">

        <h1>Quick settings</h1>

        <a href="logout.php" class="w3-btn w3-round">Logout <i class="fa fa-sign-out" aria-hidden="true"></i></a>

        <button onclick="toggleDarkMode();" class="w3-btn w3-round">Dark mode <i class="fa fa-moon-o" aria-hidden="true"></i></button>

        <button onclick="toggleLightMode();" class="w3-btn w3-round">Light mode <i class="fa fa-lightbulb-o" aria-hidden="true"></i></button>
        
        <script>
            function toggleDarkMode() {
                var element = document.body;
                element.classList.toggle("w3-dark-grey");
                localStorage.setItem("toggleDarkMode", "w3-dark-grey");
            }
            function toggleLightMode() {
                var element = document.body;
                element.classList.toggle("w3-light-blue");
                localStorage.setItem("toggleLightMode", "w3-light-blue");
            }
        </script>
        </div>
        <br />
        <?php
            include 'settings.php';
        ?>
        <hr />
        <h5>If you wish us to remove any personal details we hold about you, please email us at <i class="fa fa-envelope"></i><a href="mailto:evanrutledge226@gmail.com">evanrutledge226[at]gmail[dot]com</a></h5>
    </div>
    <?php include '../linkbar.php' ?>
</body>
</html>