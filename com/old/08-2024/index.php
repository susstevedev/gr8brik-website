<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>GR8BRIK community forums</title>
    <link rel="stylesheet" href="https://www.w3schools.com/lib/w3.css">
    <link rel="stylesheet" href="../lib/theme.css">
    <script src="../lib/main.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <meta charset="UTF-8">
    <meta name="author" content="sussteve226">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body class="w3-light-blue w3-container">
    <?php include('../navbar.php'); ?>
	
	<style>
	
	#loading {
		position: fixed;
		width: 100%;
		height: 100%;
		top: 0;
		left: 0;
		background: rgba(255, 255, 255, 0.8);
		display: flex;
		justify-content: center;
		align-items: center;
		z-index: 9999;
		border: 1px;
		border-radius: 15px;
	}
	
	</style>
	
	<script>
	window.onload = function() {
		setTimeout(function() {
			document.getElementById('loading').style.display = 'none';
		}, 3000);
	};
	</script>


        <div class="w3-center">

            <div class="w3-red w3-padding-small"><img src="../img/warning.jpg" style="width:20px;height=20px;"><b>Older forum posts from May-Aug will not be moved till the later forum update.</b></div><br />

            <div class="w3-light-grey w3-padding-small"><img src="../img/info.jpg" style="width:20px;height=20px;"><b>Please read the <a href="../rules">rules</a>, and maybe check out our <a href="../users">users</a>.</b></div>
		
			<br /><p>Wanna ask a question, or post something funny?</p><a href="post.php" class="w3-btn w3-blue w3-hover-opacity">POST A TOPIC</a><br /><br />
			
			<form method="get" action="">
				<input class="w3-input w3-hover-opacity w3-border" type="text" value="<?php if (isset($_GET['q'])) { echo $_GET['q']; } ?>" placeholder="Search for posts..." name="q">
				<input class="w3-btn w3-hover-opacity w3-border w3-blue w3-third" type="submit" value="&#128270;">
			</form><br />

            <table class="w3-table-all w3-card-8" style="color:black;">
            <thead>
                <tr>
                    <th>Post</th>
                    <th>Date</th>
                    <th>Last posted</th>
                </tr>
            </thead>
        <tbody>
			<?php
            if (isset($_GET['q']) && $_GET['q']) {
                $query = htmlspecialchars($_GET['q']);
                echo "<p>Search results for <b>" . $query . "</b></p><br />";
				echo '
					<div id="loading">
						<img src="../img/loading.gif" alt="Loading...">
					</div>';
                foreach (glob("posts/*xml") as $_FF) {
					$xml = new SimpleXMLElement($_FF, 0, true);
                    if (strpos($_FF, $query) !== false) {
                        echo "<tr><td><a href='view.php?" . urlencode($_FF) . "'>" . htmlspecialchars($xml->title) . "</a></td>";
						echo "<td>" . $xml->date . "</td></tr>";
					}
				}
			} else {
                $files = glob("posts/*xml", GLOB_NOSORT);
                usort($files, function($a, $b) {
                    return filemtime($b) - filemtime($a);
                });
                foreach ($files as $_FF) {
                    $xml = new SimpleXMLElement($_FF, 0, true);
                    echo "<tr><td><a href='view.php?" . $_FF . "'>" . htmlspecialchars($xml->title) . "</a></td>";
                    echo "<td>" . $xml->date . "</td>";
                    echo "<td>" . date("Y-m-d H:i:s", filemtime($_FF)) . "</td></tr>";
                }
			};
            ?>
        </tbody>
    </table>
    <br />
    <br />

    </div>
        
    <?php include('../linkbar.php'); ?>

</body>
</html>