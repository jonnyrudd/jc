<div style="padding-right:20px;">
<?php
	echo do_shortcode('[wp_file_manager allowed_roles="*" view="list" lang="' . (strpos(ICL_LANGUAGE_CODE,'zh-') === 0 ? "zh_CN" : "en") . '" access_folder="wp-content/plugins/tomoaddons/template" write = "true" read = "true" allowed_operations="upload,download"]');
?>
</div>