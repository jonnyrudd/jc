<?php
// Upload order data file and refresh
global $wpdb;
session_start();

require __DIR__ . '/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

if(!empty($_FILES['uploaded_file'])){
	$path = dirname(__DIR__) . '/upload/' . time() . '_' . basename($_FILES['uploaded_file']['name']);
	if(move_uploaded_file($_FILES['uploaded_file']['tmp_name'], $path)) {
		$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
		$spreadsheet = $reader->load($path);

		$sheetData = $spreadsheet->getActiveSheet()->toArray();
		
		for ($i = 1; $i < count($sheetData); $i++) {
			$sql = <<<SQL
INSERT INTO ms_order
(clientName, CD, salesQuantity, PI, Number, ETD, startPort, arrivalPort, companyName, loadBy, loadAt)
VALUES ('%s', '%s', %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', NOW())
SQL;
			$wpdb->query( $wpdb->prepare($sql, array(
														$sheetData[$i][0],
														$sheetData[$i][1],
														$sheetData[$i][2],
														$sheetData[$i][3],
														$sheetData[$i][4],
														$sheetData[$i][5],
														$sheetData[$i][6],
														$sheetData[$i][7],
														$sheetData[$i][8],
														wp_get_current_user()->user_login
													)
										)
						);
		}
		// 清空SESSION
		$_SESSION['workflow1pool'] = array();
		$_SESSION['workflow2pool'] = array();
		$_SESSION['workflow3pool'] = array();
		$_SESSION['workflow4pool'] = array();
		$_SESSION['workflow5pool'] = array();
		$_SESSION['workflow6pool'] = array();
		
		echo '<div class="alert alert-success" role="alert" style="margin: 20px 20px 0px 0px;">' . __('The file has been uploaded and processed successfully', 'tomo') . '</div>';
	} else{
		echo '<div class="alert alert-danger" role="alert" style="margin: 20px 20px 0px 0px;">' . __('There was an error uploading the file, please try again!', 'tomo') . '</div>';
	}
}

// Page display
$pgsize = 20;

// Filter Variables
$filters = isset($_GET['ft']) && trim($_GET['ft']) != '' ? base64_decode($_GET['ft']) : ' 1 = 1 ';

$sql = <<<SQL
	SELECT COUNT(*)
	FROM vw_all_orders
	WHERE $filters
SQL;
//$sql = $wpdb->prepare($sql, array());
$total = $wpdb->get_var($sql);
$pgmax = ceil($total / $pgsize);
$pgmax = $pgmax == 0 ? 1 : $pgmax;

// Pagination Variables
$pg = isset($_GET['pg']) ? $_GET['pg'] : 1;
$pg = $pg > $pgmax ? $pgmax : $pg;

$pgstart = ($pg - 1) * $pgsize;

$pgend = $pgstart + $pgsize;
$pgend = $pgend > $total ? $total : $pgend;

// Order By Variables
$ob = isset($_GET['ob']) ? $_GET['ob'] : 'NULL';
$obk = isset($_GET['obk']) ? $_GET['obk'] : '';
?>
<div class="modal fade" id="newProductModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h2 class="modal-title"><?php _e('Upload Order Data', 'tomo'); ?></h2>
			</div>
			<div class="modal-body" style="padding-left:30px;">
				<form enctype="multipart/form-data" action="admin.php?page=order_info" method="POST">
					<input type="file" name="uploaded_file" accept=".xlsx" style="display:inline-block;width: calc(100% - 80px);" />
					<input type="submit" class="btn btn-primary" value="<?php _e('Upload', 'tomo'); ?>" style="display:inline-block;"/>
				</form>
			</div>
		</div>
	</div>
</div>
<div class="modal fade" id="subOrderModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
	<div class="modal-dialog modal-lg" role="document" style="width:90%;">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
				<h2 class="modal-title"><?php _e('Sub Order Details', 'tomo'); ?></h2>
			</div>
			<div class="modal-body" style="">
				
			</div>
		</div>
	</div>
</div>
<div class="alert alert-info ignore" role="alert" style="margin:10px 20px 0px 0px;">
	<h2 style="margin:0 auto;"><?php _e('Order Maintenance', 'tomo'); ?></h2>
</div>
<div style="padding:10px 20px 0px 0px;">
	<div class="row">
		<div class="col-md-12">
			<div id="builder-import_export"></div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-4" style="text-align:left;">
			<button id="btn-del" class="btn btn-danger" disabled="disabled"><i class="fas fa-trash-alt"></i> <?php _e('Remove', 'tomo'); ?></button>
			<a id="btn-add" class="btn btn-primary" data-toggle="modal" href="javascript:void(0);" data-target="#newProductModal"><i class="fas fa-upload"></i> <?php _e('Batch Load', 'tomo'); ?></a>
			<a href="<?php echo plugins_url('template/orderTemplate.xlsx', dirname(__FILE__)); ?>"> <?php _e('Template Download', 'tomo'); ?></a>
		</div>
		<div class="col-md-6" style="text-align:center;">
			<nav aria-label="Page navigation">
				<ul class="pagination" style="margin: 0 0 0 0;">
					<?php
					$showPgNumber = 11;
					
					$st = $pg - floor($showPgNumber/2) > 1 ? $pg - floor($showPgNumber/2) : 1;
					$ed = $st + $showPgNumber - 1 >  $pgmax ? $pgmax : $st + $showPgNumber - 1;
					
					if($pg > 1){
					?>
					<li>
						<a href="javascript:jumpToPage(1);" aria-label="Previous">
						<span aria-hidden="true"><i class="fas fa-step-backward"></i></span>
						</a>
					</li>
					<li>
						<a href="javascript:jumpToPage(<?php echo $pg - 1; ?>);" aria-label="Previous">
						<span aria-hidden="true"><i class="fas fa-caret-left"></i></span>
						</a>
					</li>
					<?php
					}
					?>
					
					<?php
					if($st > 1){
					?>
						<li>
							<a href="javascript:void(0);">
								<span aria-hidden="true">...</span>
							</a>
						</li>
					<?php
					}
					?>
					
					<?php 
						for($i = $st; $i <= $ed; $i++){
							if($i == $pg){
					?>
					<li class="active"><a href="javascript:void(0);"><?php echo $i; ?></a></li>
					<?php
							} else {
					?>
					<li><a href="javascript:jumpToPage(<?php echo $i; ?>);"><?php echo $i; ?></a></li>
					<?php
							}
						}
					?>
					
					<?php
					if($ed < $pgmax){
					?>
						<li>
							<a href="javascript:void(0);">
								<span aria-hidden="true">...</span>
							</a>
						</li>
					<?php
					}
					?>
					
					<?php
					if($pg < $pgmax){
					?>
					<li>
						<a href="javascript:jumpToPage(<?php echo $pg + 1; ?>);" aria-label="Next">
						<span aria-hidden="true"><i class="fas fa-caret-right"></i></span>
						</a>
					</li>
					<li>
						<a href="javascript:jumpToPage(<?php echo $pgmax; ?>);" aria-label="Next">
						<span aria-hidden="true"><i class="fas fa-step-forward"></i></span>
						</a>
					</li>
					<?php
					}
					?>
				</ul>
			</nav>
		</div>
		<div class="col-md-2" style="text-align:right;">
			<button id="btn-get-sql" class="btn btn-primary parse-sql" data-target="import_export" data-stmt="false"><?php _e('Filter', 'tomo'); ?></button>
			<button id="btn-reset" class="btn btn-default reset" data-target="import_export"><?php _e('Reset', 'tomo'); ?></button>
		</div>
	</div>
	
</div>
<?php
		$prodFields = $wpdb->get_results('SHOW FULL COLUMNS FROM vw_all_orders', ARRAY_N);
		$prodCols = array();
?>
<div style="margin:5px 20px 0px 0px; overflow-x:auto;">
	<div style="min-width:100%; width:<?php echo count($prodFields) * 100; ?>px;margin-bottom:0px;">
		<table id="maintable" class="table table-striped table-bordered table-hover table-sm" style="font-size:12px;margin-bottom:0px;">
			<thead class="thead-dark" style="font-weight:bold;">
				<tr>
					<td class="order_ignore"><input id="checkall" type="checkbox" /></td>
<?php
		for($i = 0; $i < count($prodFields); $i++){
			array_push($prodCols,$prodFields[$i][0]);
?>
					<td data-id="<?php echo $prodFields[$i][0]; ?>" data-order-key="<?php echo $prodFields[$i][0] == $ob ? $obk : ''; ?>"><?php echo $prodFields[$i][8] === '' ? '箱号' : $prodFields[$i][8]; ?></td>
<?php
		}
?>
				</tr>
			</thead>
<?php
$sql = <<<SQL
	SELECT *
	FROM vw_all_orders
	WHERE $filters
	ORDER BY $ob $obk
	LIMIT %d, %d
SQL;

$sql = $wpdb->prepare($sql, array($pgstart, $pgsize));

$prodInfos = $wpdb->get_results($sql, ARRAY_A);
if ( $prodInfos ){
?>
			<tbody>
<?php
	foreach ( $prodInfos as $prod ) {
?>
				<tr data-pid="<?php echo $prod['id']; ?>" <?php echo $prod['arrivalPort'] == '' ? 'class="warning"':''; ?>>
					<td><input class="checkItem" type="checkbox" /></td>
		<?php
			for($i = 0; $i < count($prodCols); $i++){
		?>
					<td>
						<?php 
							if($prodCols[$i] == 'containers'){
								$ctns = $prod[$prodCols[$i]];
								echo '<span class="containers" data-total="' . $prod['salesQuantity'] . '">';
								if(trim($ctns) != ''){
									$ctns = explode(',', $ctns);
									foreach ($ctns as $ctn) {
										echo '<span class="badge" style="display:block;margin-bottom:3px;" title="' . explode(':', $ctn)[1] . '">' . explode(':', $ctn)[0] . '</span>';
									}
								}
								echo '</span>';
							} else if($prodCols[$i] == 'invoiceNumber'){
								echo '<span class="invoiceNumber">' . $prod[$prodCols[$i]] . '</span>';
							} else if($prodCols[$i] == 'verificationNumber'){
								echo '<span class="verificationNumber">' . $prod[$prodCols[$i]] . '</span>';
							} else if($prodCols[$i] == 'salesQuantity'){
								echo $prod[$prodCols[$i]] . ($prod['arrivalPort'] == '' ? ' <i class="fas fa-plus-square expand-order"></i>':'');
							} else {
								echo $prod[$prodCols[$i]];
							}
						?>
					</td>
		<?php
			}
		?>
				</tr>
<?php		
	}
?>
			</tbody>
			<tfoot><tr><td colspan="<?php echo count($prodFields) + 1; ?>" style="font-weight:bold;"><?php echo sprintf(__("Showing %d to %d of %d entries", 'tomo'), $pgstart+1, $pgend, $total); ?></td></tr></tfoot>
<?php
} else {
?>
			<tfoot><tr><td colspan="<?php echo count($prodFields) + 1; ?>" style="font-weight:bold;"><?php echo __('No Record', 'tomo'); ?></td></tr></tfoot>
<?php
}
?>
			
		</table>
	</div>
</div>
<style>
#maintable thead tr td{
	cursor: pointer;
}

#maintable tbody tr td{
	cursor: pointer;
}

#maintable thead tr td[data-order-key=asc]::before{
	font-family: "Font Awesome 5 Free";
    content: "\f0de";
	margin-right: 5px;
}

#maintable thead tr td[data-order-key=desc]::before{
	font-family: "Font Awesome 5 Free";
    content: "\f0dd";
	margin-right: 5px;
}

.bar {
    height: 18px;
    background: green;
}
</style>
<script>
jQuery(document).ready(function() {
	var sql_import_export = "<?php echo $filters; ?>";

	jQuery('#builder-import_export').queryBuilder({
		plugins: [
			'bt-tooltip-errors',
			'not-group'
		],
		lang_code: '<?php echo strpos(ICL_LANGUAGE_CODE,'zh-') === 0 ? 'zh-CN' : 'en'; ?>',
		allow_empty: true,
		filters: [
					{
						id: 'clientName',
						label: '客户名称',
						type: 'string'
					},
					{
						id: 'CD',
						label: '商品CD',
						type: 'string'
					},
					{
						id: 'salesQuantity',
						label: '销售数量',
						type: 'integer'
					},
					{
						id: 'PI',
						label: 'PI',
						type: 'string'
					},
					{
						id: 'PI No.',
						label: 'PI No.',
						type: 'string'
					},
					{
						id: 'ETD',
						label: 'ETD',
						type: 'date'
					},
					{
						id: 'startPort',
						label: '起始港',
						type: 'string'
					},
					{
						id: 'arrivalPort',
						label: '抵达港',
						type: 'string'
					},
					{
						id: 'companyName',
						label: '公司名称',
						type: 'string'
					},
					{
						id: 'statusCode',
						label: '状态码',
						type: 'integer'
					},
					{
						id: 'loadBy',
						label: '导入人',
						type: 'string'
					},
					{
						id: 'loadAt',
						label: '导入时间',
						type: 'datetime',
						validation: {
							format: 'YYYY-MM-DD'
						}
					}
				]
	});

	jQuery('#btn-reset').on('click', function () {
		location = 'admin.php?page=order_info';
	});

	if(sql_import_export == ' 1 = 1 '){
		jQuery('#builder-import_export').queryBuilder('reset');
	} else {
		jQuery('#builder-import_export').queryBuilder('setRulesFromSQL', sql_import_export);
	}
	
	jQuery('#btn-get-sql').on('click', function () {
		var result = jQuery('#builder-import_export').queryBuilder('getSQL', false);
		changeFilter(result.sql);
	});
	
	jQuery('#maintable thead td:not(.order_ignore)').click(function(){
		var ob = jQuery(this).attr('data-id');
		var obk = jQuery(this).attr('data-order-key');
		
		if(obk == 'asc'){
			obk = 'desc';
		} else {
			obk = 'asc';
		}
		
		orderByPage(ob, obk);
	});
	
	var checkIds = [];
	
	jQuery('#checkall').click(function(){
		jQuery('.checkItem').prop('checked', jQuery(this).prop('checked'));
	});
	
	jQuery('#checkall,.checkItem').click(function(){
		checkIds = [];
		
		jQuery('.checkItem').each(function(){
			if(jQuery(this).prop('checked')){
				checkIds.push(jQuery(this).closest('tr').attr('data-pid'));
			}
		});
		
		if(checkIds.length > 0){
			jQuery('#btn-del').removeAttr('disabled');
		} else {
			jQuery('#btn-del').attr('disabled', 'disabled');
		}
	});
	
	jQuery('#btn-del').click(function(){
		jQuery(this).attr('disabled', 'disabled');

		var data = {
			'action': 'del_order',
			'ids': checkIds.join()
		};
		
		jQuery.post(ajaxurl, data, function(response) {
			location = location;
		});
	});
	
	jQuery('.alert:not(.ignore)').fadeOut(2000);
	
	jQuery('.invoiceNumber').closest('td').dblclick(function(){
		var pid = jQuery(this).closest('tr').attr('data-pid');
		
		if(jQuery(this).find('input').length > 0)
            return;
        
        var invoiceEditor = jQuery('<input type="text" style="width:80px;" value="' + jQuery(this).find('.invoiceNumber').html() + '" />');
        jQuery(this).find('.invoiceNumber').html('').append(invoiceEditor);
        invoiceEditor.focus();
        invoiceEditor.blur(function(){
            jQuery(this).attr('disabled','disabled');
			var ivn = jQuery(this).val();
			var that = jQuery(this).parent();
			
            var data = {
				'action': 	'upd_order_invoice',
				'pid': 		pid,
				'ivn':		ivn
			};
			
			jQuery.post(ajaxurl, data, function(response) {
				that.html(ivn);
			});
        });
	});
	
	jQuery('.verificationNumber').closest('td').dblclick(function(){
		var pid = jQuery(this).closest('tr').attr('data-pid');
		
		if(jQuery(this).find('input').length > 0)
            return;
        
        var verificationEditor = jQuery('<input type="text" style="width:80px;" value="' + jQuery(this).find('.verificationNumber').html() + '" />');
        jQuery(this).find('.verificationNumber').html('').append(verificationEditor);
        verificationEditor.focus();
        verificationEditor.blur(function(){
            jQuery(this).attr('disabled','disabled');
			var vfn = jQuery(this).val();
			var that = jQuery(this).parent();
			
            var data = {
				'action': 	'upd_order_verificationNumber',
				'pid': 		pid,
				'vfn':		vfn
			};
			
			jQuery.post(ajaxurl, data, function(response) {
				that.html(vfn);
			});
        });
	});
	
	jQuery('.containers').closest('td').dblclick(function(){
		var pid = jQuery(this).closest('tr').attr('data-pid');
		
		if(jQuery(this).find('textarea').length > 0)
            return;
		
		var rst = '';
		jQuery(this).find('.badge').each(function(){
			var cid = jQuery(this).html();
			var qty = jQuery(this).attr('title');
			rst = rst + cid + ':' + qty + '\n';
		});

        var containersEditor = jQuery('<textarea style="width:150px;height:100%;">' + rst.trim() + '</textarea>');
        jQuery(this).find('.containers').html('').append(containersEditor);
        containersEditor.focus();
        containersEditor.blur(function(){
            jQuery(this).attr('disabled','disabled');
			var that = jQuery(this).parent();
			var raw = jQuery(this).val().trim();
			var ttl = that.attr('data-total');
			
            var data = {
				'action': 	'upd_order_containers',
				'pid': 		pid,
				'ctn':		raw,
				'ttl':		ttl
			};
			
			jQuery.post(ajaxurl, data, function(response) {
				that.html('');

				if(raw == ''){
					;
				} else if (raw.indexOf(':') < 0){
					that.append('<span class="badge" style="display:block;margin-bottom:3px;" title="' + ttl + '">' + raw + '</span>');
				} else {
					var rawArray = raw.split('\n');
					
					for(var i = 0; i < rawArray.length; i++){
						var rawItem = rawArray[i].trim();
						that.append('<span class="badge" style="display:block;margin-bottom:3px;" title="' + rawItem.split(':')[1] + '">' + rawItem.split(':')[0] + '</span>');
					}
				}
			});
        });
	});
	
	jQuery('.expand-order').click(function(){
		var orderId = jQuery(this).closest('tr').attr('data-pid');
		
		jQuery('#subOrderModal').modal('show');
		jQuery('#subOrderModal .modal-body').html('<i class="fas fa-sync fa-spin" style="font-size:20px;"></i>');
		
		var data = {
			'action': 	'get_sub_order_details',
			'orderId':	orderId
		};
		
		jQuery.post(ajaxurl, data, function(response) {
			var obj = JSON.parse(response);
			var tb = jQuery('<table id="secondaryTable" class="table table-striped table-bordered table-hover table-sm" style="font-size:12px;"></table>');
			
			var salesNum = 0;
			
			for(var i = 1; i < obj.length; i++){
				var tr = jQuery('<tr data-id="' + obj[i][0] + '"></tr>');
				
				if(i == 1){
					for(var j = 0; j < obj[i].length; j++){
						if(obj[i][j].trim() == ''){
							obj[i][j] = '箱号';
						}
						
						var td = jQuery('<th>' + obj[i][j] + '</th>');
						td.appendTo(tr);
					}
				} else {
					for(var j = 0; j < obj[i].length; j++){
						if(obj[i][j] == 'null' || obj[i][j] == null){
							obj[i][j] = '';
						} 
						
						if(obj[i][j].indexOf(',') > 0) {
							obj[i][j] = obj[i][j].split(',').join('<br />');
						}
						
						if(j == 3){
							salesNum += obj[i][j];
						}
						
						var td = jQuery('<td>' + obj[i][j] + '</td>');
						td.appendTo(tr);
					}
				}
				
				tr.appendTo(tb);
			}
			
			jQuery('#subOrderModal .modal-body').html(tb);
			
			// send ajax request again
			var sumData = {
				'action': 	'get_sum_order_details',
				'orderId':	orderId
			};
		
			jQuery.post(ajaxurl, sumData, function(sumResponse) {
				var sumObj = JSON.parse(sumResponse);
				var sumTb = jQuery('<table id="sumTable" class="table table-sm" style="font-size:12px;"></table>');
				var sumHeader = jQuery('<tr><th>序号</th><th>客户名称</th><th>商品CD</th><th>销售数量</th><th>销售余数</th><th>PI</th><th>PI No.</th><th>公司名称</th><th>状态码</th></tr>');
				sumHeader.appendTo(sumTb);
				
				for(var i = 0; i < sumObj.length; i++){
					var sumTr = jQuery('<tr data-id="' + sumObj[i][0] + '"></tr>');
					
					for(var j = 0; j < sumObj[i].length; j++){
						if(sumObj[i][j] == 'null' || sumObj[i][j] == null){
							sumObj[i][j] = '';
						} 
						
						if(sumObj[i][j].indexOf(',') > 0) {
							sumObj[i][j] = sumObj[i][j].split(',').join('<br />');
						}
						
						if(j == 4) {
							sumObj[i][j] = sumObj[i][3] - salesNum;
						}
						
						var sumTd = jQuery('<td>' + sumObj[i][j] + '</td>');
						sumTd.appendTo(sumTr);
					}
					
					sumTr.appendTo(sumTb);
				}
				
				jQuery('#subOrderModal .modal-body').append(sumTb);
			});
		});
	});
});

function jumpToPage(pg){
	var pr = {};
	pr['pg'] = pg;
	insertParam(pr);
}

function changeFilter(ft){
	var pr = {};
	pr['pg'] = 1;
	pr['ft'] = ft;
	insertParam(pr);
}

function orderByPage(id, key){
	var pr = {};
	pr['pg'] = 1;
	pr['ob'] = id;
	pr['obk'] = key;
	insertParam(pr);
}
</script>