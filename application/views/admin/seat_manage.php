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
echo form_open('seat/operation/preserve_seat/0', array(
	'class' => 'modal fade form-horizontal',
	'id' => 'preserve_seat',
	'tabindex' => '-1',
	'role' => 'dialog',
	'aria-labelledby' => 'preserve_label',
	'aria-hidden' => 'true'
));?><div class="modal-dialog">
	<div class="modal-content">
		<div class="modal-header">
			<?php echo form_button(array(
				'content' => '&times;',
				'class' => 'close',
				'type' => 'button',
				'data-dismiss' => 'modal',
				'aria-hidden' => 'true'
			));?>
			<h4 class="modal-title" id="preserve_label">保留席位</h4>
		</div>
		<div class="modal-body">
			<p>将会保留以下席位：</p>
			
			<table class="table table-striped table-bordered table-hover table-responsive flags-16">
				<tbody>
					<tr>
						<td>席位ID</td>
						<td class="seat-id"></td>
					</tr>
					<tr>
						<td>席位名称</td>
						<td class="seat-name"></td>
					</tr>
					<tr>
						<td>等级</td>
						<td class="seat-level"></td>
					</tr>
					<tr>
						<td>状态</td>
						<td class="seat-status"></td>
					</tr>
				</tbody>
			</table>
			
			<p>席位保留后将只有<?php echo icon('tags', false);?><span class="seat-committee"></span>委员会的面试官可分配此席位。在此席位再次开放前，其他委员会面试官无法分配此席位。管理员和该委员会的面试官可以开放此席位。</p>
		</div>
		<div class="modal-footer">
			<?php echo form_button(array(
				'content' => '取消',
				'type' => 'button',
				'class' => 'btn btn-link',
				'data-dismiss' => 'modal'
			));
			echo form_button(array(
				'name' => 'submit',
				'content' => '确认保留',
				'type' => 'submit',
				'class' => 'btn btn-primary',
				'onclick' => 'loader(this);'
			)); ?>
		</div>
	</div>
</div>
<?php echo form_close();

echo form_open('seat/operation/open_seat/0', array(
	'class' => 'modal fade form-horizontal',
	'id' => 'open_seat',
	'tabindex' => '-1',
	'role' => 'dialog',
	'aria-labelledby' => 'open_label',
	'aria-hidden' => 'true'
));?><div class="modal-dialog">
	<div class="modal-content">
		<div class="modal-header">
			<?php echo form_button(array(
				'content' => '&times;',
				'class' => 'close',
				'type' => 'button',
				'data-dismiss' => 'modal',
				'aria-hidden' => 'true'
			));?>
			<h4 class="modal-title" id="open_label">开放席位</h4>
		</div>
		<div class="modal-body">
			<p>将会开放以下席位：</p>
			
			<table class="table table-striped table-bordered table-hover table-responsive flags-16">
				<tbody>
					<tr>
						<td>席位ID</td>
						<td class="seat-id"></td>
					</tr>
					<tr>
						<td>席位名称</td>
						<td class="seat-name"></td>
					</tr>
					<tr>
						<td>等级</td>
						<td class="seat-level"></td>
					</tr>
					<tr>
						<td>状态</td>
						<td class="seat-status"></td>
					</tr>
				</tbody>
			</table>
			
			<p>席位开放后将所有面试官都将可分配此席位。</p>
		</div>
		<div class="modal-footer">
			<?php echo form_button(array(
				'content' => '取消',
				'type' => 'button',
				'class' => 'btn btn-link',
				'data-dismiss' => 'modal'
			));
			echo form_button(array(
				'name' => 'submit',
				'content' => '确认开放',
				'type' => 'submit',
				'class' => 'btn btn-primary',
				'onclick' => 'loader(this);'
			)); ?>
		</div>
	</div>
</div>
<?php echo form_close(); ?>

<?php
$ajax_url = base_url('seat/ajax/list?'.$param_uri);
$ajax_js = <<<EOT
$(document).ready(function() {
	$('#seat_list').dataTable( {
		"aoColumnDefs": [
			{ "bSortable": false, "aTargets": [ 0, 7 ] }
		],
		"bProcessing": true,
		"bAutoWidth": false,
		"sAjaxSource": '{$ajax_url}',
		"fnRowCallback": function( nRow, aData, iDisplayIndex, iDisplayIndexFull ) {
			$(nRow).attr("id", 'seat-' + aData[0]);
		},
		"fnInitComplete": function() {
			$('.contact_list').popover();
		}
	} );
} );
EOT;
$this->ui->js('footer', $ajax_js);

$preserve_url = base_url('seat/operation/preserve_seat');
$open_url = base_url('seat/operation/open_seat');
$box_js = <<<EOT
function set_seat_box(id, type) {
	switch(type) {
		case 'preserve':
			$('#preserve_seat').attr('action', '{$preserve_url}/' + id);
			break;
		case 'open':
			$('#open_seat').attr('action', '{$open_url}/' + id);
			break;
	}
	
	$(':hidden[name=seat]').val(id);
	
	$('.seat-id').html($('#seat-' + id).children().eq(0).html());
	$('.seat-name').html($('#seat-' + id).children().eq(1).html());
	$('.seat-committee').html($('#seat-' + id).children().eq(2).html());
	$('.seat-level').html($('#seat-' + id).children().eq(4).html());
	
	if($('#seat-' + id).children().eq(3).is(':empty')) {
		$('.seat-status').html('<span class="label label-primary">正常</span>');
	} else {
		$('.seat-status').html($('#seat-' + id).children().eq(3).html());
	}
}
EOT;
$this->ui->js('footer', $box_js);
$this->load->view('footer');?>