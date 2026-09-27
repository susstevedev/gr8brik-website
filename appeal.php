<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';

//rahhh fixing the old page that no one uses because it was an sql vulnerability rahh
if (isset($_POST['new_appeal'])) {
    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
    if ($conn->connect_error) {
        exit($conn->connect_error);
    }

    $appeal_text = isset($_POST['appealbox']) ? $_POST['appealbox'] : null;
    $email = isset($_POST['email']) ? $_POST['email'] : null;

    if(empty($appeal_text)) {
        exit('Appeal text is empty');
    }

    if(empty($email)) {
        exit('Provide your accounts email');
    }

    $end_date = time() + 604800;

    $u_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND deactive IS NULL");
    $u_stmt->bind_param("s", $email);
    $u_stmt->execute();
    $result = $u_stmt->get_result();
    $uid = $result->fetch_assoc()['id'] ?? null;
    $u_stmt->close();

    if(!isset($uid) || $uid === null) {
        exit('An account with this email address doesn\'t exist');
    }

    $a_stmt = $conn->prepare("INSERT INTO appeals (user, reason, end_date) VALUES (?, ?, ?)");
    $a_stmt->bind_param("iss", $uid, $appeal_text, $end_date);

    if ($a_stmt->execute()) {
        $a_stmt->close();

        exit("Success! Your ban appeal has been submitted.");
    } else {
        exit("Failed to submit appeal.");
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Appeal</title>
    <?php include 'header.php' ?>
</head>

<body class="w3-light-blue w3-container">

    <?php
        include('navbar.php');
    ?>
    <div class="w3-container w3-center">
        <h1>Appeal</h1>
        <p>This page is for appealing a ban. We usually recommend emailing <?php echo DB_MAIL ?> instead because this doesn't really get updated that often</p>
        <form method="post" action="">
            <div class="w3-row-padding">
                <p><input type="text" value="<?php echo $current_user->email ?? null ?>" class="login-input w3-input w3-border w3-hover-green w3-round" name="email" placeholder="Your email" required /></p>
            </div>

            <p><textarea class="w3-border w3-hover-red w3-round" name="appealbox" placeholder="Why should we reinstate your account?" rows="4" cols="50"></textarea></p>
            <p><input type="submit" value="Appeal" name="new_appeal" class="w3-button w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo" /></p>
        </form>
    </div>

    <?php include('linkbar.php') ?>
</body>
</html>