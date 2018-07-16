<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap">
<div class="setting_pro_wrap">
<h1 class="title"><?php _e('Settings - File Manager','wp-file-manager-pro'); ?></h1>
<?php
$file_operations = array( 'mkdir', 'mkfile', 'rename', 'duplicate', 'paste', 'ban', 'archive', 'extract', 'copy', 'cut', 'edit','rm','download', 'upload', 'search', 'info', 'help','empty' );
global $wp_roles;
$roles = $wp_roles->get_names();
$allusers = get_users() ;
$wp_filemanager_options = array();
$opt = get_option('wp_filemanager_options');
if(isset($_POST['save_wp_filemanager_settings']) && wp_verify_nonce( $_POST['wp_filemanager_nonce_field'], 'wp_filemanager_action' )):
	_e("<strong>Saving Please wait...</strong>", 'wp-file-manager-pro');
	$needToUnset = array('save_wp_filemanager_settings'); //no need to save in Database
	foreach($needToUnset as $noneed):
	  unset($_POST[$noneed]);
	endforeach;
	$unsetArray = array('select_user_roles','restrict_folders','restrict_files','userrole_fileoperations','select_users','restrict_user_folders','restrict_user_files','users_fileoperations');
	foreach($unsetArray as $unsetTHIS):
		 unset($_POST[$unsetTHIS][0]);
	endforeach;
		foreach($_POST as $key => $val):
		$wp_filemanager_options[$key] = $val;
		endforeach;
		 $saveSettings = update_option('wp_filemanager_options', $wp_filemanager_options );
		if($saveSettings){
		   mk_file_folder_manager::redirect('?page=wp_file_manager_settings&msg=1');	
		}
		else {
		mk_file_folder_manager::redirect('?page=wp_file_manager_settings&msg=2');	
		}
endif;
$themes = mk_file_folder_manager::getFfmThemes();
if(isset($_GET['msg']) && $_GET['msg'] == '1'):?>
<div class="updated settings-error notice is-dismissible" id="setting-error-settings_updated"> 
<p><strong><?php _e('Settings saved.','wp-file-manager-pro'); ?></strong></p><button class="notice-dismiss" type="button"><span class="screen-reader-text"><?php _e('Dismiss this notice.','wp-file-manager-pro'); ?></span></button></div>
<?php elseif(isset($_GET['msg']) && $_GET['msg'] == '2'):?>
<div class="error updated settings-error notice is-dismissible" id="setting-error-settings_updated"> 
<p><strong><?php _e('Settings not saved.','wp-file-manager-pro'); ?></strong></p><button class="notice-dismiss" type="button"><span class="screen-reader-text"><?php _e('Dismiss this notice.','wp-file-manager-pro'); ?></span></button></div>
<?php endif; ?>
<form action="" method="post" id="ffm_manager">
<?php  wp_nonce_field( 'wp_filemanager_action', 'wp_filemanager_nonce_field' ); ?>

<hr />

<div class="set_tab_dv">
    <ul class="setting_pro_tab">
        <li class="current">
        <a href="#General">
        <span class="icon"><img src="<?php echo plugins_url( 'images/general_setting_icon.png', dirname(__FILE__) ); ?>"/></span>
        <?php _e('General','wp-file-manager-pro'); ?></a>
        </li>
        <li>
        <a href="#Code_Editor">
         <span class="icon"><img src="<?php echo plugins_url( 'images/code-editor-icon.png', dirname(__FILE__) ); ?>"/></span>
         <?php _e('Code Editor','wp-file-manager-pro'); ?></a>
        </li>
        <li>
        <a href="#User_Restriction">
         <span class="icon"><img src="<?php echo plugins_url( 'images/user-icon.png', dirname(__FILE__) ); ?>"/></span>
         <?php _e('User Restrictions','wp-file-manager-pro'); ?></a>
        </li>
        <li>
        <a href="#User_Role_Restrictions">
         <span class="icon"><img src="<?php echo plugins_url( 'images/user-role-icon.png', dirname(__FILE__) ); ?>"/></span>
         <?php _e('User Role Restrictions','wp-file-manager-pro'); ?>
         </a>
        </li>
    </ul>

<div class="setting_pro_tab_content" id="General" style="display:block;">
<div class="frm_grp">
<label for="default_category" class="label_heading"><span class="arrow_icon">&#9758;</span> <?php _e('Select User Roles to access WP File manager','wp-file-manager-pro'); ?></label>
<div class="chk_list">
<div class="frm_grp_inner">
<?php foreach($roles as $key => $role):
    if($key != 'administrator'):?>
    <span class="chk_span_outer"><span class="chk_box_span"><input type="checkbox" value="<?php echo $key; ?>"  name="fm_user_roles[]"
     <?php 
  if(!empty($opt['fm_user_roles'])):
   if(in_array($key, $opt['fm_user_roles'])): 
    echo ' checked="checked"'; 
   endif;
  endif; ?>     
    /> </span> <!--chk_box_span--> <span class="chk_box_txt"> <?php echo $role; ?></span> </span> <!--chk_span_outer-->
    <?php endif; endforeach; ?><p><?php _e('Allow user roles to access WP File Manager','wp-file-manager-pro'); ?></p>
    </div> <!--frm_grp_inner-->
    </div>
</div>  <!--frm_grp-->

<div class="frm_grp"><label for="default_category"  class="label_heading"><span class="arrow_icon">&#9758;</span> <?php _e('Seperate or Private Folder Access','wp-file-manager-pro'); ?></label>
<div class="frm_grp_inner">
<textarea class="large-text code" rows="3" placeholder="wp-content/plugins" name="private_folder_access"><?php echo !empty($opt['private_folder_access']) ? $opt['private_folder_access'] : '';?></textarea><p><?php _e('File manager will access this folder path. Else, will access root directory. <strong>e.g wp-content/plugins</strong>. <strong>Note:</strong> Will Valid for all user roles.','wp-file-manager-pro'); ?></p>
</div> <!--frm_grp_inner-->
</div> <!--frm_grp-->

<div class="frm_grp">
<label for="default_category" class="label_heading"><span class="arrow_icon">&#9758;</span> <?php _e('Maximum Upload Size','wp-file-manager-pro'); ?></label>
<div class="frm_grp_inner">
<input type="number" min="2" value="<?php echo (isset($opt['fm_max_upload_size']) && !empty($opt['fm_max_upload_size'])) ? $opt['fm_max_upload_size'] : '2';?>" name="fm_max_upload_size" /> <strong><?php _e('MB','wp-file-manager-pro'); ?> </strong><p><?php _e('Allow users to upload file of maximum size','wp-file-manager-pro'); ?></p>
</div> <!--frm_grp_inner-->
</div> <!--frm_grp-->
<div class="frm_grp">
<label for="default_category" class="label_heading"><span class="arrow_icon">&#9758;</span> <?php _e('Notification Email','wp-file-manager-pro'); ?></label>
<div class="frm_grp_inner">
<input type="text" value="<?php echo (isset($opt['fm_notification_email']) && !empty($opt['fm_notification_email'])) ? $opt['fm_notification_email'] : get_option('admin_email');?>" name="fm_notification_email" /> <p><?php _e('Sends Notifications to this email','wp-file-manager-pro'); ?></p>
</div> <!--frm_grp_inner-->
</div> <!--frm_grp-->
<?php /* Added->Upload Email Sent : 31:March:2017*/ ?>
<div class="frm_grp">
<label for="default_category" class="label_heading"><span class="arrow_icon">&#9758;</span> <?php _e('Send Notifications to admin on file upload ?','wp-file-manager-pro'); ?></label>
<div class="frm_grp_inner"><input type="checkbox" value="yes" name="allow_upload_notifications" 
<?php echo( isset($opt['allow_upload_notifications']) && $opt['allow_upload_notifications'] == 'yes') ? 'checked="checked"' : '';?>
/>
 <?php _e('Check to allow file upload notifications. Mail will sent to admin email.','wp-file-manager-pro'); ?><p style="color:#F00"><?php _e('Note: This feature is using wp_mail(), Make sure <a href="https://developer.wordpress.org/reference/functions/wp_mail/" title="Read More" target="_blank">wp_mail</a> is working or not.','wp-file-manager-pro'); ?></p>
</div> <!--frm_grp_inner-->
</div> <!--frm_grp-->

<div class="frm_grp">
<label for="allow_download_notifications" class="label_heading"><span class="arrow_icon">&#9758;</span> <?php _e('Send Notifications to admin on file download ?','wp-file-manager-pro'); ?></label>
<div class="frm_grp_inner"><input type="checkbox" value="yes" name="allow_download_notifications" 
<?php echo( isset($opt['allow_download_notifications']) && $opt['allow_download_notifications'] == 'yes') ? 'checked="checked"' : '';?>
/>
 <?php _e('Check to allow file download notifications. Mail will sent to admin email.','wp-file-manager-pro'); ?><p style="color:#F00"><?php _e('Note: This feature is using wp_mail(), Make sure <a href="https://developer.wordpress.org/reference/functions/wp_mail/" title="Read More" target="_blank">wp_mail</a> is working or not.','wp-file-manager-pro'); ?></p>
</div> <!--frm_grp_inner-->
</div> <!--frm_grp-->

<div class="frm_grp">
<label for="allow_edit_notifications" class="label_heading"><span class="arrow_icon">&#9758;</span> <?php _e('Send Notifications to admin on file edit ?','wp-file-manager-pro'); ?></label>
<div class="frm_grp_inner"><input type="checkbox" value="yes" name="allow_edit_notifications" 
<?php echo( isset($opt['allow_edit_notifications']) && $opt['allow_edit_notifications'] == 'yes') ? 'checked="checked"' : '';?>
/>
 <?php _e('Check to allow file edit notifications. Mail will sent to admin email.','wp-file-manager-pro'); ?><p style="color:#F00"><?php _e('Note: This feature is using wp_mail(), Make sure <a href="https://developer.wordpress.org/reference/functions/wp_mail/" title="Read More" target="_blank">wp_mail</a> is working or not.','wp-file-manager-pro'); ?></p>
</div> <!--frm_grp_inner-->
</div> <!--frm_grp-->
<?php /* End email sent */ ?>

<div class="frm_grp">
<label for="default_category" class="label_heading"><span class="arrow_icon">&#9758;</span> <?php _e('Select Filemanager Language','wp-file-manager-pro'); ?></label>
<div class="frm_grp_inner">
<select name="lang">
<?php foreach($this->fm_languages() as $name => $lang) { ?>
<option value="<?php echo $lang;?>" <?php echo (isset($opt['lang']) && $opt['lang'] == $lang) ? 'selected="selected"' : '';?>><?php echo $name;?></option>
<?php } ?>
</select>

 <strong><?php _e('Default: English','wp-file-manager-pro'); ?> </strong><p><?php _e('You can select any language for filemanager.','wp-file-manager-pro'); ?></p>
</div> <!--frm_grp_inner-->
</div> <!--frm_grp-->


<div class="frm_grp">
<label for="default_category" class="label_heading"><span class="arrow_icon">&#9758;</span> <?php _e('Select Filemanager Theme','wp-file-manager-pro'); ?></label>
<div class="frm_grp_inner">
<select name="theme" id="fm_theme">
<option value="light" <?php echo (isset($opt['theme']) && $opt['theme'] == 'light') ? 'selected="selected"' : ''; ?>><?php  _e('Light - Default', 'wp-file-manager'); ?></option>
<?php foreach($this->get_themes() as $theme) { ?>
<option value="<?php echo $theme;?>" <?php echo (isset($opt['theme']) && $opt['theme'] == $theme) ? 'selected="selected"' : '';?>><?php echo ucfirst($theme);?></option>
<?php } ?>
</select>

 <strong><?php _e('Default: Light','wp-file-manager-pro'); ?> </strong><p><?php _e('You can select any theme for filemanager.','wp-file-manager-pro'); ?></p>
</div> <!--frm_grp_inner-->
</div> <!--frm_grp-->

<div class="frm_grp">
<label for="allow_edit_notifications" class="label_heading"><span class="arrow_icon">&#9758;</span> <?php _e('Enable "Insert File Manager Shortcode" Button with Content Editor ?','wp-file-manager-pro'); ?></label>
<div class="frm_grp_inner"><input type="checkbox" value="yes" name="allow_shortcode_btn_editor"
<?php echo( isset($opt['allow_shortcode_btn_editor']) && $opt['allow_shortcode_btn_editor'] == 'yes') ? 'checked="checked"' : '';?>
/>
 <?php _e('Check to allow "Insert File Manager Shortcode" in post edit page with editor.','wp-file-manager-pro'); ?></p>
</div> <!--frm_grp_inner-->
</div> <!--frm_grp-->




</div> <!--General-->

<div class="setting_pro_tab_content" id="Code_Editor">

<div class="frm_grp">
<label for="default_category" class="label_heading"><span class="arrow_icon">&#9758;</span> <?php _e('Code editor allow fullscreen?','wp-file-manager-pro'); ?></label>
<div class="frm_grp_inner"><input type="checkbox" value="yes" name="allow_fullscreen" <?php echo (isset($opt['allow_fullscreen']) && ($opt['allow_fullscreen'] == 'yes')) ? 'checked="checked"' : '';?>/> <?php _e('Check to allow fullscreen code editor','wp-file-manager-pro'); ?></div>
</div> <!--frm_grp-->

<div class="frm_grp">
<label for="default_category" class="label_heading"><span class="arrow_icon">&#9758;</span> <?php _e('Select theme for Code Editor.','wp-file-manager-pro'); ?></label>
<div class="frm_grp_inner">
<select id="code_editor_theme" name="code_editor_theme">
<option value="default"><?php _e('Default','wp-file-manager-pro'); ?></option>
<?php if(!empty($themes) && is_array($themes)):
foreach($themes as $key => $theme):?>
<option value="<?php echo $key;?>" <?php echo ($opt['code_editor_theme'] == $key) ? 'selected = "selected"' : '';?>><?php echo ucwords(str_replace('-',' ',$theme));?></option>
<?php endforeach; endif; ?>
</select> <p><?php _e('Check to allow fullscreen code editor. For more information please visit <a href="http://codemirror.net/demo/theme.html" target="_blank">this link</a>','wp-file-manager-pro'); ?></p>
</div> <!--frm_grp_inner-->
</div> <!--frm_grp-->

</div> <!--Code_Editor-->

<div class="setting_pro_tab_content" id="User_Role_Restrictions">
<h2 class="title"><?php _e('Disable or Ban WP File Manager Operations to User Roles','wp-file-manager-pro'); ?></h2>
<p><?php _e('Select user role and disable operation.','wp-file-manager-pro'); ?></p>
<div class="click_rule_dv">
<span class="txt_title"><label for="default_category"> <?php _e('Click to Add Rules','wp-file-manager-pro'); ?></label></span>
<button id="add_rule_for_userrole" name="add_rule_for_userrole" class="button button-primary pro_btn"><?php _e('Add rule for User Roles','wp-file-manager-pro'); ?></button>
</div>

<div class="form-table item_cliche hide">
<div class="group_box_wrap control-tr">
<div class="group_box_left">
<div class="user_roles">
<span class="lbl_txt"><?php _e('If User role is','wp-file-manager-pro'); ?></span>
<select name="select_user_roles[]">
	<?php foreach($roles as $key => $role):?>
    <option value="<?php echo $key; ?>"><?php echo $role; ?></option>
    <?php endforeach; ?>
	</select> 
    <span class="lbl_txt_rt"><?php _e('Then','wp-file-manager-pro'); ?> </span>
    </div> <!--user_roles-->
    
    <div class="frm_grp">
    <label for="default_post_format" class="label_heading"><span class="arrow_icon">&#9758;</span> <?php _e('Disable Operations','wp-file-manager-pro'); ?></label>
    <div class="frm_grp_inner">
    <fieldset>
<?php foreach($file_operations as $file_operation):?><label for="users_can_register"><?php echo $file_operation;?> 
 <input type="checkbox" value="<?php echo $file_operation;?>" class="tmpchkname"/></label>
<?php endforeach;?>
</fieldset>
    </div> <!--frm_grp_inner-->
    </div> <!--frm_grp-->
    
    <div class="frm_grp">
    <label for="default_post_format" class="label_heading"><span class="arrow_icon">&#9758;</span> <?php _e('Seperate or private folder access <strong>e.g wp-content/themes</strong> etc. Note It will overide "Private Folder Access" settings.','wp-file-manager-pro'); ?></label>
    <div class="frm_grp_inner">
    <textarea rows="3" class="large-text code" name="seprate_folder[]" placeholder="wp-content/plugins"></textarea>
    </div> <!--frm_grp_inner-->
    </div> <!--frm_grp-->
    
<div class="frm_grp">
  <label for="default_post_format" class="label_heading"><span class="arrow_icon">&#9758;</span> <?php _e('Enter Folder or Files Paths That You want to <strong>Hide</strong> e.g wp-content/themes|wp-content/plugins. <strong>Note: Multiple separated by Vertical Bar (|)</strong>.','wp-file-manager-pro'); ?></label>
    <div class="frm_grp_inner">
<textarea rows="3" class="large-text code" name="restrict_folders[]" placeholder="wp-content/themes|wp-content/plugins"></textarea>
    </div> <!--frm_grp_inner-->
    </div> <!--frm_grp-->
    
<div class="frm_grp">
 <label for="default_post_format" class="label_heading"><span class="arrow_icon">&#9758;</span> <?php _e('Enter file extensions which you want to Lock. <strong>e.g .php|.png|.css</strong> etc','wp-file-manager-pro'); ?></label>
    <div class="frm_grp_inner">
<textarea rows="3" class="large-text code" name="restrict_files[]" placeholder=".php|.png|.css"></textarea>
    </div> <!--frm_grp_inner-->
    </div> <!--frm_grp-->
     </div> <!--group_box_left-->
     <div class="group_box_right"><div class="group_box_right_tbl"><div class="group_box_right_cel"><input type="button" class="button delete_item del_btn_pro" value="Delete" title="Remove" /></div></div></div> <!--group_box_right-->
</div> <!--group_box_wrap-->

</div>
<div class="big_social_cont">
<?php $totalRoles = isset($opt['select_user_roles']) ? count($opt['select_user_roles']) : 0;
for($i=1; $i<=$totalRoles; $i++){?>

<div class="group_box_wrap control-tr">
<div class="group_box_left">
<div class="user_roles">
<span class="lbl_txt"><?php _e('If User role is','wp-file-manager-pro'); ?></span>
<select name="select_user_roles[]">
	<?php foreach($roles as $key => $role):?>
    <option value="<?php echo $key; ?>"
     <?php if($key == $opt['select_user_roles'][$i]): echo 'selected = "selected"'; endif;?>
     ><?php echo $role; ?></option>
    <?php endforeach; ?>
	</select> 
    <span class="lbl_txt_rt"><?php _e('Then','wp-file-manager-pro'); ?> </span>
</div> <!--user_roles-->

    <div class="frm_grp">
  <label for="default_post_format" class="label_heading"><span class="arrow_icon">&#9758;</span> <?php _e('Disable Operations','wp-file-manager-pro'); ?></label>
    <div class="frm_grp_inner">
<fieldset>
<?php

 foreach($file_operations as $key => $file_operation):?><label for="users_can_register"><?php echo $file_operation;?> 
 <input type="checkbox" value="<?php echo $file_operation;?>" name="userrole_fileoperations_<?php echo $i?>[]"
 <?php 
  if(!empty($opt['userrole_fileoperations_'.$i])):
   if(in_array($file_operation, $opt['userrole_fileoperations_'.$i])): 
   echo ' checked="checked"'; 
   endif;
  endif; ?> 
 class="tmpchkname" /> </label>
<?php endforeach; ?>
</fieldset>
    </div> <!--frm_grp_inner-->
    </div> <!--frm_grp-->

    <div class="frm_grp">
   <label class="label_heading" for="default_post_format"><span class="arrow_icon">&#9758;</span> <?php _e('Seperate or private folder access <strong>e.g wp-content/themes</strong> etc. Note It will overide "Private Folder Access" settings.','wp-file-manager-pro'); ?></label>
    <div class="frm_grp_inner">
<textarea rows="3" class="large-text code" name="seprate_folder[]" placeholder="wp-content/plugins"><?php echo $opt['seprate_folder'][$i]?></textarea>
    </div> <!--frm_grp_inner-->
    </div> <!--frm_grp-->
    
        <div class="frm_grp">
<label for="default_post_format" class="label_heading"><span class="arrow_icon">&#9758;</span> <?php _e('Enter Folder or Files Paths That You want to <strong>Hide</strong> e.g wp-content/themes|wp-content/plugins. <strong>Note: Multiple separated by Vertical Bar (|)</strong>.','wp-file-manager-pro'); ?></label>
    <div class="frm_grp_inner">
<textarea rows="3" class="large-text code" name="restrict_folders[]" placeholder="wp-content/themes|wp-content/plugins"><?php echo $opt['restrict_folders'][$i]?></textarea>
    </div> <!--frm_grp_inner-->
    </div> <!--frm_grp-->
    
            <div class="frm_grp">
<label for="default_post_format" class="label_heading"><span class="arrow_icon">&#9758;</span> <?php _e('Enter file extensions which you want to Lock. <strong>e.g .php|.png|.css</strong> etc','wp-file-manager-pro'); ?></label>
    <div class="frm_grp_inner">
<textarea rows="3" class="large-text code" name="restrict_files[]" placeholder=".php|.png|.css"><?php echo $opt['restrict_files'][$i]?></textarea>
    </div> <!--frm_grp_inner-->
    </div> <!--frm_grp-->
    
</div> <!--group_box_left-->

<div class="group_box_right"><div class="group_box_right_tbl"><div class="group_box_right_cel"><input type="button" class="button delete_item del_btn_pro" value="Delete" title="Remove" /> </div></div></div> <!--group_box_right-->
     
</div> <!--group_box_wrap-->

<?php } ?>
</div>
</div> <!--User_Restriction-->

<div class="setting_pro_tab_content" id="User_Restriction">
<h2 class="title"><?php _e('Disable or Ban WP File Manager Operations to Users','wp-file-manager-pro'); ?></h2>
<p><?php _e('Select users and disable operation.','wp-file-manager-pro'); ?></p>
<div class="click_rule_dv">
<span class="txt_title"><label for="default_category"><?php _e('Click to Add Rules','wp-file-manager-pro'); ?></label></span>
<button id="add_rule_for_user" name="add_rule_for_user" class="button button-primary pro_btn"><?php _e('Add rule for Users','wp-file-manager-pro'); ?></button>
</div> <!--click_rule_dv-->

<div class="form-table item_cliche_user hide">

<div class="group_box_wrap control-tr-user">
<div class="group_box_left">
<div class="user_roles">
<span class="lbl_txt"><?php _e('If User is','wp-file-manager-pro'); ?></span>
<select name="select_users[]">
	<?php foreach($allusers as $user):?>
    <option value="<?php echo $user->user_login; ?>"><?php echo $user->user_login; ?></option>
    <?php endforeach; ?>
	</select>
    <span class="lbl_txt_rt"><?php _e('Then','wp-file-manager-pro'); ?> </span>
</div> <!--user_roles-->

    <div class="frm_grp">
    <label for="default_post_format" class="label_heading"><span class="arrow_icon">&#9758;</span> <?php _e('Disable Operations','wp-file-manager-pro'); ?></label>
    <div class="frm_grp_inner">
<fieldset>
<?php foreach($file_operations as $file_operation):?><label for="users_can_register">
<?php echo $file_operation;?> <input type="checkbox" value="<?php echo $file_operation;?>" class="tmpchkuname"/> </label>
<?php endforeach;?>
</fieldset>
    </div> <!--frm_grp_inner-->
    </div> <!--frm_grp-->

  <div class="frm_grp">
  <label class="label_heading" for="default_post_format"><span class="arrow_icon">&#9758;</span> <?php _e('Seperate or private folder access <strong>e.g wp-content/themes</strong> etc. Note It will overide "Private Folder Access" settings.','wp-file-manager-pro'); ?></label>
    <div class="frm_grp_inner">
 <textarea rows="3" class="large-text code" name="user_seprate_folder[]" placeholder="wp-content/plugins"></textarea>
    </div> <!--frm_grp_inner-->
    </div> <!--frm_grp-->
 
   <div class="frm_grp">
 <label for="default_post_format" class="label_heading"><span class="arrow_icon">&#9758;</span> <?php _e('Enter Folder or Files Paths That You want to <strong>Hide</strong> e.g wp-content/themes|wp-content/plugins. <strong>Note: Multiple separated by Vertical Bar (|)</strong>.','wp-file-manager-pro'); ?></label>
    <div class="frm_grp_inner">
<textarea rows="3" class="large-text code" name="restrict_user_folders[]" placeholder="wp-content/themes|wp-content/plugins"></textarea>
    </div> <!--frm_grp_inner-->
    </div> <!--frm_grp-->
  
     <div class="frm_grp">
<label for="default_post_format" class="label_heading"><span class="arrow_icon">&#9758;</span> <?php _e('Enter file extensions which you want to Lock. <strong>e.g .php|.png|.css</strong> etc','wp-file-manager-pro'); ?></label>
    <div class="frm_grp_inner">
<textarea rows="3" class="large-text code" name="restrict_user_files[]" placeholder=".php|.png|.css"></textarea>
    </div> <!--frm_grp_inner-->
    </div> <!--frm_grp-->
         
</div> <!--group_box_left-->

 <div class="group_box_right"><div class="group_box_right_tbl"><div class="group_box_right_cel"> <input type="button" class="button delete_item_user del_btn_pro" value="Delete" title="Remove" /> </div></div></div> <!--group_box_right-->
 
</div> <!--group_box_wrap-->
</div>

<?php /* OUT PUT USER */?>
<div class="big_social_cont_user">
<?php $totalRoles = isset($opt['select_users']) ? count($opt['select_users']) : 0;
for($i=1; $i<=$totalRoles; $i++){?>

<div class="group_box_wrap control-tr-user">
<div class="group_box_left">
<div class="user_roles">
<span class="lbl_txt"><?php _e('If User is','wp-file-manager-pro'); ?></span>
<select name="select_users[]">
	<?php foreach($allusers as $user):?>
    <option value="<?php echo $user->user_login; ?>" <?php if($user->user_login == $opt['select_users'][$i]): echo 'selected = "selected"'; endif;?>><?php echo $user->user_login; ?></option>
    <?php endforeach; ?>
	</select> 
    <span class="lbl_txt_rt"><?php _e('Then','wp-file-manager-pro'); ?> </span>
</div> <!--user_roles-->

  <div class="frm_grp">
    <label for="default_post_format" class="label_heading"><span class="arrow_icon">&#9758;</span> <?php _e('Disable Operations','wp-file-manager-pro'); ?></label>
    <div class="frm_grp_inner">
     <fieldset>
<?php
 foreach($file_operations as $key => $file_operation):?><label for="users_can_register">
<?php echo $file_operation;?> <input type="checkbox" value="<?php echo $file_operation;?>" name="users_fileoperations_<?php echo $i?>[]"
 <?php 
 if(!empty($opt['users_fileoperations_'.$i])):
  if(in_array($file_operation, $opt['users_fileoperations_'.$i])): 
   echo ' checked="checked"';
  endif;
 endif;
 ?> 
 class="tmpchkuname"/> </label>
<?php endforeach;?>
</fieldset>
    </div> <!--frm_grp_inner-->
    </div> <!--frm_grp-->
    
<div class="frm_grp">
<label class="label_heading" for="default_post_format"><span class="arrow_icon">&#9758;</span> <?php _e('Seperate or private folder access <strong>e.g wp-content/themes</strong> etc. Note It will overide "Private Folder Access" settings.','wp-file-manager-pro'); ?></label>
    <div class="frm_grp_inner">
<textarea rows="3" class="large-text code" name="user_seprate_folder[]" placeholder="wp-content/plugins"><?php echo $opt['user_seprate_folder'][$i];?></textarea>
    </div> <!--frm_grp_inner-->
    </div> <!--frm_grp-->
 
 <div class="frm_grp">
<label class="label_heading" for="default_post_format"><span class="arrow_icon">&#9758;</span> <?php _e('Enter Folder or Files Paths That You want to <strong>Hide</strong> e.g wp-content/themes|wp-content/plugins. <strong>Note: Multiple separated by Vertical Bar (|)</strong>.','wp-file-manager-pro'); ?></label>
    <div class="frm_grp_inner">
<textarea rows="3" class="large-text code" name="restrict_user_folders[]" placeholder="wp-content/themes|wp-content/plugins"><?php echo $opt['restrict_user_folders'][$i];?></textarea>
    </div> <!--frm_grp_inner-->
    </div> <!--frm_grp-->
 
  <div class="frm_grp">
<label class="label_heading" for="default_post_format"><span class="arrow_icon">&#9758;</span> <?php _e('Enter file extensions which you want to Lock. <strong>e.g .php|.png|.css</strong> etc','wp-file-manager-pro'); ?></label>
    <div class="frm_grp_inner">
<textarea rows="3" class="large-text code" name="restrict_user_files[]" placeholder=".php|.png|.css"><?php echo $opt['restrict_user_files'][$i];?></textarea>
    </div> <!--frm_grp_inner-->
    </div> <!--frm_grp-->
    
</div> <!--group_box_left-->
 <div class="group_box_right"><div class="group_box_right_tbl"><div class="group_box_right_cel"> <input type="button" class="button delete_item_user del_btn_pro" value="Delete" title="Remove" /></div></div></div> <!--group_box_right-->
</div> <!--group_box_wrap-->
<?php } ?>
</div>
</div> <!--User_Role_Restrictions-->

</div> <!--set_tab_dv-->
<p class="submit"><input type="submit" value="Save Changes" class="button button-primary" id="submit" name="save_wp_filemanager_settings"></p></form>

</div> <!--setting_pro_wrap-->

</div>
<script>
jQuery(document).ready(function(e){
<?php $opt = get_option('wp_file_manager_pro');
if(empty($opt['ispro']) && empty($opt['serialkey']))
{ ?>
setInterval(function(){ alert("Invalid Licence Key"); }, 5000);
<?php } ?>
	/* User Roles */
	jQuery('#add_rule_for_userrole').click(function(e){
		e.preventDefault();
		var totalroleDivs = jQuery('.control-tr').length;
		var totalroles = '<?php echo count($roles)?>';
		if(totalroleDivs > totalroles)
		{
		alert('You can not add more rules because only '+totalroles+' user roles exits!');
		}
		else
		{	
		jQuery('.big_social_cont').append( jQuery('.item_cliche').html() );
		jQuery('.control-tr:eq('+totalroleDivs+') .tmpchkname').attr('name','userrole_fileoperations_'+totalroleDivs+'[]');
		}
	});
	jQuery('.delete_item').live('click', function(){
		var point = jQuery(this).parents('.control-tr');
		point.fadeOut(500, function(){
			 jQuery(this).replaceWith('');
			var totalroleDivs = jQuery('.control-tr').length;
		for(i=1; i<=totalroleDivs-1; i++){
		jQuery('.control-tr:eq('+i+') .tmpchkname').attr('name','userrole_fileoperations_'+i+'[]');
		}	
		 });		
	});	
	/* Users */
	jQuery('#add_rule_for_user').click(function(e){
		e.preventDefault();
		var totalUserDivs = jQuery('.control-tr-user').length;
		var totalUsers = '<?php echo count($allusers)?>';
		if(totalUserDivs > totalUsers)
		{
		alert('You can not add more rules because only '+totalUsers+' users exits!');
		}
		else
		{
		jQuery('.big_social_cont_user').append( jQuery('.item_cliche_user').html() );
		jQuery('.control-tr-user:eq('+totalUserDivs+') .tmpchkuname').attr('name','users_fileoperations_'+totalUserDivs+'[]');
		}
	});
	jQuery('.delete_item_user').live('click', function(){
		var point = jQuery(this).parents('.control-tr-user');
		point.fadeOut(500, function(){ 		
		jQuery(this).replaceWith('');
		var totalUserDivs = jQuery('.control-tr-user').length;
		for(i=1; i<=totalUserDivs-1; i++){
		  jQuery('.control-tr-user:eq('+i+') .tmpchkuname').attr('name','users_fileoperations_'+i+'[]');
		}
		 });
	});
});

jQuery(document).ready(function(e) {
      jQuery(".setting_pro_tab a").click(function(event) {
        event.preventDefault();
        jQuery(this).parent().addClass("current");
        jQuery(this).parent().siblings().removeClass("current");
        var tab = jQuery(this).attr("href");
        jQuery(".setting_pro_tab_content").not(tab).css("display", "none");
        jQuery(tab).fadeIn();
    });
});
</script>