<?php
	session_start();
	if(isset($_SESSION['workflow2pool']) && count($_SESSION['workflow2pool']) > 0){
		require __DIR__ . '/../vendor/autoload.php';
		
		try{
			// Create processing temporary folder
			$tmpFolderTail = date("Ymd") . '_wf2_' . wp_get_current_user()->user_login . '_' . time();
			$tmpFolder = wp_upload_dir()['basedir'] . '/processing/' . $tmpFolderTail;
			mkdir($tmpFolder);
			
			$tmpFolderURL = wp_upload_dir()['baseurl'] . '/processing/' . $tmpFolderTail;
			
			global $wpdb;
			$ids = implode(',', $_SESSION['workflow2pool']);
			
			
$sql = <<<SQL
SELECT
	CONCAT(ms_order.PI, "-", ms_order.Number) AS PINumber,
	ms_order.CD,
	vw_active_product.chineseName,
	rl_order_container.qty,
	rl_order_container.qty / vw_active_product.numberInOuterBox AS boxCount,
	rl_order_container.qty / vw_active_product.numberInOuterBox * vw_active_product.outerBoxGrossWeight AS grossWeight,
	rl_order_container.qty / vw_active_product.numberInOuterBox * vw_active_product.outerBoxVolume AS volume,
	vw_active_product.productInspection,
	vw_active_product.supplier,
	vw_active_product.haveInnerBox,
	rl_order_container.cid,
	ms_order.invoiceNumber,
	vw_active_product.supplier,
	ms_order.arrivalPort,
	ms_order.ETD
FROM ms_order
LEFT JOIN vw_active_product
ON ms_order.clientName = vw_active_product.clientName AND ms_order.CD = vw_active_product.CD
LEFT JOIN rl_order_container
ON ms_order.id = rl_order_container.orderId
WHERE ms_order.id IN ($ids) AND rl_order_container.cid IS NOT NULL AND ms_order.invoiceNumber IS NOT NULL
ORDER BY ms_order.arrivalPort, rl_order_container.cid DESC, PINumber, ms_order.invoiceNumber
SQL;
			
			$boxes = $wpdb->get_results($sql, ARRAY_A);

			$spreadsheet = null;
			$sheet = null;
			
			$prevfileName = '';
			$prevCID = '';
			$cursor = 1;
			$startCursor = 1;
			
			$colorSet = ['FFFE6500', 'FFFEFE00', 'FF00AFEF', 'FF6666E2', 'FF4AEA65', 'FFD672C1', 'FFF9BE8E', 'FF5D53F3', 'FFB553F3', 'FFF353ED'];
			$colorCursor = 0;
			
			$matrics = [];
			
			$comStyleArray = [
						'borders' => [
							'outline' => [
								'borderStyle' => PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
								'color' => ['argb' => 'FF000000'],
							],
						],
						'alignment' => [
							'horizontal' => PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
							'vertical' => PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
						]
					];
			
			foreach ( $boxes as $box ) {
				$port			= $box['arrivalPort'];
				$etd			= $box['ETD'];
				
				$theFileName 	= $etd . '_' . $port . '_装箱明细.xlsx';
				
				if($theFileName != $prevfileName){
					if($prevfileName != ''){
						// ----------------------------------------------------------------------------
						// WRAP UP ACTION START
						// ----------------------------------------------------------------------------
						$sheet->setCellValue("D$cursor", "=SUM(D$startCursor:D" . ($cursor - 1) . ")");
						$sheet->setCellValue("E$cursor", "=SUM(E$startCursor:E" . ($cursor - 1) . ")");
						$sheet->setCellValue("F$cursor", "=SUM(F$startCursor:F" . ($cursor - 1) . ")");
						$sheet->setCellValue("G$cursor", "=SUM(G$startCursor:G" . ($cursor - 1) . ")");
						
						
						$sheet->setCellValue("K$cursor", "1高");
						$sheet->mergeCells("K$startCursor:K" . ($cursor - 1));
						$sheet->setCellValue("K$startCursor", "内装");
						
						$sheet->getStyle("K$cursor")->applyFromArray($comStyleArray);
						$sheet->getStyle("K$startCursor:K" . ($cursor - 1))->applyFromArray($comStyleArray);
						
						$offset = 0;
						$lastCol = 0;
						
						// Summary
						foreach ($matrics as $key => $value) {
							$col = chr(ord('A') + $offset);
							$lastCol = chr(ord('A') + $offset + 1);

							$sheet->setCellValue("$col" . ($cursor + 5), $key);
							$sheet->setCellValue("$col" . ($cursor + 6), $matrics[$key]['boxCount']);
							$sheet->setCellValue("$col" . ($cursor + 7), $matrics[$key]['grossWeight']);
							$sheet->setCellValue("$col" . ($cursor + 8), $matrics[$key]['volume']);
							$sheet->setCellValue("$col" . ($cursor + 9), implode(',', array_unique($matrics[$key]['vendors'])));
							
							$summaryStyleArray = [
								'borders' => [
									'outline' => [
										'borderStyle' => PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
										'color' => ['argb' => 'FF000000'],
									],
								],
								'fill' => [
									'fillType' => PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
									'color' => ['argb' => $matrics[$key]['color']],
								],
								'alignment' => [
									'horizontal' => PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
								]
							];
							
							$sheet->getStyle("$col" . ($cursor + 6))->applyFromArray($summaryStyleArray);
							$sheet->getStyle("$col" . ($cursor + 7))->applyFromArray($summaryStyleArray);
							$sheet->getStyle("$col" . ($cursor + 8))->applyFromArray($summaryStyleArray);
							
							$offset = $offset + 1;
						}
						
						// Summary Formula
						$prevCol = chr(ord($lastCol) - 1 );
						$sheet->setCellValue("$lastCol" . ($cursor + 6), '=SUM(A' . ($cursor + 6) . ":$prevCol" . ($cursor + 6) . ')');
						$sheet->setCellValue("$lastCol" . ($cursor + 7), '=SUM(A' . ($cursor + 7) . ":$prevCol" . ($cursor + 7) . ')');
						$sheet->setCellValue("$lastCol" . ($cursor + 8), '=SUM(A' . ($cursor + 8) . ":$prevCol" . ($cursor + 8) . ')');
						
						$sheet->getColumnDimension('A')->setWidth(25);
						$sheet->getColumnDimension('B')->setWidth(12);
						$sheet->getColumnDimension('C')->setWidth(25);
						$sheet->getColumnDimension('D')->setWidth(8);
						$sheet->getColumnDimension('E')->setWidth(8);
						$sheet->getColumnDimension('F')->setWidth(8);
						$sheet->getColumnDimension('G')->setWidth(8);
						$sheet->getColumnDimension('H')->setWidth(12);
						$sheet->getColumnDimension('I')->setWidth(12);
						$sheet->getColumnDimension('J')->setWidth(12);
						
						$writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
						$writer->save("$tmpFolder//$prevfileName");
						// ----------------------------------------------------------------------------
						// WRAP UP ACTION END
						// ----------------------------------------------------------------------------
					}
					
					$spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
					$sheet = $spreadsheet->getActiveSheet();
					
					$prevfileName = $theFileName;
					$prevCID = '';
					$cursor = 1;
					$startCursor = 1;
					$colorCursor = 0;
					$matrics = [];
				}
				
				$cid 			= $box['cid'];
				$invoiceNumber 	= $box['invoiceNumber'];
				
				if (array_key_exists($invoiceNumber, $matrics)) {
					$matrics[$invoiceNumber]['boxCount'] = $matrics[$invoiceNumber]['boxCount'] + $box['boxCount'];
					$matrics[$invoiceNumber]['grossWeight'] = $matrics[$invoiceNumber]['grossWeight'] + $box['grossWeight'];
					$matrics[$invoiceNumber]['volume'] = $matrics[$invoiceNumber]['volume'] + $box['volume'];
					array_push($matrics[$invoiceNumber]['vendors'], $box['supplier']);
				} else {
					$matrics[$invoiceNumber] = 	[
													'boxCount' 		=> $box['boxCount'],
													'grossWeight' 	=> $box['grossWeight'],
													'volume' 		=> $box['volume'],
													'color' 		=> $colorSet[$colorCursor],
													'vendors' 		=> [$box['supplier']]
												];
					$colorCursor = $colorCursor + 1;					
				}
				
				$cellStyleArray = [
					'borders' => [
						'outline' => [
							'borderStyle' => PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
							'color' => ['argb' => 'FF000000'],
						],
					],
					'fill' => [
						'fillType' => PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
						'color' => ['argb' => $matrics[$invoiceNumber]['color']],
					],
					'alignment' => [
						'horizontal' => PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
					]
				];
				
				if($prevCID != $cid){
					if($prevCID != ''){
						$sheet->setCellValue("D$cursor", "=SUM(D$startCursor:D" . ($cursor - 1) . ")");
						$sheet->setCellValue("E$cursor", "=SUM(E$startCursor:E" . ($cursor - 1) . ")");
						$sheet->setCellValue("F$cursor", "=SUM(F$startCursor:F" . ($cursor - 1) . ")");
						$sheet->setCellValue("G$cursor", "=SUM(G$startCursor:G" . ($cursor - 1) . ")");
						
						$sheet->setCellValue("K$cursor", "1高");
						$sheet->mergeCells("K$startCursor:K" . ($cursor - 1));
						$sheet->setCellValue("K$startCursor", "内装");
						
						$sheet->getStyle("K$cursor")->applyFromArray($comStyleArray);
						$sheet->getStyle("K$startCursor:K" . ($cursor - 1))->applyFromArray($comStyleArray);
						
						$cursor += 4;
					}
					
					$startCursor = $cursor + 1;

					$styleArray = [
						'borders' => [
							'outline' => [
								'borderStyle' => PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
								'color' => ['argb' => 'FF000000'],
							],
						],
						'fill' => [
							'fillType' => PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
							'color' => ['argb' => 'FFDDDDDD'],
						],
						'alignment' => [
							'horizontal' => PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
						],
						'font' => [
							'bold' => true,
						],
					];					
					
					// Header
					$sheet->setCellValue("A$cursor", 'PI');
					$sheet->setCellValue("B$cursor", '商品CD');
					$sheet->setCellValue("C$cursor", '工厂产品名称');
					$sheet->setCellValue("D$cursor", 'PCS');
					$sheet->setCellValue("E$cursor", '箱数');
					$sheet->setCellValue("F$cursor", '毛重');
					$sheet->setCellValue("G$cursor", '体积');
					$sheet->setCellValue("H$cursor", '商品检验');
					$sheet->setCellValue("I$cursor", '供应商');
					$sheet->setCellValue("J$cursor", '有无内箱');
					
					// Header Style
					$sheet->getStyle("A$cursor")->applyFromArray($styleArray);
					$sheet->getStyle("B$cursor")->applyFromArray($styleArray);
					$sheet->getStyle("C$cursor")->applyFromArray($styleArray);
					$sheet->getStyle("D$cursor")->applyFromArray($styleArray);
					$sheet->getStyle("E$cursor")->applyFromArray($styleArray);
					$sheet->getStyle("F$cursor")->applyFromArray($styleArray);
					$sheet->getStyle("G$cursor")->applyFromArray($styleArray);
					$sheet->getStyle("H$cursor")->applyFromArray($styleArray);
					$sheet->getStyle("I$cursor")->applyFromArray($styleArray);
					$sheet->getStyle("J$cursor")->applyFromArray($styleArray);
					
					$cursor++;
				}
				
				// Data
				$sheet->setCellValue("A$cursor", $box['PINumber']);
				$sheet->setCellValue("B$cursor", $box['CD']);
				$sheet->setCellValue("C$cursor", $box['chineseName']);
				$sheet->setCellValue("D$cursor", $box['qty']);
				$sheet->setCellValue("E$cursor", $box['boxCount']);
				$sheet->setCellValue("F$cursor", $box['grossWeight']);
				$sheet->setCellValue("G$cursor", $box['volume']);
				$sheet->setCellValue("H$cursor", $box['productInspection']);
				$sheet->setCellValue("I$cursor", $box['supplier']);
				$sheet->setCellValue("J$cursor", $box['haveInnerBox']);
				// Data Style
				$sheet->getStyle("A$cursor")->applyFromArray($cellStyleArray);
				$sheet->getStyle("B$cursor")->applyFromArray($cellStyleArray);
				$sheet->getStyle("C$cursor")->applyFromArray($cellStyleArray);
				$sheet->getStyle("D$cursor")->applyFromArray($cellStyleArray);
				$sheet->getStyle("E$cursor")->applyFromArray($cellStyleArray);
				$sheet->getStyle("F$cursor")->applyFromArray($cellStyleArray);
				$sheet->getStyle("G$cursor")->applyFromArray($cellStyleArray);
				$sheet->getStyle("H$cursor")->applyFromArray($cellStyleArray);
				$sheet->getStyle("I$cursor")->applyFromArray($cellStyleArray);
				$sheet->getStyle("J$cursor")->applyFromArray($cellStyleArray);
				
				$prevCID = $cid;
				$cursor++;
			}
			
			// ----------------------------------------------------------------------------
			// WRAP UP ACTION START
			// ----------------------------------------------------------------------------
			if($sheet != null){
				$sheet->setCellValue("D$cursor", "=SUM(D$startCursor:D" . ($cursor - 1) . ")");
				$sheet->setCellValue("E$cursor", "=SUM(E$startCursor:E" . ($cursor - 1) . ")");
				$sheet->setCellValue("F$cursor", "=SUM(F$startCursor:F" . ($cursor - 1) . ")");
				$sheet->setCellValue("G$cursor", "=SUM(G$startCursor:G" . ($cursor - 1) . ")");
				
				$sheet->setCellValue("K$cursor", "1高");
				$sheet->mergeCells("K$startCursor:K" . ($cursor - 1));
				$sheet->setCellValue("K$startCursor", "内装");
				
				$sheet->getStyle("K$cursor")->applyFromArray($comStyleArray);
				$sheet->getStyle("K$startCursor:K" . ($cursor - 1))->applyFromArray($comStyleArray);
						
				$offset = 0;
				$lastCol = 0;
				
				// Summary
				foreach ($matrics as $key => $value) {
					$col = chr(ord('A') + $offset);
					$lastCol = chr(ord('A') + $offset + 1);

					$sheet->setCellValue("$col" . ($cursor + 5), $key);
					$sheet->setCellValue("$col" . ($cursor + 6), $matrics[$key]['boxCount']);
					$sheet->setCellValue("$col" . ($cursor + 7), $matrics[$key]['grossWeight']);
					$sheet->setCellValue("$col" . ($cursor + 8), $matrics[$key]['volume']);
					$sheet->setCellValue("$col" . ($cursor + 9), implode(',', array_unique($matrics[$key]['vendors'])));
					
					$summaryStyleArray = [
						'borders' => [
							'outline' => [
								'borderStyle' => PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
								'color' => ['argb' => 'FF000000'],
							],
						],
						'fill' => [
							'fillType' => PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
							'color' => ['argb' => $matrics[$key]['color']],
						],
						'alignment' => [
							'horizontal' => PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
						]
					];
					
					$sheet->getStyle("$col" . ($cursor + 6))->applyFromArray($summaryStyleArray);
					$sheet->getStyle("$col" . ($cursor + 7))->applyFromArray($summaryStyleArray);
					$sheet->getStyle("$col" . ($cursor + 8))->applyFromArray($summaryStyleArray);
					
					$offset = $offset + 1;
				}
				
				// Summary Formula
				$prevCol = chr(ord($lastCol) - 1 );
				$sheet->setCellValue("$lastCol" . ($cursor + 6), '=SUM(A' . ($cursor + 6) . ":$prevCol" . ($cursor + 6) . ')');
				$sheet->setCellValue("$lastCol" . ($cursor + 7), '=SUM(A' . ($cursor + 7) . ":$prevCol" . ($cursor + 7) . ')');
				$sheet->setCellValue("$lastCol" . ($cursor + 8), '=SUM(A' . ($cursor + 8) . ":$prevCol" . ($cursor + 8) . ')');
				
				$sheet->getColumnDimension('A')->setWidth(25);
				$sheet->getColumnDimension('B')->setWidth(12);
				$sheet->getColumnDimension('C')->setWidth(25);
				$sheet->getColumnDimension('D')->setWidth(8);
				$sheet->getColumnDimension('E')->setWidth(8);
				$sheet->getColumnDimension('F')->setWidth(8);
				$sheet->getColumnDimension('G')->setWidth(8);
				$sheet->getColumnDimension('H')->setWidth(12);
				$sheet->getColumnDimension('I')->setWidth(12);
				$sheet->getColumnDimension('J')->setWidth(12);
				
				$writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
				$writer->save("$tmpFolder//$prevfileName");
			}
			// ----------------------------------------------------------------------------
			// WRAP UP ACTION END
			// ----------------------------------------------------------------------------
		} catch(Exception $e) {
			error_log($e);
		}
		
		gravity_form(8, true, true, false, false, true);
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
		location = 'admin.php?page=workflow_2';
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
	jQuery('#field_8_6').hide();
	jQuery('#input_8_6').val('<?php echo get_admin_url(null,"admin.php?page=folder_display&sub=$tmpFolderTail"); ?>');
	
	jQuery('#input_8_3').val('<?php echo $ids; ?>');
	
	//var theLink = jQuery('<li><a href="' + jQuery('#input_8_6').val() + '" target="_blank"><i class="fas fa-folder-open"></i> 装箱明细</a></li>');
	//theLink.insertAfter(jQuery('#field_8_6'));
});
</script>
<?php
	$_SESSION['workflow2pool'] = array();
?>