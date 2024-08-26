<?php
session_start();
require_once 'classes/membership.php';
$Membership = new Membership();

if ($_POST && !empty($_POST['username']) && !empty($_POST['pwd']) && !empty($_POST['email'])) {
    $response = $Membership->register_User($_POST['username'], $_POST['pwd'], $_POST['email'], $_POST['day'], $_POST['month'], $_POST['year']);
}

$words = ['Brick', 'Minifig', 'Stud', 'Build', 'Block', 'Stack', 'Baseplate', 'Roadplate', 'Fanatic', 'Craftsman'];
$randomKeys = array_rand($words, 2); // Select two random keys
$randomWord1 = $words[$randomKeys[0]];
$randomWord2 = $words[$randomKeys[1]];
$randomNumber = rand(100, 999);

$combinedString = $randomWord1 . $randomWord2 . $randomNumber;
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Register</title>
    <link rel="stylesheet" href="https://www.w3schools.com/lib/w3.css">
    <link rel="stylesheet" href="../lib/theme.css">
    <script src="../lib/main.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <meta charset="UTF-8">
    <meta name="description" content="Gr8brik is a block building browser game. No download required">
    <meta name="keywords" content="legos, online block builder, gr8brik, online lego modeler, barbies-legos8885 balteam, lego digital designer, churts, anti-coppa, anti-kosa, churtsontime, sussteve226, manofmenx">
    <meta name="author" content="sussteve226">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="w3-container w3-light-blue">
	<?php include '../navbar.php'; ?>
	<script>
		function showPass() {
			var x = document.getElementById("pwd");
			if (x.type === "password") {
				x.type = "text";
			} else {
				x.type = "password";
			}
		}
        $(document).ready(function() {
			$("#alert").fadeOut(10000);
			$("#close").click(function(){
				$("#alert").hide();
			});
        });
    </script>
    <center>
        <div class="main w3-light-grey w3-card-24">
            <form method="post" action="">
				<b>Already have an account? <a href="login.php">Login</a></b><br />
                <h1>Register</h1>
                <small>Please fill in the details to create an account</small>
                <p>
                    <label for="username">Username:</label>
                    <input type="text" value="<?php echo $combinedString; ?>" name="username" class="w3-input" required style="width:30%"/>
					<small>Nick names suggested randomly</small>
				</p>
                <p>
                    <label for="email">E-mail:</label>
                    <input type="email" name="email" class="w3-input" required style="width:30%"/>
                </p>
                <p>
                    <label for="pwd">Password:</label>
                    <input type="password" name="pwd" id="pwd" class="w3-input" required style="width:30%"/>
					<span class="w3-tag w3-light-grey w3-text-red">(!)</span><label class="w3-validate w3-text-blue">Show password</label>
					<input type="checkbox" class="w3-check" class="w3-input" onclick="showPass();">
				</p>
				<p>
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
				</p>
                <p>
					<span onclick="document.getElementById('id02').style.display='block'" class="w3-btn w3-blue w3-hover-opacity">CONTINUE</span>
                </p>
				
            <div id="id02" class="w3-modal">
                <div class="w3-modal-content w3-card-4 w3-light-blue">
                    <div class="w3-container">
                        <span onclick="document.getElementById('id02').style.display='none'" class="w3-closebtn">&times;                            </span>
                        <b>By using GR8BRIK, you confirm your over the age of 13 or have parental permission, and accept the <a href="../rules.php">Rules</a>.</b>
                        <p>Confirm your not a robot by writing the number box to box. Then press "accept".</p>
                        <?php
                            $notbotnumber = rand (1, 10000);
                            echo '<input type="text" name="notbot" placeholder=' . $notbotnumber . ' size="10" readonly />';
                        ?>
                        <input type="text" name="botbox" placeholder="<?php $notbot ?>" size="20" />
                        <p><input class="w3-btn w3-blue w3-hover-blue" type="submit" id="submit" value="CREATE ACCOUNT" name="submit" /></p>
                    </div>
                </div>
            </div>
			
            </form>
            <?php if (isset($response)) echo "<h4 class='alert'>" . $response . "</h4>"; ?>
        </div>
    </center>
	<?php include '../linkbar.php'; ?>
</body>
</html>
