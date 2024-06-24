<?php

    function bbcode($i) {

        $i = strip_tags($i);

        $i = htmlentities($i);

        $s = array(

            '/\[b\](.*?)\[\/b\]/is',

            '/\[i\](.*?)\[\/i\]/is',

            '/\[u\](.*?)\[\/u\]/is',

            '/\[s\](.*?)\[\/s\]/is',

            '/\[img\](.*?)\[\/img\]/is',

            '/\[li\](.*?)\[\/li\]/is',

            '/\[url=(.*?)\](.*?)\[\/url\]/is'

        );
        
        $r = array(

            '<b>$1</b>',

            '<i>$1</i>',

            '<u>$1</u>',

            '<s>$1</s>',

            '<img scr="$1" alt="$1" title="$1" onerror="Image not found">',

            '<li>$1</li>',

            '<a href="$1">$2</a>'

        );

        return preg_replace($s,$r,$i);

    }

?>
