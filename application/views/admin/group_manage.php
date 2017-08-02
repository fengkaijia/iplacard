<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 代表团管理视图
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
 * @package iPlacard
 * @since 2.0
 */

$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/bootstrap.datatables.css' : 'static/css/bootstrap.datatables.min.css').'" rel="stylesheet">');
$this->ui->html('header', '<style>
	.status-column {
		max-width: 32px !important;
		font-weight: normal;
	}
</style>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/jquery.datatables.js' : 'static/js/jquery.datatables.min.js').'"></script>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/locales/jquery.datatables.locale.js' : 'static/js/locales/jquery.datatables.locale.min.js').'"></script>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/bootstrap.datatables.js' : 'static/js/bootstrap.datatables.min.js').'"></script>');
$this->load->view('header');?>

<div class="page-header">
	<h1>代表团列表</h1>
</div>

<table id="group_list" class="table table-striped table-bordered table-hover table-responsive">
	<thead>
		<tr>
			<th>ID</th>
			<th>团队名称</th>
			<th>团队人数</th>
			<?php foreach($status_order as $now_status)
			{
				$status = $statuses[$now_status]; ?><th class="status-column">
				<span id="status_<?php echo $now_status;?>_help" data-html=true data-placement="top" data-trigger="hover focus" data-original-title="<?php echo "{$status['title']}状态";?>" data-toggle="popover" data-content="<?php echo $status['description'];?>">
					<?php echo $status['short'];?>
				</span>
			</th><?php $this->ui->js('footer', "$('#status_{$now_status}_help').popover();");
			} ?>
			<th>领队</th>
			<th>操作</th>
		</tr>
	</thead>
	
	<tbody>
		
	</tbody>
</table>

<?php
$ajax_url = base_url('group/ajax/list');
$ajax_js = <<<EOT
$(document).ready(function() {
	$('#group_list').dataTable( {
		"aoColumnDefs": [
			{ "bSortable": false, "aTargets": [ 0, 3, 4, 5, 6, 8 ] }
		],
		"bProcessing": true,
		"bAutoWidth": false,
		"sAjaxSource": '{$ajax_url}'
	} );
} );
EOT;
$this->ui->js('footer', $ajax_js);
$this->load->view('footer');?>