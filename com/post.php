<?php
session_start();

if(!file_exists('../acc/users/' . $_SESSION['username'] . '.xml')){

    header('Location: /acc/login.php');

}

if($_POST) {

    $uname = $_SESSION['username'];

    $description = htmlspecialchars($_POST['description']);

    $title = htmlspecialchars($_POST['title']);

    $time = date('F d Y H:i:s');

    if($title != "") {

        $handle = fopen("../com/posts/" . $title . '.xml', "a");

        fwrite($handle, "<table class='w3-table-all w3-card-4' style='color:black;'><td>By:" . $uname . "</td><br /><td>Created:" . $time . "</td><br /></table><table class='w3-table-all w3-card-4' style='color:black;'><td>" . $description . "</td></table><br /><hr />");

        fclose($handle);

    } else {

        echo "Title is blank!";

    }

}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>GR8BRIK community forums</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../w3.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
</head>
<body class="w3-light-blue w3-container">
    <?php include '../navbar.php' ?>
    <center>
        <h1>Post</h1>
        <form method="post" action="">
            <p>Title:<input type="text" name="title" placeholder="Input a descriptive title" size="50" /></p>
            <p>Description:<br /><textarea name="description" placeholder="The body of your post" rows="4" cols="50"></textarea></p>

            <br/>

            <p><input type="submit" value="POST" name="post" class="w3-btn" /></p>

        </form>
    </center><br /><br />
    
    <?php include('../linkbar.php'); ?>

</body>
</html>