<div class="gr8-theme w3-container w3-card-2 w3-light-grey w3-padding-small w3-center">
    <?php
		if($alert > 0){
			echo '<h2 style="color:red;">' . $alert  . ' new notifications</h2>';
		}
        echo '<h4>Hello,</h4><h2><span class="usertext">' . $user . '</span><a href="/acc/">';
        echo '<i class="fa fa-pencil" aria-hidden="true"></i></a></h2>';
    ?>
    <button class="w3-hide-large w3-btn w3-large w3-white w3-hover-blue w3-mobile w3-border" onclick='document.getElementById("gr8-drawer").classList.toggle("w3-hide-small");'>Show/hide drawer</button><br />
    <div id="gr8-drawer" class="w3-hide-small gr8-drawer">
        <a href="/@<?php echo $user ?>" class="w3-btn w3-large w3-white w3-hover-blue w3-mobile w3-border"><li class="fa fa-user" aria-hidden="true"></li>Profile</a>&nbsp;
        <a href="/acc/new" class="w3-btn w3-large w3-white w3-hover-blue w3-mobile w3-border"><i class="fa fa-inbox" aria-hidden="true"></i>Notifications</a>&nbsp;
        <a href="/acc/creations" class="w3-btn w3-large w3-white w3-hover-blue w3-mobile w3-border"><i class="fa fa-building" aria-hidden="true"></i>My Creations</a>&nbsp;
        <a href="/acc/following" class="w3-btn w3-large w3-white w3-hover-blue w3-mobile w3-border"><i class="fa fa-user-plus" aria-hidden="true"></i>People</a>&nbsp;
        <?php
            if($admin === '1'){
                echo '<br /><h4>Moderator tools</h4>';
                echo '<a href="/acc/appeals" class="w3-btn w3-large w3-white w3-hover-blue w3-mobile w3-border"><i class="fa fa-address-book" aria-hidden="true"></i>Ban Appeals</a>&nbsp;';
                echo '<a href="/acc/reported" class="w3-btn w3-large w3-white w3-hover-blue w3-mobile w3-border"><i class="fa fa-flag" aria-hidden="true"></i>Reported Creations</a>';
            }
        ?>
    </div>
</div><br /><br />