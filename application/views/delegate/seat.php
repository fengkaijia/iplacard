<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 席位视图
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
 * @package iPlacard
 * @since 2.0
 */

$option_available = array();
$option_highlight = array();
$option_html = array();
foreach($selectabilities as $selectability)
{
	$groupname = $committees[$selectability['seat']['committee']]['name'];
	
	if(in_array($selectability['seat']['id'], $selectability_available))
		$option_available[$groupname][$selectability['seat']['id']] = $selectability['seat']['name'];
	
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
							<td><?php echo $seat_mode == 'select' ? '选择时间' : '分配时间';?></td>
							<td><?php echo sprintf('%1$s（%2$s）', date('n月j日 H:i:s', $seat['time']), nicetime($seat['time']));?></td>
						</tr>
						<tr>
							<td>状态</td>
							<td><?php echo $delegate['status'] == 'locked' ? '<span class="text-success">已经锁定</span>' : '<span class="text-primary">已经保留</span>';?></td>
						</tr>
					</tbody>
				</table>
				
				<?php if(!empty($attached_seats)) { ?><p>当前席位为多代席位，您可以查看与您共同代表该席位的代表信息。</p>
				<a id="seat_change_start" href="#attach" data-toggle="tab" class="btn btn-primary" onclick="$('.nav-menu li').removeClass('active'); $('#attach_tab').addClass('active');">查看席位多代信息</a><?php
				} ?>
			</div>
			
			<div class="col-md-4">
				<?php if($seat_mode == 'select') { ?><h3>更换席位</h3>
				<p>您当前已经选择席位，但您的面试官稍后仍可能会向您提供更多席位供选择。在您锁定现有席位前，您可以随时更换您的席位；锁定席位后，您将无法更换您的席位。</p>
				<?php
				if(!$change_open)
				{
					$this->ui->js('footer', "$('#seat_change_lock').popover();");
					if($delegate['status'] == 'locked') { ?><a id="seat_change_lock" data-original-title="无法选择新的席位" href="#" class="btn btn-primary" data-toggle="popover" data-placement="right" data-content="您已锁定现有席位，无法更换席位。" title="">更换席位</a><?php }
				} else { ?><a id="seat_change_start" href="#select" data-toggle="tab" class="btn btn-primary" onclick="$('.nav-menu li').removeClass('active'); $('#select_tab').addClass('active');">更换席位</a><?php } ?>
				
				<hr /><?php } else { ?><h3>更换席位分配</h3>
				<p>当前您已经分配有席位。如果对席位分配结果不满意，您可以在您锁定现有席位前随时联系您的面试官（或学术团队成员）调整席位。锁定席位后，您的面试官将无法为您更换席位。</p>
				
				<hr /><?php } ?>
				
				<?php if($lock_open) { ?><h3>确认锁定席位</h3>
				<p>现可以确认申请完成并锁定您的席位，锁定后您的席位将不可被调整。</p>
				<a class="btn btn-primary" href="#" data-toggle="modal" data-target="#lock">立即锁定席位</a><?php $this->load->view('delegate/panel/lock'); } ?>
			</div>
		</div><?php } ?>
		
		<?php if(!empty($attached_seats)) { ?>
		<div class="tab-pane" id="attach">
			<div class="col-md-8">
				<h3><?php echo $attached_double ? '全部席位' : '主席位';?></h3>
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
						</tr><?php if(!empty($profile_extra))
						{
							foreach($profile_extra as $profile_item => $profile_title) { ?><tr>
							<td><?php echo $profile_title;?></td>
							<td><?php echo $attached_primary['delegate'][$profile_item];?></td>
						</tr><?php } } } else { ?><tr>
							<td>状态</td>
							<td>席位尚未被分配</td>
						</tr><?php } ?>
					</tbody>
				</table>
				
				<?php if(!$attached_double) { ?><hr />
				
				<h3>子席位</h3><?php } else {?><br /><?php } ?>
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
						</tr><?php if(!empty($profile_extra))
						{
							foreach($profile_extra as $profile_item => $profile_title) { ?><tr>
							<td><?php echo $profile_title;?></td>
							<td><?php echo $attached_seat['delegate'][$profile_item];?></td>
						</tr><?php } } } else { ?><tr>
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
				<p>您的席位为多代席位，本页显示与您合作代表该席位的代表。</p>
				<p>请注意通常情况下席位为常见的双代席位，部分情况下，例如正副代表席位，系统将标识主席位和子席位。部分尚无代表的席位将会提示尚未分配，多代名单将随着录取和席位滚动随时变化。</p>
				<p>我们建议您使用提供的电子邮箱地址和其他信息联系您的合作代表。</p>
			</div>
		</div><?php } ?>
		
		<?php if($seat_mode == 'select') { ?><div class="tab-pane<?php echo empty($seat) ? ' active' : '';?>" id="select">
			<div class="col-md-8">
				<h3>席位分配</h3>
				<p>我们已经向您分配了 <?php echo $selectability_count;?> 个席位，其中包括了 <?php echo $selectability_available_count;?> 个当前可选席位和 <?php echo $selectability_count - $selectability_available_count;?> 个当前不可选席位。不可选席位指虽然面试官开放了此席位选项，但已经被其他代表选中的席位，如果该代表后续因故退会或更换席位时，此席位将再次可选。</p>
				<p>面试官推荐的席位已被加粗显示。如果您对以下席位感到不满意或不适合，您可以与您的面试官<?php echo anchor('apply/interview', '联系');?>，他将可以向您提供更多席位供选择。</p>

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
								echo in_array($one_seat['id'], $selectability_available) ? '<span class="text-primary">可选席位</span>' : '<span class="text-muted">不可选席位</span>';
							else
								echo '<span class="text-success">已选席位</span>';?></td>
							<td><?php printf('%1$s（%2$s）', date('n月j日', $selectability['time']), nicetime($selectability['time']));?></td>
							<td><?php
							if($selectability['seat']['delegate'] == $delegate['id'])
								echo '<a style="cursor: pointer;" onclick="remove_seat('.$one_seat['id'].');">'.icon('minus-square', false).'取消</a>';
							elseif(in_array($one_seat['id'], $selectability_available))
								echo '<a style="cursor: pointer;" onclick="select_seat('.$one_seat['id'].');">'.icon('plus-square', false).'选择</a>';?></td>
						</tr><?php } ?>
					</tbody>
				</table>
			</div>

			<div class="col-md-4">
				<h3>选择席位</h3>
				<div id="pre_select">
					<?php
					if(option("invoice_amount_{$delegate['application_type']}", 0) > 0) { ?><p>提交席位选择后，您选择的席位将被临时保留，其他人无法继续选择其为参会席位。同时我们将为您生成会费账单，您将可以在 <?php echo option('invoice_due_fee', 15);?> 天内完成会费支付，支付完成后您的席位将被永久保留，直到您锁定席位前，您都可以随时调整席位。如果未能在 <?php echo option('invoice_due_fee', 15);?> 天内完成会费支付，您的席位将被自动释放给其他代表选择，您可以在完成会费支付后继续选择此席位或者其他席位。</p><?php }
					else { ?><p>提交席位选择后，您选择的席位将被临时保留，其他人无法继续选择其为参会席位，直到您锁定席位前，您都可以随时调整席位。</p><?php } ?>
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
					echo form_open_multipart('apply/seat/select', array('id' => 'seat_form'), array('seat_selected' => empty($seat) ? '' : $seat['id']));?>
						<p>请在下方下拉框中选择您的参会席位。完成选择后请点击提交席位选择按钮。</p>

						<div class="form-group <?php if(form_has_error('seat')) echo 'has-error';?>">
							<?php echo form_label('席位', 'seat', array('class' => 'control-label'));?>
							<div>
								<?php echo form_dropdown_select('seat', $option_available, empty($seat) ? array() : $seat['id'], $selectability_available_count > 10 ? true : false, $option_highlight, array(), $option_html, array(), 'selectpicker flags-16', 'data-width="100%" title="选择席位"');
								if(form_has_error('seat'))
									echo form_error('seat');
								?>
							</div>
							<?php $this->ui->js('footer', "$('select[name=\"seat\"]').change(function () {
								if($('select[name=\"seat\"]').val() != null) {
									select_seat($('select[name=\"seat\"]').val());
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

$seat_available_ids = json_encode(array());
if($selectability_available)
	$seat_available_ids = json_encode($selectability_available);
$icon_remove = icon('minus-square', false);
$icon_add = icon('plus-square', false);
$select_js = <<<EOT
function select_seat(id) {
	if($('input[name=seat_selected]').val() != '')
		remove_seat($('input[name=seat_selected]').val());
	
	$('select[name=seat]').val(id);
	$('input[name=seat_selected]').val(id);
	$('.selectpicker').selectpicker('refresh');

	$('#seat-' + id).children().eq(4).html('<a style="cursor: pointer;" onclick="remove_seat(' + id + ');">{$icon_remove}取消</a>');
}

function remove_seat(id) {
	$('select[name=seat]').val(null);
	$('input[name=seat_selected]').val('');
	$('.selectpicker').selectpicker('refresh');

	$('#seat-' + id).children().eq(4).html('<a style="cursor: pointer;" onclick="select_seat(' + id + ');">{$icon_add}选择</a>');
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