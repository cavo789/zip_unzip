<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('html_errors', '1');
ini_set('docref_root', 'http://www.php.net/');
ini_set('error_prepend_string', "<div style='color:red; font-family:verdana; border:1px solid red; padding:5px;'>");
ini_set('error_append_string', '</div>');
error_reporting(E_ALL);

/*
  * AVONTURE Christophe - https://www.avonture.be
  *
  * Unzip a .zip file ini the current folder
  *
  * When AUTO_DELETE_ONCE_UNCOMPRESSED is set to true, the zip file is deleted once unzipped
  */

define('AUTO_DELETE_ONCE_UNCOMPRESSED', true);
define('REPO', 'https://github.com/cavo789/zip_unzip');

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta name="viewport" content="width=device-width, initial-scale=1">
   <link href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>

   <div class="container">
      <div class="jumbotron">
         <div class="container"><h1>Unzip</h1></div>
      </div>

<?php

ini_set('max_execution_time', '0');
ini_set('set_time_limit', '0');

// Get the list of ZIP files in the current folder
$dir  = '.';
$dh   = opendir($dir);
$files=null;

while (false !== ($filename = readdir($dh))) {
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    if ('zip' == $ext) {
        $files[] = $filename;
    }
}

if (null != $files) {
    sort($files);
}

// And, if more than one, uncompress files one by one

if (count($files) > 0) {
    echo '<p>There are ' . count($files) . ' to decompress...</p>';
    $i=0;

    foreach ($files as $file) {
        $i++;

        $zip = new ZipArchive();
        $res = $zip->open($file);

        if (true === $res) {
            $zip->extractTo('./');
            $zip->close();

            echo '<h2 class="text-success">' . $i . '. ' . $file . ' has been extracted.</h2>';
            if (AUTO_DELETE_ONCE_UNCOMPRESSED) {
                unlink($file);
            }
        } else {
            echo '<h2 class="text-danger">' . $i . '. ' . $file . ' - Failure detected during the extraction.</h2>';
        }
    }
} else {
    echo '<p>No zip files found in ' . __DIR__ . '</p>';
}

// This file is no more needed
unlink(__FILE__);

?>
   </div>
</body>
</html>
