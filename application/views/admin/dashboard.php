<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 仪表盘视图
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
 * @package iPlacard
 * @since 2.0
 */

$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/echarts.js' : 'static/js/echarts.min.js').'"></script>');
$this->load->view('header');?>

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
						<p><?php echo anchor_capable('committee/manage', icon('university')."<strong>{$count['committee']}</strong> 委员会", 'administrator');?></p>
						<p><?php echo anchor('seat/manage', icon('th-list')."<strong>{$count['seat']}</strong> 席位");?></p>
						<p><?php echo anchor_capable('user/manage', icon('user')."<strong>{$count['admin']}</strong> 管理员", array('bureaucrat', 'administrator'));?></p>
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
				
				<small>输入代表 ID、姓名、Email、手机或其他唯一身份标识符快速访问代表信息。</small>
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
		
		<?php if($stat_enable) { ?><div id="ui-stat" class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">
					<?php echo icon('bar-chart-o');?><span class="dropdown">
						<a class="dropdown-toggle" data-toggle="dropdown" href="#" style="color: inherit;"><span id="stat_name">统计</span> <b class="caret"></b></a>
						<ul class="dropdown-menu">
							<li><a style="cursor: pointer;" onclick="$('#stat_name').html('周代表增量统计'); draw_chart('application_increment');">周代表增量统计</a></li>
							<li><a style="cursor: pointer;" onclick="$('#stat_name').html('申请状态分布统计'); draw_chart('application_status');">申请状态分布统计</a></li>
							<li><a style="cursor: pointer;" onclick="$('#stat_name').html('面试评分分布统计'); draw_chart('interview_<?php echo $stat_interview;?>');">面试评分分布统计</a></li>
							<li><a style="cursor: pointer;" onclick="$('#stat_name').html('席位状态统计'); draw_chart('seat_status');">席位状态统计</a></li>
						</ul>
					</span>
				</h3>
			</div>
			<div id="stat_body" class="panel-body">
				<div id="stat_chart"></div>
			</div>
		</div><?php
			$chart_url = base_url('admin/ajax/stat');
			$chart_js = "function draw_chart(type) {
				$('#ui-stat').removeClass('panel-warning').addClass('panel-default');
				$('#stat_body').css({'padding': '15px'});
				$('#stat_chart').css({'height': '300px'});
				$('#stat_chart').css({'margin': '0'});
				
				var stat = echarts.init(document.getElementById('stat_chart'));
				var chart_option;
				
				stat.showLoading({
					text: '正在加载数据....',
					effect: 'whirling',
					backgroundColor: 'rgba(0, 0, 0, 0)'
				});
				
				$.ajax({
					url: '$chart_url?chart=' + type,
					dataType: 'json',
					success: function( result ) {
						if(result) {
							$('#stat_body').css({'padding': '15px 0 15px 0'});
							
							switch(type) {
								case 'application_increment':
									chart_option = chart_option_application_increment;
									
									chart_option.xAxis[0].data = result.category;
									chart_option.series[0].data = result.series['delegate'];
									chart_option.series[1].data = result.series['observer'];
									chart_option.series[2].data = result.series['volunteer'];
									chart_option.series[3].data = result.series['teacher'];
									
									$('#stat_chart').css({'height': '350px'});
									$('#stat_chart').css({'margin': '-40px -40px 0'});
									stat = echarts.init(document.getElementById('stat_chart'));
									break;
									
								case 'application_status':
									chart_option = chart_option_application_status;
									
									chart_option.legend.data = result.legend;
									chart_option.series[0].data = result.series;
									break;
									
								case 'interview_2d':
									chart_option = chart_option_interview_2d;

									chart_option.series[0].data = result.series['failed'];
									chart_option.series[1].data = result.series['passed'];
									
									$('#stat_chart').css({'height': '400px'});
									$('#stat_body').css({'padding': '0'});
									stat = echarts.init(document.getElementById('stat_chart'));
									break;
									
								case 'seat_status':
									chart_option = chart_option_seat_status;
									
									chart_option.xAxis[0].data = result.category;
									chart_option.series[0].data = result.series['available'];
									chart_option.series[1].data = result.series['assigned'];
									chart_option.series[2].data = result.series['interview'];
									
									$('#stat_chart').css({'height': '350px'});
									$('#stat_chart').css({'margin': '-40px -40px 0'});
									stat = echarts.init(document.getElementById('stat_chart'));
									break;
							}
							
							stat.hideLoading();
							stat.setOption(chart_option);
						} else {
							$('#ui-stat').removeClass('panel-default').addClass('panel-warning');
							$('#stat_chart').css({'height': 'auto'});
							$('#stat_chart').html('无可用数据。');
						}
					},
					error: function ( error ) {
						$('#ui-stat').removeClass('panel-default').addClass('panel-warning');
						$('#stat_chart').css({'height': 'auto'});
						$('#stat_chart').html('载入数据失败。');
					}
				});
			}
			
			$(document).ready(function() {
				draw_chart('application_increment');
				$('#stat_name').html('周代表增量统计');
			});";
			$this->ui->js('footer', $chart_js);
		} ?>
	</div>

	<div class="col-md-6">
		<?php if($has_task) { ?><div id="ui-task" class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title"><?php echo icon('tasks');?>待办事项</h3>
			</div>
			<div class="list-group">
				<?php
				if(isset($task['delete']))
					echo anchor('delegate/manage/?status=deleted', "{$task['delete']} 个代表帐户将被删除。", 'class="list-group-item"');
				if(isset($task['disable']))
					echo anchor('delegate/manage/?enabled=0', "{$task['disable']} 个代表帐户被停用。", 'class="list-group-item"');
				if(isset($task['review']))
					echo anchor('delegate/manage/?status=application_imported', "{$task['review']} 份参会申请正在等待审核。", 'class="list-group-item"');
				if(isset($task['interview_assign']))
					echo anchor('delegate/manage/?status=review_passed', "{$task['interview_assign']} 位代表正在等待分配面试官。", 'class="list-group-item"');
				if(isset($task['interview_arrange']))
					echo anchor('interview/manage?interviewer=u&status=assigned', "{$task['interview_arrange']} 位代表正在等待安排面试时间。", 'class="list-group-item"');
				if(isset($task['reviewer_seat_assign']))
					echo anchor('delegate/manage/?status=review_passed', "{$task['reviewer_seat_assign']} 位代表正在等待分配席位。", 'class="list-group-item"');
				if(isset($task['seat_assign']))
					echo anchor('delegate/manage?type=delegate&status=interview_completed&interviewer=u', "{$task['seat_assign']} 位代表正在等待分配席位。", 'class="list-group-item"');
				if(isset($task['seat_select']))
					echo anchor('delegate/manage?type=delegate&status=seat_assigned&interviewer=u', "{$task['seat_select']} 位代表尚未选择席位。", 'class="list-group-item"');
				if(isset($task['interview_do']))
					echo anchor('interview/manage?interviewer=u&status=arranged', sprintf('%1$s 场面试等待进行，最近的面试安排在 %2$s（%3$s）。', $task['interview_do'], date('m-d H:i', $task['interview_next_schedule']), nicetime($task['interview_next_schedule'])), 'class="list-group-item"');
				if(isset($task['interview_global_arrange']))
					echo anchor('interview/manage?status=assigned&display_interviewer=1', "全局共 {$task['interview_global_arrange']} 位代表尚未安排面试时间。", 'class="list-group-item"');
				if(isset($task['interview_global_do']))
					echo anchor('interview/manage?status=arranged&display_interviewer=1', "全局共 {$task['interview_global_do']} 位代表等待面试。", 'class="list-group-item"');
				if(isset($task['seat_global_assign']))
					echo anchor('delegate/manage?type=delegate&status=interview_completed', "全局共 {$task['seat_global_assign']} 位代表等待分配席位。", 'class="list-group-item"');
				if(isset($task['reviewer_seat_global_assign']))
					echo anchor('delegate/manage?type=delegate&status=review_passed', "全局共 {$task['reviewer_seat_global_assign']} 位代表等待分配席位。", 'class="list-group-item"');
				if(isset($task['seat_global_select']))
					echo anchor('delegate/manage?type=delegate&status=seat_assigned', "全局共 {$task['seat_global_select']} 位代表尚未选择席位。", 'class="list-group-item"');	
				if(isset($task['invoice_receive']))
					echo anchor('billing/manage?status=unpaid&transaction=1', "{$task['invoice_receive']} 份账单已经标记支付并等待确认。", 'class="list-group-item"');
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