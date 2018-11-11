<?php

/**
 * Author : AVONTURE Christophe - https://www.avonture.be.
 *
 * Compress a folder (subfolders included). Copy this script in any of your FTP folder
 * (your root folder or a subfolder) and then just call the script like f.i. http://yoursite/zip.php
 *
 * This script is "raw" : will try to make the ZIP file but will not work a huge website or
 * slow hoster. You can therefore encounters errors like, f.i., CPU timeout.
 */

define('DEBUG', true);
define('REPO', 'https://github.com/cavo789/zip_unzip');

set_time_limit(0);

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

class aeSecureFct
{
    /**
     * Safely read posted variables.
     *
     * @param type  $name    f.i. "password"
     * @param type  $type    f.i. "string"
     * @param type  $default f.i. "default"
     * @param mixed $base64
     *
     * @return type
     */
    public static function getParam($name, $type = 'string', $default = '', $base64 = false)
    {
        $tmp   ='';
        $return=$default;

        if (isset($_POST[$name])) {
            if (in_array($type, ['int', 'integer'])) {
                $return=filter_input(INPUT_POST, $name, FILTER_SANITIZE_NUMBER_INT);
            } elseif ('boolean' == $type) {
                // false = 5 characters
                $tmp   =substr(filter_input(INPUT_POST, $name, FILTER_SANITIZE_STRING), 0, 5);
                $return=(in_array(strtolower($tmp), ['on', 'true'])) ? true : false;
            } elseif ('string' == $type) {
                $return=filter_input(INPUT_POST, $name, FILTER_SANITIZE_STRING);
                if (true === $base64) {
                    $return=base64_decode($return);
                }
            } elseif ('unsafe' == $type) {
                $return=$_POST[$name];
            }
        } else { // if (isset($_POST[$name]))
            if (isset($_GET[$name])) {
                if (in_array($type, ['int', 'integer'])) {
                    $return=filter_input(INPUT_GET, $name, FILTER_SANITIZE_NUMBER_INT);
                } elseif ('boolean' == $type) {
                    // false = 5 characters
                    $tmp   =substr(filter_input(INPUT_GET, $name, FILTER_SANITIZE_STRING), 0, 5);
                    $return=(in_array(strtolower($tmp), ['on', 'true'])) ? true : false;
                } elseif ('string' == $type) {
                    $return=filter_input(INPUT_GET, $name, FILTER_SANITIZE_STRING);
                    if (true === $base64) {
                        $return=base64_decode($return);
                    }
                } elseif ('unsafe' == $type) {
                    $return=$_GET[$name];
                }
            }
        }

        if ('boolean' == $type) {
            $return=(in_array($return, ['on', '1']) ? true : false);
        }

        return $return;
    }
}

class aeSecureFiles
{
    public static function get_files($folder, $include_subs = false)
    {
        // Remove any trailing slash
        if ('/' == substr($folder, -1)) {
            $folder = substr($folder, 0, -1);
        }

        // Make sure a valid folder was passed
        if (!file_exists($folder) || !is_dir($folder) || !is_readable($folder)) {
            echo '<p class="text-danger">Incorrect folder : ' . $folder . '</p>';

            return false;
            die();
        }

        // Grab a file handle
        $all_files = false;

        if ($handle = opendir($folder)) {
            $all_files = [];

            // Start looping through a folder contents
            while ($file = @readdir($handle)) {
                // Set the full path
                $path = $folder . '/' . $file;

                // Filter out this and parent folder
                if ('.' != $file && '..' != $file) {
                    // Test for a file or a folder
                    if (is_file($path)) {
                        $all_files[] = $path;
                    } elseif ((is_dir($path) && $include_subs) && (('backups' != $file) && ('cache' != $file))) {
                        // Get the subfolder files
                        $subfolder_files = self::get_files($path, true);

                        // Anything returned
                        if ($subfolder_files) {
                            $all_files = array_merge($all_files, $subfolder_files);
                        }
                    }
                }
            }

            // Cleanup
            closedir($handle);
        }

        // Return the file array
        @sort($all_files);

        return $all_files;
    }

    /**
     * Creates a compressed zip file.
     *
     * @param array  $files
     * @param string $destination
     * @param bool   $overwrite
     *
     * @return void
     */
    public static function create_zip($files = [], $destination = '', $overwrite = false)
    {
        $sReturn='';

        //if the zip file already exists and overwrite is false, return false
        if (file_exists($destination) && !$overwrite) {
            return '<p class="text-danger">File ' . $destination . ' already exists.</p>';
        }

        $valid_files = [];

        // Be sure files are well there
        if (is_array($files)) {
            foreach ($files as $file) {
                if (file_exists($file)) {
                    $valid_files[] = $file;
                }
            }
        }

        if (count($valid_files)) {
            //create the archive
            $zip = new ZipArchive();
            if (true !== $zip->open($destination, $overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE)) {
                return '<p class="text-danger">Problem occured by trying to create ' . $destination . '.</p>';
            }

            //add each files to the archive
            foreach ($valid_files as $file) {
                $zip->addFile($file, $file);
            }

            if (file_exists($destination)) {
                $sReturn .= '<h3>' . $destination . ' has been created</h3>';
                if (DEBUG === true) {
                    $sReturn .= '<p class="text-info">The zip archive contains ' . $zip->numFiles . ' files</p>';
                }
            } else {
                $sReturn .= '<p class="text-danger">File is missing : ' . $destination . '.</p>';
            }

            $zip->close();

            return $sReturn;
        } else {
            return '<p class="text-danger">There is nothing to archive.</p>';
        }
    }
}

if (DEBUG === true) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    ini_set('html_errors', '1');
    ini_set('docref_root', 'http://www.php.net/');
    ini_set('error_prepend_string', "<div style='color:red; font-family:verdana; border:1px solid red; padding:5px;'>");
    ini_set('error_append_string', '</div>');
    error_reporting(E_ALL);
} else {
    ini_set('error_reporting', E_ALL & ~E_NOTICE);
}

$task=aeSecureFct::getParam('task', 'string', '', false);

if ('doIt' === $task) {
    //get absolute path of this file
    $abs_path =$_SERVER['SCRIPT_FILENAME'];
    $dir      =dirname($abs_path);
    $files    =aeSecureFiles::get_files($dir, true);
    $files_str=chr(10);
    $files2zip=[];

    for ($i=0; $i < count($files); $i++) {
        if ($files[$i] != $abs_path && 'Thumbs.db' != basename($files[$i]) && basename($files[$i]) != basename($dir) . '.zip') {
            $files2zip[]=str_replace($dir . '/', '', $files[$i]);
        }
    }

    $result = aeSecureFiles::create_zip($files2zip, basename($dir) . '.zip', true);

    echo $result;

    echo '<h2>Files compressed</h2><pre>' . print_r($files2zip, true) . '</pre>';
    die();
}

?>

<!DOCTYPE html>
<html lang="en">

   <head>
      <meta charset="utf-8"/>
      <meta name="author" content="Christophe Avonture" />
      <meta name="robots" content="noindex, nofollow" />
      <meta name="viewport" content="width=device-width, initial-scale=1.0" />
      <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
      <meta http-equiv="X-UA-Compatible" content="IE=9; IE=8;" />
      <title>Make zip</title>      
      <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet"integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
      <link href="https://cdnjs.cloudflare.com/ajax/libs/jquery.tablesorter/2.25.3/css/theme.ice.min.css" rel="stylesheet" media="screen" />

      <style>
         .ajax_loading {display:inline-block;
            width:32px;
            height:32px;
            margin-right:20px;
            background-image: url('data:image/gif;base64,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AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=');
          }
      </style>
   </head>

   <body>
      <div class="container">
         <div class="page-header"><h1>Make archive of <?php echo __DIR__;?></h1></div>
         <div id="Result">&nbsp;</div>
      </div>
      <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
      <script type="text/javascript" src="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>
      <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery.tablesorter/2.25.3/js/jquery.tablesorter.combined.min.js"></script>
      <script type="text/javascript">

         $(document).ready(function() {
            var $data = new Object;
            $data.task = "doIt"

            $.ajax({
               beforeSend: function() {
                  $('#Result').html('<div><span class="ajax_loading">&nbsp;</span><span style="font-style:italic;font-size:1.5em;">Un peu de patience svp...</span></div>');
               },
               async:true,
               type:"POST",
               url: "<?php echo basename(__FILE__); ?>",
               data:$data,
               datatype:"html",
               success: function (data) {
                  $('#Result').html(data);
               }
            });
         });

      </script>

   </body>
</html>
