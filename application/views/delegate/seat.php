<?php
$option_primary = array();
$option_backorder = array();
$option_highlight = array();
$option_html = array();
foreach($selectabilities as $selectability)
{
	$groupname = $committees[$selectability['seat']['committee']]['name'];
	
	if($selectability['primary'])
		$option_primary[$groupname][$selectability['seat']['id']] = $selectability['seat']['name'];
	
	$option_backorder[$groupname][$selectability['seat']['id']] = $selectability['seat']['name'];
	$option_html[$selectability['seat']['id']] = flag($selectability['seat']['iso'], true, true, false, false).$selectability['seat']['name'];
	
	if($selectability['recommended'])
		$option_highlight[] = $selectability['seat']['id'];
}

$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/bootstrap.datatables.css' : 'static/css/bootstrap.datatables.min.css').'" rel="stylesheet">');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/jquery.datatables.js' : 'static/js/jquery.datatables.min.js').'"></script>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/locales/jquery.datatables.locale.js' : 'static/js/locales/jquery.datatables.locale.min.js').'"></script>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/bootstrap.datatables.js' : 'static/js/bootstrap.datatables.min.js').'"></script>');
$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/bootstrap.select.css' : 'static/css/bootstrap.select.min.css').'" rel="stylesheet">');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/bootstrap.select.js' : 'static/js/bootstrap.select.min.js').'"></script>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/locales/bootstrap.select.locale.js' : 'static/js/locales/bootstrap.select.locale.min.js').'"></script>');
$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/flags.css' : 'static/css/flags.min.css').'" rel="stylesheet">');
$this->load->view('header');?>

<div class="page-header">
	<div class="row">
		<div class="col-md-<?php echo !empty($seat) ? '8' : '12';?>">
			<h1><?php echo icon('list-alt')?>席位信息</h1>
		</div>
		<?php $this->ui->js('footer', 'nav_menu_top();
		$(window).resize(function($){
			nav_menu_top();
		});');
		if(!empty($seat)) { ?><div class="col-md-4 menu-tabs">
			<ul class="nav nav-tabs nav-menu">
				<li class="active"><a href="#seat" data-toggle="tab">我的席位</a></li>
				<?php if(!empty($attached_seats)) { ?><li id="attach_tab"><a href="#attach" data-toggle="tab">多代信息</a></li><?php } ?>
				<?php if($seat_mode == 'select') { ?><li id="select_tab"><a href="#select" data-toggle="tab">席位选择</a></li><?php } ?>
			</ul>
		</div><?php } ?>
	</div>
</div>

<div class="menu-pills"></div>

<div class="row">
	<div class="tab-content">
		<?php if(!empty($seat)) { ?>
		<div class="tab-pane active" id="seat">
			<div class="col-md-8">
				<h3><?php echo $seat_mode == 'select' ? '已选择席位' : '已分配席位';?></h3>
				<table class="table table-bordered table-striped table-hover flags-16">
					<tbody>
						<tr>
							<td>席位名称</td>
							<td><?php echo flag($seat['iso']).$seat['name'];?></td>
						</tr>
						<tr>
							<td>委员会</td>
							<td><?php echo "{$seat['committee']['name']}（{$seat['committee']['abbr']}）";?></td>
						</tr>
						<tr>
							<td>选择时间</td>
							<td><?php echo sprintf('%1$s（%2$s）', date('n月j日 H:i:s', $seat['time']), nicetime($seat['time']));?></td>
						</tr>
						<tr>
							<td>状态</td>
							<td><?php echo $delegate['status'] == 'locked' ? '<span class="text-success">已经锁定</span>' : '<span class="text-primary">尚未锁定</span>';?></td>
						</tr>
					</tbody>
				</table>
				
				<?php if(!empty($attached_seats)) { ?><p>当前席位为多代席位，您可以查看与您共同代表该席位的代表信息。</p>
				<a id="seat_change_start" href="#attach" data-toggle="tab" class="btn btn-primary" onclick="$('.nav-menu li').removeClass('active'); $('#attach_tab').addClass('active');">查看席位多代信息</a><?php
				} ?>
				
				<?php if(!empty($backorders)) { ?>
				<hr />
				
				<h3>席位候选</h3>
				<p>您已候选了以下席位，在锁定席位前，您有可能被调整为以下席位。</p>
				<table id="backorder_list" class="table table-striped table-bordered table-hover table-responsive flags-16">
					<thead>
						<tr>
							<th>席位名称</th>
							<th>委员会</th>
							<th>候选时间</th>
						</tr>
					</thead>

					<tbody>
						<?php foreach($backorders as $backorder) { ?><tr>
							<td><?php echo flag($backorder['seat']['iso'], true).$backorder['seat']['name'];?></td>
							<td><?php echo $backorder['seat']['committee']['name'];?></td>
							<td><?php echo sprintf('%1$s（%2$s）', date('n月j日', $backorder['order_time']), nicetime($backorder['order_time']));?></td>
						</tr><?php } ?>
					</tbody>
				</table><?php } ?>
			</div>
			
			<div class="col-md-4">
				<?php if($seat_mode == 'select') { ?><h3>调整席位选择</h3>
				<p>您当前已经选择席位，但您的面试官仍可能会继续向您开放席位选择。在您锁定席位前，您可以随时调整您的席位选择。锁定席位后，您将无法调整席位。</p>
				<?php
				if(!$change_open)
				{
					$this->ui->js('footer', "$('#seat_change_lock').popover();");
					if($delegate['status'] == 'locked') { ?><a id="seat_change_lock" data-original-title="无法选择席位" href="#" class="btn btn-primary" data-toggle="popover" data-placement="right" data-content="您锁定席位，无法调整席位。" title="">调整席位</a><?php }
				} else { ?><a id="seat_change_start" href="#select" data-toggle="tab" class="btn btn-primary" onclick="$('.nav-menu li').removeClass('active'); $('#select_tab').addClass('active');">调整席位</a><?php } ?>
				
				<hr /><?php } ?>
				
				<?php if($lock_open) { ?><h3>确认锁定席位</h3>
				<p>现可以确认申请完成并锁定您的席位，锁定后将不会发生变动。</p>
				<a class="btn btn-primary" href="#" data-toggle="modal" data-target="#lock">立即锁定席位</a><?php $this->load->view('delegate/panel/lock'); } ?>
			</div>
		</div><?php } ?>
		
		<?php if(!empty($attached_seats)) { ?>
		<div class="tab-pane" id="attach">
			<div class="col-md-8">
				<h3>主席位</h3>
				<table class="table table-bordered table-striped table-hover flags-16">
					<tbody>
						<tr>
							<td class="attach_item">席位名称</td>
							<td><?php echo flag($attached_primary['iso']).$attached_primary['name'];?></td>
						</tr>
						<tr>
							<td>委员会</td>
							<td><?php echo "{$attached_primary['committee']['name']}（{$attached_primary['committee']['abbr']}）";?></td>
						</tr>
						<?php if($attached_primary['id'] == $seat['id']) { ?><tr>
							<td>状态</td>
							<td>主席位为您的席位</td>
						</tr><?php }
						elseif(!empty($attached_primary['delegate'])) { ?><tr>
							<td>代表</td>
							<td><?php echo icon('user').$attached_primary['delegate']['name'];?></td>
						</tr>
						<tr>
							<td>代表邮箱</td>
							<td><?php echo icon('envelope-o').$attached_primary['delegate']['email'];?></td>
						</tr><?php } else { ?><tr>
							<td>状态</td>
							<td>席位尚未被分配</td>
						</tr><?php } ?>
					</tbody>
				</table>
				
				<hr />
				
				<h3>子席位</h3>
				<?php foreach($attached_seats as $attached_seat) { ?>
				<table class="table table-bordered table-striped table-hover flags-16">
					<tbody>
						<tr>
							<td class="attach_item">席位名称</td>
							<td><?php echo flag($attached_seat['iso']).$attached_seat['name'];?></td>
						</tr>
						<?php if($attached_seat['committee']['id'] != $attached_primary['committee']['id']) { ?><tr>
							<td>委员会</td>
							<td><?php echo "{$attached_seat['committee']['name']}（{$attached_seat['committee']['abbr']}）";?></td>
						</tr><?php }
						if($attached_seat['id'] == $seat['id']) { ?><tr>
							<td>状态</td>
							<td>此席位为您的席位</td>
						</tr><?php }
						elseif(!empty($attached_seat['delegate'])) { ?><tr>
							<td>代表</td>
							<td><?php echo icon('user').$attached_seat['delegate']['name'];?></td>
						</tr>
						<tr>
							<td>代表邮箱</td>
							<td><?php echo icon('envelope-o').$attached_seat['delegate']['email'];?></td>
						</tr><?php } else { ?><tr>
							<td>状态</td>
							<td>席位尚未被分配</td>
						</tr><?php } ?>
					</tbody>
				</table>
				
				<br />
				<?php } ?>
			</div>
			
			<div class="col-md-4">
				<h3>多代席位</h3>
				<p>您选择的席位为多代席位，本页显示与您合作代表该席位的代表。</p>
				<p>在显示的信息中，主席位和子席位可能存在等级关系，同时也可能没有区别（例如处理双代席位的情况）。部分尚无代表的席位将会提示尚未分配，随着代表录取和席位滚动的进行，多代信息的名单将会随时变化。</p>
				<p>我们建议使用提供的电邮地址联系您的合作伙伴。</p>
			</div>
		</div><?php } ?>
		
		<?php if($seat_mode == 'select') { ?><div class="tab-pane<?php echo empty($seat) ? ' active' : '';?>" id="select">
			<div class="col-md-8">
				<h3>席位分配</h3>
				<p>我们已经向您分配了 <?php echo $selectability_count;?> 个席位，其中包括了 <?php echo $selectability_primary_count;?> 个主分配席位和 <?php echo $selectability_count - $selectability_primary_count;?> 个候选分配席位。</p>
				<p>您可以在主分配席位中选择 1 个席位为您的参会席位，同时您在主分配席位和候选分配席位中还可以选择最多 <?php echo $select_backorder_max;?> 个候选席位。由于其他原因（例如席位已经被其他代表选中或者我们尚未决定是否要设置此席位等），您的面试官无法开放部分席位为主分配席位，因此他将此类席位开放为候选分配席位，您可以现在选择这类席位为候选席位，当选中此席位的代表因故退会或调整席位时，您将有机会调整您的席位为此席位。</p>
				<p>iPlacard 已经加粗显示了面试官推荐的席位。如果您认为您不适合分配的席位，您可以与您的面试官<?php echo anchor('apply/interview', '联系');?>，他将可以根据情况追加席位分配。</p>

				<table id="selectability_list" class="table table-striped table-bordered table-hover table-responsive flags-16">
					<thead>
						<tr>
							<th>ID</th>
							<th>席位名称</th>
							<th>委员会</th>
							<th>类型</th>
							<th>开放时间</th>
							<th>操作</th>
						</tr>
					</thead>

					<tbody>
						<?php foreach($selectabilities as $selectability)
						{
							$one_seat = $selectability['seat'];
							?><tr id="seat-<?php echo $one_seat['id'];?>">
							<td><?php echo $one_seat['id'];?></td>
							<td><?php echo flag($one_seat['iso'], true);
							echo $selectability['recommended'] ? "<strong>{$one_seat['name']}</strong>" : $one_seat['name'];?></td>
							<td><?php echo $committees[$one_seat['committee']]['name'];?></td>
							<td><?php
							if($selectability['seat']['delegate'] != $delegate['id'])
								echo $selectability['primary'] ? '<span class="text-success">主分配席位</span>' : '<span class="text-primary">候选分配席位</span>';
							else
								echo '<span class="text-success">已选为主席位</span>';?></td>
							<td><?php printf('%1$s（%2$s）', date('n月j日', $selectability['time']), nicetime($selectability['time']));?></td>
							<td><?php if($selectability['primary'] && $one_seat['status'] != 'assigned')
								echo '<a style="cursor: pointer;" onclick="select_seat('.$one_seat['id'].', true);">'.icon('plus-square', false).'席位</a> ';
							echo '<a class="select_backorder_button" style="cursor: pointer;" onclick="select_seat('.$one_seat['id'].', false);">'.icon('plus-square-o', false).'候选</a>';?></td>
						</tr><?php } ?>
					</tbody>
				</table>
			</div>

			<div class="col-md-4">
				<h3>选择席位</h3>
				<div id="pre_select">
					<p>提交席位选择后，您选择的席位将被临时保留，其他人无法继续选择其为参会席位。</p>
					<p>点击提交席位选择后，我们将为您生成会费账单，您将可以在 <?php echo option('seat_payment_timeout', 7);?> 天内完成会费支付，支付完成后您的席位将被锁定。如果未能在 <?php echo option('seat_payment_timeout', 7);?> 天内完成会费支付，您的席位将会自动解锁。</p>
					<?php
					if(!$select_open)
					{
						$this->ui->js('footer', "$('#seat_confirm_lock').popover();");
						if($delegate['status'] == 'quitted') { ?><a id="seat_confirm_lock" data-original-title="无法选择席位" href="#" class="btn btn-primary" data-toggle="popover" data-placement="right" data-content="您已退会无法选择席位。" title="">开始选择席位</a><?php }
						elseif($delegate['status'] == 'locked') { ?><a id="seat_confirm_lock" data-original-title="无法选择席位" href="#" class="btn btn-primary" data-toggle="popover" data-placement="right" data-content="您已锁定席位。" title="">开始选择席位</a><?php }
						else { ?><a id="seat_confirm_lock" data-original-title="席位选择尚未开放" href="#" class="btn btn-primary" data-toggle="popover" data-placement="right" data-content="席位选择功能暂时关闭，请稍后尝试。" title="">开始选择席位</a><?php }
					} else { ?><a id="seat_confirm_start" href="#" class="btn btn-primary" onclick="$('#pre_select').hide(); $('#do_select').show(); $('#selectability_list').dataTable().fnSetColumnVis( 5, true );">开始选择席位</a><?php } ?>
				</div>

				<div id="do_select">
					<?php
					echo form_open_multipart('apply/seat/select', array('id' => 'seat_form'), array('seat_primary' => empty($seat) ? '' : $seat['id']));?>
						<p>请在下方下拉框中选择您的参会席位和候选席位。完成选择后请点击提交席位选择按钮。</p>

						<div class="form-group <?php if(form_has_error('primary')) echo 'has-error';?>">
							<?php echo form_label('席位', 'primary', array('class' => 'control-label'));?>
							<div>
								<?php echo form_dropdown_select('primary', $option_primary, empty($seat) ? array() : $seat['id'], $selectability_primary_count > 10 ? true : false, $option_highlight, array(), $option_html, 'selectpicker flags-16', 'data-width="100%" title="选择主席位"');
								if(form_has_error('primary'))
									echo form_error('primary');
								?>
							</div>
							<?php $this->ui->js('footer', "$('select[name=\"primary\"]').change(function () {
								if($('select[name=\"primary\"]').val() != null) {
									deselect_primary($('input[name=seat_primary]').val());
									select_primary($('select[name=\"primary\"]').val());
								}
							});");?>
						</div>

						<div class="form-group <?php if(form_has_error('backorder')) echo 'has-error';?>">
							<?php echo form_label('候选席位', 'backorder', array('class' => 'control-label'));?>
							<div>
								<?php echo form_dropdown_multiselect('backorder[]', $option_backorder, empty($backorders) ? array() : $backordered_seats, $selectability_count > 10 ? true : false, $option_highlight, array(), $option_html, 'selectpicker flags-16', 'data-selected-text-format="count" data-width="100%" title="请选择最多 '.$select_backorder_max.' 个候选席位"');
								if(form_has_error('backorder'))
									echo form_error('backorder');
								?>
							</div>
							<?php $this->ui->js('footer', "$('select[name=\"backorder[]\"]').change(function () {
								if($('select[name=\"backorder[]\"] option:selected').length == $select_backorder_max) {
									lock_backorder();
								} else {
									unlock_backorder();
								}
							});");?>
						</div>

						<?php echo form_button(array(
							'name' => 'submit',
							'content' => '提交席位选择',
							'type' => 'submit',
							'class' => 'btn btn-primary',
							'onclick' => 'loader(this);'
						));?>

					<?php echo form_close(); ?>
				</div>
				<?php $this->ui->js('footer', "$('#do_select').hide();");?>
			</div>
		</div><?php } ?>
	</div>
</div>

<?php
//多代席位宽度
$width_js = <<<EOT
$('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
	var lowest = Infinity;
	
	$('.attach_item').each(function() {
		if($(this).width() < lowest) {
			lowest = $(this).width();
		}
	});
	
	$('.attach_item').width(lowest);
});
EOT;
if(!empty($attached_seats))
	$this->ui->js('footer', $width_js);

$selectability_js = <<<EOT
$(document).ready(function() {
	$('#selectability_list').dataTable( {
		"aoColumnDefs": [
			{ "bVisible": false, "aTargets": [ 0, 5 ] }
		],
		"bProcessing": true,
		"bAutoWidth": false,
		"sDom": "<'row'r>t<'col-xs-4'i><'col-xs-8'p>"
	} );
} );
EOT;
if($seat_mode == 'select')
	$this->ui->js('footer', $selectability_js);

//TODO
$seat_primary_ids = json_encode(array());
if($selectability_primary)
	$seat_primary_ids = json_encode($selectability_primary);
$icon_remove = icon('minus-square', false);
$icon_add_primary = icon('plus-square', false);
$icon_add_backorder = icon('plus-square-o', false);
$select_js = <<<EOT
function select_seat(id, primary) {
	if(primary === true) {
		if($('input[name=seat_primary]').val() != '')
			deselect_primary($('input[name=seat_primary]').val());
		select_primary(id);
	} else {
		select_backorder(id);
	}
}

function remove_seat(id) {
	deselect_primary(id);
	deselect_backorder(id);
}

function select_primary(id) {
	deselect_backorder(id);

	$('select[name=primary]').val(id);
	$('select[name="backorder[]"]').find('[value=' + id + ']').attr('disabled', 'disabled');
	$('input[name=seat_primary]').val(id);
	$('.selectpicker').selectpicker('refresh');

	select_text(id);
}

function deselect_primary(id) {
	$('select[name="backorder[]"]').find('[value=' + id + ']').removeAttr('disabled');
	$('.selectpicker').selectpicker('refresh');

	deselect_text(id);
}

function select_backorder(id) {
	var added = [];
	var selected = $('select[name="backorder[]"]').val();

	if(selected === null) {
		$('select[name="backorder[]"]').val(id);
	} else {
		$('select[name="backorder[]"]').selectpicker('deselectAll');
		$('select[name="backorder[]"]').val([id,selected]);
	}

	$('.selectpicker').selectpicker('refresh');

	select_text(id);
}

function deselect_backorder(id) {
	$('select[name="backorder[]"]').find('[value=' + id + ']').removeAttr('selected');
	$('.selectpicker').selectpicker('refresh');

	deselect_text(id);
}

function select_text(id) {
	$('#seat-' + id).children().eq(4).html('<a style="cursor: pointer;" onclick="remove_seat(' + id + ');">{$icon_remove}移除</a>');
}

function deselect_text(id) {
	if($.inArray(id, {$seat_primary_ids}) !== -1) {
		$('#seat-' + id).children().eq(4).html('<a style="cursor: pointer;" onclick="select_seat(' + id + ', true);">{$icon_add_primary}席位</a> <a class="select_backorder_button" style="cursor: pointer;" onclick="select_seat(' + id + ', false);">{$icon_add_backorder}候选</a>');
	} else {
		$('#seat-' + id).children().eq(4).html('<a class="select_backorder_button" style="cursor: pointer;" onclick="select_seat(' + id + ', false);">{$icon_add_backorder}候选</a>');
	}
}

function lock_backorder() {
	$('a[class=select_backorder_button]').hide();

	$('select[name="backorder[]"] option:not(:selected)').attr('disabled', 'disabled');
	$('.selectpicker').selectpicker('refresh');
}

function unlock_backorder() {
	$('a[class=select_backorder_button]').show();

	$('select[name="backorder[]"] option').removeAttr('disabled');
	$('select[name="backorder[]"]').find('[value=' + $('input[name=seat_primary]').val() + ']').attr('disabled', 'disabled');
	$('.selectpicker').selectpicker('refresh');
}	
EOT;
if($seat_mode == 'select')
	$this->ui->js('footer', $select_js);

$selectpicker_js = <<<EOT
$('.selectpicker').selectpicker({
	iconBase: 'fa',
	tickIcon: 'fa-check'
});
EOT;
$this->ui->js('footer', $selectpicker_js);

if(empty($seat))
	$this->ui->js('footer', "$('.selectpicker').selectpicker('val', null);");

$this->load->view('footer');?>