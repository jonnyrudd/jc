<?php
global $wpdb;
$pgsize = 15;

// Filter Variables
$filters = isset($_GET['ft']) && trim($_GET['ft']) != '' ? base64_decode($_GET['ft']) : ' 1 = 1 ';

$sql = <<<SQL
	SELECT COUNT(*)
	FROM vw_active_warehouse
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
	<div class="modal-dialog modal-lg" role="document" style="width:80%;">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h2 class="modal-title"></h2>
			</div>
			<div class="modal-body" style="padding-left:30px;">
			</div>
			<div class="modal-footer">
			</div>
		</div>
	</div>
</div>
<div class="alert alert-info" role="alert" style="margin:10px 20px 0px 0px;">
	<h2 style="margin:0 auto;"><?php _e('Warehouse Maintenance', 'tomo'); ?></h2>
</div>
<div style="padding:10px 20px 0px 0px;">
	<div class="row">
		<div class="col-md-12">
			<div id="builder-import_export"></div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-2" style="text-align:left;">
			<button id="btn-get-sql" class="btn btn-primary parse-sql" data-target="import_export" data-stmt="false"><?php _e('Filter', 'tomo'); ?></button>
			<button id="btn-reset" class="btn btn-default reset" data-target="import_export"><?php _e('Reset', 'tomo'); ?></button>
		</div>
		<div class="col-md-8" style="text-align:center;">
			<nav aria-label="Page navigation">
				<ul class="pagination" style="margin: 0 0 0 0;">
					<?php
					if($pg > 1){
					?>
					<li>
						<a href="javascript:jumpToPage(<?php echo $pg - 1; ?>);" aria-label="Previous">
						<span aria-hidden="true">&laquo;</span>
						</a>
					</li>
					<?php
					}
					?>
					<?php 
						for($i = 1; $i <= $pgmax; $i++){
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
					if($pg < $pgmax){
					?>
					<li>
						<a href="javascript:jumpToPage(<?php echo $pg + 1; ?>);" aria-label="Next">
						<span aria-hidden="true">&raquo;</span>
						</a>
					</li>
					<?php
					}
					?>
				</ul>
			</nav>
		</div>
		<div class="col-md-2" style="text-align:right;">
			<a id="btn-add" class="btn btn-success" href="admin.php?page=warehouse_request"><i class="fas fa-plus-circle"></i> <?php _e('Add New', 'tomo'); ?></a>
		</div>
	</div>
	
</div>
<?php
		$prodFields = $wpdb->get_results('SHOW FULL COLUMNS FROM vw_active_warehouse', ARRAY_N);
		$prodCols = array();
?>
<div style="margin:5px 20px 0px 0px; overflow-x:auto;">
	<div style="min-width:100%; width:<?php echo count($prodFields) * 80; ?>px;margin-bottom:0px;">
		<table id="maintable" class="table table-striped table-bordered table-hover table-sm" style="font-size:12px;margin-bottom:0px;">
			<thead class="thead-dark" style="font-weight:bold;">
				<tr>
<?php
		for($i = 0; $i < count($prodFields); $i++){
			array_push($prodCols,$prodFields[$i][0]);
?>
					<td data-id="<?php echo $prodFields[$i][0]; ?>" data-order-key="<?php echo $prodFields[$i][0] == $ob ? $obk : ''; ?>"><?php echo $prodFields[$i][8]; ?></td>
<?php
		}
?>
				</tr>
			</thead>
<?php
$sql = <<<SQL
	SELECT *
	FROM vw_active_warehouse
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
				<tr data-pid="<?php echo $prod['id']; ?>">
		<?php
			for($i = 0; $i < count($prodCols); $i++){
		?>
					<td><?php echo $prod[$prodCols[$i]]; ?></td>
		<?php
			}
		?>
		
				</tr>
<?php		
	}
?>
			</tbody>
			<tfoot><tr><td colspan="<?php echo count($prodFields); ?>" style="font-weight:bold;"><?php echo sprintf(__("Showing %d to %d of %d entries", 'tomo'), $pgstart+1, $pgend, $total); ?></td></tr></tfoot>
<?php
} else {
?>
			<tfoot><tr><td colspan="<?php echo count($prodFields); ?>" style="font-weight:bold;"><?php echo __('No Record', 'tomo'); ?></td></tr></tfoot>
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
						id: 'name',
						label: '仓库名称',
						type: 'string'
					},
					{
						id: 'address',
						label: '仓库地址',
						type: 'string'
					},
					{
						id: 'contact',
						label: '仓库联系人',
						type: 'string'
					},
					{
						id: 'phone',
						label: '联系电话',
						type: 'string'
					},
					{
						id: 'createdTime',
						label: '创建时间',
						type: 'datetime',
						validation: {
							format: 'YYYY-MM-DD'
						}
					}
				]
	});

	jQuery('#btn-reset').on('click', function () {
		location = 'admin.php?page=warehouse_info';
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
	
	jQuery('#maintable thead td').click(function(){
		var ob = jQuery(this).attr('data-id');
		var obk = jQuery(this).attr('data-order-key');
		
		if(obk == 'asc'){
			obk = 'desc';
		} else {
			obk = 'asc';
		}
		
		orderByPage(ob, obk);
	});
	
	jQuery('#maintable tbody tr').dblclick(function(){
		jQuery('#newProductModal .modal-body').html('<div style="text-align:center;font-size:30px;padding:30px;"><i class="fas fa-sync fa-spin"></i></div>');
		jQuery('#newProductModal').modal('show');
		
		var pid = jQuery(this).attr('data-pid');

		jQuery.ajax({
			url: ajaxurl,
			data: {
				action: 'load_warehouse_detail',
				pid: 	pid
			},
			success: function(data) {
				if(data == 'error'){
					jQuery('#newProductModal .modal-body').html('<div class="alert alert-danger" role="alert"><?php _e('Something went wrong!','tomo'); ?></div>');
				} else {
					data = jQuery.parseJSON(data);
					
					var wrapper = jQuery('<div style="margin:5px 20px 0px 0px; overflow-x:auto;"></div>');
					var innerWrapper = jQuery('<div style="min-width:100%; width:<?php echo count($prodFields) * 80; ?>px;margin-bottom:0px;"></div>');
					
					var tb = jQuery('<table id="subTable" class="table table-striped table-bordered table-hover table-sm" style="font-size:12px;margin-bottom:0px;"></table>');
					
					var hd = data[0];
					var hddom = jQuery('<tr style="font-weight:bold;"></tr>');
					for(var i = 0; i < hd.length; i++){
						hddom.append('<td>' + hd[i] + '</td>');
					}
					tb.append(hddom);
					
					for(var i = 2; i < data.length; i++){
						var tr = i == 2 ? jQuery('<tr class="info"></tr>') : jQuery('<tr></tr>');
						for(var j = 0; j < data[i].length; j++){
							tr.append('<td>' + data[i][j] + '</td>');
						}
						
						tb.append(tr);
					}
					innerWrapper.append(tb);
					wrapper.append(innerWrapper);

					var fv = [];
					fv[0] = data[1];
					fv[1] = data[2];
					var fv64 = b64EncodeUnicode(JSON.stringify(fv));
					
					jQuery('#newProductModal .modal-body').html(wrapper);
					
					jQuery('#newProductModal .modal-header .modal-title').html('<?php _e('History Version','tomo'); ?>');
					
					jQuery('#newProductModal .modal-footer').html('<button type="button" class="btn btn-default" data-dismiss="modal"><?php _e('Close','tomo'); ?></button><form style="display:inline-block;margin-left:10px;" action="admin.php?page=warehouse_request" method="post"><input type="hidden" name="fv" value="' + fv64 + '" /><input type="submit" class="btn btn-primary" value="<?php _e('Edit Request','tomo'); ?>" />');			
				}
			}
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