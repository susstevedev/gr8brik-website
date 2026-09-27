<?php
/*ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

$name = base64_decode($_GET['id']) . '.jpg';

$imagePath = '../acc/users/pfps/' . $name;

if(!file_exists($imagePath)) {
    $imagePath = '../img/user.png';
}

$fp = fopen($imagePath, 'rb');

$contents = file_get_contents($imagePath);

$data = 'data:image/png;base64,'. base64_encode($contents);

if ($_GET['method'] === 'image') {
    header("Content-Type: image/png");
    header("Content-Length: " . filesize($imagePath));
    fpassthru($fp);
    echo $data;
    exit;
}

if($_GET['method'] === 'data') {
    echo $data;
    exit;
} 
if($_GET['method'] === 'url') {
    header('Location:' . $imagePath);
    exit;
}
echo "No loading method specified.";*/

// eol for the old pfp api, redirect to the folder that the image is located in
/*$path = '../acc/users/pfps/' . base64_decode($_GET['id']) . '.jpg';
header('Location:' . $path);
exit;*/
    
if(isset($_GET['method']) && isset($_GET['letter']) && $_GET['method'] === 'default_legacy') {
    header('Content-type: image/svg+xml');
    
    $valid = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z');
    
    if(!in_array(htmlspecialchars($_GET['letter']), $valid)) {
        $_GET['letter'] = "?";
    }
    
    echo '<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
    	<g>
            <defs>
              <mask id="letter">
                <rect x="0" y="0" width="100%" height="100%" fill="#FFF"/>
                <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-family="Verdana,Geneva,sans-serif" font-size="4rem">' . htmlspecialchars($_GET['letter']) . '</text>
              </mask>
            </defs>
            <!-- <circle cx="50" cy="50" r="50" fill="#1E90FF" mask="url(#letter)"/> -->
        	<rect x="50%" y="50%" fill="#1E90FF" mask="url(#letter)"/>
         </g>
      </svg>';
    
    exit;
}

if(isset($_GET['method']) && isset($_GET['letter']) && $_GET['method'] === 'default') {    
    $valid = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z');
    
    if(!in_array(htmlspecialchars($_GET['letter']), $valid)) {
        $_GET['letter'] = "?";
        
        $imagePath = '../img/no_image.png';

        $fp = fopen($imagePath, 'rb');

        $data = 'data:image/png;base64,'. base64_encode(file_get_contents($imagePath));

        header("Content-Type: image/png");
     	header("Content-Length: " . filesize($imagePath));
       	fpassthru($fp);
        echo $data;
    	exit;
    } else {
    	header('Content-type: image/svg+xml');
    
        echo '<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
             <g>
                <rect dominant-baseline="middle" width="100%" height="100%" fill="#1E90FF"/>
                <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-family="Verdana,Geneva,sans-serif" font-size="4rem">' . htmlspecialchars($_GET['letter']) . '</text>
              </g>
          </svg>';

        exit;
    }
}

/*if (isset($_GET['pictureupdatepictureforeymen'])) {
    $okay = true;
        $upload = "../acc/users/pfps/5.webp";
        
        $data = file_get_contents('../acc/users/pfps/5.jpg');
    	$image = imagecreatefromstring($data);
        
    	if (!$image) {
            $okay = false;
        	echo "Invalid image file";
            exit;
    	}

        if ($okay != false) {
            if (imagewebp($image, $upload, 80)) {
                    ob_start();
                    echo "Profile picture updated"; 
            } else {
                echo "Failed to save image.";
            }
        }
}*/
?>