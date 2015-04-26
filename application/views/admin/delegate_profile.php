<?php
$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/bootstrap.datatables.css' : 'static/css/bootstrap.datatables.min.css').'" rel="stylesheet">');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/jquery.datatables.js' : 'static/js/jquery.datatables.min.js').'"></script>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/locales/jquery.datatables.locale.js' : 'static/js/locales/jquery.datatables.locale.min.js').'"></script>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/bootstrap.datatables.js' : 'static/js/bootstrap.datatables.min.js').'"></script>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/jquery.shorten.js' : 'static/js/jquery.shorten.min.js').'"></script>');
$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/flags.css' : 'static/css/flags.min.css').'" rel="stylesheet">');

if($profile_editable)
{
	$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/bootstrap.editable.css' : 'static/css/bootstrap.editable.min.css').'" rel="stylesheet">');
	$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/bootstrap.editable.js' : 'static/js/bootstrap.editable.min.js').'"></script>');
}

$this->load->view('header');?>

<div class="page-header">
	<div class="row">
		<div class="col-md-8">
			<h1 style="position: relative;">
				<a class="thumbnail" style="width: 50px; height: 50px; position: absolute; margin-top: -2px;">
					<?php echo avatar($profile['id'], 40, 'img');?>
				</a>
				<span style="margin-left: 58px;"><?php echo $profile['name'];?></span>
			</h1>
		</div>
		<?php $this->ui->js('footer', 'nav_menu_top();
		$(window).resize(function($){
			nav_menu_top();
		});');?>
		<div class="col-md-4 menu-tabs">
			<ul class="nav nav-tabs nav-menu">
				<li class="active"><a href="#application" data-toggle="tab">个人信息</a></li>
				<li><a href="#interview" data-toggle="tab">面试审核</a></li>
				<?php if($seat_open) { ?><li id="seat_tab"><a href="#seat" data-toggle="tab">席位分配</a></li><?php } ?>
			</ul>
		</div>
	</div>
</div>

<div class="menu-pills"></div>

<div class="row">
	<div class="col-md-8">
		<div class="tab-content">
			<div class="tab-pane active" id="application">
				<h3>个人信息</h3>
				<table class="table table-bordered table-striped table-hover">
					<tbody>
						<?php
						$rules_text = option('profile_list_general', array()) + option("profile_list_{$profile['application_type']}", array());
						$rules = array(
							'name' => '姓名',
							'id' => '用户 ID',
							'email' => '电子邮箱地址',
							'phone' => '电话号码',
							'application_type_text' => '申请类型',
							'status_text' => '申请状态',
						) + $rules_text;
						foreach($rules as $rule => $text) { ?><tr>
							<td><?php echo $text;?></td>
							<td><?php if($rule == 'name' && $profile['pinyin'])
							{
								echo "<span class='delegate_name' data-original-title='{$profile['pinyin']}' data-toggle='tooltip'>{$profile['name']}</span>";
							}
							else
							{
								$profile_text = empty($profile[$rule]) ? '' : $profile[$rule];
								
								if(in_array($rule, array_keys($rules_text)))
									echo "<span class='profile_editable' data-name='{$rule}' data-title='编辑{$text}'>$profile_text</span>";
								else
									echo $profile_text;
							} ?></td>
						</tr><?php }
						$this->ui->js('footer', "$('.delegate_name').tooltip();");
						?>
					</tbody>
				</table>

				<?php if($groups) { ?><h3>团队信息</h3>
				<?php if($group) { ?><table class="table table-bordered table-striped table-hover">
					<tbody>
						<tr>
							<th>代表团</th>
							<th>人数</th>
							<th>领队</th>
						</tr>
						<tr>
							<td><?php echo anchor("delegate/manage/?group={$group['id']}", $group['name']);?></td>
							<td><?php echo $group['count'];?></td>
							<td><?php if($head_delegate) { ?><span class="label label-primary">此代表是领队</span><?php }
							elseif(!$group['head_delegate']) { ?><span class="label label-warning">该团队暂无领队</span><?php }
							else echo anchor("delegate/profile/{$group['head_delegate']['id']}", icon('user', false).$group['head_delegate']['name']);?></td>
						</tr>
					</tbody>
				</table>
				
				<div class="btn-group">
					<button type="button" data-toggle="modal" data-target="#group_edit" onclick="$('input[name=head_delegate]').attr('checked', <?php echo set_value('group', $head_delegate ? true : false) ? 'true' : 'false';?>); $('select[name=group]').removeAttr('disabled');" class="btn btn-primary"><?php echo icon('retweet');?>调整团队</button>
					<button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
					<ul class="dropdown-menu">
						<?php if(!$head_delegate) { ?><li><a data-toggle="modal" data-target="#group_edit" onclick="$('input[name=head_delegate]').attr('checked', true); $('select[name=group]').attr('disabled', true);">设为团队领队</a></li><?php }
						else { ?><li><a data-toggle="modal" data-target="#group_edit" onclick="$('input[name=head_delegate]').attr('checked', false); $('select[name=group]').attr('disabled', true);">取消领队属性</a></li><?php } ?>
						<li><a data-toggle="modal" data-target="#group_remove">取消团队</a></li>
					</ul>
                </div>
				
				<?php echo form_open("delegate/group/remove/{$profile['id']}", array(
					'class' => 'modal fade form-horizontal',
					'id' => 'group_remove',
					'tabindex' => '-1',
					'role' => 'dialog',
					'aria-labelledby' => 'remove_label',
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
								<h4 class="modal-title" id="remove_label">转换为个人代表</h4>
							</div>
							<div class="modal-body">
								<p>将会转换<?php echo icon('user', false).$profile['name'];?>为个人代表。</p>
								
								<div class="form-group <?php if(form_has_error('confirm')) echo 'has-error';?>">
									<?php echo form_label('确认转换', 'confirm', array('class' => 'col-lg-3 control-label'));?>
									<div class="col-lg-9">
										<div class="checkbox">
											<label>
												<?php echo form_checkbox(array(
													'name' => 'confirm',
													'id' => 'confirm',
													'value' => true,
													'checked' => false,
												)); ?> 确认转换为个人代表
											</label>
										</div>
										<?php echo form_error('confirm');?>
									</div>
								</div>
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
									'content' => '确认转换',
									'type' => 'submit',
									'class' => 'btn btn-primary',
									'onclick' => 'loader(this);'
								)); ?>
							</div>
						</div>
					</div>
				<?php echo form_close(); } else { ?><p>此申请者为个人申请代表，不属于任何团队。</p>
				<a data-toggle="modal" data-target="#group_edit" class="btn btn-primary"><?php echo icon('retweet');?>转换为团队代表</a><?php }
				echo form_open("delegate/group/edit/{$profile['id']}", array(
					'class' => 'modal fade form-horizontal',
					'id' => 'group_edit',
					'tabindex' => '-1',
					'role' => 'dialog',
					'aria-labelledby' => 'edit_label',
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
								<h4 class="modal-title" id="edit_label"><?php echo $group ? '调整团队' : '加入团队';?></h4>
							</div>
							<div class="modal-body">
								<p>将会调整<?php echo icon('user', false).$profile['name'];?>的团队属性，调整完成后将会通知代表。</p>
								
								<div class="form-group <?php if(form_has_error('group')) echo 'has-error';?>">
									<?php echo form_label('所属团队', 'group', array('class' => 'col-lg-3 control-label'));?>
									<div class="col-lg-5">
										<?php echo form_dropdown('group', array('' => '选择代表团...') + $groups, set_value('group', $group ? $group['id'] : ''), 'class="form-control" id="committee"');
										if(form_has_error('group'))
											echo form_error('group');
										else { ?><div class="help-block">代表所属的代表团。</div><?php } ?>
									</div>
								</div>
								
								<div class="form-group <?php if(form_has_error('head_delegate')) echo 'has-error';?>">
									<?php echo form_label('设为领队', 'head_delegate', array('class' => 'col-lg-3 control-label'));?>
									<div class="col-lg-9">
										<div class="checkbox">
											<label>
												<?php echo form_checkbox(array(
													'name' => 'head_delegate',
													'id' => 'head_delegate',
													'value' => true,
													'checked' => set_value('group', $head_delegate ? true : false),
												)); ?> 设置此代表为团队领队
											</label>
										</div>
										<?php if(form_has_error('head_delegate'))
											echo form_error('head_delegate');
										else { ?><div class="help-block">如设置的代表团已存在领队，此操作仍然有效。</div><?php } ?>
									</div>
								</div>
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
									'content' => '确认提交',
									'type' => 'submit',
									'class' => 'btn btn-primary',
									'onclick' => '$(\'select[name=group]\').removeAttr(\'disabled\'); loader(this);'
								)); ?>
							</div>
						</div>
					</div>
				<?php echo form_close(); } ?>
				
				<?php if(!empty($profile['experience'])) { ?><h3>参会经历</h3>
				<table class="table table-bordered table-striped table-hover">
					<thead>
						<?php $rules = option('profile_list_experience', array());
						foreach($rules as $rule => $text) { ?><th><?php echo $text;?></th><?php } ?>
					</thead>
					<tbody>
						<?php foreach($profile['experience'] as $experience) { ?>
						<tr><?php foreach($rules as $rule => $text) { ?>
							<td><?php echo $experience[$rule];?></td><?php } ?>
						</tr><?php } ?>
					</tbody>
				</table><?php } ?>

				<?php if(!empty($profile['club'])) { ?><h3>社会活动</h3>
				<table class="table table-bordered table-striped table-hover">
					<thead>
						<?php $rules = option('profile_list_club', array());
						foreach($rules as $rule => $text) { ?><th><?php echo $text;?></th><?php } ?>
					</thead>
					<tbody>
						<?php foreach($profile['club'] as $experience) { ?>
						<tr><?php foreach($rules as $rule => $text) { ?>
							<td><?php echo $experience[$rule];?></td><?php } ?>
						</tr><?php } ?>
					</tbody>
				</table><?php } ?>

				<?php if(!empty($profile['test'])) { ?><h3 id="test">学术测试</h3>
				<table class="table table-bordered table-striped table-hover">
					<tbody>
						<?php
						$show_empty = option('profile_show_test_all', false);
						$questions = option('profile_list_test');
						foreach($questions as $qid => $question)
						{
							if($show_empty || !empty($profile['test'][$qid])) { ?>
							<tr><td><?php echo $question;?></td></tr>
							<tr><td><?php echo nl2br($profile['test'][$qid]);?></td></tr><?php }
						} ?>
					</tbody>
				</table><?php } ?>
			</div>
			
			<div class="tab-pane" id="interview">
				<?php if(!empty($interviews)) {
				foreach($interviews as $interview) { ?><h3><?php echo $interview['id'] == $current_interview ? '当前面试信息' : '早前面试信息';?></h3>
				<table class="table table-bordered table-striped table-hover">
					<tbody>
						<tr>
							<td>状态</td>
							<td class="text-<?php echo $interview['status_class'];?>"><?php echo $interview['status_text'];?></td>
						</tr>
						<tr>
							<td>指派面试官</td>
							<td><?php echo anchor("user/edit/{$interview['interviewer']['id']}", icon('user').$interview['interviewer']['name']);
							if(!empty($interview['interviewer']['committee']))
								echo "（{$interview['interviewer']['committee']['name']}）";?></td>
						</tr>
						<tr>
							<td>指派时间</td>
							<td><?php echo sprintf('%1$s（%2$s）', date('n月j日 H:i:s', $interview['assign_time']), nicetime($interview['assign_time']));?></td>
						</tr>
						<?php if(!empty($interview['schedule_time'])) { ?><tr>
							<td>安排时间</td>
							<td><?php echo sprintf('%1$s（%2$s）', date('n月j日 H:i:s', $interview['schedule_time']), nicetime($interview['schedule_time']));?></td>
						</tr><?php }
						if(!empty($interview['finish_time'])) { ?><tr>
							<td><?php echo $interview['status'] == 'cancelled' ? '取消时间' : '完成时间';?></td>
							<td><?php echo sprintf('%1$s（%2$s）', date('n月j日 H:i:s', $interview['finish_time']), nicetime($interview['finish_time']));?></td>
						</tr><?php }
						if(!empty($interview['score'])) { ?><tr>
							<td>面试总分</td>
							<td><strong><?php echo round($interview['score'], 2);?></strong></td>
						</tr><?php }
						if(!empty($interview['feedback']['score'])) { ?><tr>
							<td>详细评分</td>
							<td><?php foreach(option('interview_score_standard', array('score' => array('name' => '总分'))) as $sid => $one)
							{
								$score = isset($interview['feedback']['score'][$sid]) && !is_null($interview['feedback']['score'][$sid]) ? $interview['feedback']['score'][$sid] : 'N/A';
								echo "<span class=\"label label-primary\">{$one['name']}</span> {$score} ";
							} ?></td>
						</tr><?php }
						if(!empty($interview['feedback'])) { ?><tr>
							<td style="min-width: 100px;">面试反馈</td>
							<td><?php echo $interview['feedback']['feedback'];?></td>
						</tr><?php } ?>
					</tbody>
				</table>
				<hr />
				<?php } } ?>

				<?php if(!empty($events)) { ?><h3>事件日志</h3>
				<div>
					<ul class="timeline timeline-small">
						<?php foreach($events as $event) { ?><li>
							<div class="timeline-badge <?php echo $event['class'];?>"><?php echo !empty($event['icon']) ? icon($event['icon'], false) : '';?></div>
							<div class="timeline-panel">
								<div class="timeline-heading">
									<h4 class="timeline-title<?php if(empty($event['text'])) echo ' nobody';?>"><?php echo $event['title'];?> <small class="text-muted"><?php echo icon('clock-o');
									printf('%1$s（%2$s）', date('n月j日 H:i:s', $event['time']), nicetime($event['time']));?></small></h4>
								</div>
								<?php if(!empty($event['text'])) { ?><div class="timeline-body">
									<?php echo $event['text'];?>
								</div><?php } ?>
							</div>
						</li><?php } ?>
					</ul>
				</div>
			</div><?php } ?>
			
			<?php if($seat_open) { ?><div class="tab-pane" id="seat">
				<?php if($seat_mode == 'select' ? $selectabilities : $seat) { ?><div id="seat_now">
					<?php if(!empty($seat)) { ?>
					<h3><?php echo $seat_mode == 'select' ? '已选择席位' : '已分配席位';?></h3>
					<p><?php echo icon('user', false).$profile['name'];?>代表当前席位如下。</p>
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
								<td><?php echo $profile['status'] == 'locked' ? '<span class="text-success">已经锁定</span>' : '<span class="text-primary">尚未锁定</span>';?></td>
							</tr>
						</tbody>
					</table>
					
					<hr /><?php } ?>
					
					<?php if(!empty($backorders)) { ?>
					<h3>席位候选</h3>
					<p><?php echo icon('user', false).$profile['name'];?>代表当前候选了以下席位，在候选窗口关闭之前，他都有可能调整为以下席位。</p>
					
					<table id="backorder_list" class="table table-striped table-bordered table-hover table-responsive flags-16">
						<thead>
							<tr>
								<th>ID</th>
								<th>席位名称</th>
								<th>委员会</th>
								<th>候选时间</th>
							</tr>
						</thead>

						<tbody>
							<?php foreach($backorders as $backorder) { ?><tr id="backorder-<?php echo $backorder['id'];?>">
								<td><?php echo $backorder['seat']['id'];?></td>
								<td><?php echo flag($backorder['seat']['iso'], true).$backorder['seat']['name'];?></td>
								<td><?php echo $backorder['seat']['committee']['abbr'];?></td>
								<td><?php echo sprintf('%1$s（%2$s）', date('n月j日', $backorder['order_time']), nicetime($backorder['order_time']));?></td>
							</tr><?php } ?>
						</tbody>
					</table>
					
					<hr /><?php } ?>
					
					<?php if($seat_mode == 'select' && $selectabilities) { ?><h3>开放席位分配</h3>
					<p>以下席位权限已经开放给<?php echo icon('user', false).$profile['name'];?>代表，代表可以在其中选择 1 个为其主席位，同时他还可以选择 <?php echo option('seat_backorder_max', 2);?> 个候选席位。</p>
					<table id="selectability_list" class="table table-striped table-bordered table-hover table-responsive flags-16">
						<thead>
							<tr>
								<th>ID</th>
								<th>席位名称</th>
								<th>委员会</th>
								<th>等级</th>
								<th>主项</th>
								<th>推荐</th>
							</tr>
						</thead>

						<tbody>

						</tbody>
					</table>
					
					<?php if($seat_assignable) { ?><p>如需向代表开放分配更多席位选择权限，请点击增加席位分配。</p>
					<p><a class="btn btn-primary" onclick="open_seat();"><?php echo icon('plus');?>增加席位分配</a></p><?php } }
					
					elseif($seat_assignable) { ?><h3>更改席位分配</h3>
					<p>如需更换代表的席位，请点击更改席位分配。</p>
					<p><a class="btn btn-primary" onclick="open_seat();"><?php echo icon('pencil');?>更改席位分配</a></p><?php }?>
				</div><?php } ?>
				
				<?php
				if($seat_assignable)
				{
					$this->ui->js('footer', "
							jQuery(function($){
								$('#seat_add').hide();
							});
						"); ?><div id="seat_add">
					<h3>分配席位</h3>
					<p><?php if($seat_mode == 'select') { ?>将会向<?php echo icon('user', false).$profile['name'];?>代表分配席位选择权限。之后，代表将可以在其中选择 1 个为其主席位，同时他还可以选择 <?php echo option('seat_backorder_max', 2);?> 个候选席位。<?php }
					else { ?>将会向<?php echo icon('user', false).$profile['name'];?>代表分配席位。<?php } ?></p>
					<table id="seat_list" class="table table-striped table-bordered table-hover table-responsive flags-16">
						<thead>
							<tr>
								<th>ID</th>
								<th>席位名称</th>
								<th>委员会</th>
								<th>席位状态</th>
								<th>等级</th>
								<th>分配代表</th>
								<th>分配情况</th>
								<th>操作</th>
							</tr>
						</thead>

						<tbody>

						</tbody>
					</table>
					
					<?php if($selectabilities || $seat_mode == 'assign') { ?><p><a class="btn btn-primary" onclick="$('#seat_now').show(); $('#seat_add').hide();"><?php echo icon('th-list');?>返回已分配席位</a></p><?php } ?>
				</div><?php } ?>
			</div><?php } ?>
		</div>
	</div>
	
	<div class="col-md-4">
		<div id="operation_bar">
			<h3 id="operation">操作</h3>
			<div id="operation_area">
				操作载入中……
			</div>
			
			<hr />
		</div>
		
		<div id="note_bar">
			<h3 id="note">笔记</h3>
			<div id="note_area">
				操作载入中……
			</div>
		</div>
	</div>
</div>

<?php
$read_more = icon('caret-right', false);
$read_less = icon('caret-left', false);

$selectability_url = base_url("seat/ajax/list_selectability?delegate=$uid");
$selectability_js = <<<EOT
$(document).ready(function() {
	$('#selectability_list').dataTable( {
		"aoColumnDefs": [
			{ "bSortable": false, "aTargets": [ 0 ] }
		],
		"bProcessing": true,
		"bAutoWidth": false,
		"sAjaxSource": '{$selectability_url}',
		"sDom": "<'row'r>t<'col-xs-8 col-xs-offset-4'p>>",
		"fnDrawCallback": function() {
			$('.shorten-select').shorten({
				showChars: '25',
				moreText: '{$read_more}',
				lessText: '{$read_less}'
			});
		}
	} );
} );
EOT;
if($seat_mode == 'select' && $selectabilities)
	$this->ui->js('footer', $selectability_js);

$seat_opened_ids = json_encode(array());
if($seat_mode == 'assign' && $seat)
	$seat_opened_ids = json_encode(array($seat['id']));
elseif($selectabilities)
	$seat_opened_ids = json_encode($selectabilities);
$seat_opened_text = $seat_mode == 'select' ? '已经开放' : '已经分配';
$seat_hide_column = $seat_mode == 'select' ? '5' : '5, 6';
$seat_url = base_url('seat/ajax/list?operation=assign');
$seat_js = <<<EOT
$(document).ready(function() {
	$('#seat_list').dataTable( {
		"aoColumnDefs": [
			{ "bSortable": false, "aTargets": [ 0, 7 ] },
			{ "bVisible": false, "aTargets": [ {$seat_hide_column} ] }
		],
		"bProcessing": true,
		"bAutoWidth": false,
		"sAjaxSource": '{$seat_url}',
		"sDom": "<'row'<'col-xs-6'l><'col-xs-6'f>r>t<'row'<'col-xs-4'i><'col-xs-8'p>>",
		"fnRowCallback": function( nRow, aData, iDisplayIndex, iDisplayIndexFull ) {
			$(nRow).attr("id", 'seat-' + aData[0]);
			if($.inArray(aData[0], {$seat_opened_ids}) !== -1) {
				$(nRow).children().eq(5).html('<p class="text-success">{$seat_opened_text}</p>');
			}
		},
		/* TODO
		"fnDrawCallback": function() {
			$('.shorten').shorten({
				showChars: '25',
				moreText: '{$read_more}',
				lessText: '{$read_less}'
			});
		}*/
	} );
} );
EOT;
if($seat_assignable)
	$this->ui->js('footer', $seat_js);

$edit_csrf_hash = $this->security->get_csrf_hash();
$edit_csrf_token = $this->security->get_csrf_token_name();
$edit_icon_ok = icon('check', false);
$edit_icon_remove = icon('times', false);
$edit_url = base_url("delegate/ajax/profile_edit?id=$uid");
$edit_js = <<<EOT
$.fn.editableform.buttons = '<button type="submit" class="btn btn-primary btn-sm editable-submit">{$edit_icon_ok}</button><button type="button" class="btn btn-default btn-sm editable-cancel">{$edit_icon_remove}</button>';

$('.profile_editable').editable({
	pk: $uid,
	disabled: true,
	type: 'text',
	url: "$edit_url",
	mode: 'popup',
	emptytext: '空',
	params: {
		$edit_csrf_token: '$edit_csrf_hash'
	}
});
EOT;
if($profile_editable)
	$this->ui->js('footer', $edit_js);

$ajax_url = base_url("delegate/ajax/sidebar?id=$uid");
$operation_js = <<<EOT
$.ajax({
	url: "$ajax_url",
	dataType : "json",
	success : function( sidebar ){
		$("#operation_bar").html( sidebar.html );
	}
});
EOT;
$this->ui->js('footer', $operation_js);

$note_url = base_url("delegate/ajax/note?id=$uid");
$note_js = <<<EOT
$.ajax({
	url: "$note_url",
	dataType : "json",
	success : function( note ){
		$("#note_area").html( note.html );
	}
});
EOT;
$this->ui->js('footer', $note_js);

$this->load->view('footer');?>