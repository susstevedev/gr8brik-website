<!DOCTYPE html>
<html lang="en" uri="hidden">
<head>
    <title><?php echo urldecode(basename($_SERVER['QUERY_STRING'])) ?></title>
    <link rel="stylesheet" href="../w3.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="w3-light-grey">

<script> 
    var $buoop = {required:{e:-4,f:-3,o:-3,s:-1,c:-3},insecure:true,api:2023.10 }; 
    function $buo_f(){ 
    var e = document.createElement("script"); 
    e.src = "//browser-update.org/update.min.js"; 
    document.body.appendChild(e);
    };
    try {document.addEventListener("DOMContentLoaded", $buo_f,false)}
    catch(e){window.attachEvent("onload", $buo_f)}

    function darkMode() {
        var element = document.body;
        element.classList.toggle("w3-dark-grey");
    }
</script>

<center>

    <?php $file = urldecode($_SERVER['QUERY_STRING']); ?>

    <a href="../index.php" class='w3-btn w3-round w3-green w3-hover-white w3-card-8' title="GR8BRIK homepage" download><i class="fa fa-home" aria-hidden="true"></i></a>
    <a href="<?PHP echo $file ?>" class='w3-btn w3-round w3-green w3-hover-white w3-card-8' title="Download model" download><i class="fa fa-download" aria-hidden="true"></i></a>
    <button class="w3-btn w3-round w3-green w3-hover-white w3-card-8" onclick="toggleDarkMode()" title="Dark mode"><i class="fa fa-moon-o" aria-hidden="true"></i>
</button>

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
        if(file_exists(urldecode($_SERVER['QUERY_STRING']))){
            echo $code; 
        } else {
            echo '<center><h1>Error 404</h1><hr />';
            echo '<b>This model was not found</b><br />';
            echo '<button onclick="window.history.go(-1);" class="w3-btn w3-round w3-green w3-hover-white w3-card-8">BACK</button></center>';
        }
    ?>

</center>

    </div>

</div>

</body>
</html>