<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 代表管理视图
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
 * @package iPlacard
 * @since 2.0
 */

$hidden_array = array();
$table_array = array();
if(!empty($profile_option))
{
	for($i = 0; $i < count($profile_option); $i++)
	{
		$table_array[] = "<th>profile_{$profile_option[$i]}</th>";
		$hidden_array[] = $i + 9;
	}
}

$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/bootstrap.datatables.css' : 'static/css/bootstrap.datatables.min.css').'" rel="stylesheet">');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/jquery.datatables.js' : 'static/js/jquery.datatables.min.js').'"></script>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/locales/jquery.datatables.locale.js' : 'static/js/locales/jquery.datatables.locale.min.js').'"></script>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/bootstrap.datatables.js' : 'static/js/bootstrap.datatables.min.js').'"></script>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/jquery.shorten.js' : 'static/js/jquery.shorten.min.js').'"></script>');
$this->load->view('header');?>

<div class="page-header">
	<h1><?php echo $title;?></h1>
</div>

<table id="user_list" class="table table-striped table-bordered table-hover table-responsive">
	<thead>
		<tr>
			<th>ID</th>
			<th>姓名</th>
			<th>团队</th>
			<th>申请类型</th>
			<th>申请状态</th>
			<th>申请提交日期</th>
			<th>委员会</th>
			<th>操作</th>
			<?php echo join('', $table_array);?>
		</tr>
	</thead>
	
	<tbody>
		
	</tbody>
</table>

<?php
$read_more = icon('caret-right', false);
$read_less = icon('caret-left', false);
$hidden_list = join(', ', $hidden_array);
$ajax_url = base_url('delegate/ajax/list?'.$param_uri);
$ajax_js = <<<EOT
$(document).ready(function() {
	$('#user_list').dataTable( {
		"aoColumnDefs": [
			{ "bSortable": false, "aTargets": [ 0, 7 ] },
			{ "bVisible": false, "aTargets": [ {$hidden_list} ] },
			{ "bVisible": false, "aTargets": [ 8 ] },
			{ "iDataSort": 8, "aTargets": [ 5 ] }
		],
		"bProcessing": true,
		"bAutoWidth": false,
		"sAjaxSource": '{$ajax_url}',
		"fnDrawCallback": function() {
			$('.contact_list').popover();
		
			$('.shorten').shorten({
				showChars: '10',
				moreText: '{$read_more}',
				lessText: '{$read_less}'
			});
		}
	} );
} );
EOT;
$this->ui->js('footer', $ajax_js);
$this->load->view('footer');?>