<?php
session_start();
//$_SESSION['workflow1pool'] = array();
// Upload order data file and refresh
global $wpdb;
// Page display
$pgsize = 20;

$builtInWhere = isset($_SESSION['workflow1pool']) && count($_SESSION['workflow1pool']) > 0 ? ' statusCode = 0 AND id NOT IN (' . implode(',', $_SESSION['workflow1pool']) . ') ' : ' statusCode = 0 ';

// Filter Variables
$filters = isset($_GET['ft']) && trim($_GET['ft']) != '' ? base64_decode($_GET['ft']) : ' 1 = 1 ';

$sql = <<<SQL
	SELECT COUNT(*)
	FROM vw_all_orders
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
			<div class="modal-body" style="padding-left:30px;">
				
			</div>
			<div class="modal-footer">
				<button id="secondaryRemove" type="button" class="btn btn-danger" disabled="disabled"><?php _e('Batch Remove', 'tomo'); ?></button>
				<button id="startWorkflow" onclick="javascript:location='admin.php?page=wf1_request';" class="btn btn-primary"><?php _e('Start Workflow', 'tomo'); ?></button>
			</div>
		</div>
	</div>
</div>
<div class="alert alert-info" role="alert" style="margin:10px 20px 0px 0px;">
	<h2 style="margin:0 auto;"><?php _e('Workflow One', 'tomo'); ?></h2>
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
					if(isset($_SESSION['workflow1pool']) && count($_SESSION['workflow1pool']) > 0){
				?>
					<span class="badge"><?php echo count($_SESSION['workflow1pool']); ?></span>
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
		$prodFields = $wpdb->get_results('SHOW FULL COLUMNS FROM vw_all_orders', ARRAY_N);
		$prodCols = array();
?>
<div style="margin:5px 20px 0px 0px; overflow-x:auto;">
	<div style="min-width:100%; width:<?php echo count($prodFields) * 80; ?>px;margin-bottom:0px;">
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
				<tr data-pid="<?php echo $prod['id']; ?>">
					<td class="order_checkbox_cell"><input class="checkItem" type="checkbox" /></td>
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
		location = 'admin.php?page=workflow_1';
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
		addToCart(checkIds.join(), 'workflow1pool', function(){
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
		
		addToCart(theId, 'workflow1pool', function(){
			theLine.fadeOut(1000);
		});
	});
	
	jQuery('#btn-showCart').click(function(){
		jQuery('#newProductModal').modal('show');
		
		jQuery('#newProductModal .modal-body').html('<i class="fas fa-sync fa-spin" style="font-size:20px;"></i>');
		
		var data = {
			'action': 	'get_pool_orders',
			'status': 	0,
			'pool':		'workflow1pool'
		};
		
		jQuery.post(ajaxurl, data, function(response) {
			var obj = JSON.parse(response);
			var tb = jQuery('<table id="secondaryTable" class="table table-striped table-bordered table-hover table-sm" style="font-size:12px;"></table>');
			for(var i = 1; i < obj.length; i++){
				var tr = jQuery('<tr data-id="' + obj[i][0] + '"></tr>');
				
				if(i == 1){
					jQuery('<th><input id="secondaryCheckall" type="checkbox" /></th>').appendTo(tr);
					
					for(var j = 0; j < obj[i].length; j++){
						if(obj[i][j].trim() == ''){
							obj[i][j] = '箱号';
						}
						
						var td = jQuery('<th>' + obj[i][j] + '</th>');
						td.appendTo(tr);
					}
				} else {
					jQuery('<td><input class="secondaryCheckItem" type="checkbox" /></td>').appendTo(tr);
					for(var j = 0; j < obj[i].length; j++){
						if(obj[i][j] == 'null' || obj[i][j] == null){
							obj[i][j] = '';
						} 
						
						if(obj[i][j].indexOf(',') > 0) {
							obj[i][j] = obj[i][j].split(',').join('<br />');
						}
						
						var td = jQuery('<td>' + obj[i][j] + '</td>');
						td.appendTo(tr);
					}
				}
				
				tr.appendTo(tb);
			}
			
			jQuery('#newProductModal .modal-body').html(tb);
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
		removeFromCart(jQuery(this).closest('tr').attr('data-id'), 'workflow1pool', function(){
			line.fadeOut(1000);
		});
	});
	
	jQuery('#secondaryRemove').click(function(){
		removeFromCart(checkSecondaryIds.join(), 'workflow1pool', function(){
			jQuery('.secondaryCheckItem:checked').closest('tr').fadeOut(1000);
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
</script>