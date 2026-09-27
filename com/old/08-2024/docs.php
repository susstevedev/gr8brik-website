<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Docs / GR8BRIK</title>
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

            <table class="w3-table-all w3-card-8" style="color:black;">
            <thead>
                <tr>
                    <th>Post</th>
                    <th>Date</th>
                </tr>
            </thead>
        <tbody>
			<?php
                $query = "docs";

                foreach (glob("posts/*.xml") as $_FF) {
                    $xml = new SimpleXMLElement($_FF, 0, true);

                    if ((string)$xml->status === (string)$query) {
                        echo "<tr><td><a href='view.php?" . $_FF . "'>" . htmlspecialchars($xml->title) . "</a></td>";
					    echo "<td>" . $xml->date . "</td></tr>";
                    }
                }

            ?>
        </tbody>
    </table>
    <br />
    <br />

    </div>
        
    <?php include('../linkbar.php'); ?>

</body>
</html>