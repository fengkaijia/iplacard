<?php
$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/bootstrap.datatables.css' : 'static/css/bootstrap.datatables.min.css').'" rel="stylesheet">');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/jquery.datatables.js' : 'static/js/jquery.datatables.min.js').'"></script>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/locales/jquery.datatables.locale.js' : 'static/js/locales/jquery.datatables.locale.min.js').'"></script>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/bootstrap.datatables.js' : 'static/js/bootstrap.datatables.min.js').'"></script>');
$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/mimes.css' : 'static/css/mimes.min.css').'" rel="stylesheet">');
$this->load->view('header');?>

<div class="page-header">
	<h1><?php echo $title;?></h1>
</div>

<table id="document_list" class="table table-striped table-bordered table-hover table-responsive mimes-16">
	<thead>
		<tr>
			<th>ID</th>
			<th>文件名称</th>
			<th>发布时间</th>
			<th>分发范围</th>
			<th>版本</th>
			<th>标识</th>
			<th>下载量</th>
			<th>操作</th>
		</tr>
	</thead>
	
	<tbody>
		
	</tbody>
</table>

<?php
$hide_column = $show_detail ? '' : '5, 6';
$ajax_url = base_url('document/ajax/list');
$ajax_js = <<<EOT
$(document).ready(function() {
	$('#document_list').dataTable( {
		"aoColumnDefs": [
			{ "bSortable": false, "aTargets": [ 0, 7 ] },
			{ "bVisible": false, "aTargets": [ {$hide_column} ] }
		],
		"bProcessing": true,
		"bAutoWidth": false,
		"sAjaxSource": '{$ajax_url}',
		"fnDrawCallback": function() {
			$('.committee_list').popover();
			$('.version_list').popover();
			$('.document_info').tooltip();
		}
	} );
} );
EOT;
$this->ui->js('footer', $ajax_js);
$this->load->view('footer');?>