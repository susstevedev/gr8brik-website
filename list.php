<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Creations - GR8BRIK</title>
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

        <h1>Creations</h1>
        <div class="w3-container">
                <p>Want to build your own model? <a href="modeler.php" class="w3-btn w3-round">MODELER</a></p>
        </div>

        <table>
            <?php
            if (isset($_GET['q']) && $_GET['q']) {
                $query = htmlspecialchars($_GET['q']);
                echo "<p>Search results for <b>" . $query . "</b></p><br />";
                foreach (glob("cre/*json") as $_FF) {
                    if (strpos($_FF, $query) !== false) {
                        echo "<a href='creation.php?" . $_FF . "'><img src='img/blured_model.jpg' width='320' height='240' ></a><br />";
                        echo "<b>Name:" . $_FF . "</b><br />";
                        echo "<b>Created:" . date("F d Y H:i:s", filemtime($_FF)) . "</b><br />";
                    }
                }
            } else {
                foreach (glob("cre/*json") as $_FF) {
                    echo "<a href='creation.php?" . $_FF . "'><img src='img/blured_model.jpg' width='320' height='240' ></a><br />";
                    echo "<b>Name:" . $_FF . "</b><br />";
                    echo "<b>Created:" . date ("F d Y H:i:s", filemtime($_FF)) . "</b><br />";
                };
            }

            ?>
        </table><br /><br />

    <?php include('linkbar.php'); ?>


</body>
</html>