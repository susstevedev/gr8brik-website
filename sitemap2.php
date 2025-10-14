<?php
$base = 'https://gr8brik.rf.gd/';
$dir = __DIR__;

$exclude = ['sitemap2.php', 'robots.txt', 'google4cb09ae7ae2edb25.html', '.htaccess', '/acc/ajax/constants.php'];
$exclude_mini = ['/acc/users/sessions/', '/com/old/', '/acc/classes/', '/cre/'];

header('Content-Type: application/xml; charset=utf-8');

$files = scandir($dir);
read($dir, $exclude, $exclude_mini);

/*$filtered = array_filter($files, function($file) use ($excluded) {
    if(is_file($file) && !in_array($file, $excluded)) {
        return $file;
    } elseif(is_dir($file) && !in_array($file, $excluded)) {
        $files = array_diff(scandir($file));
    }
}); */

/* function read() {
    global $files, $file, $excluded;
    $all_files = [];

    $filtered = array_filter($files, function($file) use ($excluded) {
        if(is_file($file) && !in_array($file, $excluded)) {
            //return $file;
            $all_files[] = $file;
        } elseif(is_dir($file) && !in_array($file, $excluded)) {
            $files = array_diff(scandir($file));
            read();
        }
    });
} */

function read($dir, $exclude, $exclude_mini) {
    $result = [];

    foreach (scandir($dir) as $file) {
        if ($file === '.' || $file === '..' || in_array($file, $exclude)) {
            continue;
        }

        $path = $dir . '/' . $file;

            foreach ($exclude_mini as $pattern) {
                if (strpos($path, $pattern) !== false) {
                    continue 2;
                }
            }

        if (is_file($path)) {
            $result[] = $path;
        } elseif (is_dir($path)) {
            $result = array_merge($result, read($path, $exclude, $exclude_mini));
        }
    }
    return $result;
}

$files = read($dir, $exclude, $exclude_mini);

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($files as $file): ?>
  <url>
    <loc><?php echo htmlspecialchars($base . urldecode(str_replace("/home/vol1000_8/infinityfree.com/if0_36019408/gr8brik.rf.gd/htdocs/", "", $file))) ?></loc>
    <lastmod><?php echo date('Y-m-d', filemtime($file)) ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
<?php endforeach; ?>
</urlset>