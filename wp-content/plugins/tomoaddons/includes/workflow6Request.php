<?php
	session_start();
	$tmpFolder 		= wp_upload_dir()['basedir'] . '/finance/';
	$tmpFolderURL 	= wp_upload_dir()['baseurl'] . '/finance/';
	
	if(isset($_SESSION['workflow6pool']) && count($_SESSION['workflow6pool']) > 0 && isset($_POST['submit'])){
		require __DIR__ . '/../vendor/autoload.php';
		
		$taxRefundDate 				= $_POST['taxRefundDate'];
		$vendorInvoiceNumber 		= $_POST['vendorInvoiceNumber'];
		$vendorInvoiceReceiveDate	= $_POST['vendorInvoiceReceiveDate'];
		$salesOfferDate 			= $_POST['salesOfferDate'];
		$salesGetMoneyDate 			= $_POST['salesGetMoneyDate'];
		$creditLetterFeeInput		= $_POST['creditLetterFeeInput'];
		
		try{
			global $wpdb;
			$ids = implode(',', $_SESSION['workflow6pool']);
			
			// Processing
$mainsql = <<<SQL
SELECT 
	* 
FROM vw_finance_report 
WHERE ETD IS NOT NULL AND id IN ($ids) 
ORDER BY yr, mn
SQL;
			
			$fns = $wpdb->get_results($mainsql, ARRAY_A);
			
			$theSheetName = '';
			$spreadsheet = null;
			$sheet = null;
			$cursor = 2;
			
			$theFileName = date('Y-m-d') . '_' . time() . '_财务文件.xlsx';
			
			foreach($fns as $fn){
				if($spreadsheet == null){
					$spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
				}
				
				$sheetName = '20' . $fn['yr'] . $fn['mn'];
				
				if($theSheetName != $sheetName){
					$theSheetName = $sheetName;
					$sheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, $sheetName);
					$spreadsheet->addSheet($sheet, 0);
					
					$cursor = 2;

					$sheet->setCellValue("A1", '年');
					$sheet->setCellValue("B1", '月');
					$sheet->setCellValue("C1", '日');
					$sheet->setCellValue("D1", '公司名称');
					$sheet->setCellValue("E1", '内部编号');
					$sheet->setCellValue("F1", '发票号码');
					$sheet->setCellValue("G1", '客户名');
					$sheet->setCellValue("H1", '报关单号');
					$sheet->setCellValue("I1", '商品编码');
					$sheet->setCellValue("J1", '报关名称');
					$sheet->setCellValue("K1", '海关编码');
					$sheet->setCellValue("L1", 'RMB采购单价');
					$sheet->setCellValue("M1", 'USD采购单价');
					$sheet->setCellValue("N1", '出货数量');
					$sheet->setCellValue("O1", 'RMB采购金额');
					$sheet->setCellValue("P1", 'USD采购金额');
					$sheet->setCellValue("Q1", '退税率');
					$sheet->setCellValue("R1", '退税额');
					$sheet->setCellValue("S1", '供应商');
					$sheet->setCellValue("T1", '净重');
					$sheet->setCellValue("U1", '报关单位');
					$sheet->setCellValue("V1", 'PI号码');
					$sheet->setCellValue("W1", '报关单价');
					$sheet->setCellValue("X1", '报关金额');
					//$sheet->setCellValue("Y1", '汇率');
					//$sheet->setCellValue("Z1", '利润额');
					//$sheet->setCellValue("AA1", '利润率');
					$sheet->setCellValue("Y1", '状态码');

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
					$sheet->getStyle("A1:AB1")->applyFromArray($styleArray);
					
					$sheet->getColumnDimension("A")->setWidth(3);
					$sheet->getColumnDimension("B")->setWidth(3);
					$sheet->getColumnDimension("C")->setWidth(3);
					$sheet->getColumnDimension("D")->setWidth(12);
					$sheet->getColumnDimension("E")->setWidth(12);
					$sheet->getColumnDimension("F")->setWidth(12);
					$sheet->getColumnDimension("G")->setWidth(12);
					$sheet->getColumnDimension("H")->setWidth(12);
					$sheet->getColumnDimension("I")->setWidth(12);
					$sheet->getColumnDimension("J")->setWidth(12);
					$sheet->getColumnDimension("K")->setWidth(12);
					$sheet->getColumnDimension("L")->setWidth(15);
					$sheet->getColumnDimension("M")->setWidth(15);
					$sheet->getColumnDimension("N")->setWidth(12);
					$sheet->getColumnDimension("O")->setWidth(15);
					$sheet->getColumnDimension("P")->setWidth(15);
					$sheet->getColumnDimension("Q")->setWidth(9);
					$sheet->getColumnDimension("R")->setWidth(12);
					$sheet->getColumnDimension("S")->setWidth(12);
					$sheet->getColumnDimension("T")->setWidth(12);
					$sheet->getColumnDimension("U")->setWidth(12);
					$sheet->getColumnDimension("V")->setWidth(18);
					$sheet->getColumnDimension("W")->setWidth(12);
					$sheet->getColumnDimension("X")->setWidth(12);
					//$sheet->getColumnDimension("Y")->setWidth(12);
					//$sheet->getColumnDimension("Z")->setWidth(12);
					//$sheet->getColumnDimension("AA")->setWidth(9);
					$sheet->getColumnDimension("Y")->setWidth(9);

					$sheet->setCellValue("I11", '可自行登记信息：');
					$sheet->setCellValue("I12", '供方发票号码：' 		. $vendorInvoiceNumber);
					$sheet->setCellValue("I13", '供方发票收到日期：' 	. $vendorInvoiceReceiveDate);
					$sheet->setCellValue("I14", '销售交单日期：' 		. $salesOfferDate);
					$sheet->setCellValue("I15", '销售收汇日期：' 		. $salesGetMoneyDate);
					$sheet->setCellValue("I16", '信用证手续费录入：' 	. $creditLetterFeeInput);
					$sheet->setCellValue("I17", '退税日期：' 			. $taxRefundDate);
				}
				
				$sheet->insertNewRowBefore($cursor, 1);
				
				$sheet->setCellValue("A$cursor", $fn['yr']);
				$sheet->setCellValue("B$cursor", $fn['mn']);
				$sheet->setCellValue("C$cursor", $fn['dy']);
				$sheet->setCellValue("D$cursor", $fn['companyName']);
				$sheet->setCellValue("E$cursor", $fn['verificationNumber']);
				$sheet->setCellValue("F$cursor", $fn['invoiceNumber']);
				$sheet->setCellValue("G$cursor", $fn['clientName']);
				$sheet->setCellValue("H$cursor", $fn['declareNumber']);
				$sheet->setCellValue("I$cursor", $fn['CD']);
				$sheet->setCellValue("J$cursor", $fn['customerChsAbbr']);
				$sheet->setCellValue("K$cursor", $fn['customerCode']);
				$sheet->setCellValue("L$cursor", $fn['factoryPriceRMB']);
				$sheet->setCellValue("M$cursor", $fn['factoryPriceUSD']);
				$sheet->setCellValue("N$cursor", $fn['salesQuantity']);
				$sheet->setCellValue("O$cursor", $fn['amount']);
				$sheet->setCellValue("P$cursor", $fn['usdAmount']);
				$sheet->setCellValue("Q$cursor", $fn['taxRefundRate'] * 100 . '%');
				$sheet->setCellValue("R$cursor", round($fn['taxRefund'],2));
				$sheet->setCellValue("S$cursor", $fn['supplier']);
				$sheet->setCellValue("T$cursor", $fn['netWeight']);
				$sheet->setCellValue("U$cursor", $fn['declareUnit']);
				$sheet->setCellValue("V$cursor", $fn['PINumber']);
				$sheet->setCellValue("W$cursor", $fn['declareUnitPriceUSD']);
				$sheet->setCellValue("X$cursor", $fn['declareAmount']);
				//$sheet->setCellValue("Y$cursor", $fn['xrate']);
				//$sheet->setCellValue("Z$cursor", round($fn['profit'],2));
				//$sheet->setCellValue("AA$cursor", round($fn['profitRate'],4) * 100 . '%');
				$sheet->setCellValue("Y$cursor", $fn['statusCode']);
				
				$lineStyleArray = [
								'font' => [
										'bold' => false,
								],
								'borders' => [
									'allBorders' => [
										'borderStyle' => PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
										'color' => ['argb' => 'FF000000'],
									],
								]
							];
				$sheet->getStyle("A$cursor:AB$cursor")->applyFromArray($lineStyleArray);
				
				$cursor = $cursor + 1;
				
				$fnoid = $fn['id'];
				$fnoid_count = $wpdb->get_var( "SELECT COUNT(*) FROM rl_order_finance WHERE orderId = $fnoid" );
				if($fnoid_count > 0){
					$wpdb->update( 
						'rl_order_finance', 
						array( 
							'FileLink' 		=> "$tmpFolderURL//$theFileName",
							'CreateTime' 	=> current_time('mysql')
						),
						array( 'orderId' => $fnoid ),
						array(
							'%s',
							'%s'
						),
						array( '%d' ) 
					);
				} else {
					$wpdb->insert( 
						'rl_order_finance', 
						array( 
							'orderId'	=> $fnoid, 
							'FileLink' 	=> "$tmpFolderURL//$theFileName"
						), 
						array( 
							'%d',
							'%s'
						)
					);
				}
				
			}
			
			if($spreadsheet != null){
				$save2path = "$tmpFolder/$theFileName";
				$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
				$writer->save($save2path);
			}
		} catch(Exception $e) {
			error_log($e);
		}
?>
<?php
	}
?>
<div style="padding-right:20px;">
<?php
	echo do_shortcode('[wp_file_manager allowed_roles="*" view="list" lang="' . (strpos(ICL_LANGUAGE_CODE,'zh-') === 0 ? "zh_CN" : "en") . '" access_folder="' . substr($tmpFolder, strpos($tmpFolder, 'wp-content')) . '" write = "true" read = "true" allowed_operations="upload,download"]');
?>
</div>
<?php
	$_SESSION['workflow6pool'] = array();
?>