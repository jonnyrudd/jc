function generate_fmp_shortcode(){
	var allowed_roles = [];
	var allowed_operations = [];
	var ban_users = [];
	var shortcode = '[wp_file_manager';
	
	 if(jQuery('#fm_view').val() != ''){
		 shortcode +=' view="'+jQuery('#fm_view').val()+'"';
	 }
	 if(jQuery('#fm_lang').val() != ''){
		 shortcode +=' lang="'+jQuery('#fm_lang').val()+'"';
	 }
	  if(jQuery('#fm_theme').val() != ''){
		 shortcode +=' theme="'+jQuery('#fm_theme').val()+'"';
	 }
	 if(jQuery('#fm_date_format').val() != ''){
		 shortcode +=' dateformat="'+jQuery('#fm_date_format').val()+'"';
	 }
	 var total_roles = jQuery( ".fm_user_roles" ).size();
	 jQuery( ".fm_user_roles" ).each(function() {
		var current_element = jQuery(this).prop('checked');
		if(current_element) {
			allowed_roles.push(jQuery(this).val());
		}
	 });
	 if(allowed_roles != '') {
		if(allowed_roles.length == total_roles) {
			shortcode +=' allowed_roles="*"'; 
		} else {
		   shortcode +=' allowed_roles="'+allowed_roles+'"'; 
		}
	 }	
	 if(jQuery('#fm_access_folder').val() != ''){
		 shortcode +=' access_folder="'+jQuery('#fm_access_folder').val()+'"';
	 } 
	 if(jQuery('.fm_write:checked').val() != '' && typeof(jQuery('.fm_write:checked').val()) !== 'undefined'){
		 shortcode +=' write ="'+jQuery('.fm_write:checked').val()+'"';
	 } 
	 if(jQuery('.fm_read:checked').val() != ''  && typeof(jQuery('.fm_read:checked').val()) !== 'undefined'){
		 shortcode +=' read ="'+jQuery('.fm_read:checked').val()+'"';
	 } 
	 if(jQuery('#fm_hide_files').val() != ''){
		 shortcode +=' hide_files ="'+jQuery('#fm_hide_files').val()+'"';
	 } 
	 if(jQuery('#fm_lock_extensions').val() != ''){
		 shortcode +=' lock_extensions ="'+jQuery('#fm_lock_extensions').val()+'"';
	 } 
	  var total_operations = jQuery( ".fm_allowed_operation" ).size();
	 jQuery( ".fm_allowed_operation" ).each(function() {
		var current_el = jQuery(this).prop('checked');
		if(current_el) {
			allowed_operations.push(jQuery(this).val());
		}
	 });
	 if(allowed_operations != '') {
		if(allowed_operations.length == total_operations) {
			shortcode +=' allowed_operations="*"'; 
		} else {
		   shortcode +=' allowed_operations="'+allowed_operations+'"'; 
		}
	 }	
	 
	 jQuery( ".fm_ban_users" ).each(function() {
		var current_els = jQuery(this).prop('checked');
		if(current_els) {
			ban_users.push(jQuery(this).val());
		}
	 });
	 if(ban_users != '') {
		 shortcode +=' ban_user_ids="'+ban_users+'"'; 
	 }
	 
	shortcode += ']';
	jQuery('#fm_generated_shortcode').val(shortcode);
}
jQuery(document).ready(function(e) {
   generate_fmp_shortcode();
});
jQuery('#fm_date_format').keyup(function(e) {
   generate_fmp_shortcode(); 
});
jQuery('#fm_date_format').change(function(e) {
   generate_fmp_shortcode(); 
});
jQuery('#fm_view').change(function(e) {	
   generate_fmp_shortcode(); 
});
jQuery('#fm_lang').change(function(e) {	
   generate_fmp_shortcode(); 
});
jQuery('#fm_theme').change(function(e) {	
   generate_fmp_shortcode(); 
});
jQuery('.fm_user_roles').click(function(e) {	
   generate_fmp_shortcode(); 
});
jQuery('#fm_access_folder').click(function(e) {	
   generate_fmp_shortcode(); 
});
jQuery('#fm_access_folder').change(function(e) {
   generate_fmp_shortcode(); 
});
jQuery('.fm_write').click(function(e) {	
   generate_fmp_shortcode(); 
});
jQuery('.fm_read').click(function(e) {	
   generate_fmp_shortcode(); 
});
jQuery('#fm_hide_files').click(function(e) {	
   generate_fmp_shortcode(); 
});
jQuery('#fm_hide_files').change(function(e) {
   generate_fmp_shortcode(); 
});
jQuery('#fm_lock_extensions').click(function(e) {	
   generate_fmp_shortcode(); 
});
jQuery('#fm_lock_extensions').change(function(e) {
   generate_fmp_shortcode(); 
});
jQuery('.fm_allowed_operation').click(function(e) {	
   generate_fmp_shortcode(); 
});
jQuery('.fm_ban_users').click(function(e) {	
   generate_fmp_shortcode(); 
});
jQuery('#insert_fm_shortcode').click(function(e) {
	e.preventDefault();
    var generated_shortcode = jQuery('#fm_generated_shortcode').val();
	window.send_to_editor(generated_shortcode);
   jQuery('.shortcode_plus_container_pop').hide();
});