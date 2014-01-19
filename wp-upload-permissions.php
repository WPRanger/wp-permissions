<?php
/*
Plugin Name: WP Upload Permissions
Plugin URI: http://wpranger.co.uk/plugins/wp-upload-permissions
Description: Lists the currently set WordPress uploads directory permissions
Author: Dave Naylor
Version: 0.6.2
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
    wp_enqueue_script( 'jquery.TablesTools', plugins_url( 'js/TableTools.min.js', __FILE__), array(), '1.0.0', true );
    wp_enqueue_script( 'jquery.ZeroClipboard', plugins_url( 'js/ZeroClipboard.js', __FILE__), array(), '1.0.0', true );
    wp_register_style( 'wp_permissions', plugins_url( 'css/style.css', __FILE__));
    wp_enqueue_style( 'wp_permissions' );
}
add_action( 'admin_enqueue_scripts', 'wp_permissions_scripts' );

function wp_permissions_tools_page() {
    
    $version = "v0.7.0";

    // find WordPress uploads directory absolute path
    $upload_dir =  wp_upload_dir();
    $uploadpath = $upload_dir['basedir'];
    $subdirpath = basename(realpath($uploadpath));

    // grabs process owner on *nix servers
    if(function_exists(posix_getpwuid)) {
        $processowner = @posix_getpwuid(@posix_geteuid());
    } else {
        $processowner = "Not known";
    }

	echo "<div class='wrap'>"; 
	echo "<h2>WP Upload Permissions</h2>";
    echo "<h3>Current permissions for your WordPress uploads directory</h3>";
    echo "<ul class='wp-permissions-info'>";
    echo "<li>Any text can be filtered.  Search <strong><em>no</em></strong> for problems, <strong><em>yes</em></strong> for satisfaction</li>";
    echo "<li>World writeable directories get a scary red background</li>";
    echo "<li>Clicking the table headers sorts them</li>";
    echo "</ul>";
    echo "<strong>Current php process owner:</strong> " . $processowner['name'];
    echo "<p><strong>Absolute upload path set to: </strong>{$uploadpath}</p>";
 	echo "</div>";
    
    // datatables settings 
    $swf_path = plugins_url( 'swf/copy_csv_xls_pdf.swf', __FILE__);
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function() {
        jQuery('#wp-permissions').dataTable( {
            "aaSorting": [[ 4, "desc" ]],
            "sDom": 'T<"clear">lfrtip',
            "oTableTools": {
                "sSwfPath": "<?php echo $swf_path; ?>"
            },
            "aoColumnDefs": [
                        { "bVisible": false, "aTargets": [ 6 ] },
                        { "bVisible": false, "aTargets": [ 8 ] }
            ], 
        } );
    } );
    </script>
    <!-- end datatables settings -->

    <p>
    <form method="post" action="<?=$_SERVER['PHP_SELF']?>?page=wp_permissions">
    <select name="bops">
      <option value="NONE">Select List Type</option>
      <option value="DIRS">Just Directories</option>
      <option value="BOTH">Directories and Files</option>
    </select>
    <input type="submit" name="submit" value="Submit" />
    </form>
    </p>

    <?php

    // grab the directories and optionally the files
    $dirlist = getFileList($uploadpath, true);

    // gets info for uploads root dir itself
    $uploads_perms  = perm_the_obj("$uploadpath");
    $uploads_write  = write_the_obj("$uploadpath");
    $uploads_read   = read_the_obj("$uploadpath");
    $uploads_owner  = own_the_obj("$uploadpath");
    $uploads_group  = group_the_obj("$uploadpath"); 

    // Render the output table
    echo "<table class='wpr-table' id='wp-permissions'>\n";
    echo "<thead><th class='left'>Name</th>
        <th>Tupe</th>
        <th>Permissions</th>
        <th>Owner</th>
        <th>Group</th>
        <th>Write</th>
        <th>Moo</th>
        <th>Read</th>
        <th>Boo</th>
        </thead>\n";
    
    // Top table row hard coded to uploads basedir 
    echo "<tr>";
    echo "<td class='directory left'>{$subdirpath}/</td>";
    echo "<td >dir</td>";
    echo "<td>{$uploads_perms}</td>";
    echo "<td>{$uploads_owner}</td>";
    echo "<td>{$uploads_group}</td>";
    echo "{$uploads_write}";
    echo "{$uploads_read}";
    echo "</tr>\n";

    // the big spangly table is rendered here 
    foreach($dirlist as $file) {
        echo "<tr>\n";
        $findme = stripos($file['name'], $subdirpath);
        $uploads_path = substr($file['name'],$findme);

        if($file['type'] == "dir") {
        echo "<td class='directory left'>{$uploads_path}</td>\n";
        } else { echo "<td class='file left'>{$uploads_path}</td>\n";
        }
        echo "<td>{$file['type']}</td>\n";

        // Red background if world writeable
        $yikes = substr($file['f_perm'], 2, 1);
        if($yikes == 7) {
            echo "<td class='warn'>{$file['f_perm']}</td>\n";
        } else {
            echo "<td>{$file['f_perm']}</td>\n";
        }
        
        echo "<td>{$file['f_own']}</td>\n";
        echo "<td>{$file['g_own']}</td>\n";
        echo "{$file['write']}\n";
        echo "{$file['read']}\n";
        echo "</tr>\n";
    }
    echo "</table>\n\n";
    
    echo "<div class='creds-wrap'>\n";
    echo "<small>WP Upload Permissions {$version}</small><br />";
    echo "<small><a href='http://wpranger.co.uk'>WPRanger</a>\n";
    echo "</div>\n";
}

// the meat in the sausage, this is what gets all the dirs and files
// some original PHP code by Chirp Internet: www.chirp.com.au
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
            $retval[] = array(
                "name"   => "$dir$entry/",
                "type"   => filetype("$dir$entry"),
                "f_perm" => perm_the_obj("$dir$entry"),
                "f_own"  => own_the_obj("$dir$entry/"),
                "g_own"  => group_the_obj("$dir$entry/"),
                "write"  => write_the_obj("$dir$entry"),
                "read"   => read_the_obj("$dir$entry")
            );
            if($recurse && is_readable("$dir$entry/")) {
                if($depth === false) {
                    $retval = array_merge($retval, getFileList("$dir$entry/", true));
                } elseif($depth > 0) {
                    $retval = array_merge($retval, getFileList("$dir$entry/", true, $depth-1));
                }
            }
        } 

        elseif(is_readable("$dir$entry") && $_POST["bops"] == "BOTH")  {

            // if fileinfo is available, use it, if not, call it a file
            if(function_exists(finfo_open)) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $ftype = finfo_file($finfo, $dir.$entry);
                } else {
                    $ftype = "file";
            }
            $retval[] = array(
                "name"   => "$dir$entry",
                "type"   => $ftype,
                "f_perm" => perm_the_obj("$dir$entry"),
                "f_own"  => own_the_obj("$dir$entry"),
                "g_own"  => group_the_obj("$dir$entry"),
                "write"  => write_the_obj("$dir$entry"),
                "read"   => read_the_obj("$dir$entry")
            );
        }
    }
    $d->close();

    return $retval;
}
// functions to grab the info
function read_the_obj($readthing) {

    $numero = substr(perm_the_obj("$readthing"), 0, 1);

    if(is_dir($readthing)) {
        
        if($numero != 7) {
        $readres = "<td class='cross'></td><td class='cross'>no</td>";
        } else {
        $readres = "<td class='tick'></td><td class='tick'>yes</td>";
        }

        return $readres;
    } else {

        if($numero >= 4) {
        $readres = "<td class='tick'></td><td class='tick'>yes</td>";
        } else {
        $readres = "<td class='cross'><td><td class='cross'>no<td>";
        }
        return $readres;
    }
}

function write_the_obj($writething) {

        if(is_dir($writething)) {
            if(is_writeable("$writething")) {
            $writeres = "<td class='tick'></td><td class='tick'>yes</td>";
            } else {
            $writeres = "<td class='cross'></td><td class='cross'>no</td>";
            }

            return $writeres;

        } else {
            if(is_writeable("$writething")) {
            $writeres = "<td class='tick'></td><td class='tick'>yes</td>";
            } else {
            $writeres = "<td class='cross'></td><td class='cross'>no</td>";
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
        $fotest = @posix_getpwuid(fileowner("$ownthing"));
        return $fotest[name];
    } else {
        return "N/A";
    }
}
function group_the_obj($groupthing) {

    if(function_exists(posix_getgrgid)) {
        $gotest = @posix_getgrgid(filegroup("$groupthing"));
        return $gotest[name];
    } else {
        return "N/A";
    }
}
?>
