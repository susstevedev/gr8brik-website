<div class="gr8-theme w3-container w3-card-2 w3-light-grey w3-padding-small w3-center">
    <?php
		if($users_row['alert'] > 0) {
			echo '<h2 style="color:red;">' . $users_row['alert']  . ' new notifications</h2>';
		}
        echo '<h4>Hello,</h4><h2><span class="usertext">' . $user . '</span><a href="/acc/">';
        echo '<i class="fa fa-pencil" aria-hidden="true"></i></a></h2>';
    ?>
    <button class="w3-hide-large w3-btn w3-large w3-white w3-hover-blue w3-mobile w3-border" onclick='document.getElementById("gr8-drawer").classList.toggle("w3-hide-small");'>Show/hide drawer</button><br />
    <div id="gr8-drawer" class="w3-hide-small gr8-drawer">
        <a href="/user/<?php echo $id ?>" class="gr8-panelbtn gr8-panelbtn-profile w3-btn w3-large w3-white w3-hover-blue w3-mobile w3-border"><li class="fa fa-user" aria-hidden="true"></li>Profile</a>&nbsp;
        <a href="/acc/new" class="gr8-panelbtn gr8-panelbtn-notifications w3-btn w3-large w3-white w3-hover-blue w3-mobile w3-border"><i class="fa fa-inbox" aria-hidden="true"></i>
        Notifications <?php if($users_row['alert'] > 0) { echo "<span style='color:red;'>" . $users_row['alert']  . "</span>"; } ?></a>&nbsp;
        <a href="/acc/creations" class="gr8-panelbtn gr8-panelbtn-mycreations w3-btn w3-large w3-white w3-hover-blue w3-mobile w3-border"><i class="fa fa-building" aria-hidden="true"></i>My Creations</a>&nbsp;
        <a href="/acc/following" class="gr8-panelbtn gr8-panelbtn-people w3-btn w3-large w3-white w3-hover-blue w3-mobile w3-border"><i class="fa fa-user-plus" aria-hidden="true"></i>People</a>&nbsp;
        <a href="/acc/logins" class="gr8-panelbtn gr8-panelbtn-sessions w3-btn w3-large w3-white w3-hover-blue w3-mobile w3-border"><i class="fa fa-lock" aria-hidden="true"></i>Sessions</a>&nbsp;
        <?php
            if($admin === '1'){
                echo '<br /><h4>Moderator tools</h4>';
                echo '<a href="/acc/appeals" class="gr8-panelbtn gr8-panelbtn-banappeals w3-btn w3-large w3-white w3-hover-blue w3-mobile w3-border"><i class="fa fa-address-book" aria-hidden="true"></i>Ban Appeals</a>&nbsp;';
                echo '<a href="/acc/reported" class="gr8-panelbtn gr8-panelbtn-reportedcreations w3-btn w3-large w3-white w3-hover-blue w3-mobile w3-border"><i class="fa fa-flag" aria-hidden="true"></i>Reported Creations</a>';
            }
        ?>
    </div>
</div><br /><br />