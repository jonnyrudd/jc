<?php 
if(!class_exists('mk_fm_controller')) {
	class mk_fm_controller {
		/* Auto */
		public function __construct() {
			$ajax_modes = array('uc','dc','fn');
			foreach($ajax_modes as $ajax_mode) {
			 add_action('wp_ajax_mk_file_folder_manager_'.$ajax_mode.'', array(&$this, 'mk_file_folder_manager_'.$ajax_mode.'_callback'));	
			}
		}
		 /* UC */
		public function mk_file_folder_manager_uc_callback() {
			  $admin_email = get_option('admin_email');
			  $opt = get_option('wp_filemanager_options');
			  if(isset($opt['fm_notification_email'])) {
				$admin_email = $opt['fm_notification_email'];
			  }
			  $siteurl = site_url();
			  $userID = isset($_POST['uploadedby']) ? $_POST['uploadedby'] : '';
			  $uploadedfiles = isset($_POST['uploadefiles']) ? $_POST['uploadefiles'] : '';
			  $user_info = get_userdata($userID);
			  if(!empty($user_info) && !empty($uploadedfiles)) {
				 $files = '';
				 $fileCount = 1;
				 foreach($uploadedfiles as $uploadedfile) {
					$files .= '('.$fileCount.')'.$uploadedfile.', '; 
				 $fileCount++;	
				 }
				 $user_id = $user_info->ID;
				 $user_email = $user_info->user_email;
				 $user_dn = $user_info->display_name;
				 $subject = 'Files Downloaded';
				 $body = 'Hello, Admin ('.$admin_email.') - '.$siteurl;
				 $body .= ', Some Files are uploaded on filemanager of your website '.$siteurl.' by User: '.$user_dn.' ('.$user_email.') (ID: '.$user_id.'). Uploaded Files are '.$files;
				 $this->send($admin_email, $subject, $body);
			  }
			  die;
		  }
	  /* DC */
	   public function mk_file_folder_manager_dc_callback() {		
			$admin_email = get_option('admin_email');
			$opt = get_option('wp_filemanager_options');
			if(isset($opt['fm_notification_email'])) {
			$admin_email = $opt['fm_notification_email'];
			}
			  $siteurl = site_url();
			  $userID = isset($_POST['downloadedby']) ? $_POST['downloadedby'] : '';
			  $downloadedfiles = isset($_POST['downloadedFiles']) ? $_POST['downloadedFiles'] : '';
			  $user_info = get_userdata($userID);
			  if(!empty($user_info) && !empty($downloadedfiles)) {
				 $files = '';
				 $fileCount = 1;
				 foreach($downloadedfiles as $downloadedfile) {
					$files .= '('.$fileCount.')'.$downloadedfile.', '; 
				 $fileCount++;	
				 }
				 $user_id = $user_info->ID;
				 $user_email = $user_info->user_email;
				 $user_dn = $user_info->display_name;
				 $subject = 'Files Uploaded';
				 $body = 'Hello, Admin ('.$admin_email.') - '.$siteurl;
				 $body .= ', Some Files are downloaded from filemanager of your website '.$siteurl.' by User: '.$user_dn.' ('.$user_email.') (ID: '.$user_id.'). Downloaded Files are '.$files;
				 $this->send($admin_email, $subject, $body);
			  }
			  die;	  
	   }
	    /* fn */
	   public function mk_file_folder_manager_fn_callback() {		
				$admin_email = get_option('admin_email');
				$opt = get_option('wp_filemanager_options');
				if(isset($opt['fm_notification_email'])) {
				$admin_email = $opt['fm_notification_email'];
				}
			  $siteurl = site_url();
			  $userID = isset($_POST['editedby']) ? $_POST['editedby'] : ''; 
			  $file = isset($_POST['file']) ? $_POST['file'] : ''; 
			  $user_info = get_userdata($userID);
			    if(!empty($user_info) && !empty($file)) {
					 $user_id = $user_info->ID;
					 $user_email = $user_info->user_email;
					 $user_dn = $user_info->display_name;
					 $subject = 'File Modified';
					 $body = 'Hello, Admin ('.$admin_email.') - '.$siteurl;
					 $body .= ', '.$file.' is modified or edited on filemanager of your website '.$siteurl.' by User: '.$user_dn.' ('.$user_email.') (ID: '.$user_id.')';
					 $this->send($admin_email, $subject, $body);					
				}
			  die;	  
	   }
	   /* Send */
	   public function send($email, $subject, $body) {
		  $headers = array('Content-Type: text/html; charset=UTF-8'); 
		  $send_Mail = wp_mail( $email, $subject, $body, $headers );	
		   if($send_Mail) {
					 echo 'Mail Sent!';
				 } else {
					 echo 'Not Sent!';
				 }	  
	  }
	}
}
?>
