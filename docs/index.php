<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/bbcode.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/ltmp.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Documentation</title>
    <?php include '../header.php' ?>
</head>

<body class="w3-light-blue w3-container">
       <?php 
    		//include '../navbar.php';
    		echo '<a href="/" class="w3-top w3-left"><img src="/img/logo/192.png" style="width: 25px; height: 25px; border-radius: 15px;">Home</a>';
    	?>
    
		<div style="margin-left: 25% !important">
            <input class="w3-input w3-border w3-threequarter" value="<?php if (isset($_GET['q'])) { echo $_GET['q']; } ?>" type="text" id="search-input-2" placeholder="search for...">
            <button class="w3-btn w3-large w3-white w3-hover-blue w3-mobile w3-border w3-quarter" id="search-button-2"><i class="fa fa-search" aria-hidden="true"></i></button><br /><br />
            
            <?php
       			if(isset($_GET['view_p'])) {
                    if($_GET['view_p'] != null) {
                        $view_p = htmlentities($_GET['view_p'], ENT_QUOTES, 'UTF-8');
                        $content = file_get_contents('content/' . $view_p);
                        $content_parsed = null;
                        $lines = file('content/' . $view_p);
                        
                        foreach($lines as $line) {
  							if(strpos($line, "PARSER_USE_BBCODE") !== false) {
                                $begin = '<strong>Content is rendered in BBCode.</strong> Last modified <em>' . date("D, M d, Y h:i A", filemtime('content/' . $view_p)) . '</em>.<br />';
    							$bbcode = new BBCode;
                        		$content_parsed = $bbcode->toHTML($content);
                                break;
                            } else {
                                $begin = '<strong>Content is rendered in <a href="https://github.com/EymenWinnerYT/LTMP">LTMP</a>.</strong> Last modified <em>' . date("D, M d, Y h:i A", filemtime('content/' . $view_p)) . '</em>.<br />';
                                $ltmp = new LTMP();
                                $content_parsed = $content_parsed . $ltmp->parse($line);
                                if($ltmp->parse_error){
                                 	$content_parsed = $content_parsed . $line;
                                }
                            }
						}
                        echo '<pre class="main-document ltmp-bbcode-parsed">' . $begin . $content_parsed . '</pre>';
                    }
                } else {
                    echo '<b style="color: #d3d3d3;">No document active.</b>';
                }
           	?>
    	</div>
    
		<div style="width: 25%; position: fixed; left: 0; top: 0;">
            <br /><table class="gr8-theme w3-table w3-card-2 w3-light-grey" style="color: black;">
            <thead>
                <tr>
                    <th>Post file name</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
            <?php
               	// Source - https://stackoverflow.com/a/48444582
                // Posted by Vladimir
                // Retrieved 2025-11-11, License - CC BY-SA 3.0

                $files = scandir('content');
                foreach ($files as $file) {
                    //if (strpos('to-dlkkl', $file) !== false) {
                        if(!is_dir($file)) {
                             echo '<tr><td><a href="index?view_p=' . $file . '">' . str_replace('.txt', '', $file) . '</a></td><td>' . date("D, M d, Y h:i A", filemtime('content/' . $file)) . '</td></tr>';
                        }
                    //}
                }
            ?>
            </tbody></table>
    	</div>
    
      <script>
          /*
          	Fix for parser flags
          */
          console.log("[DOM] JavaScript is supported."); 
          const main = document.querySelector(".main-document").innerHTML;
          document.querySelector(".main-document").innerHTML = main.replace("PARSER_USE_BBCODE", "");
      </script>
    	
    <div style="height: 500px"></div>
        
    <?php include '../linkbar.php' ?>

</body>
</html>