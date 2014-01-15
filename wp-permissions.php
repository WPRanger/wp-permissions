<?php
/*
Plugin Name: WordPress Upload Permissions
Plugin URI: http://wpranger.co.uk
Description: Lists the currently set WordPress uploads directory permissions
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
    $version = "v0.2";
    $upload_dir =  wp_upload_dir();
    $uploadpath = $upload_dir['basedir'];
    $subdirpath = basename(realpath($uploadpath));
	?>

    <style type="text/css">
    .file-permissions th {text-align: center; background: #ccc;}
    .file-permissions th.left {text-align: left;}
    .file-permissions tr:nth-child(2n+1) {background: #ddd;}
    .file-permissions td, .file-permissions th {padding: 10px;border: 1px solid #ccc;}
    .file-permissions td.directory {color: green;}
    .file-permissions td.file {color: blue;}
    .file-permissions td.warn {background: salmon;}
    .file-permissions td.sweet {background: lightgreen;}
    .file-permissions td.boom {background: orangered;text-align: center; color: #FFF;font-weight: bold;}
    .file-permissions tr:hover {background: #ffffee;}
    </style>

	<div class="wrap">
		<h2>WordPress Upload Permissions</h2>
        <h3>Current permissions for your WordPress uploads directory</h3>
        <p><strong>Absolute upload path is set to: </strong><?php echo $uploadpath; ?></p>
        <!-- <p>Directories are shown in <span style="color: green;">Green</span>, files are shown in <span style="color: blue;">Blue</span></p> -->
	</div>

	<?php	

    $dirlist = getFileList($uploadpath, true);

    $uperm   = perm_the_dir("$uploadpath");
    $uwtest  = write_the_dir("$uploadpath");
    $urtest  = read_the_dir("$uploadpath"); 

    echo "<table class='file-permissions'>\n";
    echo "<tr><th class='left'>Name</th><th class='left'>Type</th><th>Permissions</th><th>Writeable</th><th>Readable</th></tr>\n";
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
    ?>
    <div class="wrap">
    <small>Dave Naylor - Wordpress Upload Permissions <?php echo $version; ?></small><br />
    <small><a href="http://wpranger.co.uk">WPRanger</a>
    </div>
    <?php
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

      // To do, pagination in case of MASSIVE media libraries.  Uncomment the below if brave.
     
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
