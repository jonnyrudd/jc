<?php
session_start();
global $wpdb;
// Page display
$pgsize = 20;

$builtInWhere = isset($_SESSION['workflow6pool']) && count($_SESSION['workflow6pool']) > 0 ? ' id NOT IN (' . implode(',', $_SESSION['workflow6pool']) . ') ' : ' 1 = 1 ';

// Filter Variables
$filters = isset($_GET['ft']) && trim($_GET['ft']) != '' ? base64_decode($_GET['ft']) : ' 1 = 1 ';

$sql = <<<SQL
	SELECT COUNT(*)
	FROM vw_finance_report
	WHERE $builtInWhere AND $filters
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
	<div class="modal-dialog modal-lg" role="document" style="width:90%;">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h2 class="modal-title"><?php _e('Selected Item List', 'tomo'); ?></h2>
			</div>
			<div class="modal-body" style="padding:0px;overflow-x:auto;">
				
			</div>
			<div class="modal-footer">
				<button id="secondaryRemove" type="button" class="btn btn-danger" disabled="disabled" style="margin-bottom:10px;"><?php _e('Batch Remove', 'tomo'); ?></button>
				<form action="admin.php?page=wf6_request" method="POST" style="display:block;" class="form-inline" onsubmit="javascript:return checkInput();">
					<div class="form-group">
						<input type="text" name="vendorInvoiceNumber" class="form-control" placeholder="供方发票号码">
					</div>
					<div class="form-group">
						<input type="text" name="vendorInvoiceReceiveDate" class="form-control" placeholder="供方发票收到日期">
					</div>
					<div class="form-group">
						<input type="text" name="salesOfferDate" class="form-control" placeholder="销售交单日期">
					</div>
					<div class="form-group">
						<input type="text" name="salesGetMoneyDate" class="form-control" placeholder="销售收汇日期">
					</div>
					<div class="form-group">
						<input type="text" name="creditLetterFeeInput" class="form-control" placeholder="信用证手续费录入">
					</div>
					<div class="form-group">
						<input type="text" name="taxRefundDate" class="form-control" placeholder="退税日期">
					</div>
					<input id="startWorkflow" type="submit" class="btn btn-primary" name="submit" value="<?php _e('Generate Report', 'tomo'); ?>" />
				</form>
			</div>
		</div>
	</div>
</div>
<div class="alert alert-info" role="alert" style="margin:10px 20px 0px 0px;">
	<h2 style="margin:0 auto;"><?php _e('Finance Data', 'tomo'); ?></h2>
</div>
<div style="padding:10px 20px 0px 0px;">
	<div class="row">
		<div class="col-md-12">
			<div id="builder-import_export"></div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-3" style="text-align:left;">
			<button id="btn-add" class="btn btn-default"><i class="fas fa-plus-circle"></i> <?php _e('Batch Add', 'tomo'); ?></button>
			<a id="btn-showCart" class="btn btn-primary" href="javascript:void(0);">
				<i class="fas fa-cart-arrow-down"></i> 
				<?php _e('Selected Items', 'tomo'); ?> 
				<?php
					if(isset($_SESSION['workflow6pool']) && count($_SESSION['workflow6pool']) > 0){
				?>
					<span class="badge"><?php echo count($_SESSION['workflow6pool']); ?></span>
				<?php
					}
				?>
			</a>
		</div>
		<div class="col-md-7" style="text-align:center;">
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
		$prodFields = $wpdb->get_results('SHOW FULL COLUMNS FROM vw_finance_report', ARRAY_N);
		$prodCols = array();
?>
<div style="margin:5px 20px 0px 0px; overflow-x:auto;">
	<div style="min-width:100%; width:<?php echo count($prodFields) * 80; ?>px;margin-bottom:0px;">
		<table id="maintable" class="table table-striped table-bordered table-hover table-sm" style="font-size:12px;margin-bottom:0px;">
			<thead class="thead-dark" style="font-weight:bold;">
				<tr>
					<td class="order_ignore"><input id="checkall" type="checkbox" /></td>
<?php
		$headTitle 	= 	[
							'编号',
							'年',
							'月',
							'日',
							'公司名称',
							'内部编号',
							'发票号码',
							'客户名',
							'报关单号',
							'商品编码',
							'报关名称',
							'海关编码',
							'RMB采购单价',
							'USD采购单价',
							'出货数量',
							'RMB采购金额',
							'USD采购金额',
							'退税率',
							'退税额',
							'供应商',
							'净重',
							'报关单位',
							'PI号码',
							'报关单价',
							'报关金额',
							'汇率',
							'利润额',
							'利润率',
							'状态码',
							'关联文件',
							'ETD'
						];
		for($i = 0; $i < count($prodFields); $i++){
			array_push($prodCols,$prodFields[$i][0]);
?>
					<td data-id="<?php echo $prodFields[$i][0]; ?>" data-order-key="<?php echo $prodFields[$i][0] == $ob ? $obk : ''; ?>"><?php echo $headTitle[$i]; ?></td>
<?php
		}
?>
				</tr>
			</thead>
<?php
$sql = <<<SQL
	SELECT *
	FROM vw_finance_report
	WHERE $builtInWhere AND $filters
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
				<tr data-pid="<?php echo $prod['id']; ?>" <?php echo $prod['FileLink'] != '' ? 'class="warning" title="' . basename($prod['FileLink']) . '"':''; ?>>
					<td class="order_checkbox_cell"><input class="checkItem" type="checkbox" /></td>
		<?php
			for($i = 0; $i < count($prodCols); $i++){
		?>
					<td>
						<?php 
							if($prodCols[$i] == 'taxRefundRate'){
								echo $prod[$prodCols[$i]] * 100 . '%';
							} else if($prodCols[$i] == 'taxRefund'){
								echo round($prod[$prodCols[$i]],2);
							} else if($prodCols[$i] == 'profit'){
								echo round($prod[$prodCols[$i]],2);
							} else if($prodCols[$i] == 'profitRate'){
								echo round($prod[$prodCols[$i]],4) * 100 . '%';
							} else if($prodCols[$i] == 'FileLink'){
								$theLink = $prod[$prodCols[$i]];
								if($theLink != ''){
									echo "<a href='$theLink' target='_blank'>" . __('See the file', 'tomo') . "</a>";
								}
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

.form-control {
	width: 170px !important;
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
						id: 'ETD',
						label: 'ETD',
						type: 'datetime',
						validation: {
							format: 'YYYY-MM-DD'
						}
					},
					{
						id: 'companyName',
						label: '公司名称',
						type: 'string'
					},
					{
						id: 'verificationNumber',
						label: '内部编号',
						type: 'string'
					},
					{
						id: 'invoiceNumber',
						label: '发票号码',
						type: 'string'
					},
					{
						id: 'clientName',
						label: '客户名',
						type: 'string'
					},
					{
						id: 'declareNumber',
						label: '报关单号',
						type: 'string'
					},
					{
						id: 'CD',
						label: '商品编码',
						type: 'string'
					},
					{
						id: 'customerChsAbbr',
						label: '报关名称',
						type: 'string'
					},
					{
						id: 'customerCode',
						label: '海关编码',
						type: 'string'
					},
					{
						id: 'factoryPriceRMB',
						label: 'RMB采购单价',
						type: 'double'
					},
					{
						id: 'factoryPriceUSD',
						label: 'USD采购单价',
						type: 'double'
					},
					{
						id: 'salesQuantity',
						label: '出货数量',
						type: 'integer'
					},
					{
						id: 'amount',
						label: 'RMB采购金额',
						type: 'double'
					},
					{
						id: 'usdAmount',
						label: 'USD采购金额',
						type: 'double'
					},
					{
						id: 'taxRefundRate',
						label: '退税率',
						type: 'double'
					},
					{
						id: 'taxRefund',
						label: '退税额',
						type: 'double'
					},
					{
						id: 'supplier',
						label: '供应商',
						type: 'string'
					},
					{
						id: 'netWeight',
						label: '净重',
						type: 'double'
					},
					{
						id: 'PINumber',
						label: 'PI号码',
						type: 'string'
					},
					{
						id: 'declareUnitPriceUSD',
						label: '报关单价',
						type: 'double'
					},
					{
						id: 'declareAmount',
						label: '报关金额',
						type: 'double'
					},
					{
						id: 'xrate',
						label: '汇率',
						type: 'double'
					},
					{
						id: 'profit',
						label: '利润额',
						type: 'double'
					},
					{
						id: 'profitRate',
						label: '利润率',
						type: 'double'
					},
					{
						id: 'statusCode',
						label: '状态码',
						type: 'integer'
					},
					{
						id: 'FileLink',
						label: '关联文件',
						type: 'string'
					}
				]
	});

	jQuery('#btn-reset').on('click', function () {
		location = 'admin.php?page=workflow_6';
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
	
	jQuery('#btn-add').attr('disabled', 'disabled').click(function(){
		addToCart(checkIds.join(), 'workflow6pool', function(){
			location = location;
		});
	});
	
	jQuery('#checkall,.checkItem').click(function(){
		checkIds = [];
		
		jQuery('.checkItem').each(function(){
			if(jQuery(this).prop('checked')){
				checkIds.push(jQuery(this).closest('tr').attr('data-pid'));
			}
		});
		
		if(checkIds.length > 0){
			jQuery('#btn-add').removeAttr('disabled');
		} else {
			jQuery('#btn-add').attr('disabled', 'disabled');
		}
	});
	
	jQuery('#maintable tbody td:not(.order_checkbox_cell)').dblclick(function(event){
		var theLine = jQuery(this).closest('tr');
		var theId 	= theLine.attr('data-pid');
		
		addToCart(theId, 'workflow6pool', function(){
			theLine.fadeOut(200);
		});
	});
	
	jQuery('#btn-showCart').click(function(){
		jQuery('#newProductModal').modal('show');
		
		jQuery('#newProductModal .modal-body').html('<i class="fas fa-sync fa-spin" style="font-size:20px;"></i>');
		
		var data = {
			'action': 	'get_pool_finance_orders',
			'pool':		'workflow6pool'
		};
		
		jQuery.post(ajaxurl, data, function(response) {
			var obj = JSON.parse(response);
			var tb = jQuery('<table id="secondaryTable" class="table table-striped table-bordered table-hover table-sm" style="font-size:12px;margin-bottom:0px;"></table>');
			for(var i = 1; i < obj.length; i++){
				var tr = jQuery('<tr data-id="' + obj[i][0] + '"></tr>');
				
				if(i == 1){
					jQuery('<th><input id="secondaryCheckall" type="checkbox" /></th>').appendTo(tr);
					
					for(var j = 0; j < obj[i].length; j++){
						var td = jQuery('<th>' + obj[i][j] + '</th>');
						td.appendTo(tr);
					}
				} else {
					jQuery('<td><input class="secondaryCheckItem" type="checkbox" /></td>').appendTo(tr);
					for(var j = 0; j < obj[i].length; j++){
						if(obj[i][j] == 'null' || obj[i][j] == null){
							obj[i][j] = '';
						}
						
						// taxRefundRate
						if(j == 17){
							obj[i][j] = obj[i][j] * 100 + '%';
						}
						
						// taxRefund
						if(j == 18){
							obj[i][j] = new Number(obj[i][j]).toFixed(2);
						}
						
						// profit
						if(j == 26){
							obj[i][j] = new Number(obj[i][j]).toFixed(2);
						}
						
						// profitRate
						if(j == 27){
							obj[i][j] = new Number(obj[i][j]).toFixed(4) * 100 + '%';
						}
						
						// FileLink
						if(j == 29){
							obj[i][j] = '-';
						}
						
						var td = jQuery('<td>' + obj[i][j] + '</td>');
						td.appendTo(tr);
					}
				}
				
				tr.appendTo(tb);
			}
			var tbContainer = jQuery('<div style="min-width:100%; width:<?php echo count($prodFields) * 80; ?>px;margin-bottom:0px;"></div>');
			tbContainer.append(tb);
			
			jQuery('#newProductModal .modal-body').html('').append(tbContainer);
		});
	});

	var checkSecondaryIds = [];
	
	jQuery('#newProductModal').on('click', '#secondaryCheckall', function(){
		jQuery('.secondaryCheckItem').prop('checked', jQuery(this).prop('checked'));
	});
	
	jQuery('#newProductModal').on('click', '#secondaryCheckall,.secondaryCheckItem', function(){
		checkSecondaryIds = [];
		
		jQuery('.secondaryCheckItem').each(function(){
			if(jQuery(this).prop('checked')){
				checkSecondaryIds.push(jQuery(this).closest('tr').attr('data-id'));
			}
		});
		
		if(checkSecondaryIds.length > 0){
			jQuery('#secondaryRemove').removeAttr('disabled');
		} else {
			jQuery('#secondaryRemove').attr('disabled', 'disabled');
		}
	});
	
	jQuery('#newProductModal').on('dblclick', '#secondaryTable tr td', function(){
		var line = jQuery(this).closest('tr');
		removeFromCart(jQuery(this).closest('tr').attr('data-id'), 'workflow6pool', function(){
			line.fadeOut(200);
		});
	});
	
	jQuery('#secondaryRemove').click(function(){
		removeFromCart(checkSecondaryIds.join(), 'workflow6pool', function(){
			jQuery('.secondaryCheckItem:checked').closest('tr').fadeOut(200);
		});
	});
	
	jQuery('#newProductModal').on('hidden.bs.modal', function (e) {
		location = location;
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

function addToCart(ids, pool, callback){
	if(ids.trim() == '' || pool.trim() == ''){
		return;
	}
	
	var data = {
		'action': 	'add_to_session_pool',
		'ids': 		ids,
		'pool':		pool
	};
	
	jQuery.post(ajaxurl, data, function(response) {
		jQuery('#btn-showCart .badge').length > 0 ? jQuery('#btn-showCart .badge').html(response) : jQuery('#btn-showCart').append('<span class="badge">' + response + '</span>');;
		callback();
	});
}

function removeFromCart(ids, pool, callback){
	if(ids.trim() == '' || pool.trim() == ''){
		return;
	}
	
	var data = {
		'action': 	'remove_from_session_pool',
		'ids': 		ids,
		'pool':		pool
	};
	
	jQuery.post(ajaxurl, data, function(response) {
		if(response > 0){
			jQuery('#btn-showCart .badge').length > 0 ? jQuery('#btn-showCart .badge').html(response) : jQuery('#btn-showCart').append('<span class="badge">' + response + '</span>');;
		} else {
			jQuery('#btn-showCart .badge').remove();
		}
		callback();
	});
}

function checkInput(){
	if(jQuery.trim(jQuery('input[name=taxRefundDate]').val()) == ''){
		alert('<?php _e('Please input valid tax refund date.', 'tomo'); ?>');
		return false;
	}
	
	if(jQuery.trim(jQuery('input[name=vendorInvoiceNumber]').val()) == ''){
		alert('<?php _e('Please input valid vendor invoice number.', 'tomo'); ?>');
		return false;
	}
	
	if(jQuery.trim(jQuery('input[name=vendorInvoiceReceiveDate]').val()) == ''){
		alert('<?php _e('Please input valid vendor invoice receive date.', 'tomo'); ?>');
		return false;
	}
	
	if(jQuery.trim(jQuery('input[name=salesOfferDate]').val()) == ''){
		alert('<?php _e('Please input valid sales send date.', 'tomo'); ?>');
		return false;
	}
	
	if(jQuery.trim(jQuery('input[name=salesGetMoneyDate]').val()) == ''){
		alert('<?php _e('Please input valid sales get money date.', 'tomo'); ?>');
		return false;
	}
	
	if(jQuery.trim(jQuery('input[name=creditLetterFeeInput]').val()) == ''){
		alert('<?php _e('Please input valid credit letter fee input.', 'tomo'); ?>');
		return false;
	}
	
	return true;
}
</script>