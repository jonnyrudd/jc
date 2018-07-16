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
<p class="submit"><input type="button" name="git_push_ch" id="git_push_ch" class="button button-primary" data-popup-open="popup-1" value="GIT Push Changes"></p>


<div class="popup" data-popup="popup-1">
<div class="popup-inner">
<h2>GIT PUSH</h2>
<p>Git Message: <textarea id="git_push_status_message" rows="6" cols="100"></textarea></p>
<p><input type="button" name="git_push_changes" id="git_push_changes" class="button button-primary" value="Push"></p>
<a class="popup-close" data-popup-close="popup-1" href="#">x</a>
</div>
</div>



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
		var message = jQuery('#git_push_status_message').val();
		if(message == '') {
			alert('Please enter a commit message.');
		} else {
			   jQuery(this).val('Please wait...');
				jQuery.ajax({
					url : '<?php echo admin_url('admin-ajax.php')?>',
					type : 'post',
					data : {
						action : 'mk_file_folder_manager_push_git_changes',
						message: message
					},
					success : function( response ) {
						jQuery('#git_push_changes').val('GIT Push Changes');
						jQuery('[data-popup="popup-1"]').fadeOut(350);
						alert(response);
					}
				});
		}
    });
});
</script>
<style>
/* Outer */
.popup {
width:100%;
height:100%;
display:none;
position:fixed;
top:0px;
left:0px;
background:rgba(0,0,0,0.75);
}
/* Inner */
.popup-inner {
max-width:700px;
width:90%;
padding:40px;
position:absolute;
top:50%;
left:50%;
-webkit-transform:translate(-50%, -50%);
transform:translate(-50%, -50%);
box-shadow:0px 2px 6px rgba(0,0,0,1);
border-radius:3px;
background:#fff;
}
/* Close Button */
.popup-close {
width:30px;
height:30px;
padding-top:4px;
display:inline-block;
position:absolute;
top:0px;
right:0px;
transition:ease 0.25s all;
-webkit-transform:translate(50%, -50%);
transform:translate(50%, -50%);
border-radius:1000px;
background:rgba(0,0,0,0.8);
font-family:Arial, Sans-Serif;
font-size:20px;
text-align:center;
line-height:100%;
color:#fff;
}
.popup-close:hover {
-webkit-transform:translate(50%, -50%) rotate(180deg);
transform:translate(50%, -50%) rotate(180deg);
background:rgba(0,0,0,1);
text-decoration:none;
}
</style>
<script>
jQuery(function() {
//----- OPEN
jQuery('[data-popup-open]').on('click', function(e)  {
jQuery('#git_push_status_message').val('');
var targeted_popup_class = jQuery(this).attr('data-popup-open');
jQuery('[data-popup="' + targeted_popup_class + '"]').fadeIn(350);
e.preventDefault();
});
//----- CLOSE
jQuery('[data-popup-close]').on('click', function(e)  {
var targeted_popup_class = jQuery(this).attr('data-popup-close');
jQuery('[data-popup="' + targeted_popup_class + '"]').fadeOut(350);
e.preventDefault();
});
});
</script>
