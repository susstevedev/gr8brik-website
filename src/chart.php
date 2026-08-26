<?php
    require_once 'ajax/config.php';

    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

    $count_result = $conn->query("SELECT COUNT(*) as active FROM users WHERE deactive IS NULL AND admin = 0");
    $count_result2 = $conn->query("SELECT COUNT(*) as deleted FROM users WHERE deactive IS NOT NULL");
    $count_result3 = $conn->query("SELECT COUNT(*) as admin FROM users WHERE admin = 1");
	$count_result4 = $conn->query("SELECT COUNT(*) as banned FROM blacklist");

    $active_users = $count_result->fetch_assoc()['active'] * 5;
    $deleted_users = $count_result2->fetch_assoc()['deleted'] * 5;
    $admin_users = $count_result3->fetch_assoc()['admin'] * 5;
	$banned_users = $count_result4->fetch_assoc()['banned'] * 5;

	$users_total = $active_users + $deleted_users + $admin_users + $banned_users;
?>
<html>
  <head>
  <title>Users chart - Gr8brik.rf.gd</title>
    <link rel="stylesheet" href="/lib/w3.css">
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
      google.charts.load('current', {'packages':['corechart']});
      google.charts.setOnLoadCallback(drawChart);

      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          ['Task',        'Users'],
          ['Active',      <?php echo $active_users ?>],
          ['Banned',      <?php echo $banned_users ?>],
          ['Deactivated', <?php echo $deleted_users ?>],
          ['Admin',       <?php echo $admin_users ?>]
        ]);

        var options = {
          title: 'Gr8brik Users',
          is3D: true,
          backgroundColor: '#f1f1f1',
        };

        var chart = new google.visualization.PieChart(document.getElementById('piechart'));

        chart.draw(data, options);
      }
	  
	  window.onload = function() {
		var http = new XMLHttpRequest();

		http.onload = function() {
			var json = JSON.parse(this.responseText);
			var user_link = document.querySelector('#user-link');
			var user_img = document.querySelector('#user-avatar');
			var user_name = document.querySelector('#user-name');

			if(json) {
				user_link.href = "/user/" + json.id;
				user_name.innerText = json.user;

				if(user_img && json.pfp) {
					user_img.setAttribute('img', json.pfp);
				}
			}
		}

		http.open("GET", "/ajax/user.php?ajax=true");
		http.send();
	  }
    </script>
  </head>
  <body class="w3-container w3-light-grey">
	<nav class="w3-bar w3-black">
		<div class="w3-bar-item w3-btn w3-hover-white">
			<a href="/"><img src="/img/logo/192.png" style="width: 25px; height: 25px; border-radius: 15px;">Home</a>
		</div>

		<div id="user" class="w3-right w3-bar-item w3-btn w3-hover-white">
			<a id="user-link" href="/user/"><img id="user-avatar" src="/img/no_image.png" width="25px" height="25px" alt="User Avatar"><span id="user-name"></span></a>
		</div>
	</nav>

    <div id="piechart" style="width: 500px; height: 500px;" class="w3-light-grey"></div>

	<p>Total users: <b><?php echo $users_total ?></b></p>
	<p>Last fixed <?php echo date("M d, Y",filemtime('chart.php')); ?></p>

    <a href="http://www.gr8brik.rf.gd/users?src=googleChart" class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo" target="_blank">Users Page</a>

    <span class="w3-padding-tiny">|</span>

    <a href="http://developers.google.com/chart?src=gr8brik" class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo" target="_blank">Google Chart Docs</a>
  </body>
</html>