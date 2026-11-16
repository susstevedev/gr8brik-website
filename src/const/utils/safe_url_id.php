<?php
/**
 * Creates random URL-safe Ids
 * Similar to Youtube's Id system
 * @param int $l length of id
 */
function random_url_safe(int $l = 11): string {
    //each 3 bytes becomes 4 base64 chars
    //int before ceil to convert from float
    $bytes = random_bytes((int)ceil($l * 3 / 4));

    //make url safe
    $b64 = strtr(base64_encode($bytes), '+/', '-_');

    return substr($b64, 0, $l);
}
?>