<!DOCTYPE html>
<?php
    require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';

?>
<head>
    <title>Privacy Policy</title>
    <?php include 'header.php' ?>
</head>
<body class="w3-light-blue w3-container">
<?php include('navbar.php') ?>
<center><br /><br />

<h4>Last updated <?php echo gmdate("M d, Y H:i",filemtime('privacy.php')); ?></h4>

<p>At Gr8brik, accessible from <a href="http://<?php echo $_SERVER['HTTP_HOST'] ?>"><?php echo $_SERVER['HTTP_HOST'] ?></a>, one of our main priorities is the privacy of our visitors. This Privacy Policy document contains types of information that is collected and recorded by Gr8brik and how we use it.</p>

<p>If you have additional questions or require more information about our Privacy Policy, do not hesitate to <a href="mailto:<?php echo DB_MAIL ?>">contact us</a>. Our Privacy Policy was created with the help of the <a href="http://www.generateprivacypolicy.com/">Privacy Policy Generator</a>.</p>

<h2>1. Log Files</h2>

<p>Gr8brik follows a standard procedure of using log files. These files log visitors when they visit websites. All hosting companies do this and a part of hosting services' analytics. The information collected by log files include internet protocol (IP) addresses, browser type, Internet Service Provider (ISP), date and time stamp, referring/exit pages, and possibly the number of clicks. These are not linked to any information that is personally identifiable. The purpose of the information is for analyzing trends, administering the site, tracking users' movement on the website, and gathering demographic information.</p>

<p>In addition, we also store IP addresses in our database when users Login or Register an account on our services.</p>

<h2>2. Cookies</h2>

<p>Like any other website, Gr8brik uses "cookies". These cookies are used to store information including visitors' preferences, and the pages on the website that the visitor accessed or visited. The information is used to optimize the users' experience by customizing our web page content based on visitors' browser type and/or other information.</p>

<h2>3. Children's Information</h2>

<p>Another part of our priority is adding protection for children while using the internet. We encourage parents and guardians to observe, participate in, and/or monitor and guide their online activity.</p>

<p>Gr8brik does not knowingly collect any Personal Identifiable Information from children under the age of 13. If you think that your child provided this kind of information on our website, we strongly encourage you to contact us immediately and we will do our best efforts to promptly remove such information from our records.</p>
    
<h2>4. Data removal</h2>
    
<p>If you have registered an account on Gr8brik, you have the right to remove it and anonymize public information like Creations or forum posts.</p>

<p>To do so you can go to <a href="http://<?php echo $_SERVER['HTTP_HOST'] ?>/acc">http://<?php echo $_SERVER['HTTP_HOST'] ?>/acc</a> and click "Deactivate account" and follow the instruction there, or <a href="mailto:<?php echo DB_MAIL ?>">contact us</a>.</p>

<h2>5. Consent</h2>

<p>By using our website, you hereby consent to our Privacy Policy and agree to its <a href="http://<?php echo $_SERVER['HTTP_HOST'] ?>/terms.php">Terms and Conditions.</a></p>
<hr />
</center><br /><br />
<?php include('linkbar.php') ?>
</body>
</html>