<?php
/*
Plugin Name: WP Upload Permissions
Plugin URI: http://wpranger.co.uk/wp-permissions
Description: Lists the currently set WordPress uploads directory permissions
Author: Dave Naylor
Version: 0.5
Author URI: http://wpranger.co.uk
License: GPL2
*/

/*  Copyright 2014  Dave Naylor  (email : dave@wpranger.co.uk)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
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
    
    $version = "v0.5";
    $upload_dir =  wp_upload_dir();
    $uploadpath = $upload_dir['basedir'];
    $subdirpath = basename(realpath($uploadpath));

	echo "<div class='wrap'>"; 
	echo "<h2>WP Upload Permissions</h2>";
    echo "<h3>Current permissions for your WordPress uploads directory</h3>";
    echo "<p><strong>Absolute upload path is set to: </strong>{$uploadpath}</p>";
    echo "<ul>";
    echo "<li>Any text can be filtered.  Search <strong><em>no</em></strong> for problems, <strong><em>yes</em></strong> for satisfaction</li>";
    echo "<li>World writeable directories get a scary red background</li>";
    echo "<li>Clicking the table headers sorts them</li>";
    echo "</ul>";
    echo "Current php process owner: " . get_current_user();
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

    $uperm   = perm_the_obj("$uploadpath");
    $uwtest  = write_the_obj("$uploadpath");
    $urtest  = read_the_obj("$uploadpath");
     
    // Render the output table
    echo "<table class='wpr-table' id='wp-permissions'>\n";
    echo "<thead><th class='left'>Name</th><th>Type</th><th>Permissions</th><th>Owner</th><th>Group</th><th>Writeable</th><th>Readable</th></thead>\n";
    
    // Top table row hard coded to uploads basedir 
    echo "<tr>";
    echo "<td class='directory name'>{$subdirpath}</td>";
    echo "<td >dir</td>";
    echo "<td>{$uperm}</td>";
    echo "<td></td>";
    echo "<td></td>";
    echo "{$uwtest}";
    echo "{$urtest}";
    echo "</tr>";
     
    foreach($dirlist as $file) {
        echo "<tr>\n";
        $findme = stripos($file['name'], $subdirpath);
        $uppath = substr($file['name'],$findme);

        if($file['type'] == "dir") {
        echo "<td class='directory name'>{$uppath}</td>\n";
        } else { echo "<td class='file name'>{$uppath}</td>\n";
        }
        echo "<td>{$file['type']}</td>\n";
        $yikes = substr($file['fperm'], 2, 1);
        if($yikes == 7) {
            echo "<td class='warn'>{$file['fperm']}</td>\n";
        } else {
            echo "<td>{$file['fperm']}</td>\n";
        }
        echo "<td>{$file['fown']}</td>\n";
        echo "<td>{$file['gown']}</td>\n";
        echo "{$file['write']}\n";
        echo "{$file['read']}\n";
        echo "</tr>\n";
    }
    echo "</table>\n\n";
    
    echo "<div class='creds-wrap'>\n";
    echo "<small>WP Upload Permissions {$version}</small><br />";
    echo "<small><a href='http://wpranger.co.uk'>WPRanger</a>\n";
    echo "</div>";
}

// The meat in the sausage.
// Some original PHP code by Chirp Internet: www.chirp.com.au
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
            $rtest = read_the_obj("$dir$entry");
            $wtest = write_the_obj("$dir$entry");
            $fptest = perm_the_obj("$dir$entry");
            $owtest = own_the_obj("dir$entry");
            $grtest = group_the_obj("$dir$entry");
            $retval[] = array(
                "name" => "$dir$entry/",
                "type" => filetype("$dir$entry"),
                "fperm" => "$fptest",
                "fown" => "$owtest",
                "gown" => "$grtest",
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
                "fperm" => perm_the_obj("$dir$entry"),
                "fown" =>  own_the_obj("$dir$entry"),
                "gown" => group_the_obj("$dir$entry"),
                "write" => write_the_obj("$dir$entry"),
                "read" =>  read_the_obj("$dir$entry")
            );
        }
    }
    $d->close();

    return $retval;
}

function read_the_obj($readthing) {

    $numero = substr(perm_the_obj("$readthing"), 0, 1);

    if(is_dir($readthing)) {
        
        if($numero != 7) {
        $readres = "<td class='cross'>no</td>";
        } else {
        $readres = "<td class='tick'>yes</td>";
        }

        return $readres;
    } else {

        if($numero >= 4) {
        $readres = "<td class='tick'>yes</td>";
        } else {
        $readres = "<td class='cross'>no<td>";
        }
        return $readres;
    }
}

function write_the_obj($writething) {

        if(is_dir($writething)) {
            if(is_writeable("$writething")) {
            $writeres = "<td class='tick'>yes</td>";
            } else {
            $writeres = "<td class='cross'>no</td>";
            }

            return $writeres;

        } else {
            if(is_writeable("$writething")) {
            $writeres = "<td class='tick'>yes</td>";
            } else {
            $writeres = "<td class='cross'>no</td>";
            }
            return $writeres;
        }
}

function perm_the_obj($permthing) {

    $permres = decoct(fileperms("$permthing") & 0777);

    return $permres;
}
function own_the_obj($ownthing) {

    if(function_exists(posix_getpwuid)) {
        $fotest = posix_getpwuid(fileowner("$ownthing"));
        return $fotest[name];
    } else {
        return "N/A";
    }

}
function group_the_obj($groupthing) {

    if(function_exists(posix_getgrgid)) {
        $gotest = posix_getgrgid(filegroup("$groupthing"));
        return $gotest[name];
    } else {
        return "N/A";
    }

}
