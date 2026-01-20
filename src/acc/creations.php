<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/filesize.php';
if(!loggedin()) {
    header('Location:login.php');
}
$conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
if ($conn2->connect_error) {
    exit($conn->connect_error);
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>My Creations</title>
    <?php include '../header.php' ?>
</head>
<body class="w3-light-blue w3-container">

    <?php 
        include('../navbar.php');
        include('panel.php');
    ?>
    
	<center>
        <div class="w3-row">
            <span data-testid="gr8-my-creations-text-sb"><p data-testid="gr8-my-creations-text-sb--text">Creations are sorted by newest by default.</p></span>
            <table class="w3-table-all" style="color:black;">
            <?php
                $stmt = $conn2->prepare('SELECT * FROM model WHERE user = ? ORDER BY date DESC');
                $stmt->bind_param('i', $token['user']);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $model_id = $row['id'];

                    $name = htmlspecialchars(substr($row['name'], 0, 30));
                    if (strlen($row['name']) >= 30) {
                        $name .= '...';
                    }

                    ?>

                    <div class='w3-display-container w3-left w3-padding'>
                    <a href='creations/edit/<?php echo $row['id'] ?>'>
                        <img src='<?php echo $row['screenshot'] ?: '/img/no_image.png' ?>' width='320px' height='240px' loading='lazy' class='w3-card-2 w3-hover-shadow w3-grey'>
                    </a>
                    <div class='gr8-theme w3-card-2 w3-light-grey w3-padding-small'><h4><?php echo $name ?: 'Untited Creation' ?></h4>
                    <span>By you on <?php echo date("D, M d, Y", strtotime($row['date'])) ?></span>
                    <br />
                    <span>Viewed <?php echo $row['views'] ?> times, <?php echo $row['likes'] ?> likes, <?php echo size_unit($row['size']) ?></span>
                    </div></div>

                    <?php } ?>
            </table>
        </div>
    </center><br /><br />
    <?php include('../linkbar.php') ?>
</body>
</html>