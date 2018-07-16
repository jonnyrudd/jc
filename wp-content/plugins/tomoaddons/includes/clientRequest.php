<?php
	$fv = isset($_POST['fv']) ? json_decode(base64_decode($_POST['fv'])) : '';
	
	$fvarr = array();
	for($i = 0; $i < count($fv[0]); $i++){
		$fvarr[$fv[0][$i]] = $fv[1][$i];
	}
	gravity_form(5, true, true, false, $fvarr, true);
?>
<style>
.gform_wrapper ul.gform_fields li.gfield{
	display: inline-block;
	width: 33%;
}

.gform_wrapper ul.gform_fields li.gfield input:not([type=radio]){
	width: 95%;
}

body .gform_wrapper .top_label div.ginput_container{
	margin-top: 0px !important;
}

body .gform_wrapper ul li.gfield {
	margin-top: 10px !important;
	margin-bottom: 0px !important;
}
</style>