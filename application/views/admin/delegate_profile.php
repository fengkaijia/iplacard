<?php
$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/flags.css' : 'static/css/flags.min.css').'" rel="stylesheet">');
$this->load->view('header');?>

<div class="page-header">
	<div class="row">
		<div class="col-md-8">
			<h1><?php echo $profile['name'];?></h1>
		</div>
		<?php $this->ui->js('footer', 'nav_menu_top();
		$(window).resize(function($){
			nav_menu_top();
		});');?>
		<div class="col-md-4 menu-tabs">
			<ul class="nav nav-tabs nav-menu">
				<li class="active"><a href="#application" data-toggle="tab">个人信息</a></li>
				<li><a href="#interview" data-toggle="tab">面试审核</a></li>
				<?php if($profile['application_type'] == 'delegate' && $profile['status_code'] >= $this->delegate_model->status_code('interview_completed')) { ?><li><a href="<?php echo base_url("seat/assign/$uid");?>">席位分配</a></li><?php } ?>
			</ul>
		</div>
	</div>
</div>

<div class="menu-pills">
	
</div>

<div class="row">
	<div class="col-md-8">
		<div class="tab-content">
			<div class="tab-pane active" id="application">
				<h3>个人信息</h3>
				<table class="table table-bordered table-striped table-hover">
					<tbody>
						<?php $rules = array(
							'name' => '姓名',
							'email' => '电子邮箱地址',
							'phone' => '电话号码',
							'application_type_text' => '申请类型',
							'status_text' => '申请状态',
						) + option('profile_list_general', array()) + option("profile_list_{$profile['application_type']}", array());
						foreach($rules as $rule => $text) { ?><tr>
							<td><?php echo $text;?></td>
							<td><?php if(!empty($profile[$rule])) echo $profile[$rule];?></td>
						</tr><?php } ?>
					</tbody>
				</table>

				<h3>团队信息</h3>
				<?php if($group) { ?><table class="table table-bordered table-striped table-hover">
					<tbody>
						<tr>
							<th>代表团</th>
							<th>人数</th>
							<th>领队</th>
						</tr>
						<tr>
							<td><?php echo anchor("delegate/manage/group={$group['id']}", $group['name']);?></td>
							<td><?php echo $group['count'];?></td>
							<td><?php if($head_delegate) { ?><span class="label label-primary">此代表是领队</span><?php }
							elseif(!$group['head_delegate']) { ?><span class="label label-warning">该团队暂无领队</span><?php }
							else echo anchor("delegate/profile/{$group['head_delegate']['id']}", icon('user', false).$group['head_delegate']['name']);?></td>
						</tr>
					</tbody>
				</table>
				<a data-toggle="modal" data-target="#group_edit" class="btn btn-primary">调整团队</a>
				<a data-toggle="modal" data-target="#group_remove" class="btn btn-warning">取消团队</a>
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
				<a data-toggle="modal" data-target="#group_edit" class="btn btn-primary"><?php echo icon('retweet');?>转换为团队代表</a><?php } ?>
				
				<hr />
				
				<?php if(!empty($profile['experience'])) { ?><h3>参会经历</h3>
				<table class="table table-bordered table-striped table-hover">
					<thead>
						<?php $rules = option('profile_list_experience');
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
						<?php $rules = option('profile_list_club');
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
						<?php $questions = option('profile_list_test');
						foreach($questions as $qid => $question) { ?>
						<tr><td><?php echo $question;?></td></tr>
						<tr><td><?php echo nl2br($profile['test'][$qid]);?></td></tr><?php } ?>
					</tbody>
				</table><?php } ?>
			</div>
			
			<div class="tab-pane" id="interview">
				<?php if(isset($seat) && !empty($seat)) { ?>
				<h3>席位分配</h3>
				<table class="table table-bordered table-striped table-hover flags-16">
					<tbody>
						<tr>
							<td>席位名称</td>
							<td><?php echo flag($seat['iso']).$seat['name'];?></td>
						</tr>
						<tr>
							<td>委员会</td>
							<td><?php echo "{$committee['name']}（{$committee['abbr']}）";?></td>
						</tr>
						<tr>
							<td>分配时间</td>
							<td><?php echo sprintf('%1$s（%2$s）', date('n月j日 H:i:s', $seat['time']), nicetime($seat['time']));?></td>
						</tr>
						<tr>
							<td>状态</td>
							<td><?php echo $profile['status_code'] > $this->delegate_model->status_code('seat_assigned') ? '已经确认锁定' : '尚未确认锁定';?></td>
						</tr>
					</tbody>
				</table>
				
				<hr /><?php } ?>
				
				<?php if(!empty($interviews)) {
				foreach($interviews as $interview) { ?><h3><?php echo $interview['id'] == $current_interview ? '当前面试信息' : '早前面试信息';?></h3>
				<table class="table table-bordered table-striped table-hover">
					<tbody>
						<tr>
							<td>状态</td>
							<td><?php echo $interview['status_text'];?></td>
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
							<td>完成时间</td>
							<td><?php echo sprintf('%1$s（%2$s）', date('n月j日 H:i:s', $interview['finish_time']), nicetime($interview['finish_time']));?></td>
						</tr><?php }
						if(!empty($interview['score'])) { ?><tr>
							<td>面试总分</td>
							<td><?php echo round($interview['score'], 2);?></td>
						</tr><?php }
						if(!empty($interview['feedback'])) { ?><tr>
							<td>面试反馈</td>
							<td><?php echo $interview['feedback'];?></td>
						</tr><?php } ?>
					</tbody>
				</table>
				<hr />
				<?php } } ?>

				<h3>事件日志</h3>
				<?php //TODO ?>
			</div>
		</div>
	</div>
	
	<div class="col-md-4">
		<div id="operation_bar">
			<h3 id="operation">操作</h3>
			<div id="operation_area">
				操作载入中……
			</div>
			
			<hr />
			
			<h3 id="note">笔记</h3>
			<div id="note_area">
				操作载入中……
			</div>
		</div>
	</div>
</div>

<?php
$ajax_url = base_url("delegate/ajax/sidebar?id=$uid");
$operation_js = <<<EOT
$.ajax({
	url: "$ajax_url",
	dataType : "json",
	success : function( sidebar ){
		$("#operation_area").html( sidebar.html );
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