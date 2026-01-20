<div class="gr8-theme w3-container w3-card-2 w3-light-grey w3-padding w3-round w3-center">
    <?php if($users_row['alert'] > 0) { ?>
			<h2 style="color:red;"><?php echo $users_row['alert'] ?> new notifications</h2>
	<?php } ?>
	<span><h4>Hello, </h4><span class="usertext"><h2><?php echo $users_row['username'] ?></h2></span></span>

	<a href='/acc/' class='w3-btn w3-hover-opacity w3-white w3-border w3-padding-small w3-round-small'><i class='fa fa-pencil' aria-hidden='true'></i>Account Settings</a>&nbsp;<br />

    <button class="w3-hide-large w3-btn w3-hover-opacity w3-large w3-white w3-mobile w3-border w3-padding w3-round" onclick='document.getElementById("gr8-drawer").classList.toggle("w3-hide-small");'>Show/hide drawer</button><br />

    <div id="gr8-drawer" class="w3-hide-small gr8-drawer">
        <a href="/user/<?php echo $id ?>" class="gr8-panelbtn gr8-panelbtn-profile w3-btn w3-hover-opacity w3-large w3-white w3-mobile w3-border w3-padding w3-round">
            <li class="fa fa-user-o" aria-hidden="true"></li>
            My Profile
        </a>&nbsp;
        
        <a href="/acc/new" class="gr8-panelbtn gr8-panelbtn-notifications w3-btn w3-hover-opacity w3-large w3-white w3-mobile w3-border w3-padding w3-round">
            <i class="fa fa-bell-o" aria-hidden="true"></i>
      		Notifications 
        	<?php if($users_row['alert'] > 0) { 
    			echo "<span style='color:red;'>" . $users_row['alert']  . "</span>";
			} ?>
        </a>&nbsp;
        
        <a href="/acc/creations" class="gr8-panelbtn gr8-panelbtn-mycreations w3-btn w3-hover-opacity w3-large w3-white w3-mobile w3-border w3-padding w3-round">
            <i class="fa fa-th" aria-hidden="true"></i>
            My Creations
        </a>&nbsp;
        
        <a href="/acc/following" class="gr8-panelbtn gr8-panelbtn-people w3-btn w3-hover-opacity w3-large w3-white w3-mobile w3-border w3-padding w3-round">
            <i class="fa fa-bullhorn" aria-hidden="true"></i>
            Social (formally People)
        </a>&nbsp;
        
        <a href="/acc/logins" class="gr8-panelbtn gr8-panelbtn-sessions w3-btn w3-hover-opacity w3-large w3-white w3-mobile w3-border w3-padding w3-round">
            <i class="fa fa-shield" aria-hidden="true"></i>
            Sessions
        </a>&nbsp;
        
        <?php if(isset($users_row['admin']) && $users_row['admin'] == 1) { ?>
        	<br /><h4>Moderator tools</h4>
        	<a href="/acc/appeals" class="gr8-panelbtn gr8-panelbtn-banappeals w3-btn w3-hover-opacity w3-large w3-white w3-mobile w3-border w3-padding w3-round">
                <i class="fa fa-address-book-o" aria-hidden="true"></i>
                Ban Appeals
			</a>&nbsp;
        
        	<a href="/acc/reported" class="gr8-panelbtn gr8-panelbtn-reportedcreations w3-btn w3-hover-opacity w3-large w3-white w3-mobile w3-border w3-padding w3-round">
                <i class="fa fa-flag-o" aria-hidden="true"></i>
                Reported Creations
        	</a>
        <?php } ?>
    </div>
</div><br /><br />