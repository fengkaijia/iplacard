<?php $this->load->view('header');?>

<?php if($welcome) { ?><div id="welcome" class="jumbotron">
	<h1 style="font-size: 56px;">欢迎使用 iPlacard</h1>
	<p>全新的下一代模拟联合国会议管理系统已经为您准备就绪，立即开始使用。</p>
	<div class="form-inline">
		<div class="form-group">
			<a class="btn btn-primary btn-lg" onclick="dismiss_welcome();">开始使用</a>
		</div>
		<div class="checkbox" style="padding-left: 10px;">
			<label>
				<input id="remember_dismiss" type="checkbox" checked> 不再显示此欢迎提示
			</label>
		</div>
	</div>
	<p></p>
	<?php
	$welcome_dismiss_url = base_url('admin/ajax/dismiss_welcome');
	$welcome_js = "function dismiss_welcome()
	{
		if($('#remember_dismiss').is(':checked')) {
			$.ajax({
				url: '{$welcome_dismiss_url}',
				async: true,
				dataType : 'json'
			});
		}

		$('#welcome').hide();
	}";
	$this->ui->js('footer', $welcome_js);
	?>
</div><?php } ?>

<div class="row">
	<div class="col-md-6">
		<div id="ui-dashboard" class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title"><?php echo icon('dashboard');?>控制板</h3>
			</div>
			<div class="panel-body">
				<div class="row">
					<div class="col-md-6">
						<p><?php echo anchor_capable('delegate/manage?type=delegate', icon('user')."<strong>{$count['delegate']}</strong> 参会代表", 'administrator');?></p>
						<p><?php echo anchor_capable('delegate/manage?type=observer', icon('user')."<strong>{$count['observer']}</strong> 观察员", 'administrator');?></p>
						<p><?php echo anchor_capable('delegate/manage?type=volunteer', icon('user')."<strong>{$count['volunteer']}</strong> 志愿者", 'administrator');?></p>
						<p><?php echo anchor_capable('delegate/manage?type=teacher', icon('user')."<strong>{$count['teacher']}</strong> 指导老师", 'administrator');?></p>
					</div>
					<div class="col-md-6">
						<p><?php echo anchor_capable('group/manage', icon('users')."<strong>{$count['group']}</strong> 代表团", 'administrator');?></p>
						<p><?php echo anchor_capable('committee/manage', icon('archive')."<strong>{$count['committee']}</strong> 委员会", 'administrator');?></p>
						<p><?php echo anchor('seat/manage', icon('th-list')."<strong>{$count['seat']}</strong> 席位");?></p>
						<p><?php echo anchor_capable('user/manage', icon('user')."<strong>{$count['admin']}</strong> 管理员", 'bureaucrat');?></p>
					</div>
				</div>
				
				<small>运行 iPlacard <?php echo IP_VERSION;?>。</small>
			</div>
		</div>
		
		<div id="ui-spdy" class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title"><?php echo icon('bolt');?>快速访问</h3>
			</div>
			<div class="panel-body">
				<div class="input-group" style="margin-bottom: 10.5px;">
					<span id="spdy_icon" class="input-group-addon"><?php echo icon('user', false);?></span>
					<?php echo form_input(array(
						'id' => 'spdy_input',
						'name' => 'keyword',
						'value' => set_value('keyword'),
						'class' => 'form-control',
						'placeholder' => '代表信息',
						'onclick' => "$('#spdy_go').removeClass('disabled');"
					));?>
					<span class="input-group-btn">
						<?php echo form_button(array(
							'id' => 'spdy_go',
							'content' => icon('sign-in', false),
							'type' => 'button',
							'class' => 'btn btn-primary disabled',
							'onclick' => 'loader(this); spdy();'
						));?>
					</span>
				</div>
				
				<small>输入代表ID、姓名、Email、手机或其他唯一身份标识符快速访问代表信息。</small>
			</div>
			
			<div id="spdy_result" class="list-group"></div>
				<?php
				$spdy_url = base_url('admin/ajax/spdy');
				$spdy_delegate_url = base_url('delegate/profile');
				$spdy_empty_keyword = anchor('#', icon('exclamation-circle').'输入为空。', 'class="list-group-item"', true);
				$spdy_button = icon('sign-in', false);
				$spdy_js = "function spdy()
				{
					keyword = $('#spdy_input').val();
					
					if(keyword !== '')
					{
						$.ajax({
							url: '{$spdy_url}',
							async: true,
							data: {keyword: keyword},
							dataType : 'json',
							success : function( json ){
								$('#spdy_result').html(json.html);

								if(!json.result)
								{
									$('#ui-spdy').removeClass('panel-default').removeClass('panel-danger').removeClass('panel-success').removeClass('panel-primary').addClass('panel-warning');
									$('#spdy_go').removeClass('btn-success').removeClass('btn-danger').removeClass('btn-primary').addClass('btn-warning');
								}
								else if(json.redirect)
								{
									$('#ui-spdy').removeClass('panel-default').removeClass('panel-danger').removeClass('panel-warning').removeClass('panel-primary').addClass('panel-success');
									$('#spdy_go').removeClass('btn-warning').removeClass('btn-danger').removeClass('btn-primary').addClass('btn-success');

									setTimeout(function(){
										location.href = '{$spdy_delegate_url}/' + json.id
									}, 500);

									return;
								}
								else
								{
									$('#ui-spdy').removeClass('panel-default').removeClass('panel-danger').removeClass('panel-success').removeClass('panel-warning').addClass('panel-primary');
									$('#spdy_go').removeClass('btn-success').removeClass('btn-danger').removeClass('btn-warning').addClass('btn-primary');
								}
							}
						});
					}
					else
					{
						$('#spdy_result').html('{$spdy_empty_keyword}');
						
						$('#ui-spdy').removeClass('panel-default').removeClass('panel-success').removeClass('panel-warning').removeClass('panel-primary').addClass('panel-danger');
						$('#spdy_go').removeClass('btn-success').removeClass('btn-warning').removeClass('btn-primary').addClass('btn-danger');
					}
					
					$('#spdy_go').html('{$spdy_button}');
				}";
				$this->ui->js('footer', $spdy_js);
				
				//回车自动激活
				$keypress_js = "$('#spdy_input').keypress(function(event)
				{
					if(event.which == 13)
					{
						spdy();
					}
				});";
				$this->ui->js('footer', $keypress_js);
				
				//小屏幕不显示图标
				$this->ui->html('footer', "<style>
					@media (max-width: 767px) {
						#spdy_icon {
							display: none;
						}
					}
				</style>");
				?>
		</div>
	</div>

	<div class="col-md-6">
		<?php if($has_task) { ?><div id="ui-task" class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title"><?php echo icon('tasks');?>待办事项</h3>
			</div>
			<div class="list-group">
				<?php
				if(isset($task['review']))
					echo anchor('delegate/manage/?status=application_imported', "{$task['review']} 份参会申请正在等待审核。", 'class="list-group-item"');
				if(isset($task['interview_assign']))
					echo anchor('delegate/manage/?status=review_passed', "{$task['interview_assign']} 位代表正在等待分配面试官。", 'class="list-group-item"');
				if(isset($task['interview_arrange']))
					echo anchor('interview/manage?interviewer=u&status=assigned', "{$task['interview_arrange']} 位代表正在等待安排面试时间。", 'class="list-group-item"');
				if(isset($task['interview_do']))
					echo anchor('interview/manage?interviewer=u&status=arranged', sprintf('%1$s 场面试等待进行，最近的面试安排在 %2$s（%3$s）。', $task['interview_do'], date('m-d H:i', $task['interview_next_schedule']), nicetime($task['interview_next_schedule'])), 'class="list-group-item"');
				?>
			</div>
		</div><?php } ?>
		
		<?php if($feed_enable) { ?><div id="ui-news" class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title"><?php echo icon('globe');?>新闻</h3>
			</div>
			<div class="list-group">
				<?php foreach($feed as $num => $news) { ?><a href="<?php echo $news['link'];?>" target="_blank" class="list-group-item">
					<h4 class="list-group-item-heading"><?php echo $news['title'];?> <small><?php echo nicetime(strtotime($news['date']));?></small></h4>
					<div class="list-group-item-text">
						<?php echo character_limiter(strip_tags(empty($news['content']) ? $news['description'] : $news['content']), 300);?>
					</div>
				</a><?php } ?>
			</div>
		</div><?php } ?>
	</div>
</div>

<?php $this->load->view('footer');?>