<p>this is just a test</p><br />

<b>sussteve226%3Ae454f891c46a20dd010c81d701113ad61555701b8558ac431094f0f2f69f48a91e4abee295779eccf753cf8c199e5799b080734d8c91d92e802ffa66c914bd57</b>

<p>also here is another test</p><br />

<?php
    $emptyPassword = "";
    $hash = password_hash($emptyPassword, PASSWORD_DEFAULT);
    echo "<b>" . $hash . "</b>";
?>
