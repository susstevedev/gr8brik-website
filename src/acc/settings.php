<?php
if(!file_exists($_SERVER["DOCUMENT_ROOT"] . '/acc/users/' . $_SESSION['username'] . '.xml')){
    $xml = 'No login!';
}else{
    $xml = new SimpleXMLElement('../acc/users/' . $_SESSION['username'] . '.xml', 0, true);
};
if(isset($_POST['update'])){
    $dir = "../acc/users/pfps/";
    $target_file = $dir . basename($_FILES['fileToUpload']['name']);
    $username = $_SESSION['username'];
    $filename = $dir . $username;
    $extension = substr_replace($target_file , 'jpg', strrpos($filename, '.') +1);
    $temp = explode(".", $_FILES["fileToUpload"]["name"]);
    $upload = $filename . $extension;
    $okay = 1;

    // Check file size
    if ($_FILES["fileToUpload"]["size"] > 5242880) {
        echo "Sorry, your file is too large.";
    $uploadOk = 0;
    }

    // Check if $uploadOk is set to 0 by an error
    if ($okay == 0) {
        echo "Sorry, your file was not uploaded.";
    // if everything is ok, try to upload file
    } else {
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $upload )) {
        echo "Your Profile Pricture has been updated.";
    } else {
        echo "Sorry, there was an error uploading your file.";
    }
    }

}
$password_error = false;
if($password_error) {
    echo '<b>Some of the passwords do not match</b>';
}
if(isset($_POST['change'])){
    $old = md5($_POST['o_password']);
    $new = md5($_POST['n_password']);
    $confirm = md5($_POST['c_password']);
    $xml = new SimpleXMLElement('users/' . $_SESSION['username'] . '.xml', 0, true);
    if($old == $xml->password) {
        if($new == $confirm) {
            $xml->password = $new;
            $xml->asXML('users/' . $_SESSION['username'] . '.xml');
            header('Location: logout.php');
            die;
        }
    };
    $password_error = true;
}
?>
<?php
if($xml->username == '') {
    echo '<b>You are banned</b>';
}
if(isset($_POST['u_change'])){
    $new = $_POST['handle'];
    $xml = new SimpleXMLElement('users/' . $_SESSION['username'] . '.xml', 0, true);
        $xml->username = $new;
        $xml->asXML('users/' . $_SESSION['username'] . '.xml');
        header('Location: logout.php');
        die;
    }
?>
<?php
$email_error = false;
if($email_error) {
    echo '<b>Some of the emails do not match</b>';
}
if(isset($_POST['e_change'])){
    $old = $_POST['o_email'];
    $new = $_POST['n_email'];
    $confirm = $_POST['c_email'];
    $xml = new SimpleXMLElement('users/' . $_SESSION['username'] . '.xml', 0, true);
    if($old == $xml->email) {
        if($new == $confirm) {
            $xml->email = $new;
            $xml->asXML('users/' . $_SESSION['username'] . '.xml');
            header('Location: logout.php');
            die;
        }
    
    }
    $password_error = true;
}
?>
<?php
if(isset($_POST['a_change'])){
    $new = $_POST['age'];
    $xml = new SimpleXMLElement('users/' . $_SESSION['username'] . '.xml', 0, true);
        $xml->age = $new;
        $xml->asXML('users/' . $_SESSION['username'] . '.xml');
        header('Location: logout.php');
        die;
    }
?>
<h1>Settings</h1>
  <h2>Profile picture (no image if none)</h2>
  <center>
  <div>
        <img src="<?php echo '/acc/users/pfps/' . $_SESSION['username'] . '..jpg' ?>" style="width: 250px; height: 250px; border-radius: 50%;" class="w3-card-8" />
    <form action="" method="post" enctype="multipart/form-data">
    <input type="file" name="fileToUpload" id="fileToUpload" class="w3-input" style="width:30%" >
    <input type="submit" value="Update profile" name="update" class="w3-btn w3-round">
    </form>
  </div>
  </center>

  <br />

  <h2>Change password</h2>
    <center>
    <form method="post" action="">
    <input type="password" class="w3-input" name="o_password" placeholder="Old password" style="width:30%" />
    <br /><input type="password" class="w3-input" name="n_password" placeholder="New password" style="width:30%" />
    <br /><input type="password" class="w3-input" name="c_password" placeholder="Confirm password" style="width:30%" />
    <br /><input type="submit" name="change" value="Update password" class="w3-btn w3-round" />
    </form>
    </center>

    <h2 id="handle">Change handle</h2>

    <center>
    <form method="post" action="">
    <input type="text" class="w3-input" name="handle" placeholder="New handle" style="width:30%" />
    <br /><input type="submit" name="u_change" value="Update handle" class="w3-btn w3-round" />
    </form>
    </center>

    <h2 id="email">Change email</h2>

    <center>
    <form method="post" action="">
    <br /><input type="text" name="o_email" placeholder="Old email" class="w3-input" style="width:30%" />
    <br /><input type="text" name="n_email" placeholder="New email" class="w3-input" style="width:30%" />
    <br /><input type="text" name="c_email" class="w3-input" placeholder="Confirm email" style="width:30%" />
    <br /><input type="submit" name="e_change" value="Update email" class="w3-btn w3-round" />
    </form>
    </center>

    <h2 id="age">Change age</h2>

    <center>
    <form method="post" action="">
    <input type="number" class="w3-input" name="age" placeholder="New age" style="width:30%" />
    <br /><input type="submit" name="a_change" value="Update age" class="w3-btn w3-round" />
    </form>
    </center>

  <br />
