<?php
$subfolder = '';

if(isset($_GET['sub'])){
	$subfolder = $_GET['sub'];
} else {
	die();
}

$writable = 'false';
if(isset($_GET['writable']) && $_GET['writable'] == 'true'){
	$writable = 'true';
}

$ops = 'download';
if(isset($_GET['ops']) && $_GET['ops'] == 'upload'){
	$ops = 'upload,download';
}

?>
<div style="padding-right:20px;">
<?php
echo do_shortcode('[wp_file_manager allowed_roles="*" view="list" lang="' . (strpos(ICL_LANGUAGE_CODE,'zh-') === 0 ? "zh_CN" : "en") . '" access_folder="wp-content/uploads/processing/' . $subfolder . '" write = "' . $writable . '" read = "false" allowed_operations="' . $ops . '"]');
?>
</div>