<?php
/*
Plugin Name: WordPress Upload Permissions
Plugin URI: http://wpranger.co.uk
Description: Lists the currently set WordPress uploads directory file permissions
Author: Dave Naylor
Version: 0.1
Author URI: http://wpranger.co.uk
License: GPL2
*/

add_action('admin_init', 'wp_permissions_init' );
add_action('admin_menu', 'wp_permissions_add_page');

// Init plugin options to white list our options
function wp_permissions_init(){
    register_setting( 'wp_permissions', 'wp-permissions', 'intval' );
}
add_action( 'admin_init', 'wp_permissions_init' );

// Add menu page
function wp_permissions_add_page() {
	add_management_page('Upload Permissions', 'Upload Permissions', 'manage_options', 'wp_permissions', 'wp_permissions_tools_page');
}

// Draw the menu page itself
function wp_permissions_tools_page() {
    $upload_dir =  wp_upload_dir();
    $path = $upload_dir['path'];
	?>
	<div class="wrap">
		<h2>WordPress Upload Permissions</h2>
        <h3>The currently set file and directory permissions for your WordPress uploads directory</h3>
        <p><strong>Upload path is set to: </strong><?php echo $path; ?></p>
        <p>Directories are shown in <span style="color: green;">Green</span> followed by their permission in octal notation (e.g. <span style="color: green;">755</span>)</p>
        <p>Files are shown in <span style="color: blue;">Blue</span> followed by their permission in octal notation (e.g. <span style="color: blue;">644</span>)</p>
	</div>
	<?php	
	    $tree = retrieveTree($path); 
	    echo "<pre style='color:lightgray;'> ";
	    print_r($tree);
	    echo "</pre>";
}

$delim = strstr(PHP_OS, "WIN") ? "\\" : "/";

function retrieveTree($path)  {
    global $delim;

    if ($dir=@opendir($path)) {
        while (($element=readdir($dir))!== false) {
            if (is_dir($path.$delim.$element) && $element!= "." && $element!= "..") {
			
				$s = stat($path.$delim.$element);
				$permissions = substr(decoct($s[mode]), -3);

				$array_element = '<span style="color:green;">'.$element.' '.$permissions.'</span>';
				
				$array[$array_element] = retrieveTree($path.$delim.$element);
				
            } elseif ($element!= "." && $element!= "..") {
			
				$s = stat($path.$delim.$element);
				$permissions = substr(decoct($s[mode]), -3);
				
                $array[] = '<span style="color:blue;">'.$element.' '.$permissions.'</span>';
            }
        }
        closedir($dir);
    }
    return (isset($array) ? $array : false);
}

