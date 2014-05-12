<?php
$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/bootstrap.datatables.css' : 'static/css/bootstrap.datatables.min.css').'" rel="stylesheet">');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/jquery.datatables.js' : 'static/js/jquery.datatables.min.js').'"></script>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/locales/jquery.datatables.locale.js': 'static/js/locales/jquery.datatables.locale.min.js').'"></script>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/bootstrap.datatables.js' : 'static/js/bootstrap.datatables.min.js').'"></script>');
$this->load->view('header');?>

<div class="page-header">
	<div class="row">
		<div class="col-md-<?php echo !empty($part) ? '8' : '12';?>">
			<h1><?php echo $title;?></h1>
		</div>
		<?php $this->ui->js('footer', 'nav_menu_top();
		$(window).resize(function($){
			nav_menu_top();
		});');
		if(!empty($part)) { ?><div class="col-md-4 menu-tabs">
			<ul class="nav nav-tabs nav-menu">
				<li<?php if($part == 'all') echo ' class="active"';?>><?php echo anchor('billing/manage?'.$param_tab['all'], '全部账单');?></li>
				<li<?php if($part == 'unpaid') echo ' class="active"';?>><?php echo anchor('billing/manage?'.$param_tab['unpaid'], '未支付账单');?></li>
				<li<?php if($part == 'pending') echo ' class="active"';?>><?php echo anchor('billing/manage?'.$param_tab['pending'], '待确认账单');?></li>
			</ul>
		</div><?php } ?>
	</div>
</div>

<div class="menu-pills"></div>

<table id="invoice_list" class="table table-striped table-bordered table-hover table-responsive">
	<thead>
		<tr>
			<th>ID</th>
			<th>代表</th>
			<th>账单标题</th>
			<th>金额</th>
			<th>生成时间</th>
			<th>到期时间</th>
			<th>账单状态</th>
			<th>转账确认</th>
			<th>转账时间</th>
			<th>交易渠道</th>
			<th>流水号</th>
			<th>交易金额</th>
			<th>操作</th>
		</tr>
	</thead>
	
	<tbody>
		
	</tbody>
</table>

<?php
$ajax_url = base_url('billing/ajax/list?'.$param_uri);
$ajax_js = <<<EOT
$(document).ready(function() {
	$('#invoice_list').dataTable( {
		"aoColumnDefs": [
			{ "bSortable": false, "aTargets": [ 0, 11 ] },
			{ "bVisible": false, "aTargets": [ 7, 8, 9, 10, 11 ] }
		],
		"bProcessing": true,
		"bAutoWidth": false,
		"sAjaxSource": '{$ajax_url}',
		"fnInitComplete": function() {
			$('.contact_list').popover();
		}
	} );
} );
EOT;
$this->ui->js('footer', $ajax_js);
$this->load->view('footer');?>