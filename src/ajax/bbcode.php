<?php

/**
 * BBCode to HTML converter
 *
 * Created by Kai Mallea (kmallea@gmail.com), modified by susstevedev (mail@gr8brik.rf.gd) for Gr8brik.rf.gd
 *
 * Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
 */

class BBCode
{
  protected $bbcode_table = array();

  public function __construct()
  {

    // Replace [code]...[/code] with <pre><code>...</code></pre>
    $this->bbcode_table["/\[code\](.*?)\[\/code\]/is"] = function ($match) {
      //$escapedCode = preg_replace('/\[\/?([^\]]+)\]/', '&#91;$1&#93;', $match[1]);
      $escapedCode = str_replace(['[',']'],['&#91;','&#93;'],$match[1]);
      return "<pre style='background-color:grey;color:white;padding:2px;'><code>$escapedCode</code></pre>";
    };


    // Replace [email]...[/email] with <a href="mailto:...">...</a>
    $this->bbcode_table["/\[email\](.*?)\[\/email\]/is"] = function ($match) {
      return "<a href=\"mailto:$match[1]\" nofollow ugc noopener noreferrer>htmlspecialchars($match[1])</a>";
    };


    // Replace [email=someone@somewhere.com]An e-mail link[/email] with <a href="mailto:someone@somewhere.com">An e-mail link</a>
    $this->bbcode_table["/\[email=(.*?)\](.*?)\[\/email\]/is"] = function ($match) {
      return "<a href=\"mailto:$match[1]\" nofollow ugc noopener noreferrer>htmlspecialchars($match[2])</a>";
    };


    // Replace @user_id (or @user_name) with <a href="/user/user_id">@user_name</a>
    // Also replace #searchquery with <a href="/list?q=searchquery">#searchquery</a>
    $this->bbcode_table["/(?<!\[url\])(?<!\[img\])(?<!\[)(?<!&)(@|#)([\p{L}\p{N}_\-.]+)(?!\])(?!;)(?!<)(?!>)(?!:)/ui"] = function ($match) {
      if ($match[1] === '@') {
        $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

        if ($conn->connect_error) {
          return "@{$match[2]}";
        }

        $username = $conn->real_escape_string($match[2]);
        $matchResult = $conn->query("SELECT id, username FROM users WHERE username = '$username' OR id = '$username'");

        if ($matchResult && $matchRow = $matchResult->fetch_assoc()) {
          return "<a class=\"gr8-username-embed\" href=\"/user/{$matchRow['id']}\"><b>@{$matchRow['username']}</b></a>";
        } else {
          return "@{$username}";
        }
      } elseif ($match[1] === '#') {
        return "<a href=\"/list.php?q={$match[2]}\"><b>#{$match[2]}</b></a>";
      } else {
        //return "<a href=\"{$match[1]}\" target=\"_blank\">$match[1]</a>";
        return "@{$match[2]}";
      }
    };


    // Replace [b]...[/b] with <strong>...</strong>
    $this->bbcode_table["/\[b\](.*?)\[\/b\]/is"] = function ($match) {
      return "<strong>$match[1]</strong>";
    };


    // Replace [i]...[/i] with <em>...</em>
    $this->bbcode_table["/\[i\](.*?)\[\/i\]/is"] = function ($match) {
      return "<em>$match[1]</em>";
    };


    // Replace [quote]...[/quote] with <blockquote><p>...</p></blockquote>
    $this->bbcode_table["/\[quote\](.*?)\[\/quote\]/is"] = function ($match) {
      return "<blockquote><p>$match[1]</p></blockquote>";
    };


    // Replace [quote="person"]...[/quote] with <blockquote><p>...</p></blockquote>
    $this->bbcode_table["/\[quote=\"([^\"]+)\"\](.*?)\[\/quote\]/is"] = function ($match) {
      return "$match[1] wrote: <blockquote><p>$match[2]</p></blockquote>";
    };


    // Replace [size=30]...[/size] with <span style="font-size:30%">...</span>
    $this->bbcode_table["/\[size=(\d+)\](.*?)\[\/size\]/is"] = function ($match) {
      return "<span style=\"font-size:$match[1]%\">$match[2]</span>";
    };


    // Replace [s] with <del>
    $this->bbcode_table["/\[s\](.*?)\[\/s\]/is"] = function ($match) {
      return "<del>$match[1]</del>";
    };


    // Replace [u]...[/u] with <span style="text-decoration:underline;">...</span>
    $this->bbcode_table["/\[u\](.*?)\[\/u\]/is"] = function ($match) {
      return '<span style="text-decoration:underline;">' . $match[1] . '</span>';
    };


    // Replace [center]...[/center] with <div style="text-align:center;">...</div>
    $this->bbcode_table["/\[center\](.*?)\[\/center\]/is"] = function ($match) {
      return '<div style="text-align:center;">' . $match[1] . '</div>';
    };


    // Replace [color=somecolor]...[/color] with <span style="color:somecolor">...</span>
    $this->bbcode_table["/\[color=([#a-z0-9]+)\](.*?)\[\/color\]/is"] = function ($match) {
      return '<span style="color:' . $match[1] . ';">' . $match[2] . '</span>';
    };

    // Replace [url]...[/url] with <a href="...">...</a>
    $this->bbcode_table["/\[url\](.*?)\[\/url\]/is"] = function ($match) {
      return "<a href=\"$match[1]\" target=\"_blank\" nofollow ugc noopener noreferrer>$match[1]</a>";
    };


    // Replace [url=http://www.google.com/]A link to google[/url] with <a href="http://www.google.com/">A link to google</a>
    $this->bbcode_table["/\[url=(.*?)\](.*?)\[\/url\]/is"] = function ($match) {
      return "<a href=\"$match[1]\" target=\"_blank\" nofollow ugc noopener noreferrer>$match[2]</a>";
    };


    // Replace [img]...[/img] with <img src="..."/>
    $this->bbcode_table["/\[img\](.*?)\[\/img\]/is"] = function ($match) {
      return "<br /><img src=\"$match[1]\"/>";
    };


    // Replace [list]...[/list] with <ul><li>...</li></ul>
    $this->bbcode_table["/\[list\](.*?)\[\/list\]/is"] = function ($match) {
      $match[1] = preg_replace_callback("/\[\*\]([^\[\*\]]*)/is", function ($submatch) {
        return "<li>" . preg_replace("/[\n\r?]$/", "", $submatch[1]) . "</li>";
      }, $match[1]);

      return "<ul>" . preg_replace("/[\n\r?]/", "", $match[1]) . "</ul>";
    };


    // Replace [list=1|a]...[/list] with <ul|ol><li>...</li></ul|ol>
    $this->bbcode_table["/\[list=(1|a)\](.*?)\[\/list\]/is"] = function ($match) {
      if ($match[1] == '1') {
        $list_type = '<ol>';
      } else if ($match[1] == 'a') {
        $list_type = '<ol style="list-style-type: lower-alpha">';
      } else {
        $list_type = '<ol>';
      }

      $match[2] = preg_replace_callback("/\[\*\]([^\[\*\]]*)/is", function ($submatch) {
        return "<li>" . preg_replace("/[\n\r?]$/", "", $submatch[1]) . "</li>";
      }, $match[2]);

      return $list_type . preg_replace("/[\n\r?]/", "", $match[2]) . "</ol>";
    };


    $this->bbcode_table["/\[youtube\]([A-Z0-9\-_]+)\[\/youtube\]/i"] = function ($match) {
      $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http');
      $iframeUrl = "$protocol://www.youtube.com/embed/$match[1]";
      return "<iframe class=\"youtube-player\" type=\"text/html\" width=\"640\" height=\"385\" src=\"$iframeUrl\" frameborder=\"0\"></iframe>";
    };
  }

  public function toHTML($str, $escapeHTML = false, $nr2br = false)
  {
    if (!$str) {
      return "";
    }

    if ($escapeHTML) {
      $str = htmlspecialchars($str);
    }

    foreach ($this->bbcode_table as $key => $val) {
      $str = preg_replace_callback($key, $val, $str);
    }

    if ($nr2br) {
      $str = preg_replace_callback("/\n\r?/", function ($match) {
        return "<br/>";
      }, $str);
    }

    return $str;
  }

  // Source - https://stackoverflow.com/a/5574424
  // Posted by Day
  // Retrieved 2026-07-31, License - CC BY-SA 2.5

  //edited by me

  /*public function Smilify(&$subject)
  {
    $smilies = array(
      ':|'  => 'neutral',
      ':-|' => 'neutral',
      ':-o' => 'e_surprised',
      ':-O' => 'e_surprised',
      ':o'  => 'e_surprised',
      ':O'  => 'e_surprised',
      ';)'  => 'e_wink',
      ';-)' => 'e_wink',
      ':p'  => 'razz',
      ':-p' => 'razz',
      ':P'  => 'razz',
      ':-P' => 'razz',
      ':D'  => 'e_biggrin',
      ':-D' => 'e_biggrin',
      '8)'  => 'cool',
      '8-)' => 'cool',
      ':)'  => 'e_smile',
      ':-)' => 'e_smile',
      ':('  => 'e_sad',
      ':-(' => 'e_sad',
    );

    $sizes = array(
      'e_biggrin' => 18,
      'cool' => 20,
      'haha' => 20,
      'neutral' => 20,
      'e_surprised' => 20,
      'e_sad' => 20,
      'e_smile' => 18,
      'razz' => 20,
      'e_wink' => 20,
    );

    $replace = array();
    foreach ($smilies as $smiley => $imgName) {
      $size = $sizes[$imgName];
      array_push($replace, '<img src="/img/emote/icon_' . $imgName . '.gif" data-textog="' . $subject . '" title="emojis are from phpbb" alt="' . $smiley . '" xwidth="' . $size . '" xheight="' . $size . '" />');
    }
    $subject = str_replace(array_keys($smilies), $replace, $subject);
    return $subject;
  }*/

  public function Smilify(&$subject)
  {
    $smilies = array(
      ':|'  => 'neutral',
      ':-|' => 'neutral',
      ':-o' => 'e_surprised',
      ':-O' => 'e_surprised',
      ':o'  => 'e_surprised',
      ':O'  => 'e_surprised',
      ';)'  => 'e_wink',
      ';-)' => 'e_wink',
      ':p'  => 'razz',
      ':-p' => 'razz',
      ':P'  => 'razz',
      ':-P' => 'razz',
      ':D'  => 'e_biggrin',
      ':-D' => 'e_biggrin',
      '8)'  => 'cool',
      '8-)' => 'cool',
      ':)'  => 'e_smile',
      ':-)' => 'e_smile',
      ':('  => 'e_sad',
      ':-(' => 'e_sad',
      ':-?'         => 'e_confused',
      ':?'          => 'e_confused',
      ':geek:'      => 'e_geek',
      ':ubergeek:'  => 'e_ugeek',
      ':shock:'     => 'eek',
      ':lol:'       => 'lol',
      ':mad:'       => 'mad',
      ':cry:'       => 'cry',
      ':evil:'      => 'evil',
      ':twisted:'   => 'twisted',
      ':roll:'      => 'rolleyes',
      ':oops:'      => 'redface',
      ':mrgreen:'   => 'mrgreen',
      ':idea:'      => 'idea',
      ':arrow:'     => 'arrow',
      ':!:'         => 'exclaim',
      ':?:'         => 'question',
    );

    $widths = array(
      'neutral'     => 15,
      'e_surprised' => 15,
      'e_wink'      => 15,
      'razz'        => 15,
      'e_biggrin'   => 15,
      'cool'        => 15,
      'e_smile'     => 15,
      'e_sad'       => 15,
      'e_confused'  => 15,
      'e_geek'      => 15,
      'e_ugeek'     => 15,
      'eek'         => 15,
      'lol'         => 15,
      'mad'         => 15,
      'cry'         => 15,
      'evil'        => 15,
      'twisted'     => 15,
      'rolleyes'    => 15,
      'redface'     => 15,
      'mrgreen'     => 15,
      'idea'        => 15,
      'arrow'       => 15,
      'exclaim'     => 15,
      'question'    => 15,
    );

    $heights = array(
      'neutral'     => 17,
      'e_surprised' => 17,
      'e_wink'      => 17,
      'razz'        => 17,
      'e_biggrin'   => 17,
      'cool'        => 17,
      'e_smile'     => 17,
      'e_sad'       => 17,
      'e_confused'  => 17,
      'e_geek'      => 17,
      'e_ugeek'     => 17,
      'eek'         => 17,
      'lol'         => 17,
      'mad'         => 17,
      'cry'         => 17,
      'evil'         => 17,
      'twisted'     => 17,
      'rolleyes'    => 17,
      'redface'     => 17,
      'mrgreen'     => 17,
      'idea'        => 17,
      'arrow'       => 17,
      'exclaim'     => 17,
      'question'    => 17,
    );

    $replace = array();
    foreach ($smilies as $smiley => $imgName) {
      $w = $widths[$imgName] * 1.2;
      $h = $heights[$imgName] * 1.2;
      array_push($replace, '<img src="/img/emote/icon_' . $imgName . '.gif" title="emojis are from phpbb" alt="' . $smiley . '" width="' . $w . '" height="' . $h . '" />');
    }
    $subject = str_replace(array_keys($smilies), $replace, $subject);
    return $subject;
  }
}
