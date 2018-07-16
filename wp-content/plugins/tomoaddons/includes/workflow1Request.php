<?php
	session_start();
	if(isset($_SESSION['workflow1pool']) && count($_SESSION['workflow1pool']) > 0){
		require __DIR__ . '/../vendor/autoload.php';
		
		try{
			// Create processing temporary folder
			$tmpFolderTail = date("Ymd") . '_wf1_' . wp_get_current_user()->user_login . '_' . time();
			$tmpFolder = wp_upload_dir()['basedir'] . '/processing/' . $tmpFolderTail;
			mkdir($tmpFolder);
			
			$tmpFolderURL = wp_upload_dir()['baseurl'] . '/processing/' . $tmpFolderTail;
			
			global $wpdb;
			$ids = implode(',', $_SESSION['workflow1pool']);
			
			
$sql = <<<SQL
SELECT 
	order_summary.PI,
	vw_active_product.supplier,
	CASE WHEN vw_active_product.factoryPriceRMB > 0 THEN 'RMBContract' ELSE 'USDContract' END AS contractType,
	order_summary.CD, 
	vw_active_product.englishName, 
	vw_active_product.chineseName, 
	order_summary.totalSalesQuantity, 
	vw_active_product.factoryPriceRMB,
	vw_active_product.factoryPriceUSD,
	order_summary.deliveryDateList,
	vw_active_vendor.name,
	vw_active_vendor.factoryAddress,
	vw_active_vendor.factoryContract,
	vw_active_vendor.factoryPhone
FROM (
	SELECT 
		ms_order.PI,
		ms_order.clientName,
		ms_order.CD,
		sum(ms_order.salesQuantity) AS totalSalesQuantity,
		GROUP_CONCAT(date_add(ms_order.ETD, interval -10 day)) AS deliveryDateList
	FROM ms_order
	WHERE
		ms_order.id IN ($ids)
	GROUP BY
		ms_order.PI,
		ms_order.clientName,
		ms_order.CD
) AS order_summary
LEFT JOIN vw_active_product 
	ON order_summary.clientName = vw_active_product.clientName 
	AND order_summary.CD = vw_active_product.CD
LEFT JOIN vw_active_vendor
	ON vw_active_product.supplier = vw_active_vendor.shortName
ORDER BY order_summary.PI, vw_active_product.supplier, contractType
SQL;
			
			$contracts = $wpdb->get_results($sql, ARRAY_A);
			
			$theFileName = '';
			$theSpreadSheet = null;
			$cursor = 14;
			
			foreach ( $contracts as $contract ) {
				$PI 			= $contract['PI'];
				$supplier 		= $contract['supplier'];
				$contractType 	= $contract['contractType'];
				$fileName = $PI . '_' . $supplier . '_' . $contractType . '.xlsx';
				
				if($theFileName != $fileName){
					if($theSpreadSheet != null){
						// Save to destination folder
						$leadingWord = $contractType == 'USDContract' ? '美金合计金额（大写）FOB上海：' : '人民币合计金额（大写）：';
						$total = $theSpreadSheet->getActiveSheet()->getCell('H'.($cursor + 2))->getCalculatedValue();
						$theSpreadSheet->getActiveSheet()->setCellValue('A'.($cursor + 3), 	$leadingWord . num_to_rmb($total));
			
						$save2path = "$tmpFolder/$theFileName";
						$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($theSpreadSheet);
						$writer->save($save2path);
					}
					
					// Load from template file
					$path = __DIR__ . '/../template/' . $contractType . '.xlsx';
					$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
					
					$theSpreadSheet = $reader->load($path);
					$theFileName = $fileName;
					$cursor = 14;

					$nextCursor = $cursor + 1;
					$theSpreadSheet->getActiveSheet()->insertNewRowBefore($nextCursor, 1);
					$theSpreadSheet->getActiveSheet()->mergeCells("E$nextCursor:F$nextCursor");
					$theSpreadSheet->getActiveSheet()->mergeCells("I$nextCursor:J$nextCursor");
					$theSpreadSheet->getActiveSheet()->setCellValue("H$nextCursor", "=D$nextCursor*G$nextCursor");
					$theSpreadSheet->getActiveSheet()->setCellValue('D'.($nextCursor + 2), '=SUM(D14:D' . ($nextCursor + 1) . ')');
					$theSpreadSheet->getActiveSheet()->setCellValue('H'.($nextCursor + 2), '=SUM(H14:H' . ($nextCursor + 1) . ')');
					//$daXie = 'H'.($nextCursor + 2);
					//$theSpreadSheet->getActiveSheet()->setCellValue('A'.($nextCursor + 3), "=CONCATENATE(\"美金合计金额（大写）FOB上海：\",IF(ABS($daXie)<0.005,\"\",IF($daXie<0,\"负\",)&IF(INT(ABS($daXie)),TEXT(INT(ABS($daXie)),\"[dbnum2]\")&\"元\",)&IF(INT(ABS($daXie)*10)-INT(ABS($daXie))*10,TEXT(INT(ABS($daXie)*10)-INT(ABS($daXie))*10,\"[dbnum2]\")&\"角\",IF(INT(ABS($daXie))=ABS($daXie),,IF(ABS($daXie)<0.1,,\"零\")))&IF(ROUND(ABS($daXie)*100-INT(ABS($daXie)*10)*10,),TEXT(ROUND(ABS($daXie)*100-INT(ABS($daXie)*10)*10,),\"[dbnum2]\")&\"分\",\"整\")))");
					
					$theSpreadSheet->getActiveSheet()->setCellValue('A7', 	'     供方：' . $contract['name']);
					$theSpreadSheet->getActiveSheet()->setCellValue('H7', 	'合同号：18JC110');
					$theSpreadSheet->getActiveSheet()->setCellValue('H8', 	'管理号：17A-NT030');
					$theSpreadSheet->getActiveSheet()->setCellValue('H10', 	'签定日期：' . date("Y-m-d"));

					$theSpreadSheet->getActiveSheet()->setCellValue("A$cursor", 	$contract['CD']);
					$theSpreadSheet->getActiveSheet()->setCellValue("B$cursor", 	$contract['englishName']);
					$theSpreadSheet->getActiveSheet()->setCellValue("C$cursor", 	$contract['chineseName']);
					$theSpreadSheet->getActiveSheet()->setCellValue("D$cursor", 	$contract['totalSalesQuantity']);
					$theSpreadSheet->getActiveSheet()->setCellValue("E$cursor", 	'个');
					if($contractType == 'USDContract'){
						$theSpreadSheet->getActiveSheet()->setCellValue("G$cursor", 	$contract['factoryPriceUSD']);
					} else {
						$theSpreadSheet->getActiveSheet()->setCellValue("G$cursor", 	$contract['factoryPriceRMB']);
						
					}
					$theSpreadSheet->getActiveSheet()->setCellValue("I$cursor", 	$contract['PI']);
					
					$summaryDeliveryDateList = implode(',', array_unique(explode(',',$contract['deliveryDateList'])));
					$theSpreadSheet->getActiveSheet()->setCellValue('A22', 	'四、交货时间：' . $summaryDeliveryDateList . ' 具体日期以送货通知日期为准。');
					
					$theSpreadSheet->getActiveSheet()->setCellValue('A31', 	'供方：' . $contract['name']);
					$theSpreadSheet->getActiveSheet()->setCellValue('A32', 	'联系地址：' . $contract['factoryAddress']);
					$theSpreadSheet->getActiveSheet()->setCellValue('A33', 	'联系人：' . $contract['factoryContract']);
					$theSpreadSheet->getActiveSheet()->setCellValue('A34', 	'电话：' . $contract['factoryPhone']);
				} else {
					$nextCursor = $cursor + 1;
					$theSpreadSheet->getActiveSheet()->insertNewRowBefore($nextCursor, 1);
					$theSpreadSheet->getActiveSheet()->mergeCells("E$nextCursor:F$nextCursor");
					$theSpreadSheet->getActiveSheet()->mergeCells("I$nextCursor:J$nextCursor");
					$theSpreadSheet->getActiveSheet()->setCellValue("H$nextCursor", "=D$nextCursor*G$nextCursor");
					$theSpreadSheet->getActiveSheet()->setCellValue('D'.($nextCursor + 2), '=SUM(D14:D' . ($nextCursor + 1) . ')');
					$theSpreadSheet->getActiveSheet()->setCellValue('H'.($nextCursor + 2), '=SUM(H14:H' . ($nextCursor + 1) . ')');
					//$daXie = 'H'.($nextCursor + 2);
					//$theSpreadSheet->getActiveSheet()->setCellValue('A'.($nextCursor + 3), "=CONCATENATE(\"美金合计金额（大写）FOB上海：\",IF(ABS($daXie)<0.005,\"\",IF($daXie<0,\"负\",)&IF(INT(ABS($daXie)),TEXT(INT(ABS($daXie)),\"[dbnum2]\")&\"元\",)&IF(INT(ABS($daXie)*10)-INT(ABS($daXie))*10,TEXT(INT(ABS($daXie)*10)-INT(ABS($daXie))*10,\"[dbnum2]\")&\"角\",IF(INT(ABS($daXie))=ABS($daXie),,IF(ABS($daXie)<0.1,,\"零\")))&IF(ROUND(ABS($daXie)*100-INT(ABS($daXie)*10)*10,),TEXT(ROUND(ABS($daXie)*100-INT(ABS($daXie)*10)*10,),\"[dbnum2]\")&\"分\",\"整\")))");
					
					$theSpreadSheet->getActiveSheet()->setCellValue("A$cursor", 	$contract['CD']);
					$theSpreadSheet->getActiveSheet()->setCellValue("B$cursor", 	$contract['englishName']);
					$theSpreadSheet->getActiveSheet()->setCellValue("C$cursor", 	$contract['chineseName']);
					$theSpreadSheet->getActiveSheet()->setCellValue("D$cursor", 	$contract['totalSalesQuantity']);
					$theSpreadSheet->getActiveSheet()->setCellValue("E$cursor", 	'个');
					if($contractType == 'USDContract'){
						$theSpreadSheet->getActiveSheet()->setCellValue("G$cursor", 	$contract['factoryPriceUSD']);						
					} else {
						$theSpreadSheet->getActiveSheet()->setCellValue("G$cursor", 	$contract['factoryPriceRMB']);						
						
					}
					$theSpreadSheet->getActiveSheet()->setCellValue("I$cursor", 	$contract['PI']);
				}
				
				$cursor = $cursor + 1;
			}

			if($theSpreadSheet != null){
				// Save to destination folder
				$leadingWord = $contractType == 'USDContract' ? '美金合计金额（大写）FOB上海：' : '人民币合计金额（大写）：';
				$total = $theSpreadSheet->getActiveSheet()->getCell('H'.($cursor + 2))->getCalculatedValue();
				$theSpreadSheet->getActiveSheet()->setCellValue('A'.($cursor + 3), 	$leadingWord . num_to_rmb($total));
				
				$lastSavePath = "$tmpFolder/$theFileName";
				$lastWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($theSpreadSheet);
				$lastWriter->save($lastSavePath);
			}
		} catch(Exception $e) {
			error_log($e);
		}
		
		gravity_form(3, true, true, false, false, true);
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
		location = 'admin.php?page=workflow_1';
	</script>
<?php
	}
	
function num_to_rmb($num){
    $c1 = "零壹贰叁肆伍陆柒捌玖";
    $c2 = "分角元拾佰仟万拾佰仟亿";
    //精确到分后面就不要了，所以只留两个小数位
    $num = round($num, 2); 
    //将数字转化为整数
    $num = $num * 100;
    if (strlen($num) > 10) {
            return "金额太大，请检查";
    } 
    $i = 0;
    $c = "";
    while (1) {
            if ($i == 0) {
                    //获取最后一位数字
                    $n = substr($num, strlen($num)-1, 1);
            } else {
                    $n = $num % 10;
            }
            //每次将最后一位数字转化为中文
            $p1 = substr($c1, 3 * $n, 3);
            $p2 = substr($c2, 3 * $i, 3);
            if ($n != '0' || ($n == '0' && ($p2 == '亿' || $p2 == '万' || $p2 == '元'))) {
                    $c = $p1 . $p2 . $c;
            } else {
                    $c = $p1 . $c;
            }
            $i = $i + 1;
            //去掉数字最后一位了
            $num = $num / 10;
            $num = (int)$num;
            //结束循环
            if ($num == 0) {
                    break;
            } 
    }
    $j = 0;
    $slen = strlen($c);
    while ($j < $slen) {
            //utf8一个汉字相当3个字符
            $m = substr($c, $j, 6);
            //处理数字中很多0的情况,每次循环去掉一个汉字“零”
            if ($m == '零元' || $m == '零万' || $m == '零亿' || $m == '零零') {
                    $left = substr($c, 0, $j);
                    $right = substr($c, $j + 3);
                    $c = $left . $right;
                    $j = $j-3;
                    $slen = $slen-3;
            } 
            $j = $j + 3;
    } 
    //这个是为了去掉类似23.0中最后一个“零”字
    if (substr($c, strlen($c)-3, 3) == '零') {
            $c = substr($c, 0, strlen($c)-3);
    }
    //将处理的汉字加上“整”
    if (empty($c)) {
            return "零元整";
    }else{
            return $c . "整";
    }
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
	jQuery('#field_3_6').hide();
	jQuery('#input_3_6').val('<?php echo get_admin_url(null,"admin.php?page=folder_display&sub=$tmpFolderTail"); ?>');
	
	jQuery('#input_3_3').val('<?php echo $ids; ?>');
	
	//var theLink = jQuery('<li><a href="' + jQuery('#input_3_6').val() + '" target="_blank"><i class="fas fa-folder-open"></i> 采购合同</a></li>');
	//theLink.insertAfter(jQuery('#field_3_6'));
});
</script>
<?php
	$_SESSION['workflow1pool'] = array();
?>