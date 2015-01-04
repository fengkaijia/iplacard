<?php
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/jquery.shorten.js' : 'static/js/jquery.shorten.min.js').'"></script>');
$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/bootstrap.datatables.css' : 'static/css/bootstrap.datatables.min.css').'" rel="stylesheet">');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/jquery.datatables.js' : 'static/js/jquery.datatables.min.js').'"></script>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/locales/jquery.datatables.locale.js' : 'static/js/locales/jquery.datatables.locale.min.js').'"></script>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/bootstrap.datatables.js' : 'static/js/bootstrap.datatables.min.js').'"></script>');
$this->load->view('header');?>

<div class="page-header">
	<h1>知识库文章列表</h1>
</div>

<table id="knowledge_list" class="table table-striped table-bordered table-hover table-responsive">
	<thead>
		<tr>
			<th>ID</th>
			<th>知识库编号</th>
			<th>标题</th>
			<th>最后更新时间</th>
			<th>排序</th>
			<th>阅读量</th>
			<th>操作</th>
		</tr>
	</thead>
	
	<tbody>
		
	</tbody>
</table>

<?php
$read_more = icon('caret-right', false);
$read_less = icon('caret-left', false);
$ajax_url = base_url('knowledgebase/ajax/list');
$ajax_js = <<<EOT
$(document).ready(function() {
	$('#knowledge_list').dataTable( {
		"aoColumnDefs": [
			{ "bSortable": false, "aTargets": [ 0, 6 ] }
		],
		"bProcessing": true,
		"bAutoWidth": false,
		"sAjaxSource": '{$ajax_url}',
		"fnDrawCallback": function() {
			$('.system_article').tooltip();
		
			$('.shorten').shorten({
				showChars: '35',
				moreText: '{$read_more}',
				lessText: '{$read_less}'
			});
		}
	} );
} );
EOT;
$this->ui->js('footer', $ajax_js);
$this->load->view('footer');?>