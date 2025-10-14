<?php
// modified version of my user agent script on github
    
function get_browser_name($user_agent) {
    if (strpos($user_agent, 'Opera') || strpos($user_agent, 'OPR/')) return 'opera';
    elseif (strpos($user_agent, 'Edge') || strpos($user_agent, 'Edg/')) return 'Microsoft Edge';
    elseif (strpos($user_agent, 'Chrome')) return 'Google Chrome';
    elseif (strpos($user_agent, 'Safari')) return 'Apple Safari';
    elseif (strpos($user_agent, 'Mypal/6') || strpos($user_agent, 'Mypal/7')) return 'Actual Mypal';
    elseif (strpos($user_agent, 'PaleMoon') || strpos($user_agent, 'Mypal/2')) return 'Pale Moon';
    elseif (strpos($user_agent, 'r3dfox')) return 'r3dfox';
    elseif (strpos($user_agent, 'Firefox')) return 'Mozilla Firefox';
    elseif (strpos($user_agent, 'MSIE') || strpos($user_agent, 'Trident/7')) return 'Internet Explorer';
    
    return 'unknown';
}

define("UA", get_browser_name($_SERVER['HTTP_USER_AGENT']));
$user_agent = UA;

?>