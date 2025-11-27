<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';

if(loggedin() === true) {
    header('Location: index.php');
}

$words = ['Brick', 'Minifig', 'Stud', 'Build', 'Block', 'Stack', 'Baseplate', 'Roadplate', 'Fanatic', 'Craftsman', 'Awesome', 'Great'];
$randomKeys = array_rand($words, 2);
$randomWord1 = $words[$randomKeys[0]];
$randomWord2 = $words[$randomKeys[1]];
$randomNumber = rand(100, 999);

$combinedString = $randomWord1 . $randomWord2 . $randomNumber;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Create Account</title>
    <?php include '../header.php' ?>
</head>
<body class="w3-container w3-light-blue">
    <?php include '../navbar.php'; ?>
    <script>
        $(document).ready(function() {
            $("#loginBtn").click(function(event) {
                event.preventDefault();

                var name = $("#loginForm input[name='name']").val();
                var mail = $("#loginForm input[name='mail']").val();
                var pwd = $("#loginForm input[name='pwd']").val();

                $.ajax({
                    url: "../ajax/auth",
                    method: "POST",
                    data: { register: true, name: name, mail: mail, pwd: pwd },
                    success: function(response) {
                        if(response.success === true) {
                            window.location.href = "/";
                        } else {
                            alert(response.error || "An unknown error occurred. Please try again later.")
                        }
                    },

                    error: function(jqXHR, textStatus, errorThrown) {
                        if (jqXHR.status === 500 || jqXHR.status === 400) {
                            var response = JSON.parse(jqXHR.responseText);
                            alert(response.error);
                        } else {
                            console.error("AJAX Error:", textStatus, errorThrown);
                            alert("An error occurred. Please try again later.");
                        }
                    }
                });
            });
        });
    </script>
    <h2>Create Account</h2>
    <div id="loginForm" class="w3-container">
        <span>Already have an account? <a href="login">Login</a></span><br />
        <input class="w3-input w3-border" value="<?php echo $combinedString; ?>" type="text" name="name" placeholder="Username"><br />
        <input class="w3-input w3-border" type="email" name="mail" placeholder="Email"><br />
        <input class="w3-input w3-border" type="password" name="pwd" placeholder="Password"><br />
        <!--<p>Birth date:</p>
        <div class="w3-row-padding">
            <select class="w3-select" name="day">
                <option value="01" disabled selected>day</option>
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
            </select><br />
            <select class="w3-select" name="month">
                <option value="1" disabled selected>month</option>
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
            </select><br />
            <select class="w3-select" name="year">
                <option value="" disabled selected>year</option>
                </select></div></p>

                <script>
                // dynamic year system
                const s = document.querySelector('select[name="year"]');
                for (let y = 1890; y <= 2027; y++) {
                    const o = document.createElement("option");
                    o.value = y;
                    o.textContent = y;
                    s.appendChild(o);
                }
                </script> -->
        <p>By registering, you are agreeing:</p>
        <ul>
            <li>To our <a href="/terms.php">Terms and Conditions</a></li> 
            <li>To our <a href="/privacy.php">Privacy Policy</a></li>
            <li>That you are at least 13 years old</li>
            <li>You do not live in the UK, the state of Wyoming or Mississippi</li>
        </ul>
        <br /><button class="w3-btn w3-blue w3-hover-opacity w3-round w3-padding w3-border w3-border-indigo" id="loginBtn" name="login">Create Account</button>
        
    </div>
    <?php include '../linkbar.php' ?>
</body>
</html>