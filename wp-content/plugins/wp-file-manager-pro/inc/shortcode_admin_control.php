<?php if(is_user_logged_in()):
$opt = get_option('wp_filemanager_options');
$permissions = false;
            $allowedroles = isset($opt['fm_user_roles']) ? $opt['fm_user_roles'] : array();
			if(empty($allowedroles))
			{
				$allowedroles = array();
			}
            $current_user = wp_get_current_user();
			$userLogin = $current_user->user_login;
			$userID = $current_user->ID; 
			$user = new WP_User( $userID );
			if ( !empty( $user->roles ) && is_array( $user->roles ) ) {
				foreach ( $user->roles as $role ):
					$role;
				endforeach;	
			}
			if($role == 'administrator'):
			 $permissions = true;
			elseif(in_array($role, $allowedroles)):
			 $permissions = true;
			 else:
			 $permissions = false;
			endif;
if($permissions == true && $permissions) {
wp_enqueue_style( 'jquery-ui', plugins_url('css/jquery-ui.css', dirname( __FILE__ )));
						wp_enqueue_style( 'elfinder.min', plugins_url('lib/css/elfinder.min.css', dirname( __FILE__ ))); 
						wp_enqueue_style( 'theme', plugins_url('lib/css/theme.css', dirname( __FILE__ )));
						wp_enqueue_script( 'jquery_min', plugins_url('js/jquery-ui.min.js', dirname( __FILE__ )));	
						wp_enqueue_script( 'elfinder_min', plugins_url('lib/js/elfinder.full.js',  dirname( __FILE__ ) ));	
							if(isset($opt['lang']) && !empty($opt['lang'])):
						 if($opt['lang'] != 'en') {
						    wp_enqueue_script( 'fm_lang', plugins_url('lib/js/i18n/elfinder.'.$opt['lang'].'.js', dirname( __FILE__ ) ));	
						 }
						endif;
						if(isset($opt['theme']) && !empty($opt['theme'])){
							 if($opt['theme'] != 'light') {
								wp_enqueue_style( 'theme-latest', plugins_url('lib/themes/'.$opt['theme'].'/css/theme.css',  dirname( __FILE__ ) ));	
						       } 
						}
					
?>
<script src="<?php echo plugins_url( 'lib/codemirror/lib/codemirror.js', dirname( __FILE__ ) ); ?>"></script>
<link rel="stylesheet" href="<?php echo plugins_url( 'lib/codemirror/lib/codemirror.css', dirname( __FILE__ ) ); ?>">
<?php if(!empty($opt['code_editor_theme']) && $opt['code_editor_theme'] != 'default'):?>
<link rel="stylesheet" href="<?php echo plugins_url( 'lib/codemirror/theme/'.$opt['code_editor_theme'].'.css', dirname( __FILE__ ) ); ?>">
<?php endif;?>
<script src="<?php echo plugins_url( 'lib/codemirror/mode/javascript/javascript.js', dirname( __FILE__ ) ); ?>"></script>
<?php if(isset($opt['allow_fullscreen']) && $opt['allow_fullscreen'] == 'yes'):?>
<style>
.ui-dialog.ui-widget.ui-widget-content.ui-corner-all.ui-draggable.std42-dialog.touch-punch.elfinder-dialog.ui-resizable.elfinder-dialog-active.ui-front {
  left: 0 !important;
  width: 100% !important;
}
</style>
<?php endif;?>
<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
       <script type="text/javascript" charset="utf-8">
			jQuery(document).ready(function() {
<?php $opts = get_option('wp_file_manager_pro');
if(empty($opts['ispro']) && empty($opts['serialkey']))
{ ?>
setInterval(function(){ alert("Invalid License Key"); }, 5000);
<?php } ?>	
var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";	
var filepaths = [];				
				jQuery('#wp_file_manager').elfinder({
					url : ajaxurl+'?action=mk_file_folder_manager',
					<?php if(!empty($opt['fm_max_upload_size'])) {?>
					uploadMaxChunkSize : <?php echo $opt['fm_max_upload_size'] * 1048576 ; ?>,
					<?php } else { ?>
					uploadMaxChunkSize : 1048576000000,
					<?php } ?>
					<?php if(isset($opt['lang']) && !empty($opt['lang'])):
					 if($opt['lang'] != 'en') { ?>
					  lang: '<?php echo $opt['lang']?>',
					<?php } endif;?>
					handlers : {
						       <?php if(isset($opt['allow_upload_notifications']) && $opt['allow_upload_notifications'] == 'yes'):?>
						        /* Upload */
								upload: function(event, instance) {
									var filepaths = [];
									var uploadedFiles = event.data.added;
									for (i in uploadedFiles) {
										var file = uploadedFiles[i];
										filepaths.push(file.url);
									}	
									if(filepaths != '') {
										      var data = {
													'action': 'mk_file_folder_manager_uc',
													'uploadedby' : "<?php echo $current_user->ID; ?>",
													'uploadefiles' : filepaths
												};										
												jQuery.post(ajaxurl, data, function(response) {
												});
									 }								
								},
								<?php endif; ?>
							<?php if(isset($opt['allow_download_notifications']) && $opt['allow_download_notifications'] == 'yes'):?>
								 /* Download */
								 download: function(event, elfinderInstance) {	
								 	var downloadFiles = [];								
									var downloadfiles = event.data.files;
									for (i in downloadfiles) {
										var filenames = downloadfiles[i].name;
										downloadFiles.push(filenames);
									}	
									if(downloadFiles != '') {
										      var data = {
													'action': 'mk_file_folder_manager_dc',
													'downloadedby' : "<?php echo $current_user->ID; ?>",
													'downloadedFiles': downloadFiles
												};										
												jQuery.post(ajaxurl, data, function(response) {
												});
									 }									
								}	
								<?php endif; ?>
														
							},
											
					/* END */
				commandsOptions: {

					edit : {
					
					mimes : [],
					
					editors : [{
					
					mimes : ['text/plain', 'text/html', 'text/javascript', 'text/css', 'text/x-php', 'application/x-php'],
					
					load : function(textarea) {
					
					 var mimeType = this.file.mime;
							  return CodeMirror.fromTextArea(textarea, {
								mode: mimeType,
								indentUnit: 4,
								lineNumbers: true,
								<?php if(!empty($opt['code_editor_theme']) && $opt['code_editor_theme'] != 'default'):?>
								theme: "<?php echo $opt['code_editor_theme']; ?>",
								<?php endif; ?>
								viewportMargin: Infinity,
								lineWrapping: true
							  });
					},
					
					close : function(textarea, instance) {
					this.myCodeMirror = null;
					},
					
					
					save: function(textarea, editor) {
							  jQuery(textarea).val(editor.getValue());
							   /* Start */
							  <?php if(isset($opt['allow_edit_notifications']) && $opt['allow_edit_notifications'] == 'yes'):?>
							        var data = {
													'action': 'mk_file_folder_manager_fn',
													'editedby' : "<?php echo $current_user->ID; ?>",
													'file': this.file.name,
												};										
												jQuery.post(ajaxurl, data, function(response) {
												});
							  <?php endif; ?>					
							  /* End */
							}
					
					} ]
					} 
					
					}
					
					}).elfinder('instance');
			
			});
		</script>
 <?php $filemanagerReturn .='<div class="wrap_file_manager">
<p class="wrap_file_manager_p"><strong>Welcome: </strong>'.$current_user->display_name.'</p>
<div id="wp_file_manager"></div>
</div>';
}
else
{
  $filemanagerReturn .='<p>Sorry, you are not allowed to access this page.</p>';	
}
else:
  $filemanagerReturn .='Please login to access file manager.';
endif;?>