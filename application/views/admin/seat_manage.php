<?php
$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/bootstrap.datatables.css' : 'static/css/bootstrap.datatables.min.css').'" rel="stylesheet">');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/jquery.datatables.js' : 'static/js/jquery.datatables.min.js').'"></script>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/locales/jquery.datatables.locale.js': 'static/js/locales/jquery.datatables.locale.min.js').'"></script>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/bootstrap.datatables.js' : 'static/js/bootstrap.datatables.min.js').'"></script>');
$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/flags.css' : 'static/css/flags.min.css').'" rel="stylesheet">');
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
				<li<?php if($part == 'all') echo ' class="active"';?>><?php echo anchor('seat/manage?'.$param_tab['all'], '全部席位');?></li>
				<li<?php if($part == 'available') echo ' class="active"';?>><?php echo anchor('seat/manage?'.$param_tab['available'], '待分配席位');?></li>
				<li<?php if($part == 'assigned') echo ' class="active"';?>><?php echo anchor('seat/manage?'.$param_tab['assigned'], '已分配席位');?></li>
			</ul>
		</div><?php } ?>
	</div>
</div>

<div class="menu-pills"></div>

<table id="seat_list" class="table table-striped table-bordered table-hover table-responsive flags-16">
	<thead>
		<tr>
			<th>ID</th>
			<th>席位名称</th>
			<th>委员会</th>
			<th>席位状态</th>
			<th>等级</th>
			<th>分配代表</th>
			<th>可分配情况</th>
			<th>操作</th>
		</tr>
	</thead>
	
	<tbody>
		
	</tbody>
</table>

<?php
$ajax_url = base_url('seat/ajax/list?'.$param_uri);
$ajax_js = <<<EOT
$(document).ready(function() {
	$('#seat_list').dataTable( {
		"aoColumnDefs": [
			{ "bSortable": false, "aTargets": [ 0, 7 ] }
		],
		"bProcessing": true,
		"sAjaxSource": '{$ajax_url}',
		"fnInitComplete": function() {
			this.fnAdjustColumnSizing();
			$('.contact_list').popover();
		}
	} );
} );
EOT;
$this->ui->js('footer', $ajax_js);
$this->load->view('footer');?>