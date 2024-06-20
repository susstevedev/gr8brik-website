<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>GR8BRIK community forums</title>
    <link rel="stylesheet" href="../w3.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <meta charset="UTF-8">
    <meta name="author" content="sussteve226">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body class="w3-light-blue w3-container">
    <?php include('../navbar.php'); ?>

    <div class="w3-display-container w3-card-8 w3-mobile w3-bar" style="width: 100%; height: 25%;color:black;">
        <center><img src="../img/blured_model.jpg" style="width: 75%; height: 25%;" alt="Banner"></center>
        <b class="w3-display-middle">Welcome to the new GR8BRIK community!</b>
    </div><br />

        <div class="w3-center">

            <a href="post.php" class="w3-btn">POST A TOPIC</a>

            <table class="w3-table-all w3-card-8" style="color:black;">
            <thead>
                <tr>
                    <th>Post</th>
                    <th>Date</th>
                </tr>
            </thead>
        <tbody>
            <?php
                foreach (glob("posts/*xml",GLOB_NOSORT) as $_FF) {
                    echo "<tr><td><a href='view.php?" . $_FF . "'>" . $_FF . "</a></td>";
                    echo "<td>" . date ("F d Y H:i:s", filemtime($_FF)) . "</td></tr>";
                };
            ?>
        </tbody>
    </table>
    <br />
    <br />

    </div>
        
    <?php include('../linkbar.php'); ?>

</body>
</html>