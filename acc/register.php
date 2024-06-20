<?php
session_start();
if(file_exists('users/' . $_SESSION['username'] . '.xml')){
    header('Location: index.php');
}
$errors = array();

if(isset($_POST['login'])){
    $username = htmlspecialchars($_POST['username']);

    $email = $_POST['email'];

    $password = $_POST['password'];

    $c_password = $_POST['c_password'];

    $robot = $_POST['botbox'];

    $day = $_POST['day'];

    $month = $_POST['month'];

    $year = $_POST['year'];

    $birthday = mktime(0,0,0,$month,$day,$year);

    $difference = time() - $birthday;

    $age = floor($difference / 31556926);

    echo $age;

    if(!$age >= 13) {
        $errors[] = 'You must be at least 13 to use GR8BRIK';
    }

    if(file_exists('users/' . $username . '.xml')){
        $errors[] = 'Username already exists';
        }

    if($username == '') {
        $errors[] = 'Username is blank';
    }

    if($email == ''){
        $errors[] = 'Email is blank';
        }

    if($password == '' || $c_password == ''){
        $errors[] = 'Passwords are blank';
        }

    if($password != $c_password){
        $errors[] = 'Passwords do not match';
        }

    if($botbox != $notbot){
        $errors[] = 'Please confim that your not a robot';
        }

        if(count($errors) == 0){
            $xml = new SimpleXMLElement ('<user></user>');

            $xml->addChild('userid', $notbotnumber);

            $xml->addChild('password', md5($password));

            $xml->addChild('email', $email);

            $xml->addChild('age', $age);

            $xml->addChild('username', $username);

            $xml->asXml('users/' . $username . '.xml');

            session_start();
            error_reporting(0);
            header('Location: index.php');
        }
            
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Register - Gr8brik</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../w3.css">
    <link rel="stylesheet" href="../theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
</head>
<body class="w3-light-blue w3-container">
    <?php include '../navbar.php' ?>
    <br />
    <center>
        <h1>Register</h1>
        <form method="post" action="">
            <?php
            if(count($errors) > 0) {
                echo '<ul>';
                foreach($errors as $e){
                    echo '<li>' . $e . '</li>';
                }
                echo '<ul>';
            }
            ?>
            <p><input type="text" name="username" class="w3-input" placeholder="Username (eg user1234)" style="width:30%" /></p>
            <p><input type="text" name="email" class="w3-input" placeholder="Email (eg user1234@mail.com)" style="width:30%" /></p>
            
            <p><input type="password" class="w3-input" placeholder="Password (eg password1234)" name="password" style="width:30%" /></p>
            <p><input type="password" class="w3-input" placeholder="Write your password again" name="c_password" style="width:30%" /></p>
        <div class="w3-row-padding">
            <select class="w3-select" name="day" style="width:30%">
                <option value="" disabled selected>Day</option>
                <option>01</option>
                <option>02</option>
                <option>03</option>
                <option>04</option>
                <option>05</option>
                <option>06</option>
                <option>07</option>
                <option>09</option>
                <option>10</option>
                <option>11</option>
                <option>12</option>
                <option>13</option>
                <option>14</option>
                <option>15</option>
                <option>16</option>
                <option>17</option>
                <option>18</option>
                <option>19</option>
                <option>20</option>
                <option>21</option>
                <option>22</option>
                <option>23</option>
                <option>24</option>
                <option>25</option>
                <option>26</option>
                <option>27</option>
                <option>28</option>
                <option>29</option>
                <option>30</option>
                <option>31</option>
            </select>
            <br/>
            <select class="w3-select" name="month" style="width:30%">
                <option value="" disabled selected>Month</option>
                <option value="1">Jan</option>
                <option value="2">Feb</option>
                <option value="3">Mar</option>
                <option value="4">Apr</option>
                <option value="5">May</option>
                <option value="6">Jun</option>
                <option value="7">Jul</option>
                <option value="8">Aug</option>
                <option value="9">Sep</option>
                <option value="10">Oct</option>
                <option value="11">Nov</option>
                <option value="12">Dec</option>
            </select>
            <br/>
                <select class="w3-select" name="year" style="width:30%">
                <option value="" disabled selected>Year</option>
                <option value="1904">1904</option>
                <option value="1905">1905</option>
                <option value="1906">1906</option>
                <option value="1907">1907</option>
                <option value="1908">1908</option>
                <option value="1909">1909</option>
                <option value="1910">1910</option>
                <option value="1911">1911</option>
                <option value="1912">1912</option>
                <option value="1913">1913</option>
                <option value="1914">1914</option>
                <option value="1915">1915</option>
                <option value="1916">1916</option>
                <option value="1917">1917</option>
                <option value="1918">1918</option>
                <option value="1919">1919</option>
                <option value="1920">1920</option>
                <option value="1921">1921</option>
                <option value="1922">1922</option>
                <option value="1923">1923</option>
                <option value="1924">1924</option>
                <option value="1925">1925</option>
                <option value="1926">1926</option>
                <option value="1927">1927</option>
                <option value="1928">1928</option>
                <option value="1929">1929</option>
                <option value="1930">1930</option>
                <option value="1931">1931</option>
                <option value="1932">1932</option>
                <option value="1933">1933</option>
                <option value="1934">1934</option>
                <option value="1935">1935</option>
                <option value="1936">1936</option>
                <option value="1937">1937</option>
                <option value="1938">1938</option>
                <option value="1939">1939</option>
                <option value="1940">1940</option>
                <option value="1941">1941</option>
                <option value="1942">1942</option>
                <option value="1943">1943</option>
                <option value="1944">1944</option>
                <option value="1945">1945</option>
                <option value="1946">1946</option>
                <option value="1947">1947</option>
                <option value="1948">1948</option>
                <option value="1949">1949</option>
                <option value="1950">1950</option>
                <option value="1951">1951</option>
                <option value="1952">1952</option>
                <option value="1953">1953</option>
                <option value="1954">1954</option>
                <option value="1955">1955</option>
                <option value="1956">1956</option>
                <option value="1957">1957</option>
                <option value="1958">1958</option>
                <option value="1959">1959</option>
                <option value="1960">1960</option>
                <option value="1961">1961</option>
                <option value="1962">1962</option>
                <option value="1963">1963</option>
                <option value="1964">1964</option>
                <option value="1965">1965</option>
                <option value="1966">1966</option>
                <option value="1967">1967</option>
                <option value="1968">1968</option>
                <option value="1969">1969</option>
                <option value="1970">1970</option>
                <option value="1971">1971</option>
                <option value="1972">1972</option>
                <option value="1973">1973</option>
                <option value="1974">1974</option>
                <option value="1975">1975</option>
                <option value="1976">1976</option>
                <option value="1977">1977</option>
                <option value="1978">1978</option>
                <option value="1979">1979</option>
                <option value="1980">1980</option>
                <option value="1981">1981</option>
                <option value="1982">1982</option>
                <option value="1983">1983</option>
                <option value="1984">1984</option>
                <option value="1985">1985</option>
                <option value="1986">1986</option>
                <option value="1987">1987</option>
                <option value="1988">1988</option>
                <option value="1989">1989</option>
                <option value="1990">1990</option>
                <option value="1991">1991</option>
                <option value="1992">1992</option>
                <option value="1993">1993</option>
                <option value="1994">1994</option>
                <option value="1995">1995</option>
                <option value="1996">1996</option>
                <option value="1997">1997</option>
                <option value="1998">1998</option>
                <option value="1999">1999</option>
                <option value="2000">2000</option>
                <option value="2001">2001</option>
                <option value="2002">2002</option>
                <option value="2003">2003</option>
                <option value="2004">2004</option>
                <option value="2005">2005</option>
                <option value="2006">2006</option>
                <option value="2007">2007</option>
                <option value="2008">2008</option>
                <option value="2009">2009</option>
                <option value="2010">2010</option>
                <option value="2011">2011</option>
                <option value="2012">2012</option>
                <option value="2013">2013</option>
                <option value="2014">2014</option>
                <option value="2015">2015</option>
                <option value="2016">2016</option>
                <option value="2017">2017</option>
                <option value="2018">2018</option>
                <option value="2019">2019</option>
                <option value="2020">2020</option>
                <option value="2021">2021</option>
                <option value="2022">2022</option>
                <option value="2023">2023</option>
                <option value="2024">2024</option>
                <option value="2025">2025</option>
            </select>
        </div>
            <a onclick="document.getElementById('id02').style.display='block'" class="w3-btn w3-round">REGISTER</a>
            
            <div id="id02" class="w3-modal">
                <div class="w3-modal-content w3-card-4 w3-light-blue">
                    <div class="w3-container">
                        <span onclick="document.getElementById('id02').style.display='none'" class="w3-closebtn">&times;                            </span>
                        <b>By using GR8BRIK, you confirm your over the age of 13 or have parental permission, and accept the <a href="../privacy.php">Privacy Policy</a> and <a href="../terms.php">Terms and Conditions</a>. You cannot change your username.</b>
                        <p>Confirm your not a robot by writing the number box to box. Then press "accept".</p>
                        <?php
                            $notbotnumber = rand (1, 10000);
                            echo '<input type="text" name="notbot" placeholder=' . $notbotnumber . ' size="10" readonly />';
                        ?>
                        <input type="text" name="botbox" placeholder="<?php $notbot ?>" size="20" />
                        <p><input type="submit" value="ACCEPT" name="login" class="w3-btn w3-large w3-white w3-hover-green" /></p>
                    </div>
                </div>
            </div>
        </form>
        <a class="w3-large" href="login.php">Login</a>
    </center>
    <br />
    <br />
    <?php include '../linkbar.php' ?>
</body>
</html>