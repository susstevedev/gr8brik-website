<div class="w3-card-2 w3-light-grey w3-padding-tiny w3-center">
    <?php 
		if($alert > 0){
			echo '<h2 style="color:red;">' . $alert  . ' inbox</h2>';
		}
        echo '<h4>Welcome,</h4><h2 class="usertext">@' . $user . '<a href="/acc/">';
        echo '<i class="fa fa-pencil" aria-hidden="true"></i></a></h2>';
    ?>
    <div class="w3-hide-small w3-show-medium">
        <a href="/@<?php echo $user ?>" class="w3-btn w3-large w3-white w3-hover-blue w3-mobile w3-border"><li class="fa fa-user" aria-hidden="true"></li><b>PROFILE</b></a>
        <a href="/acc/new" class="w3-btn w3-large w3-white w3-hover-blue w3-mobile w3-border"><i class="fa fa-bell" aria-hidden="true"></i><b>NOTIFICATIONS</b></a>
        <a href="/acc/creations" class="w3-btn w3-large w3-white w3-hover-blue w3-mobile w3-border"><i class="fa fa-building" aria-hidden="true"></i><b>MY CREATIONS</b></a>
        <a href="/acc/following" class="w3-btn w3-large w3-white w3-hover-blue w3-mobile w3-border"><i class="fa fa-user-plus" aria-hidden="true"></i><b>PEOPLE</b></a>
        <?php
            if($admin === '1'){
                echo '<br /><h2>Mod Tools</h2>';
                echo '<a href="/acc/appeals" class="w3-btn w3-large w3-white w3-hover-blue w3-mobile w3-border"><i class="fa fa-address-book" aria-hidden="true"></i><b>BAN APPEALS</b></a>';
            }
        ?>
    </div>
</div><br /><br />