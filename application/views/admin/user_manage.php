<?php
$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/bootstrap.datatables.css' : 'static/css/bootstrap.datatables.min.css').'" rel="stylesheet">');
$this->ui->html('header', '<style>
	.role-column {
		max-width: 32px !important;
		font-weight: normal;
	}
</style>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/jquery.datatables.js' : 'static/js/jquery.datatables.min.js').'"></script>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/locales/jquery.datatables.locale.js' : 'static/js/locales/jquery.datatables.locale.min.js').'"></script>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/bootstrap.datatables.js' : 'static/js/bootstrap.datatables.min.js').'"></script>');
$this->load->view('header');?>

<div class="page-header">
	<h1>管理用户列表</h1>
</div>

<table id="user_list" class="table table-striped table-bordered table-hover table-responsive">
	<thead>
		<tr>
			<th>ID</th>
			<th>姓名</th>
			<th>称谓</th>
			<th>委员会</th>
			<th>面试队列</th>
			<th>权限统计</th>
			<?php foreach($role_order as $now_role)
			{
				$role = $roles[$now_role]; ?><th class="role-column">
				<span id="role_<?php echo $now_role;?>_help" data-html=true data-placement="top" data-trigger="hover focus" data-original-title="<?php echo "{$role['title']}权限";?>" data-toggle="popover" data-content="<?php echo str_replace('|', '<br />', $role['description']);?>">
					<?php echo $role['short'];?>
				</span>
			</th><?php $this->ui->js('footer', "$('#role_{$now_role}_help').popover();");
			} ?>
			<th>最后登录</th>
			<th>操作</th>
		</tr>
	</thead>
	
	<tbody>
		
	</tbody>
</table>

<?php
$ajax_url = base_url('user/ajax/list');
$ajax_js = <<<EOT
$(document).ready(function() {
	$('#user_list').dataTable( {
		"aoColumnDefs": [
			{ "bSortable": false, "aTargets": [ 0, 6, 7, 8, 9, 10, 11, 13 ] }
		],
		"bProcessing": true,
		"bAutoWidth": false,
		"sAjaxSource": '{$ajax_url}',
		"fnDrawCallback": function() {
			$('.contact_list').popover();
		}
	} );
} );
EOT;
$this->ui->js('footer', $ajax_js);
$this->load->view('footer');?>