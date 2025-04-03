<?php
    require_once 'acc/classes/user.php';

    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

    $count_result = $conn->query("SELECT COUNT(*) as active FROM users WHERE deactive = '' AND admin = 0");
    $count_result2 = $conn->query("SELECT COUNT(*) as deleted FROM users WHERE deactive != ''");
    $count_result3 = $conn->query("SELECT COUNT(*) as admin FROM users WHERE admin = 1");

    $count_row = $count_result->fetch_assoc();
    $count_row2 = $count_result2->fetch_assoc();
    $count_row3 = $count_result3->fetch_assoc();

    /*if (!isset($count_row['active'])) {
        $count_row['active'] = 0;
    }
    if (!isset($count_row2['deleted'])) {
        $count_row2['deleted'] = 0;
    }
    if (!isset($count_row3['admin'])) {
        $count_row3['admin'] = 0;
    }*/
?>
<html>
  <head>
  <title>/chart</title>
    <link rel="stylesheet" href="/lib/w3.css">
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
      google.charts.load('current', {'packages':['corechart']});
      google.charts.setOnLoadCallback(drawChart);

      function drawChart() {

        var data = google.visualization.arrayToDataTable([
          ['Task',    'Users'],
          ['Active',    <?php echo $count_row['active'] ?>],
          ['Banned',    2],
          ['Deactivated',    <?php echo $count_row2['deleted'] ?>],
          ['Admin',    <?php echo $count_row3['admin'] ?>]
        ]);

        var options = {
          title: 'Gr8brik Users',
          is3D: true,
          backgroundColor: '#f1f1f1',
        };

        var chart = new google.visualization.PieChart(document.getElementById('piechart'));

        chart.draw(data, options);
      }
    </script>
  </head>
  <body class="w3-container w3-light-grey">
    <div id="piechart" style="width: 500px; height: 500px;" class="w3-light-grey"></div>
    <a href="http://www.gr8brik.rf.gd/users?src=googleChart" class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo" target="_blank">Users Page</a>
    <span class="w3-padding-tiny">|</span>
    <a href="http://developers.google.com/chart?src=gr8brik" class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-padding-small w3-border w3-border-indigo" target="_blank">Google Chart Docs</a>
  </body>
</html>