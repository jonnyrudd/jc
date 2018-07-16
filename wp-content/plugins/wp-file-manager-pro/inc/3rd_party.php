<?php if ( ! defined( 'ABSPATH' ) ) exit; 
 if(isset($_POST['submit'])) { 
		   $save = update_option('wp_file_manager_pro_3rd_party', $_POST);
		  if($save) {
			  echo '<script>';
			  echo 'window.location.href="?page=wp_file_manager_3rd_party&status=1"';
			  echo '</script>';
		  } else {
			  echo '<script>';
			  echo 'window.location.href="?page=wp_file_manager_3rd_party&status=2"';
			  echo '</script>';
		  }
	   }
$settings = get_option('wp_file_manager_pro_3rd_party');
?>
<div class="wrap">
<h3><?php _e('Dropbox', 'wp-file-manager-pro');?></h3>
<form action="" method="post">
<table class="form-table">
<tr>
<th><?php _e('ENABLE DROPBOX','wp-file-manager-pro')?></th>
<td>
<input name="ELFINDER_ENABLE_DROPBOX" type="checkbox" id="ELFINDER_ENABLE_DROPBOX" value="1" class="regular-text" <?php echo isset($settings['ELFINDER_ENABLE_DROPBOX']) && ($settings['ELFINDER_ENABLE_DROPBOX'] == 1) ? 'checked="checked"' : '';?>>
<p class="description"><?php _e('Check to enable Dropbox','wp-file-manager-pro')?></p>
</td>
</tr>
<tr>
<th><?php _e('DROPBOX APP KEY','wp-file-manager-pro')?></th>
<td>
<input name="ELFINDER_DROPBOX_APPKEY" type="text" id="ELFINDER_DROPBOX_APPKEY" value="<?php echo isset($settings['ELFINDER_DROPBOX_APPKEY']) && !empty($settings['ELFINDER_DROPBOX_APPKEY']) ? $settings['ELFINDER_DROPBOX_APPKEY'] : '';?>" class="regular-text">
<p class="description"><?php _e('Enter Dropbox APP Key','wp-file-manager-pro')?></p>
</td>
</tr>
<tr>
<th><?php _e('DROPBOX APP SECRET','wp-file-manager-pro')?></th>
<td>
<input name="ELFINDER_DROPBOX_APPSECRET" type="text" id="ELFINDER_DROPBOX_APPSECRET" value="<?php echo isset($settings['ELFINDER_DROPBOX_APPSECRET']) && !empty($settings['ELFINDER_DROPBOX_APPSECRET']) ? $settings['ELFINDER_DROPBOX_APPSECRET'] : '';?>" class="regular-text">
<p class="description"><?php _e('Enter Dropbox APP Secret','wp-file-manager-pro')?></p>
</td>
</tr>
<tr>
<th><?php _e('DROPBOX ACCESS TOKEN','wp-file-manager-pro')?></th>
<td>
<input name="ELFINDER_ACCESS_TOKEN" type="text" id="ELFINDER_ACCESS_TOKEN" value="<?php echo isset($settings['ELFINDER_ACCESS_TOKEN']) && !empty($settings['ELFINDER_ACCESS_TOKEN']) ? $settings['ELFINDER_ACCESS_TOKEN'] : '';?>" class="regular-text">
<p class="description"><?php _e('Enter Dropbox Access Token','wp-file-manager-pro')?></p>
</td>
</tr>
</table>
<p><?php _e('You can get above settings at <a href="https://www.dropbox.com/developers/apps" target="_blank">https://www.dropbox.com/developers/apps</a>','wp-file-manager-pro')?></p>
<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></p>
</form>
</div>