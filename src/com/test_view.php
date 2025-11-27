<?php

session_start();

if(!file_exists('posts/' . urldecode(basename($_SERVER['QUERY_STRING'])))){

    header('Location: index.php?dp');

    die;

}

$p = 'posts/' . urldecode(basename($_SERVER['QUERY_STRING']));

$p_xml = simplexml_load_file($p);

if($_POST['comment']) {

    if (!isset($_SESSION['username']) || $_SESSION['username'] == '') {

        $u = 'Anonymous User';

        $a = 'An Anonymous User';

    } else {

        $xml = new SimpleXMLElement('../acc/users/' . $_SESSION['username'] . '.xml', 0, true);

        $u = $_SESSION['username'];

        $a = "@" . $xml->username;

    }

    $c = htmlspecialchars($_POST['commentbox']);

    $pfp = '../acc/users/pfps/' . $u . '..jpg';

    $id = rand(10, 10000);

    $handle = fopen($p, "a");

    fwrite($handle, "<table id='" . $id . "' class='w3-table-all w3-card-4' style='color:black;'><td><a href='../profile.php?" . $u . "'><img src='" . $pfp . "' width='50px' height='50px' style='border-radius:50px'>" . $a . "</a> <b>said:</b><b style='float:right'><a id='" . $id . "' href='#" . $id . "'>" . date('F d Y H:i:s') . "</a></b><br /><p>" . $c . "</p></td></table><br />");

    fclose($handle);

    }

include('bbcode.php');
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

<?php include('../navbar.php') ?>

        <?php echo "<h1>" . $p . "</h1>" ?>

        <br /><form method='post' action=''><textarea name='commentbox' placeholder='Add a comment...' rows='4' cols='50'></textarea>
        <br /><input type='submit' value='POST' name='comment' class='w3-btn' /></form>

        <?php

            foreach ($p_xml->comment as $comment) {
                // Extract the comment text
                $commentText = (string)$comment;

                // Apply BBCode parsing
                $bbcode = new BBCode();
                $parsedComment = $bbcode->parse($commentText);

                // Output or store the parsed comment
                echo $parsedComment;
            }

            echo '</div><br /><br />';

            include('../linkbar.php');

        ?>

</body>
</html>