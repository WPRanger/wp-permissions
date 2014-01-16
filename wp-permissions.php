<?php
/*
Plugin Name: WordPress Upload Permissions
Plugin URI: http://wpranger.co.uk
Description: Lists the currently set WordPress uploads directory permissions
Author: Dave Naylor
Version: 0.4
Author URI: http://wpranger.co.uk
License: GPL2
*/

add_action('admin_init', 'wp_permissions_init' );
add_action('admin_menu', 'wp_permissions_admin_menu');

function wp_permissions_init(){
    register_setting( 'wp_permissions', 'wp-permissions', 'intval' );
}
add_action( 'admin_init', 'wp_permissions_init' );

function wp_permissions_admin_menu() {
    add_management_page('Upload Permissions', 'Upload Permissions', 'manage_options', 'wp_permissions', 'wp_permissions_tools_page');
}
function wp_permissions_scripts($hook) {
    if($hook != 'tools_page_wp_permissions')  {
        return;
    }
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'jquery.dataTables', plugins_url( 'js/jquery.dataTables.min.js', __FILE__), array(), '1.0.0', true );
    wp_register_style( 'wp_permissions', plugins_url( 'css/style.css', __FILE__));
    wp_enqueue_style( 'wp_permissions' );
}
add_action( 'admin_enqueue_scripts', 'wp_permissions_scripts' );

function wp_permissions_tools_page() {
    
    $version = "v0.4";
    $upload_dir =  wp_upload_dir();
    $uploadpath = $upload_dir['basedir'];
    $subdirpath = basename(realpath($uploadpath));

	echo "<div class='wrap'>"; 
	echo "<h2>WordPress Upload Permissions</h2>";
    echo "<h3>Current permissions for your WordPress uploads directory</h3>";
    echo "<p><strong>Absolute upload path is set to: </strong>{$uploadpath}</p>";
 	echo "</div>";
    
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function() {
        jQuery('#wp-permissions').dataTable( {
            "aaSorting": [[ 4, "desc" ]]
        } );
    } );
    </script>
    <?php
    
    // Grab the files and directories
    $dirlist = getFileList($uploadpath, true);

    $uperm   = perm_the_dir("$uploadpath");
    $uwtest  = write_the_dir("$uploadpath");
    $urtest  = read_the_dir("$uploadpath");

    
     
    // Render the output table
    echo "<table class='wpr-table' id='wp-permissions'>\n";
    echo "<thead><th class='left'>Name</th><th class='left'>Type</th><th>Permissions</th><th>Writeable</th><th>Readable</th></thead>\n";
    
    // Top table row hard coded to uploads basedir 
    echo "<tr>";
    echo "<td class='directory'>{$subdirpath}</td>";
    echo "<td >dir</td>";
    echo "<td>{$uperm}</td>";
    echo "{$uwtest}";
    echo "{$urtest}";
    echo "</tr>";
     
    foreach($dirlist as $file) {
        echo "<tr>\n";
        $findme = stripos($file['name'], $subdirpath);
        $uppath = substr($file['name'],$findme);

        if($file['type'] == "dir") {
        echo "<td class='directory'>{$uppath}</td>\n";
        } else { echo "<td class='file'>{$uppath}</td>\n";
        }
        echo "<td>{$file['type']}</td>\n";
        echo "<td>{$file['fperm']}</td>\n";
        echo "{$file['write']}\n";
        echo "{$file['read']}\n";
        echo "</tr>\n";
    }
    echo "</table>\n\n";
    
    echo "<div class='creds-wrap'>\n";
    echo "<small>Wordpress Upload Permissions {$version}</small><br />";
    echo "<small><a href='http://wpranger.co.uk'>WPRanger</a>\n";
    echo "</div>";


}


// The meat in the sausage.
// Original PHP code by Chirp Internet: www.chirp.com.au
function getFileList($dir, $recurse=false, $depth=false) {
    
    // array to hold return value
    $retval = array();

    // add trailing slash if missing
    if(substr($dir, -1) != "/") $dir .= "/";

    // open pointer to directory and read list of files
    $d = @dir($dir) or die("getFileList: Failed opening directory $dir for reading");
    
    while(false !== ($entry = $d->read())) {
    
    // skip hidden files
    if($entry[0] == ".") continue;
        if(is_dir("$dir$entry")) {
            $rtest = read_the_dir("$dir$entry");
            $wtest = write_the_dir("$dir$entry");
            $fptest = perm_the_dir("$dir$entry");
            $retval[] = array(
                "name" => "$dir$entry/",
                "type" => filetype("$dir$entry"),
                "fperm" => "$fptest",
                "write" => "$wtest",
                "read" => "$rtest"
            );
            if($recurse && is_readable("$dir$entry/")) {
                if($depth === false) {
                    $retval = array_merge($retval, getFileList("$dir$entry/", true));
                } elseif($depth > 0) {
                    $retval = array_merge($retval, getFileList("$dir$entry/", true, $depth-1));
                }
            }
        } 

        elseif(is_readable("$dir$entry")) {
            if(function_exists(finfo_open)) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $ftype = finfo_file($finfo, $dir.$entry);
                } else {
                    $ftype = "file";
            }
            $retval[] = array(
                "name" => "$dir$entry",
                "type" => $ftype,
                "fperm" => decoct(fileperms("$dir$entry") & 0777),
                "write" => "<td></td>",
                "read" => "<td></td>"
            );
        }
    }
    $d->close();

    return $retval;
}

function read_the_dir($readthing) {

    $numero = substr(perm_the_dir("$readthing"), 0, 1);

    if($numero != 7) {
    $readres = "<td class='warn'>Directory is <strong>NOT</strong> readable</td>";
    } else {
    $readres = "<td class='sweet'>Directory is readable</td>";
    }

    return $readres;
}

function write_the_dir($writething) {

    $yikes = perm_the_dir($writething);

    if($yikes == 777) {
        return "<td class='boom'>**WARNING** World Writeable</td>";
    }
    if(is_writeable("$writething")) {
    $writeres = "<td class='sweet'>Directory is writeable</td>";
    } else {
    $writeres = "<td class='warn'>Directory is <strong>NOT</strong> writeable</td>";
    }

    return $writeres;
}

function perm_the_dir($permthing) {

    $permres = decoct(fileperms("$permthing") & 0777);

    return $permres;
}
