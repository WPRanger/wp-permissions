<?php
/*
Plugin Name: WordPress Upload Permissions
Plugin URI: http://wpranger.co.uk
Description: Lists the currently set WordPress uploads directory file permissions
Author: Dave Naylor
Version: 0.2
Author URI: http://wpranger.co.uk
License: GPL2
*/

add_action('admin_init', 'wp_permissions_init' );
add_action('admin_menu', 'wp_permissions_add_page');

function wp_permissions_init(){
    register_setting( 'wp_permissions', 'wp-permissions', 'intval' );
}
add_action( 'admin_init', 'wp_permissions_init' );

function wp_permissions_add_page() {
	add_management_page('Upload Permissions', 'Upload Permissions', 'manage_options', 'wp_permissions', 'wp_permissions_tools_page');
}

function wp_permissions_tools_page() {
    $upload_dir =  wp_upload_dir();
    $uploadpath = $upload_dir['basedir'];
    $subdirpath = basename(realpath($uploadpath));
	?>
    <style type="text/css">
    .file-permissions tr:nth-child(2n+1) {background: #ddd;}
    .file-permissions tr:hover {background: #ffffee;}
    .file-permissions td, .file-permissions th {padding: 10px;border: 1px solid #ccc;}
    .file-permissions th {text-align: center; background: #ccc}
    .file-permissions th.left {text-align: left;}
    .file-permissions td.directory {color: green;}
    .file-permissions td.file {color: blue;}
    .file-permissions td.warn {background: salmon;}
    </style>
	<div class="wrap">
		<h2>WordPress Upload Permissions</h2>
        <h3>The currently set directory permissions for your WordPress uploads directory</h3>
        <p><strong>Absolute upload path is set to: </strong><?php echo $uploadpath; ?></p>
        <!-- <p>Directories are shown in <span style="color: green;">Green</span>, files are shown in <span style="color: blue;">Blue</span></p> -->
	</div>
	<?php	

    $dirlist = getFileList($uploadpath, true);
    
    echo "<table class='file-permissions'>\n";
    echo "<tr><th class='left'>Name</th><th class='left'>Type</th><th>Permissions</th><th>Writeable</th><th>Readable</th></tr>\n";
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
    if($file['write'] !== "Directory is writeable" && $file['type'] == "dir") {
        echo "<td class='warn'>{$file['write']}</td>\n";
    } 
    else {  echo "<td>{$file['write']}</td>\n";
    }
    if($file['read'] !== "Directory is readable" && $file['type'] == "dir") {
        echo "<td class='warn'>{$file['read']}</td>\n";
    } 
    else {  echo "<td>{$file['read']}</td>\n";
    }
    echo "</tr>\n";
  }
  echo "</table>\n\n";
}

function getFileList($dir, $recurse=false, $depth=false)
  {
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
        if(is_writable("$dir$entry")) {
            $wtest = "Directory is writeable";
        } else {
            $wtest = "Directory is <strong>NOT</strong> writeable";
        }
        if(is_readable("$dir$entry")) {
            $rtest = "Directory is readable";
        } else {
            $rtest = "Directory is <strong>NOT</strong> readable";
        }
        $retval[] = array(
          "name" => "$dir$entry/",
          "type" => filetype("$dir$entry"),
          "fperm" => decoct(fileperms("$dir$entry") & 0777),
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

      // Omitted Files for now until IO can implement ome kind of pagination
     
      //elseif(is_readable("$dir$entry")) {
      //  $retval[] = array(
      //    "name" => "$dir$entry",
      //    "type" => mime_content_type("$dir$entry"),
      //    "fperm" => decoct(fileperms("$dir$entry") & 0777) 
      //  );
      //}
    }
    $d->close();

    return $retval;
}
