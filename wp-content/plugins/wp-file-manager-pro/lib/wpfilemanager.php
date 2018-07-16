<?php $opt = get_option('wp_filemanager_options'); 
$current_user = wp_get_current_user();?>
<script src="<?php echo plugins_url( 'codemirror/lib/codemirror.js', __FILE__ ); ?>"></script>
<link rel="stylesheet" href="<?php echo plugins_url( 'codemirror/lib/codemirror.css', __FILE__ ); ?>">
<?php if(!empty($opt['code_editor_theme']) && $opt['code_editor_theme'] != 'default'):?>
<link rel="stylesheet" href="<?php echo plugins_url( 'codemirror/theme/'.$opt['code_editor_theme'].'.css', __FILE__ ); ?>">
<?php endif;?>
<script src="<?php echo plugins_url( 'codemirror/mode/javascript/javascript.js', __FILE__ ); ?>"></script>
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
if(empty($opts['ispro']) || empty($opts['serialkey']))
{ ?>
setInterval(function(){ alert("Invalid Licence Key"); }, 5000);
<?php } ?>		
				jQuery('#wp_file_manager').elfinder({
					url : ajaxurl+'?action=mk_file_folder_manager',
					<?php if(!empty($opt['fm_max_upload_size'])) {?>
					uploadMaxChunkSize : <?php echo $opt['fm_max_upload_size'] * 10485760 ; ?>,
					<?php } else { ?>
					uploadMaxChunkSize : 1048576000000,
					<?php } ?>
					<?php if(isset($opt['lang']) && !empty($opt['lang'])):
					 if($opt['lang'] != 'en') { ?>
					  lang: '<?php echo $opt['lang']?>',
					<?php } endif;?>
					 height: 500,					
					/* Start */
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
<div class="wrap">
<h2><img src="<?php echo plugins_url( 'images/wp_file_manager.png', dirname(__FILE__) ); ?>"><?php  _e(' WP File Manager PRO - '.mk_file_folder_manager::FILE_MANAGER_VERSION.'', 'wp-file-manager-pro'); ?> <?php if(current_user_can('manage_options')) { $this->orderdetails(); } ?></h2>
<div id="wp_file_manager"></div>
</div>