<?php
	session_start();
	if(isset($_SESSION['workflow4pool']) && count($_SESSION['workflow4pool']) > 0){
		require __DIR__ . '/../vendor/autoload.php';
		
		try{
			// Create processing temporary folder
			$tmpFolderTail = date("Ymd") . '_wf4_' . wp_get_current_user()->user_login . '_' . time();
			$tmpFolder = wp_upload_dir()['basedir'] . '/processing/' . $tmpFolderTail;
			mkdir($tmpFolder);
			
			$tmpFolderURL = wp_upload_dir()['baseurl'] . '/processing/' . $tmpFolderTail;
			
			global $wpdb;
			$ids = implode(',', $_SESSION['workflow4pool']);
			
			$workbooks = [];
			
			// Get PI number mapping
$sql = <<<SQL
SELECT distinct ms_order.invoiceNumber, CONCAT(ms_order.PI, "-", ms_order.Number) AS PINumber
FROM ms_order
LEFT JOIN vw_active_product 
	ON ms_order.clientName = vw_active_product.clientName AND ms_order.CD = vw_active_product.CD
WHERE vw_active_product.factoryPriceRMB > 0 AND ms_order.invoiceNumber IS NOT NULL AND ms_order.id IN ($ids)
ORDER BY ms_order.invoiceNumber
SQL;
			$PIs = $wpdb->get_results($sql, ARRAY_A);
			$matrics = [];
			$counter = 1;
			
			foreach($PIs AS $PI){
				$invoiceNumber 	= $PI['invoiceNumber'];
				$PINumber		= $PI['PINumber'];
				
				if (!array_key_exists($invoiceNumber, $matrics)) {
					$matrics[$invoiceNumber] = array();
					$counter = 1;
				}
					
				$matrics[$invoiceNumber][$PINumber] = "PI-$counter";
				
				$counter = $counter + 1;
			}
			
			// Processing
$mainsql = <<<SQL
SELECT 
	major.invoiceNumber,
	major.CD,
	vw_active_product.customerEngAbbr,
	vw_active_product.customerChsAbbr,
	vw_active_product.customerMaterial,
	major.quantity,
	vw_active_product.declareUnitPriceUSD,
	major.pis,
	major.etd,
	major.startPort,
	major.arrivalPort
FROM
(
	SELECT 
		invoiceNumber, 
		clientName, 
		CD, 
		sum(salesQuantity) AS quantity, 
		GROUP_CONCAT(CONCAT(PI, "-", Number)) AS pis, 
		min(etd) AS etd,
		min(startPort) AS startPort,
		min(arrivalPort) AS arrivalPort
	FROM ms_order
	WHERE invoiceNumber IS NOT NULL AND id IN ($ids)
	GROUP BY invoiceNumber, clientName, CD
) major
LEFT JOIN vw_active_product
	ON major.clientName = vw_active_product.clientName AND major.CD = vw_active_product.CD
WHERE vw_active_product.factoryPriceRMB > 0
ORDER BY major.invoiceNumber
SQL;
			
			$claims = $wpdb->get_results($mainsql, ARRAY_A);
			
			$theFileName = '';
			$spreadsheet = null;
			$sheet = null;
			$cursor = 25;
			$inv = '';
			
			foreach($claims as $claim){
				$inv 			= $claim['invoiceNumber'];
				$etd 			= $claim['etd'];
				$startPort 		= $claim['startPort'];
				$arrivalPort 	= $claim['arrivalPort'];
				
				$fileName = $inv . '_报关材料.xlsx';
				
				if($theFileName != $fileName){
					if($theFileName != ''){
						// ----------------------------------------------------------------------------
						// WRAP UP ACTION START
						// ----------------------------------------------------------------------------
						$sheet->removeRow(24);
						$sheet->removeRow($cursor-1);
						/*
						$save2path = "$tmpFolder/$theFileName";
						$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
						$writer->save($save2path);
						*/
						// ----------------------------------------------------------------------------
						// WRAP UP ACTION END
						// ----------------------------------------------------------------------------
					}
					
					$theFileName = $fileName;
					
					$path = __DIR__ . '/../template/declare.xlsx';
					$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
					$spreadsheet = $reader->load($path);
					$sheet = $spreadsheet->getSheet(0);
					
					$workbooks[$inv] = ["$tmpFolder/$fileName", $spreadsheet];
					
					$cursor = 25;
					
					$pi_cursor = 12;
					foreach($matrics[$inv] as $key => $value){
						$sheet->setCellValue("B$pi_cursor", $key);
						$pi_cursor = $pi_cursor + 1;
					}
					
					$sheet->setCellValue("H1", date('Y-m-d'));
					$sheet->setCellValue("H3", $inv);
					$sheet->setCellValue("H5", $etd);
					
					$sheet->setCellValue("D8", $startPort);
					$sheet->setCellValue("G8", $arrivalPort);
				}
				
				$sheet->insertNewRowBefore($cursor, 1);
				$sheet->setCellValue("A$cursor", $claim['CD']);
				$sheet->setCellValue("B$cursor", $claim['customerEngAbbr']);
				$sheet->setCellValue("C$cursor", $claim['customerChsAbbr']);
				$sheet->setCellValue("D$cursor", $claim['customerMaterial']);
				$sheet->setCellValue("E$cursor", $claim['quantity']);
				$sheet->setCellValue("F$cursor", 'PCS');
				$sheet->setCellValue("G$cursor", $claim['declareUnitPriceUSD']);
				$sheet->setCellValue("H$cursor", "=E$cursor*G$cursor");
				
				$pisnumber = [];
				foreach(explode(',', $claim['pis']) as $pi){
					array_push($pisnumber, $matrics[$inv][$pi]);
				}
				$sheet->setCellValue("I$cursor", implode('/', array_unique($pisnumber)));
				
				$cursor = $cursor + 1;
			}
			
			// ----------------------------------------------------------------------------
			// WRAP UP ACTION START
			// ----------------------------------------------------------------------------
			if($sheet != null){
				$sheet->removeRow(24);
				$sheet->removeRow($cursor-1);
			}
			/*
			$save2path = "$tmpFolder/$theFileName";
			$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
			$writer->save($save2path);
			*/
			// ----------------------------------------------------------------------------
			// WRAP UP ACTION END
			// ----------------------------------------------------------------------------
			
			///////////////////////////////////////////////////////////////////////////////
			// Sheet 2 START
			///////////////////////////////////////////////////////////////////////////////
$consql = <<<SQL
SELECT distinct ms_order.invoiceNumber, rl_order_container.cid
FROM ms_order
LEFT JOIN rl_order_container ON ms_order.id = rl_order_container.orderId
LEFT JOIN vw_active_product 
	ON ms_order.clientName = vw_active_product.clientName AND ms_order.CD = vw_active_product.CD
WHERE vw_active_product.factoryPriceRMB > 0 AND ms_order.invoiceNumber IS NOT NULL AND ms_order.id IN ($ids)
ORDER BY invoiceNumber
SQL;
			$cons = $wpdb->get_results($consql, ARRAY_A);
			$conmatrics = [];
			$concounter = 1;
			
			foreach($cons AS $con){
				$invoiceNumber 	= $con['invoiceNumber'];
				$cid			= $con['cid'];
				
				if (!array_key_exists($invoiceNumber, $conmatrics)) {
					$conmatrics[$invoiceNumber] = array();
					$concounter = 1;
				}
				
				$conmatrics[$invoiceNumber][$cid] = "CON-$concounter";
				
				$concounter = $concounter + 1;
			}

$sht2sql = <<<SQL
SELECT 
	major.invoiceNumber,
	major.CD,
	vw_active_product.englishName,
	vw_active_product.outerBoxNetWeight * major.quantity / vw_active_product.numberInOuterBox AS netWeight,
	vw_active_product.outerBoxGrossWeight * major.quantity / vw_active_product.numberInOuterBox AS grossWeight,
	vw_active_product.outerBoxVolume * major.quantity / vw_active_product.numberInOuterBox AS volume,
	major.quantity,
	vw_active_product.numberInOuterBox,
	major.quantity / vw_active_product.numberInOuterBox AS ctns,
	vw_active_product.customerCode,
	major.cons,
	major.etd,
	major.startPort,
	major.arrivalPort
FROM
(
	SELECT 
		ms_order.invoiceNumber, 
		ms_order.clientName, 
		ms_order.CD, 
		sum(ms_order.salesQuantity) AS quantity,
		min(ms_order.etd) AS etd,
		min(ms_order.startPort) AS startPort,
		min(ms_order.arrivalPort) AS arrivalPort,
		group_concat(rl_order_container.cid) AS cons
	FROM ms_order
	LEFT JOIN rl_order_container ON ms_order.id = rl_order_container.orderId
	WHERE ms_order.invoiceNumber IS NOT NULL AND ms_order.id IN ($ids)
	GROUP BY ms_order.invoiceNumber, ms_order.clientName, ms_order.CD
) major
LEFT JOIN vw_active_product
	ON major.clientName = vw_active_product.clientName AND major.CD = vw_active_product.CD
WHERE vw_active_product.factoryPriceRMB > 0
ORDER BY major.invoiceNumber
SQL;
			
			$packs = $wpdb->get_results($sht2sql, ARRAY_A);
			//$spreadsheet = null;
			$sheet = null;
			$theFileName = '';
			$cursor = 23;
			
			$sumGrossWeight = 0;
			$sumVolume = 0;
			
			foreach($packs as $pack){
				$inv 			= $pack['invoiceNumber'];
				$etd 			= $pack['etd'];
				$startPort 		= $pack['startPort'];
				$arrivalPort 	= $pack['arrivalPort'];
				
				$fileName = $inv . '_报关材料.xlsx';
				
				if($fileName != $theFileName){
					if($theFileName != ''){
						// ----------------------------------------------------------------------------
						// WRAP UP ACTION START
						// ----------------------------------------------------------------------------
						$sheet->setCellValue("G16", $sumGrossWeight);
						$sheet->setCellValue("G18", $sumVolume);
						
						$sheet->removeRow(22);
						$sheet->removeRow($cursor-1);
						// ----------------------------------------------------------------------------
						// WRAP UP ACTION END
						// ----------------------------------------------------------------------------
					}
					
					$theFileName = $fileName;

					$spreadsheet = $workbooks[$inv][1];
					$sheet = $spreadsheet->getSheet(1);
					
					$cursor = 23;
					
					$sumGrossWeight = 0;
					$sumVolume = 0;
					
					$con_cursor = 12;
					foreach($conmatrics[$inv] as $key => $value){
						$sheet->setCellValue("B$con_cursor", $key);
						$con_cursor = $con_cursor + 1;
					}
					
					$sheet->setCellValue("J1", date('Y-m-d'));
					$sheet->setCellValue("J3", $inv);
					$sheet->setCellValue("J5", $etd);
					
					$sheet->setCellValue("F8", $startPort);
					$sheet->setCellValue("I8", $arrivalPort);
				}
				
				$sheet->insertNewRowBefore($cursor, 1);
				$sheet->setCellValue("A$cursor", $pack['CD']);
				$sheet->setCellValue("B$cursor", $pack['englishName']);
				$sheet->setCellValue("E$cursor", $pack['netWeight']);
				$sheet->setCellValue("G$cursor", $pack['quantity']);
				$sheet->setCellValue("H$cursor", 'PCS');
				$sheet->setCellValue("I$cursor", $pack['numberInOuterBox']);
				$sheet->setCellValue("J$cursor", $pack['ctns']);
				
				$consnumber = [];
				foreach(explode(',', $pack['cons']) as $con){
					array_push($consnumber, $conmatrics[$inv][$con]);
				}
				$sheet->setCellValue("K$cursor", implode('/', array_unique($consnumber)));
				
				$sheet->setCellValue("L$cursor", $pack['customerCode']);
				
				$sumGrossWeight = $sumGrossWeight + $pack['grossWeight'];
				$sumVolume = $sumVolume + $pack['volume'];
				
				$cursor = $cursor + 1;
			}
			
			// ----------------------------------------------------------------------------
			// WRAP UP ACTION START
			// ----------------------------------------------------------------------------
			if($sheet != null){
				$sheet->setCellValue("G16", $sumGrossWeight);
				$sheet->setCellValue("G18", $sumVolume);
							
				$sheet->removeRow(22);
				$sheet->removeRow($cursor-1);
			}
			// ----------------------------------------------------------------------------
			// WRAP UP ACTION END
			// ----------------------------------------------------------------------------
			
			///////////////////////////////////////////////////////////////////////////////
			// Sheet 2 END
			///////////////////////////////////////////////////////////////////////////////
			
			///////////////////////////////////////////////////////////////////////////////
			// Sheet 3 START
			///////////////////////////////////////////////////////////////////////////////
$sht3sql = <<<SQL
SELECT 
	ms_order.invoiceNumber,
	vw_active_product.customerCode,
	vw_active_product.customerMaterial,
	vw_active_product.customerChsAbbr,
	vw_active_product.customerEngAbbr,
	sum(ms_order.salesQuantity) AS quantity,
	sum(ms_order.salesQuantity / vw_active_product.numberInOuterBox * vw_active_product.outerBoxNetWeight) AS netWeight,
	sum(ms_order.salesQuantity * vw_active_product.declareUnitPriceUSD) as price
FROM ms_order
LEFT JOIN vw_active_product 
	ON ms_order.clientName = vw_active_product.clientName AND ms_order.CD = vw_active_product.CD
WHERE vw_active_product.factoryPriceRMB > 0 AND ms_order.invoiceNumber IS NOT NULL AND ms_order.id IN ($ids)
GROUP BY 
	ms_order.invoiceNumber,
	vw_active_product.customerCode,
	vw_active_product.customerMaterial,
	vw_active_product.customerChsAbbr,
	vw_active_product.customerEngAbbr
ORDER BY ms_order.invoiceNumber
SQL;
			
			$declarations = $wpdb->get_results($sht3sql, ARRAY_A);
			//$spreadsheet = null;
			$sheet = null;
			$theFileName = '';
			$cursor = 13;
			
			foreach($declarations as $declaration){
				$inv 			= $declaration['invoiceNumber'];
				$fileName = $inv . '_报关材料.xlsx';
				
				if($fileName != $theFileName){
					if($theFileName != ''){
						// ----------------------------------------------------------------------------
						// WRAP UP ACTION START
						// ----------------------------------------------------------------------------
						// ----------------------------------------------------------------------------
						// WRAP UP ACTION END
						// ----------------------------------------------------------------------------
					}
					
					$theFileName = $fileName;

					$spreadsheet = $workbooks[$inv][1];
					$sheet = $spreadsheet->getSheet(2);
					
					$cursor = 13;

				}
				
				$sheet->insertNewRowBefore($cursor, 1);
				$sheet->setCellValue("B$cursor", $declaration['customerCode']);
				$sheet->setCellValue("C$cursor", $declaration['customerChsAbbr']);
				$sheet->setCellValue("D$cursor", $declaration['quantity']);
				$sheet->setCellValue("E$cursor", 'PCS');
				$sheet->setCellValue("F$cursor", $declaration['netWeight']);
				$sheet->setCellValue("G$cursor", 'KGS');
				$sheet->setCellValue("H$cursor", $declaration['price']);
				$sheet->setCellValue("I$cursor", 'USD');
				
				$cursor = $cursor + 1;
			}
			
			// ----------------------------------------------------------------------------
			// WRAP UP ACTION START
			// ----------------------------------------------------------------------------
			// ----------------------------------------------------------------------------
			// WRAP UP ACTION END
			// ----------------------------------------------------------------------------
			///////////////////////////////////////////////////////////////////////////////
			// Sheet 3 END
			///////////////////////////////////////////////////////////////////////////////
			
			// Save the files
			foreach($workbooks as $workbook){
				$save2path = $workbook[0];
				$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($workbook[1]);
				$writer->save($save2path);
			}

		} catch(Exception $e) {
			error_log($e);
		}

		gravity_form(10, true, true, false, false, true);
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
		location = 'admin.php?page=workflow_4';
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
	jQuery('#field_10_6').hide();
	jQuery('#input_10_6').val('<?php echo get_admin_url(null,"admin.php?page=folder_display&sub=$tmpFolderTail"); ?>');
	
	jQuery('#input_10_3').val('<?php echo $ids; ?>');
	
	//var theLink = jQuery('<li><a href="' + jQuery('#input_9_6').val() + '" target="_blank"><i class="fas fa-folder-open"></i> 采购合同</a></li>');
	//theLink.insertAfter(jQuery('#field_9_6'));
});
</script>
<?php
	$_SESSION['workflow4pool'] = array();
?>