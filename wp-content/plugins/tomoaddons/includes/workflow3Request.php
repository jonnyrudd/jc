<?php
	session_start();
	if(isset($_SESSION['workflow3pool']) && count($_SESSION['workflow3pool']) > 0){
		require __DIR__ . '/../vendor/autoload.php';
		
		try{
			// Create processing temporary folder
			$tmpFolderTail = date("Ymd") . '_wf3_' . wp_get_current_user()->user_login . '_' . time();
			$tmpFolder = wp_upload_dir()['basedir'] . '/processing/' . $tmpFolderTail;
			mkdir($tmpFolder);
			
			$tmpFolderURL = wp_upload_dir()['baseurl'] . '/processing/' . $tmpFolderTail;
			
			global $wpdb;
			$ids = implode(',', $_SESSION['workflow3pool']);
			
			$wid = $_POST['warehouse'];
			$sql = 'SELECT name, address, contact, phone FROM vw_active_warehouse WHERE id = %d';
			$warehouse = $wpdb->get_results($wpdb->prepare($sql, array($wid)), ARRAY_A);
			
			$warehouseName 		= $warehouse[0]['name'];
			$warehouseContact 	= $warehouse[0]['contact'];
			$warehouseAddress 	= $warehouse[0]['address'];
			$warehousePhone 	= $warehouse[0]['phone'];
			
$sql = <<<SQL
SELECT
	vw_active_product.supplier,
	CONCAT(ms_order.PI, "-", ms_order.Number) AS PINumber,
	ms_order.CD,
	vw_active_product.chineseName,
	ms_order.salesQuantity,
	ms_order.salesQuantity / vw_active_product.numberInOuterBox AS boxCount,
	ms_order.salesQuantity / vw_active_product.numberInOuterBox * vw_active_product.outerBoxGrossWeight AS grossWeight,
	ms_order.salesQuantity / vw_active_product.numberInOuterBox * vw_active_product.outerBoxVolume AS volume,
	ms_order.arrivalPort,
	date_add(ms_order.ETD, interval -5 day) AS deadline,
	ms_order.ETD
FROM ms_order
LEFT JOIN vw_active_product
ON ms_order.clientName = vw_active_product.clientName AND ms_order.CD = vw_active_product.CD
WHERE ms_order.id IN ($ids)  
ORDER BY vw_active_product.supplier, ms_order.arrivalPort
SQL;
			
			$entries = $wpdb->get_results($sql, ARRAY_A);
			$theFileName = '';
			$theSpreadSheet = null;
			$theSheet = null;
			$cursor = 11;
			$counter = 1;
			
			foreach($entries as $entry){
				$vendor 		= $entry['supplier'];
				$arrivalPort	= $entry['arrivalPort'];
				
				$fileName = "进仓单_" . $vendor . "_$arrivalPort.xlsx";
				
				if($fileName != $theFileName){
					if($theFileName != ''){
						// Wrap Up
						$theSheet->removeRow(10);
						$theSheet->setCellValue('F' . ($cursor + 1), date("Y-m-d"));
						
						$save2path = "$tmpFolder/$theFileName";
						$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($theSpreadSheet);
						$writer->save($save2path);
					}
					
					// Load from template file
					$path = __DIR__ . '/../template/entry.xlsx';
					$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
					
					$theSpreadSheet = $reader->load($path);
					$theSheet = $theSpreadSheet->getActiveSheet();
					$theFileName = $fileName;
					$cursor = 11;
					$counter = 1;

					$theSheet->setCellValue('B5', "仓库：$warehouseName");
					$theSheet->setCellValue('B6', "送货地址：$warehouseAddress");
					$theSheet->setCellValue('B7', "联系人：$warehouseContact");
					$theSheet->setCellValue('B8', "联系电话：$warehousePhone");	
				}
				
				$theSheet->insertNewRowBefore($cursor, 1);
				$theSheet->setCellValue("A$cursor", $counter);
				$theSheet->setCellValue("B$cursor", '');
				$theSheet->setCellValue("C$cursor", $vendor);
				$theSheet->setCellValue("D$cursor", $entry['PINumber']);
				$theSheet->setCellValue("E$cursor", $entry['CD']);
				$theSheet->setCellValue("F$cursor", $entry['chineseName']);
				$theSheet->setCellValue("G$cursor", $entry['salesQuantity']);
				$theSheet->setCellValue("H$cursor", $entry['boxCount']);
				$theSheet->setCellValue("I$cursor", $entry['grossWeight']);
				$theSheet->setCellValue("J$cursor", $entry['volume']);
				$theSheet->setCellValue("K$cursor", $entry['arrivalPort']);
				$theSheet->setCellValue("L$cursor", $entry['deadline']);
				$theSheet->setCellValue("M$cursor", $entry['ETD']);
				
				$cursor++;
				$counter++;
			}
			
			// Wrap Up
			if($theSheet != null){
				$theSheet->removeRow(10);
				$theSheet->setCellValue('F' . ($cursor + 1), date("Y-m-d"));
				
				$save2path = "$tmpFolder/$theFileName";
				$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($theSpreadSheet);
				$writer->save($save2path);
			}
		} catch(Exception $e) {
			error_log($e);
		}
		
		gravity_form(9, true, true, false, false, true);
?>
<div style="padding-right:20px;">
<?php
	echo do_shortcode('[wp_file_manager allowed_roles="*" view="list" access_folder="' . substr($tmpFolder, strpos($tmpFolder, 'wp-content')) . '" write = "true" read = "false" allowed_operations="upload,download"]');
?>
</div>
<?php
	} else {
?>
	<script>
		alert('<?php _e('Please select order to process the workflow', 'tomo'); ?>');
		location = 'admin.php?page=workflow_3';
	</script>
<?php
	}
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
<script>
jQuery(document).ready(function() {
	jQuery('#field_9_6').hide();
	jQuery('#input_9_6').val('<?php echo get_admin_url(null,"admin.php?page=folder_display&sub=$tmpFolderTail"); ?>');
	
	jQuery('#input_9_3').val('<?php echo $ids; ?>');
	
	//var theLink = jQuery('<li><a href="' + jQuery('#input_9_6').val() + '" target="_blank"><i class="fas fa-folder-open"></i> 采购合同</a></li>');
	//theLink.insertAfter(jQuery('#field_9_6'));
});
</script>
<?php
	$_SESSION['workflow3pool'] = array();
?>