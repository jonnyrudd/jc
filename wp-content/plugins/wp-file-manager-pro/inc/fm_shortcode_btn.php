<?php if (!defined('ABSPATH'))
    exit;
add_thickbox(); 
global $wp_roles;
$roles = $wp_roles->get_names();
$file_operations = array( 'Make Dir' => 'mkdir', 'Make File' => 'mkfile', 'Rename' => 'rename', 'Duplicate' => 'duplicate', 'Paste' => 'paste', 'Ban' => 'ban', 'Archive' => 'archive', 'Extract' => 'extract', 'Copy' => 'copy', 'Cut' => 'cut', 'Edit' => 'edit', 'Delete' => 'rm', 'Download' => 'download', 'Upload' => 'upload', 'Search' => 'search', 'Info' => 'info', 'Help' => 'help' );
$users = get_users();
?>
<a class='button button-primary thickbox' title='WP File Manager PRO' href='#TB_inline?width=100%&height=550&inlineId=fm_pro_shortcode'>
  <?php _e('Insert File Manager Shortcode', 'wp-file-manager-pro'); ?>
</a>
<div id="fm_pro_shortcode" style="display:none;">
     <table class="form-table fm_pro_form">
        <tbody>
        <tr>
        <th scope="row"><label for="blogname">Files and Folders View</label></th>
        <td><select id="fm_view"><option value="grid">Grid</option><option value="list">List</option></select>
        <p>Default: Grid</p>
        </td>
        </tr>
         <tr>
        <th scope="row"><label for="blogname">Language</label></th>
        <td><select name="lang" id="fm_lang">
			<?php foreach($this->fm_languages() as $name => $lang) { ?>
            <option value="<?php echo $lang;?>" <?php echo (isset($opt['lang']) && $opt['lang'] == $lang) ? 'selected="selected"' : '';?>><?php echo $name;?></option>
            <?php } ?>
         </select>
         <p>Default: English</p>
        </td>
        </tr>
         <tr>
        <th scope="row"><label for="blogname">Theme</label></th>
        <td><select name="theme" id="fm_theme">
    <option value="light" selected="selected"><?php  _e('Light - Default', 'wp-file-manager'); ?></option>
    <?php foreach($this->get_themes() as $theme) { ?>
    <option value="<?php echo $theme;?>"><?php echo ucfirst($theme);?></option>
    <?php } ?>
    </select>
         <p>Default: Light</p>
        </td>
        </tr>
        
         <tr>
         <th scope="row"><label for="blogname">Allowed Roles</label></th>
         <td>
         <?php foreach($roles as $key => $role): 
		 if($key == 'administrator') { ?>
          <input type="checkbox" class="fm_user_roles" name="fm_user_roles" value="<?php echo $key;?>" checked="checked" /> <?php echo $role;?> 
         <?php } else { ?>
          <input type="checkbox" class="fm_user_roles" name="fm_user_roles" value="<?php echo $key;?>" /> <?php echo $role;?> 
          <?php } ?>
         <?php endforeach;?>
        </td>
        </tr>
          <tr>
        <th scope="row"><label for="blogname">Access Folder</label></th>
        <td><input name="fm_access_folder" type="text" id="fm_access_folder" value="" class="regular-text">
         <p>Enter Access Folder Path, e.g wp-content, Leave empty, if you want to show folders of the users automatically. For User Folders use * (star) in text field.</p>
        </td>
        </tr>
         <tr>
        <th scope="row"><label for="blogname">Write</label></th>
        <td><input type="radio" class="fm_write" value="true" name="fm_write"/> True  <input type="radio" class="fm_write" value="false" name="fm_write"/> False</td>
        </tr>
         <tr>
        <th scope="row"><label for="blogname">Read</label></th>
        <td><input type="radio" class="fm_read" value="true" name="fm_read"/> True <input type="radio" class="fm_read" value="false" name="fm_read"/> False</td>
        </tr>
        <tr>
        <th scope="row"><label for="blogname">Hide Files</label></th>
        <td><input name="fm_hide_files" type="text" id="fm_hide_files" value="" class="regular-text">
         <p> it will hide mentioned here. Note: seprated by comma(,). Default: Null. e.g wp-content/plugins,wp-config.php</p>
        </td>
        </tr>
         <tr>
        <th scope="row"><label for="blogname">Lock Extensions</label></th>
        <td><input name="fm_lock_extensions" type="text" id="fm_lock_extensions" value="" class="regular-text">
         <p> It will lock mentioned in commas. you can lock more as like ".php,.css,.js" etc. Default: Null</p>
        </td>
        </tr>
         <tr>
         <th scope="row"><label for="blogname">Allowed Operation</label></th>
         <td>
         <?php foreach($file_operations as $o_name => $file_operations): ?>
          <input type="checkbox" class="fm_allowed_operation" name="fm_allowed_operation" value="<?php echo $file_operations;?>" /> <?php echo $o_name;?>
         <?php endforeach;?>
        </td>
        </tr>
         <tr>
         <th scope="row"><label for="blogname">Ban Users</label></th>
         <td>
         <?php foreach($users as $user): ?>
          <input type="checkbox" class="fm_ban_users" name="" value="<?php echo $user->ID;?>" /> <?php echo $user->display_name;?> (#<?php echo $user->ID;?>)
         <?php  endforeach;?>
        </td>
        </tr>
         <tr>
        <th scope="row"><label for="blogname">Date Format</label></th>
        <td><input name="fm_date_format" type="text" id="fm_date_format" value="" class="regular-text">
        <p><a href="http://php.net/manual/en/function.date.php" target="_blank">Click here for Date Formats</a> Default: M d, Y h:i A</p>
        </td>
        </tr>
        <tr>
<th scope="row"><label for="generated_shortcode">Generated Shortcode</label></th>
<td>
<textarea name="fm_generated_shortcode" id="fm_generated_shortcode" class="large-text code" rows="3"></textarea>
</td>
</tr>
<th scope="row"></th>
<td>
<p class="submit"><input name="insert_fm_shortcode" id="insert_fm_shortcode" class="button button-primary" value="Insert Shortcode" type="submit"></p>
</td>
</tr>
        </tbody></table> 
</div>
