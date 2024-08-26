<!DOCTYPE html>
<html lang="en" uri="hidden">
<head>
    <title><?php echo $_GET['model'] . ' - GR8BRIK' ?></title>
    <link rel="stylesheet" href="https://www.w3schools.com/lib/w3.css">
    <link rel="stylesheet" href="lib/theme.css">
    <script src="lib/main.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="w3-light-grey w3-animate-right">

<style>

body {
	transition: all 1s ease-in-out;
}

.w3-btn {
	transition: all 0.5s ease-in-out;
	border-radius: 5%
}

.w3-btn:hover {
	transform: translateY(10px);
	border-radius: 15%; /* Border radius on hover */
	color: black;
	border: 1px solid;
}



</style>

<center>

    <?php $file = $_GET['model']; ?>

    <a href="index.php" class='w3-btn w3-large w3-green w3-hover-white w3-card-24' title="GR8BRIK homepage"><i class="fa fa-home" aria-hidden="true"></i></a>
    <a href="<?php echo "cre/" . $file ?>" class='w3-btn w3-large w3-green w3-hover-white w3-card-24' title="Download model" download><i class="fa fa-download" aria-hidden="true"></i></a>
    <button class="w3-btn w3-large w3-green w3-hover-white w3-card-24" onclick="toggleDarkMode()" title="Dark mode"><i class="fa fa-moon-o" aria-hidden="true"></i>
</button>

<br />

<br />

    <script>
        function toggleDarkMode() {
            var element = document.body;
            element.classList.toggle("w3-dark-grey");
        }
    </script>

    <?php $code = "

    <div class='w3-container'>

        <div style='width:100%; margin:auto; position:relative;'>
            <canvas id='viewer' style='border: 1px solid; border-radius: 50px; width: 95%; height: 50%'>
                <p class='browsehappy'>You are using an <strong>outdated</strong> browser. Please <a href='http://browsehappy.com/'>upgrade your browser</a> to improve your experience.</p>
            </canvas>
        </div>

        <script type='text/javascript'>
            var viewer = new JSC3D.Viewer(document.getElementById('viewer'));
            viewer.setParameter('SceneUrl', 'creations/" . urldecode(basename($_SERVER['QUERY_STRING'])) . "');
            viewer.setParameter('ModelColor', '#000000');
            viewer.setParameter('BackgroundColor1', '#808080');
            viewer.setParameter('BackgroundColor2', '#D3D3D3');
            viewer.setParameter('RenderMode', 'flat');
            viewer.init();
            viewer.update();
        </script>

        " ?>

        <?php
        if(file_exists('cre/' . $_GET['model'])){
            echo $code; 
        } else {
            echo '<center><h1>Error 404</h1><hr />';
            echo '<b>This model was not found</b><br />';
            echo '<button onclick="window.history.go(-1);" class="w3-btn w3-large w3-green w3-hover-white w3-card-24">BACK</button></center>';
        }
    ?>

</center>

    </div>

</div>

</body>
</html>