<?php
// modified version of my user agent script on github
$ua_global = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
function get_browser_name($user_agent) {
    if (strpos($user_agent, 'Opera') || strpos($user_agent, 'OPR/')) return 'opera';
    elseif (strpos($user_agent, 'Edge') || strpos($user_agent, 'Edg/')) return 'Microsoft Edge';
    elseif (strpos($user_agent, 'Chrome')) return 'Google Chrome';
    elseif (strpos($user_agent, 'Safari') && strpos($user_agent, 'Linux')) return 'Webkit';
    elseif (strpos($user_agent, 'Safari') && strpos($user_agent, 'Windows')) return 'Webkit';
    elseif (strpos($user_agent, 'Safari') && strpos($user_agent, 'Macintosh')) return 'Apple Safari';
    elseif (strpos($user_agent, 'Safari') && strpos($user_agent, 'Mac OS X')) return 'Apple Safari';
    elseif (strpos($user_agent, 'Mypal/6') || strpos($user_agent, 'Mypal/7')) return 'Mypal';
    elseif (strpos($user_agent, 'PaleMoon') || strpos($user_agent, 'Mypal/2')) return 'Pale Moon';
    elseif (strpos($user_agent, 'r3dfox')) return 'r3dfox';
    elseif (strpos($user_agent, 'Firefox')) return 'Mozilla Firefox';
    elseif (strpos($user_agent, 'MSIE') || strpos($user_agent, 'Trident/7')) return 'Internet Explorer';
    
    return 'unknown';
}

function get_system_name($user_agent) {
    if (stripos($user_agent, 'FreeBSD') !== false) return 'FreeBSD';
	elseif (stripos($user_agent, 'Macintosh') !== false || stripos($user_agent, 'Mac OS X') !== false) return 'MacOS';
    elseif (stripos($user_agent, 'Ubuntu') !== false) return 'Ubuntu';
	elseif (stripos($user_agent, 'Linux') !== false) return 'Linux-based';
    
    elseif (strpos($user_agent, 'Windows NT 5.1') !== false) return 'Windows XP';
    elseif (strpos($user_agent, 'Windows NT 5.2') !== false) return 'Windows XP or Server 2003';
    elseif (strpos($user_agent, 'Windows NT 6.0') !== false) return 'Windows Vista';
    elseif (stripos($user_agent, 'Windows NT 6.1') !== false) return 'Windows 7';
    elseif (stripos($user_agent, 'Windows NT 6.2') !== false) return 'Windows 8';
    elseif (stripos($user_agent, 'Windows NT 6.3') !== false) return 'Windows 8.1';
	elseif (stripos($user_agent, 'Windows NT 10.0') !== false) return 'Windows 10';
    elseif (stripos($user_agent, 'Windows NT') !== false && max(10, (int)$user_agent) !== false) return 'Newer than Windows 10';
    
    return 'unknown';
}

function ip_subnet_same(string $ip1, string $ip2): bool {
    if ($ip1 === $ip2) { 
        return true;
    }

    if (filter_var($ip1, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && filter_var($ip2, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $parts1 = explode('.', $ip1);
        $parts2 = explode('.', $ip2);
        return ($parts1[0] === $parts2[0] && $parts1[1] === $parts2[1] && $parts1[2] === $parts2[2]);
    }

    if (filter_var($ip1, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && filter_var($ip2, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $hex1 = explode(':', $ip1);
        $hex2 = explode(':', $ip2);
        return ($hex1[0] === $hex2[0] && $hex1[1] === $hex2[1] && $hex1[2] === $hex2[2] && $hex1[3] === $hex2[3]);
    }

    return false;
}

define("UA", get_browser_name($ua_global) . ", " . get_system_name($ua_global));
$user_agent = UA;
?>