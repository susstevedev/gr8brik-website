<?php
use Astrotomic\Twemoji\Twemoji;

require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/mysql.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/const/Autoloader.php';

/**
 * Class for formatting
 */
class Formatter
{
    /**
     * We need to excape html, do markdown, and then do twemoji (Astrotomic/php-twemoji)
     * We use jbroadway/Slimdown for markdown
     * It should be installed in the "lib" folder under a path like:
     * /src/lib/jbroadway/*
     * and
     * /src/lib/Astrotomic/*
     * michelf/php-markdown is also an option
     * all files should be extracted under /src/lib/Michelf/*
     */
    public static function render(string $text): string 
    {
        try {
            //excape
            $text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);

            //username
            $text = self::username($text, 'id');

            //emoji
            $text = Twemoji::text($text)->png()->toHtml();

            //markdown
            $text = Slimdown::render($text);

            return $text;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Prepares text to be inserted into DB
     * To be later parsed with ::render
     */
    public static function prepare(string $text): string
    {
        try {
            //usernames
            $text = self::username($text, 'name');

            return $text;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    private static function username(string $str, string $type): string {
        $db = Database::get(DB_NAME);
        $map = [];

        switch ($type) {
            case 'id':
                $regex = '/userid:([0-9]+)/';
                $sql = 'SELECT id, username FROM users WHERE id';
                //$formatted_str = '[**@%s**](/user/%s)';
                $formatted_str = '<a href="/user/%s" class="user-mention" data-username="%s">@%s</a>';
                break;
            case 'name':
                $regex = '/@([a-zA-Z0-9_]+)/';
                $sql = 'SELECT id, username FROM users WHERE username';
                $formatted_str = 'userid:%s';
                break;
            default:
                return $str;
        }

        if (preg_match_all($regex, $str, $matches) && !empty($matches[1])) {
            $vals = array_unique($matches[1]);
            $placeholders = implode(',', array_fill(0, count($vals), '?'));
            $stmt = $db->prepare($sql . " IN ($placeholders)");

            if ($stmt && $stmt->execute($vals)) {
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    $key = ($type === 'id') ? $row['id'] : $row['username'];
                    $map[$key] = $row;
                }

                return preg_replace_callback($regex, function($match) use ($map, $formatted_str, $type) {
                    if (isset($map[$match[1]])) {
                        $row = $map[$match[1]];
                        //$fill = ($type === 'id') ? [$row['username'], $row['id']] : [$row['id']];
                        $fill = ($type === 'id') ? [$row['id'], $row['username'], $row['username']] : [$row['id']];

                        return vsprintf($formatted_str, $fill);
                    }

                    return $match[0];
                }, $str);
            }
        }

        return $str;
    }
}
?>