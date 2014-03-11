<?php
$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/bootstrap.datatables.css' : 'static/css/bootstrap.datatables.min.css').'" rel="stylesheet">');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/jquery.datatables.js' : 'static/js/jquery.datatables.min.js').'"></script>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/locales/jquery.datatables.locale.js': 'static/js/locales/jquery.datatables.locale.min.js').'"></script>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/bootstrap.datatables.js' : 'static/js/bootstrap.datatables.min.js').'"></script>');
$this->load->view('header');?>

<div class="page-header">
	<h1><?php echo $title;?></h1>
</div>

<table id="interview_list" class="table table-striped table-bordered table-hover table-responsive">
	<thead>
		<tr>
			<th>ID</th>
			<th>姓名</th>
			<th>面试状态</th>
			<th>分配时间</th>
			<th>安排时间</th>
			<th>完成时间</th>
			<th>面试结果</th>
			<th>操作</th>
		</tr>
	</thead>
	
	<tbody>
		
	</tbody>
</table>

<?php
$ajax_url = base_url('interview/ajax/list?'.$param_uri);
$ajax_js = <<<EOT
$(document).ready(function() {
	$('#interview_list').dataTable( {
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