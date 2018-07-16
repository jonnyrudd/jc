<?php if ( ! defined( 'ABSPATH' ) ) exit; 
 if(isset($_POST['submit'])) { 
		   $save = update_option('wp_file_manager_pro_git', $_POST);
		  if($save) {
			  echo '<script>';
			  echo 'window.location.href="?page=wp_file_manager_github&status=1"';
			  echo '</script>';
		  } else {
			  echo '<script>';
			  echo 'window.location.href="?page=wp_file_manager_github&status=2"';
			  echo '</script>';
		  }
	   }
$settings = get_option('wp_file_manager_pro_git');
?>
<div class="wrap">
<h3><?php _e('Git Hub', 'wp-file-manager-pro');?></h3>
<form action="" method="post">
<table class="form-table">
<tr>
<th><?php _e('GIT EMAIL','wp-file-manager-pro')?></th>
<td>
<input name="ELFINDER_GIT_EMAIL" type="text" id="ELFINDER_GIT_EMAIL" value="<?php echo isset($settings['ELFINDER_GIT_EMAIL']) && !empty($settings['ELFINDER_GIT_EMAIL']) ? $settings['ELFINDER_GIT_EMAIL'] : '';?>" class="regular-text">
<p class="description"><?php _e('Enter github email','wp-file-manager-pro')?></p>
</td>
</tr>
<tr>
<th><?php _e('GIT USERNAME','wp-file-manager-pro')?></th>
<td>
<input name="ELFINDER_GIT_USERNAME" type="text" id="ELFINDER_GIT_USERNAME" value="<?php echo isset($settings['ELFINDER_GIT_USERNAME']) && !empty($settings['ELFINDER_GIT_USERNAME']) ? $settings['ELFINDER_GIT_USERNAME'] : '';?>" class="regular-text">
<p class="description"><?php _e('Enter github username','wp-file-manager-pro')?></p>
</td>
</tr>
<tr>
<th><?php _e('GIT PASSWORD','wp-file-manager-pro')?></th>
<td>
<input name="ELFINDER_GIT_PASSWORD" type="text" id="ELFINDER_GIT_PASSWORD" value="<?php echo isset($settings['ELFINDER_GIT_PASSWORD']) && !empty($settings['ELFINDER_GIT_PASSWORD']) ? $settings['ELFINDER_GIT_PASSWORD'] : '';?>" class="regular-text">
<p class="description"><?php _e('Enter github password','wp-file-manager-pro')?></p>
</td>
</tr>
<tr>
<th><?php _e('GIT ACCESS DIRECTORY','wp-file-manager-pro')?></th>
<td>
<input name="ELFINDER_GIT_ACCESS_DIRECTORY" type="text" id="ELFINDER_GIT_ACCESS_DIRECTORY" value="<?php echo isset($settings['ELFINDER_GIT_ACCESS_DIRECTORY']) && !empty($settings['ELFINDER_GIT_ACCESS_DIRECTORY']) ? $settings['ELFINDER_GIT_ACCESS_DIRECTORY'] : str_replace('\\','/', ABSPATH);?>" class="regular-text">
<p class="description"><?php _e('Enter folder path you want to use for git','wp-file-manager-pro')?></p>
</td>
<tr>
<th><?php _e('GIT MASTER ACCESS DIRECTORY','wp-file-manager-pro')?></th>
<td>
<input name="ELFINDER_GIT_MASTER_ACCESS_DIRECTORY" type="text" id="ELFINDER_GIT_MASTER_ACCESS_DIRECTORY" value="<?php echo isset($settings['ELFINDER_GIT_MASTER_ACCESS_DIRECTORY']) && !empty($settings['ELFINDER_GIT_MASTER_ACCESS_DIRECTORY']) ? $settings['ELFINDER_GIT_MASTER_ACCESS_DIRECTORY'] : str_replace('\\','/', ABSPATH);?>" class="regular-text">
<p class="description"><?php _e('Enter master folder path you want to use for git','wp-file-manager-pro')?></p>
</td>
</tr>
<tr>
<th><?php _e('GIT REPOSITORY URL','wp-file-manager-pro')?></th>
<td>
<input name="ELFINDER_GIT_ACCESS_URL" type="text" id="ELFINDER_GIT_ACCESS_URL" value="<?php echo isset($settings['ELFINDER_GIT_ACCESS_URL']) && !empty($settings['ELFINDER_GIT_ACCESS_URL']) ? $settings['ELFINDER_GIT_ACCESS_URL'] : '';?>" class="regular-text">
<p class="description"><?php _e('e.g https://github.com/username/filename.git','wp-file-manager-pro')?></p>
</td>
</tr>
</table>
<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></p>
</form>
<hr />
<p class="submit"><input type="button" name="pull_from_repo" id="pull_from_repo" class="button button-primary" value="Pull From Git"></p>

<hr />
<p class="submit"><input type="button" name="check_git_changes" id="check_git_changes" class="button button-primary" value="Check Changes Status"><span id="git_changes"></span></p>

<hr />
<p class="submit"><input type="button" name="git_push_changes" id="git_push_changes" class="button button-primary" value="GIT Push Changes"></p>

</div>
<script>
jQuery(document).ready(function(e) {
	// pull repo
    jQuery('#pull_from_repo').click(function(e) {
        e.preventDefault();
		jQuery(this).val('Pulling please wait...');
		jQuery.ajax({
            url : '<?php echo admin_url('admin-ajax.php')?>',
            type : 'post',
            data : {
                action : 'mk_file_folder_manager_pull_git_request',
            },
            success : function( response ) {
				jQuery('#pull_from_repo').val('Pull From Git');
                if(response=='') {
					alert('Repository pulled successfully! Please check your desitnation folder.');
				} else {
					alert(response);
				}
            }
        });
    });
   // check git changes
   jQuery('#check_git_changes').click(function(e) {
        e.preventDefault();
		jQuery(this).val('Please wait...');
		jQuery.ajax({
            url : '<?php echo admin_url('admin-ajax.php')?>',
            type : 'post',
            data : {
                action : 'mk_file_folder_manager_check_git_changes',
            },
            success : function( response ) {
				jQuery('#check_git_changes').val('Check Changes');
                //jQuery('#git_changes').html(response);
				alert(response);
            }
        });
    });
	
	 // Push Changes to git
   jQuery('#git_push_changes').click(function(e) {
        e.preventDefault();
		jQuery(this).val('Please wait...');
		jQuery.ajax({
            url : '<?php echo admin_url('admin-ajax.php')?>',
            type : 'post',
            data : {
                action : 'mk_file_folder_manager_push_git_changes',
            },
            success : function( response ) {
				jQuery('#git_push_changes').val('GIT Push Changes');
				alert(response);
            }
        });
    });
});
</script>