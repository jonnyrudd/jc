<?php
	session_start();
	if(isset($_SESSION['workflow5pool']) && count($_SESSION['workflow5pool']) > 0){
		require __DIR__ . '/../vendor/autoload.php';
		
		try{
			// Create processing temporary folder
			$tmpFolderTail = date("Ymd") . '_wf5_' . wp_get_current_user()->user_login . '_' . time();
			$tmpFolder = wp_upload_dir()['basedir'] . '/processing/' . $tmpFolderTail;
			mkdir($tmpFolder);
			
			$tmpFolderURL = wp_upload_dir()['baseurl'] . '/processing/' . $tmpFolderTail;
			
			global $wpdb;
			$ids = implode(',', $_SESSION['workflow5pool']);
			
			$workbooks = [];
			
			// Get PI number mapping
$sql = <<<SQL
SELECT distinct ms_order.invoiceNumber, CONCAT(ms_order.PI, "-", ms_order.Number) AS PINumber
FROM ms_order
LEFT JOIN vw_active_product 
	ON ms_order.clientName = vw_active_product.clientName AND ms_order.CD = vw_active_product.CD
WHERE ms_order.invoiceNumber IS NOT NULL AND ms_order.id IN ($ids)
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
	vw_active_product.salesFOB,
	vw_active_product.factoryPriceRMB,
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
ORDER BY major.invoiceNumber
SQL;
			
			$claims = $wpdb->get_results($mainsql, ARRAY_A);
			
			$theFileName = '';
			$spreadsheet = null;
			$sheet = null;
			$cursor = 28;
			$inv = '';
			
			foreach($claims as $claim){
				$inv 			= $claim['invoiceNumber'];
				$etd 			= $claim['etd'];
				$startPort 		= $claim['startPort'];
				$arrivalPort 	= $claim['arrivalPort'];
				$title		 	= $claim['factoryPriceRMB'] > 0 ? 'SHANGHAI JINGCHAO INDUSTRIAL TRADING CO., LTD.' : 'HONG KONG JIN SUI TRADING LIMITED';
				$titleAddress	= $claim['factoryPriceRMB'] > 0 ? '9F,NO.10,SONGLIANG ROAD,BAOSHAN DISTRICT,SHANGHAI CHINA' : 'SUITE 1222，12/F,LEIGHTON CENTRE,77 LEIGHTON ROAD,CAUSEWAY BAY,HONGKONG';
				
				$fileName = $inv . '_银行材料.xlsx';
				
				if($theFileName != $fileName){
					if($theFileName != ''){
						// ----------------------------------------------------------------------------
						// WRAP UP ACTION START
						// ----------------------------------------------------------------------------
						$sheet->removeRow(27);
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
					
					$path = __DIR__ . '/../template/bank.xlsx';
					$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
					$spreadsheet = $reader->load($path);
					$sheet = $spreadsheet->getSheet(0);
					
					$workbooks[$inv] = ["$tmpFolder/$fileName", $spreadsheet];
					
					$cursor = 28;
					
					$pi_cursor = 13;
					foreach($matrics[$inv] as $key => $value){
						$sheet->setCellValue("B$pi_cursor", $key);
						$pi_cursor = $pi_cursor + 1;
					}
					
					$sheet->setCellValue("H1", date('Y-m-d'));
					$sheet->setCellValue("H3", $inv);
					$sheet->setCellValue("H7", $etd);
					
					//$sheet->setCellValue("D8", $startPort);
					$sheet->setCellValue("G9", $arrivalPort);
					
					$sheet->setCellValue("A1", $title);
					$sheet->setCellValue("A32", $titleAddress);
				}
				
				$sheet->insertNewRowBefore($cursor, 1);
				$sheet->setCellValue("A$cursor", $claim['CD']);
				$sheet->setCellValue("B$cursor", $claim['customerEngAbbr']);
				$sheet->setCellValue("C$cursor", $claim['customerChsAbbr']);
				$sheet->setCellValue("D$cursor", $claim['customerMaterial']);
				$sheet->setCellValue("E$cursor", $claim['quantity']);
				$sheet->setCellValue("F$cursor", 'PCS');
				$sheet->setCellValue("G$cursor", $claim['salesFOB']);
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
				$sheet->removeRow(27);
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
			
			// Sub Processing
$subsql = <<<SQL
SELECT 
	ms_order.invoiceNumber,
	vw_active_product.customerEngAbbr,
	vw_active_product.customerMaterial,
	sum(ms_order.salesQuantity) AS pcs,
	sum(ms_order.salesQuantity / vw_active_product.numberInOuterBox) AS ctn
FROM ms_order
LEFT JOIN vw_active_product 
	ON ms_order.clientName = vw_active_product.clientName AND ms_order.CD = vw_active_product.CD
WHERE ms_order.invoiceNumber IS NOT NULL AND ms_order.id IN ($ids)
GROUP BY
	ms_order.invoiceNumber,
	vw_active_product.customerEngAbbr,
	vw_active_product.customerMaterial
ORDER BY ms_order.invoiceNumber
SQL;
			
			$subs = $wpdb->get_results($subsql, ARRAY_A);
			
			$theFileName = '';
			$sheet = null;
			$cursor = 27;
			
			foreach($subs as $sub){
				$inv = $sub['invoiceNumber'];
				$fileName = $inv . '_银行材料.xlsx';
				
				if($theFileName != $fileName){
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
					$sheet = $spreadsheet->getSheet(0);
					
					$cursor = 27;
				}
				
				$sheet->setCellValue("K$cursor", $sub['customerEngAbbr'] . '/' . $sub['customerMaterial']);
				$sheet->setCellValue("L$cursor", $sub['ctn']);
				$sheet->setCellValue("M$cursor", $sub['pcs']);
				
				$styleArray = [
								'borders' => [
									'outline' => [
										'borderStyle' => PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
										'color' => ['argb' => 'FF000000'],
									],
								],
								'alignment' => [
									'horizontal' => PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
								]
							];

				$sheet->getStyle("K$cursor")->applyFromArray($styleArray);
				$sheet->getStyle("L$cursor")->applyFromArray($styleArray);
				$sheet->getStyle("M$cursor")->applyFromArray($styleArray);

				$cursor = $cursor + 1;
			}
			
			// ----------------------------------------------------------------------------
			// WRAP UP ACTION START
			// ----------------------------------------------------------------------------
			// ----------------------------------------------------------------------------
			// WRAP UP ACTION END
			// ----------------------------------------------------------------------------
			
			///////////////////////////////////////////////////////////////////////////////
			// Sheet 2 START
			///////////////////////////////////////////////////////////////////////////////
			$theFileName = '';
			//$spreadsheet = null;
			$sheet = null;
			$cursor = 28;
			$inv = '';
			
			foreach($claims as $claim){
				$inv 			= $claim['invoiceNumber'];
				$etd 			= $claim['etd'];
				$startPort 		= $claim['startPort'];
				$arrivalPort 	= $claim['arrivalPort'];
				$title		 	= $claim['factoryPriceRMB'] > 0 ? 'SHANGHAI JINGCHAO INDUSTRIAL TRADING CO., LTD.' : 'HONG KONG JIN SUI TRADING LIMITED';
				$titleAddress	= $claim['factoryPriceRMB'] > 0 ? '9F,NO.10,SONGLIANG ROAD,BAOSHAN DISTRICT,SHANGHAI CHINA' : 'SUITE 1222，12/F,LEIGHTON CENTRE,77 LEIGHTON ROAD,CAUSEWAY BAY,HONGKONG';
				
				$fileName = $inv . '_银行材料.xlsx';
				
				if($theFileName != $fileName){
					if($theFileName != ''){
						// ----------------------------------------------------------------------------
						// WRAP UP ACTION START
						// ----------------------------------------------------------------------------
						$sheet->removeRow(27);
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
					
					$spreadsheet = $workbooks[$inv][1];
					$sheet = $spreadsheet->getSheet(1);
					
					$cursor = 28;
					
					$pi_cursor = 13;
					foreach($matrics[$inv] as $key => $value){
						$sheet->setCellValue("B$pi_cursor", $key);
						$pi_cursor = $pi_cursor + 1;
					}
					
					$sheet->setCellValue("H1", date('Y-m-d'));
					$sheet->setCellValue("H3", $inv);
					$sheet->setCellValue("H7", $etd);
					
					//$sheet->setCellValue("D8", $startPort);
					$sheet->setCellValue("G9", $arrivalPort);
					
					$sheet->setCellValue("A1", $title);
					$sheet->setCellValue("A32", $titleAddress);
				}
				
				$sheet->insertNewRowBefore($cursor, 1);
				$sheet->setCellValue("A$cursor", $claim['CD']);
				$sheet->setCellValue("B$cursor", $claim['customerEngAbbr']);
				$sheet->setCellValue("C$cursor", $claim['customerChsAbbr']);
				$sheet->setCellValue("D$cursor", $claim['customerMaterial']);
				$sheet->setCellValue("E$cursor", $claim['quantity']);
				$sheet->setCellValue("F$cursor", 'PCS');
				$sheet->setCellValue("G$cursor", 0.998 * $claim['salesFOB']);
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
				$sheet->removeRow(27);
				$sheet->removeRow($cursor-1);
			}
			///////////////////////////////////////////////////////////////////////////////
			// Sheet 2 END
			///////////////////////////////////////////////////////////////////////////////
			
			///////////////////////////////////////////////////////////////////////////////
			// Sheet 3 START
			///////////////////////////////////////////////////////////////////////////////
$consql = <<<SQL
SELECT distinct ms_order.invoiceNumber, rl_order_container.cid
FROM ms_order
LEFT JOIN rl_order_container ON ms_order.id = rl_order_container.orderId
LEFT JOIN vw_active_product 
	ON ms_order.clientName = vw_active_product.clientName AND ms_order.CD = vw_active_product.CD
WHERE ms_order.invoiceNumber IS NOT NULL AND ms_order.id IN ($ids)
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

$sht3sql = <<<SQL
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
	vw_active_product.factoryPriceRMB,
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
ORDER BY major.invoiceNumber
SQL;
			
			$packs = $wpdb->get_results($sht3sql, ARRAY_A);
			//$spreadsheet = null;
			$sheet = null;
			$theFileName = '';
			$cursor = 30;
			
			//$sumGrossWeight = 0;
			$sumVolume = 0;
			
			foreach($packs as $pack){
				$inv 			= $pack['invoiceNumber'];
				$etd 			= $pack['etd'];
				$startPort 		= $pack['startPort'];
				$arrivalPort 	= $pack['arrivalPort'];
				$title		 	= $pack['factoryPriceRMB'] > 0 ? 'SHANGHAI JINGCHAO INDUSTRIAL TRADING CO., LTD.' : 'HONG KONG JIN SUI TRADING LIMITED';
				$titleAddress	= $pack['factoryPriceRMB'] > 0 ? '9F,NO.10,SONGLIANG ROAD,BAOSHAN DISTRICT,SHANGHAI CHINA' : 'SUITE 1222，12/F,LEIGHTON CENTRE,77 LEIGHTON ROAD,CAUSEWAY BAY,HONGKONG';
				
				$fileName = $inv . '_银行材料.xlsx';
				
				if($fileName != $theFileName){
					if($theFileName != ''){
						// ----------------------------------------------------------------------------
						// WRAP UP ACTION START
						// ----------------------------------------------------------------------------
						//$sheet->setCellValue("G16", $sumGrossWeight);
						$sheet->setCellValue("G25", $sumVolume);
						
						$sheet->removeRow(29);
						$sheet->removeRow($cursor-1);
						// ----------------------------------------------------------------------------
						// WRAP UP ACTION END
						// ----------------------------------------------------------------------------
					}
					
					$theFileName = $fileName;

					$spreadsheet = $workbooks[$inv][1];
					$sheet = $spreadsheet->getSheet(2);
					
					$cursor = 30;
					
					//$sumGrossWeight = 0;
					$sumVolume = 0;
					
					$con_cursor = 14;
					foreach($conmatrics[$inv] as $key => $value){
						$sheet->setCellValue("B$con_cursor", $key);
						$con_cursor = $con_cursor + 1;
					}
					
					$sheet->setCellValue("J1", date('Y-m-d'));
					$sheet->setCellValue("J3", $inv);
					$sheet->setCellValue("J7", $etd);
					
					//$sheet->setCellValue("F8", $startPort);
					$sheet->setCellValue("I10", $arrivalPort);
					
					$sheet->setCellValue("A1", $title);
					$sheet->setCellValue("A36", $titleAddress);
				}
				
				$sheet->insertNewRowBefore($cursor, 1);
				$sheet->setCellValue("A$cursor", $pack['CD']);
				
				$sheet->setCellValue("B$cursor", $pack['englishName']);
				$sheet->mergeCells("B$cursor:D$cursor");
				
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
				
				$sheet->setCellValue("L$cursor", $pack['grossWeight']);
				
				//$sumGrossWeight = $sumGrossWeight + $pack['grossWeight'];
				$sumVolume = $sumVolume + $pack['volume'];
				
				$cursor = $cursor + 1;
			}
			
			// ----------------------------------------------------------------------------
			// WRAP UP ACTION START
			// ----------------------------------------------------------------------------
			if($sheet != null){
				//$sheet->setCellValue("G16", $sumGrossWeight);
				$sheet->setCellValue("G25", $sumVolume);
							
				$sheet->removeRow(29);
				$sheet->removeRow($cursor-1);
			}
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
			
			///////////////////////////////////////////////////////////////////////////////
			// 出运信息表
			///////////////////////////////////////////////////////////////////////////////
$shipmentsql = <<<SQL
SELECT 
	ms_order.invoiceNumber,
	ms_order.arrivalPort,
	vw_active_product.productCategory,
	vw_active_product.CD,
	ms_order.salesQuantity,
	CONCAT(ms_order.PI, "-", ms_order.Number) AS PINumber,
	date_format(ms_order.ETD, "%y") AS yr,
	date_format(ms_order.ETD, "%m") AS mn,
	date_format(ms_order.ETD, "%d") AS dy,
	CONCAT(date_format(ms_order.ETD, "%y"), "-", quarter(ms_order.ETD), 'Q') AS belong
FROM ms_order
LEFT JOIN vw_active_product 
	ON ms_order.clientName = vw_active_product.clientName AND ms_order.CD = vw_active_product.CD
WHERE ms_order.invoiceNumber IS NOT NULL AND ms_order.id IN ($ids)
ORDER BY ms_order.invoiceNumber, ms_order.arrivalPort
SQL;
			
			$shipments = $wpdb->get_results($shipmentsql, ARRAY_A);
			
			$theFileName = '';
			$theSheetName = '';
			$spreadsheet = null;
			$sheet = null;
			$cursor = 2;
			$inv = '';
			
			foreach($shipments as $shipment){
				$inv 			= $shipment['invoiceNumber'];
				$arrivalPort 	= $shipment['arrivalPort'];
				
				$fileName = $inv . '_出运信息表.xlsx';
				
				if($theFileName != $fileName){
					if($theFileName != ''){
						// ----------------------------------------------------------------------------
						// WRAP UP ACTION START
						// ----------------------------------------------------------------------------
						$save2path = "$tmpFolder/$theFileName";
						$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
						$writer->save($save2path);
						// ----------------------------------------------------------------------------
						// WRAP UP ACTION END
						// ----------------------------------------------------------------------------
					}
					
					$theFileName = $fileName;
					
					$spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
					
					$sheetName = $arrivalPort;
					if($theSheetName != $sheetName){
						$cursor = 2;
						$sheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, $sheetName);
						$spreadsheet->addSheet($sheet, 0);
						
						$sheet->setCellValue("A1", '部门');
						$sheet->setCellValue("B1", '商品CD');
						$sheet->setCellValue("C1", 'QTY（PCS)');
						$sheet->setCellValue("D1", 'PI NO');
						$sheet->setCellValue("E1", '年');
						$sheet->setCellValue("F1", '月');
						$sheet->setCellValue("G1", '日');
						$sheet->setCellValue("H1", '发票号');
						$sheet->setCellValue("I1", '核销单号');
						$sheet->setCellValue("J1", '港口');
						$sheet->setCellValue("K1", '货代公司');
						$sheet->setCellValue("L1", '订单归属');
						
						$styleArray = [
										'font' => [
											'bold' => true,
										],
										'borders' => [
											'allBorders' => [
												'borderStyle' => PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
												'color' => ['argb' => 'FF000000'],
											],
										],
										'alignment' => [
											'horizontal' => PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
										]
									];

						$sheet->getStyle("A1:L1")->applyFromArray($styleArray);
						
						$sheet->getColumnDimension("A")->setWidth(9);
						$sheet->getColumnDimension("B")->setWidth(9);
						$sheet->getColumnDimension("C")->setWidth(12);
						$sheet->getColumnDimension("D")->setWidth(18);
						$sheet->getColumnDimension("E")->setWidth(3);
						$sheet->getColumnDimension("F")->setWidth(3);
						$sheet->getColumnDimension("G")->setWidth(3);
						$sheet->getColumnDimension("H")->setWidth(15);
						$sheet->getColumnDimension("I")->setWidth(12);
						$sheet->getColumnDimension("J")->setWidth(18);
						$sheet->getColumnDimension("K")->setWidth(12);
						$sheet->getColumnDimension("L")->setWidth(12);
						
						$theSheetName = $sheetName;
					}
				}

				$sheet->setCellValue("A$cursor", $shipment['productCategory']);
				$sheet->setCellValue("B$cursor", $shipment['CD']);
				$sheet->setCellValue("C$cursor", $shipment['salesQuantity']);
				$sheet->setCellValue("D$cursor", $shipment['PINumber']);
				$sheet->setCellValue("E$cursor", $shipment['yr']);
				$sheet->setCellValue("F$cursor", $shipment['mn']);
				$sheet->setCellValue("G$cursor", $shipment['dy']);
				$sheet->setCellValue("H$cursor", $shipment['invoiceNumber']);
				$sheet->setCellValue("I$cursor", '');
				$sheet->setCellValue("J$cursor", $shipment['arrivalPort']);
				$sheet->setCellValue("K$cursor", '');
				$sheet->setCellValue("L$cursor", $shipment['belong']);
				
				$styleArray = [
								'borders' => [
									'allBorders' => [
										'borderStyle' => PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
										'color' => ['argb' => 'FF000000'],
									],
								],
								'alignment' => [
									'horizontal' => PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
								]
							];

				$sheet->getStyle("A$cursor:L$cursor")->applyFromArray($styleArray);

				$cursor = $cursor + 1;
			}
			
			// ----------------------------------------------------------------------------
			// WRAP UP ACTION START
			// ----------------------------------------------------------------------------
			if($spreadsheet != null){
				$save2path = "$tmpFolder/$theFileName";
				$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
				$writer->save($save2path);
			}
			// ----------------------------------------------------------------------------
			// WRAP UP ACTION END
			// ----------------------------------------------------------------------------
		} catch(Exception $e) {
			error_log($e);
		}

		gravity_form(11, true, true, false, false, true);
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
		location = 'admin.php?page=workflow_5';
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
	jQuery('#field_11_6').hide();
	jQuery('#input_11_6').val('<?php echo get_admin_url(null,"admin.php?page=folder_display&sub=$tmpFolderTail"); ?>');
	
	jQuery('#input_11_3').val('<?php echo $ids; ?>');
	
	//var theLink = jQuery('<li><a href="' + jQuery('#input_9_6').val() + '" target="_blank"><i class="fas fa-folder-open"></i> 采购合同</a></li>');
	//theLink.insertAfter(jQuery('#field_9_6'));
});
</script>
<?php
	$_SESSION['workflow5pool'] = array();
?>