<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 后台管理控制器
 * @package iPlacard
 * @since 2.0
 */
class Admin extends CI_Controller
{
	/**
	 * @var array 待办事项统计
	 */
	private $task = array();
	
	/**
	 * @var bool 是否有待办事项
	 */
	private $has_task = false;
	
	function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->library('form_validation');
		$this->load->library('ui', array('side' => 'admin'));
		$this->load->model('admin_model');
		$this->load->helper('text');
		$this->load->helper('date');
		
		//检查登录情况
		if(!is_logged_in())
		{
			login_redirect();
			return;
		}
		
		//检查权限
		if(!$this->user_model->is_admin(uid()))
		{
			redirect('');
			return;
		}
		
		$this->ui->now('admin');
	}
	
	/**
	 * 控制板页面
	 */
	function dashboard()
	{
		$vars = array();
		
		$sidebar = array(
			'dashboard' => array(icon('dashboard').'控制板', '#ui-dashboard', '', true, true),
			'task' => array(icon('tasks').'待办事项', '#ui-task', '', true),
			'spdy' => array(icon('bolt').'快速访问', '#ui-spdy', '', true),
			'stat' => array(icon('bar-chart-o').'统计', '#ui-stat', 'administrator', true),
			'news' => array(icon('globe').'新闻', '#ui-news', '', true, false, true),
			
			'delegate' => array(icon('user').'代表管理', 'delegate/manage', 'administrator'),
			'interview' => array(icon('comments').'面试管理', 'interview/manage?interviewer=u', 'interviewer'),
			'document' => array(icon('file').'文件管理', 'document/manage'),
			'group' => array(icon('users').'代表团管理', 'group/manage', 'administrator'),
			'billing' => array(icon('file-text').'账单管理', 'billing/manage', 'cashier'),
			'seat' => array(icon('th-list').'席位管理', 'seat/manage', '', false, false, true),
			
			'account' => array(icon('user').'帐户', 'account/settings/home'),
			'knowledgebase' => array(icon('book').'知识库', 'help/knowledgebase'),
		);
		
		$admin = $this->admin_model->get_admin(uid());
		$vars['admin'] = $admin;
		
		//欢迎界面
		if(!user_option('ui_dismiss_welcome', false))
			$vars['welcome'] = true;
		else
			$vars['welcome'] = false;
		
		//统计
		
		//代表数量
		$this->load->model('delegate_model');
		foreach(array('delegate', 'observer', 'volunteer', 'teacher') as $delegate_type)
		{
			$delegate_ids = $this->delegate_model->get_delegate_ids('application_type', $delegate_type, 'status !=', 'quitted');
			if($delegate_ids)
				$count[$delegate_type] = count($delegate_ids);
			else
				$count[$delegate_type] = 0;
		}
		
		//代表团数量
		$this->load->model('group_model');
		$group_ids = $this->group_model->get_group_ids();
		if($group_ids)
			$count['group'] = count($group_ids);
		else
			$count['group'] = 0;
		
		//委员会数量
		$this->load->model('committee_model');
		$committee_ids = $this->committee_model->get_committee_ids();
		if($committee_ids)
			$count['committee'] = count($committee_ids);
		else
			$count['committee'] = 0;
		
		//席位数量
		$this->load->model('seat_model');
		$seat_ids = $this->seat_model->get_seat_ids();
		if($seat_ids)
			$count['seat'] = count($seat_ids);
		else
			$count['seat'] = 0;
		
		//管理员数量
		$admin_ids = $this->user_model->get_user_ids('type', 'admin');
		if($admin_ids)
			$count['admin'] = count($admin_ids);
		else
			$count['admin'] = 0;
		
		$vars['count'] = $count;
		
		//待办事项
		if($this->admin_model->capable('reviewer'))
		{
			//待审申请
			$task_review_ids = $this->delegate_model->get_delegate_ids('status', 'application_imported');
			if($task_review_ids)
				$this->_task('review', count($task_review_ids));
			
			//待分配面试
			$task_interview_assign_ids = $this->delegate_model->get_delegate_ids('status', 'review_passed');
			if($task_interview_assign_ids)
				$this->_task('interview_assign', count($task_interview_assign_ids));
		}
		
		if($this->admin_model->capable('interviewer'))
		{
			$this->load->model('interview_model');
			
			//待安排时间面试
			$task_interview_arrange_ids = $this->interview_model->get_interview_ids('interviewer', $admin['id'], 'status', 'assigned');
			if($task_interview_arrange_ids)
				$this->_task('interview_arrange', count($task_interview_arrange_ids));
			
			//等待面试
			$task_interview_do_ids = $this->interview_model->get_interview_ids('interviewer', $admin['id'], 'status', 'arranged');
			if($task_interview_do_ids)
			{
				$this->_task('interview_do', count($task_interview_do_ids));

				//最近面试
				$newest = 0;
				foreach($task_interview_do_ids as $interview_id)
				{
					$interview_time = $this->interview_model->get_interview($interview_id, 'schedule_time');

					if($newest > $interview_time || $newest == 0)
						$newest = $interview_time;
				}

				$this->_task('interview_next_schedule', $newest);
			}
			
			$task_interview_finish_ids = $this->interview_model->get_interview_ids('interviewer', $admin['id'], 'status', 'completed');
			if($task_interview_finish_ids)
			{
				$interviewer_delegate_ids = $this->interview_model->get_delegates_by_interviews($task_interview_finish_ids);
				
				//待分配席位
				$task_seat_assign_ids = $this->delegate_model->get_delegate_ids('id', $interviewer_delegate_ids, 'status', 'interview_completed');
				if($task_seat_assign_ids)
					$this->_task('seat_assign', count($task_seat_assign_ids));
				
				//待选择席位
				$task_seat_select_ids = $this->delegate_model->get_delegate_ids('id', $interviewer_delegate_ids, 'status', 'seat_assigned');
				if($task_seat_select_ids)
					$this->_task('seat_select', count($task_seat_select_ids));
			}
		}
		
		if($this->admin_model->capable('administrator'))
		{
			$this->load->model('interview_model');
			
			//全局待安排时间面试
			$task_global_interview_arrange_ids = $this->interview_model->get_interview_ids('status', 'assigned');
			if($task_global_interview_arrange_ids)
				$this->_task('interview_global_arrange', count($task_global_interview_arrange_ids));
			
			//全局等待面试
			$task_global_interview_do_ids = $this->interview_model->get_interview_ids('status', 'arranged');
			if($task_global_interview_do_ids)
				$this->_task('interview_global_do', count($task_global_interview_do_ids));
			
			//全局待分配席位
			$task_global_seat_assign_ids = $this->delegate_model->get_delegate_ids('status', 'interview_completed');
			if($task_global_seat_assign_ids)
				$this->_task('seat_global_assign', count($task_global_seat_assign_ids));

			//全局待选择席位
			$task_global_seat_select_ids = $this->delegate_model->get_delegate_ids('status', 'seat_assigned');
			if($task_global_seat_select_ids)
				$this->_task('seat_global_select', count($task_global_seat_select_ids));
		}
		
		if($this->admin_model->capable('cashier'))
		{
			$this->load->model('invoice_model');
			
			//待确认账单
			$task_invoice_receive_ids = $this->invoice_model->get_invoice_ids('status', 'unpaid', 'transaction IS NOT NULL', NULL);
			if($task_invoice_receive_ids)
				$this->_task('invoice_receive', count($task_invoice_receive_ids));
		}
		
		$vars['task'] = $this->task;
		$vars['has_task'] = $this->has_task;
		if(!$this->has_task)
			unset($sidebar['task']);
		
		//RSS
		$this->load->library('feed');
		
		$this->feed->set_feed_url(option('feed_url', 'http://iplacard.com/feed/'));
		
		$feed_enable = false;
		if(!$this->feed->parse())
		{
			unset($sidebar['news']);
		}
		else
		{
			$feed_enable = true;
			$vars['feed'] = $this->feed->get_feed(2);
		}
		
		$vars['feed_enable'] = $feed_enable;
		
		//统计
		$stat_enable = false;
		
		if($this->admin_model->capable('administrator'))
		{
			foreach(array('application_increment', 'application_status') as $type)
			{
				$option = $this->_get_chart_option($type);
				$this->ui->js('footer', "var chart_option_{$type} = {$option};");
			}
			
			$stat_enable = true;
		}
		
		$vars['stat_enable'] = $stat_enable;
		
		$this->ui->sidebar($sidebar);
		$this->ui->title('控制板');
		$this->load->view('admin/dashboard', $vars);
	}
	
	/**
	 * 广播通知
	 */
	function broadcast($action = 'email')
	{
		//检查权限
		if(!$this->admin_model->capable('administrator'))
		{
			redirect('');
			return;
		}
		
		$this->load->model('seat_model');
		$this->load->model('delegate_model');
		$this->load->model('committee_model');
		
		$vars = array();
		switch($action)
		{
			case 'email':
				$title = '群发邮件';
				break;
			case 'sms':
				$title = '群发短信';
				break;
			case 'message':
				$title = '广播站内消息';
				break;
			default:
				return;
		}
		$vars['title'] = $title;
		
		$this->form_validation->set_error_delimiters('<div class="help-block">', '</div>');
		
		if($action == 'email')
			$this->form_validation->set_rules('title', '标题', 'trim|required');
		$this->form_validation->set_rules('content', '内容', 'required');
		
		if($this->form_validation->run() == true)
		{
			//代表
			$target_delegate['status'] = $this->input->post('status');
			$target_delegate['type'] = $this->input->post('type');
			$target_delegate['committee'] = $this->input->post('committee');
			$target_delegates = $this->_get_target_delegates($target_delegate, 'delegate');
			
			//管理用户
			$target_admin['role'] = $this->input->post('role');
			$target_admins = $this->_get_target_admins($target_admin, 'admin');
			
			$ids = array_unique(array_merge($target_admins, $target_delegates));
			$count = count($ids);
			
			if($count > 0)
			{
				$content = $this->input->post('content');
				
				switch($action)
				{
					case 'email':
						$subject = trim($this->input->post('title'));
						
						//发送邮件
						$this->load->library('email');
						foreach($ids as $id)
						{
							$this->email->to($this->user_model->get_user($id, 'email'));
							$this->email->subject($subject);
							$this->email->html($content, false);

							$this->email->send();
							$this->email->clear();
						}
						
						$this->ui->alert("已经向 {$count} 位代表或管理用户发送邮件通知。", 'success');
						break;
					case 'sms':
						//短信通知
						if(option('sms_enabled', false))
						{
							$this->load->model('sms_model');
							$this->load->library('sms');

							$this->sms->set_mass(true);
							foreach($ids as $id)
							{
								$this->sms->to($id);
							}
							
							$this->sms->message($content);
							$this->sms->queue();
							
							$this->ui->alert("已经向 {$count} 位代表或管理用户发送短信通知。", 'success');
						}
						else
						{
							$this->ui->alert("短信通道已关闭或未设置。", 'warning');
						}
						break;
					case 'message':
						foreach($ids as $id)
						{
							$this->user_model->add_message($id, $content);
						}
						
						$this->ui->alert("已经向 {$count} 位代表或管理用户广播站内通知。", 'success');
						break;
				}
				
				$this->system_model->log('broadcast', array(
					'type' => $action,
					'target' => array_merge($target_delegate, $target_admin),
					'client' => $ids,
					'content' => $content,
					'title' => isset($subject) ? $subject : NULL
				));
			}
			else
			{
				$this->ui->alert('没有根据设定的群发对象筛选出需要通知的用户。', 'info');
			}
		}
		
		$select = array();
		
		//代表类型
		$application_types = array(
			'delegate',
			'observer',
			'volunteer',
			'teacher'
		);
		foreach($application_types as $application_type)
		{
			$select['type'][$application_type] = $this->delegate_model->application_type_text($application_type);
		}
		
		//申请状态
		$status = array(
			'application_imported',
			'review_passed',
			'review_refused',
			'interview_assigned',
			'interview_arranged',
			'interview_completed',
			'moved_to_waiting_list',
			'seat_assigned',
			'invoice_issued',
			'payment_received',
			'locked',
			'quitted'
		);
		foreach($status as $status_one)
		{
			$select['status'][$status_one] = $this->delegate_model->status_text($status_one);
		}
		
		//委员会
		$committees = $this->committee_model->get_committee_ids();
		
		$select['committee'][0] = '无委员会代表';
		foreach($committees as $committee)
		{
			$select['committee'][$committee] = $this->committee_model->get_committee($committee, 'name');
		}
		
		//用户权限
		$select['role'] = array(
			'reviewer' => '资料审核',
			'dais' => '主席',
			'interviewer' => '面试官',
			'cashier' => '财务管理',
			'administrator' => '会务管理',
			'bureaucrat' => '行政员',
			'zero' => '无权限用户'
		);
		
		$vars['select'] = $select;
		
		$vars['action'] = $action;
		
		$this->ui->title($title);
		$this->load->view('admin/broadcast', $vars);
	}
	
	/**
	 * AJAX
	 */
	function ajax($action = '')
	{
		$json = array();
		
		if($action == 'dismiss_welcome')
		{
			if($this->user_model->is_admin(uid()))
			{
				$this->user_model->edit_user_option('ui_dismiss_welcome', true);
				
				$json['result'] = true;
			}
			else
			{
				$json['result'] = false;
			}
		}
		elseif($action == 'spdy')
		{
			$this->load->model('delegate_model');
			
			$keyword = $this->input->get('keyword', true);
			
			$ids = $this->delegate_model->search_delegate($keyword, 5);
			
			if($ids && !empty($keyword))
			{
				if(count($ids) == 1)
				{
					$delegate = $this->delegate_model->get_delegate($ids[0]);
					
					$html = anchor('#', icon('info-circle')."正在转到{$delegate['name']}代表资料页面....", 'class="list-group-item"', true);
					
					$json['redirect'] = true;
					
					$json['id'] = $delegate['id'];
					
					$json['result'] = true;
				}
				else
				{
					$html = anchor('#', '您是不是要找：', 'class="list-group-item"', true);
				
					foreach($ids as $id)
					{
						$delegate = $this->delegate_model->get_delegate($id);

						$html .= anchor("delegate/profile/{$delegate['id']}", icon('user')."{$delegate['name']}（ID：{$delegate['id']}）", 'class="list-group-item"');
					}
					
					$json['redirect'] = false;
					
					$json['result'] = true;
				}
			}
			else
			{
				$html = anchor('#', icon('info-circle').'没有符合搜索条件的代表。', 'class="list-group-item"', true);
				
				$json['result'] = false;
			}
			
			$json['html'] = $html;
		}
		elseif($action == 'stat')
		{
			//访问检查
			$chart = $this->input->get('chart');
			if(empty($chart))
				return;
			
			if(!$this->admin_model->capable('administrator'))
				return;
			
			$chart_category = array();
			$chart_legend = array();
			$chart_data = array();
			
			switch($chart)
			{
				case 'application_increment':
					$this->load->model('delegate_model');
					
					$imported_ids = $this->delegate_model->get_event_ids('event', 'application_imported');
					if($imported_ids)
					{
						$stat = array();
						$first_week = strtotime('Monday this week');
						
						//导入记录
						foreach($imported_ids as $imported_id)
						{
							$event = $this->delegate_model->get_event($imported_id);

							$week = strtotime('Monday this week', $event['time']);
							if(!$week)
								continue;

							if($week < $first_week)
								$first_week = $week;
							
							$application_type = $this->delegate_model->get_delegate($event['delegate'], 'application_type');
							if(isset($stat[$week][$application_type]))
								$stat[$week][$application_type]++;
							else
								$stat[$week][$application_type] = 1;
						}
						
						//退会记录
						$quitted_ids = $this->delegate_model->get_event_ids('event', 'quitted');

						if($quitted_ids)
						{
							foreach($quitted_ids as $quitted_id)
							{
								$event = $this->delegate_model->get_event($quitted_id);

								$week = strtotime('Monday this week', $event['time']);
								if(!$week)
									continue;

								$application_type = $this->delegate_model->get_delegate($event['delegate'], 'application_type');
								if(isset($stat[$week][$application_type]))
									$stat[$week][$application_type]--;
								else
									$stat[$week][$application_type] = -1;
							}
						}
						
						//确定第一周
						if($first_week < strtotime('Monday this week') - 8 * 7 * 24 * 60 * 60)
							$first_week = strtotime('Monday this week') - 8 * 7 * 24 * 60 * 60;
						
						//处理记录
						for($i = $first_week; $i <= strtotime('Monday this week'); $i += 7 * 24 * 60 * 60)
						{
							$chart_category[] = date('m/d', $i);

							foreach(array('delegate', 'observer', 'volunteer', 'teacher') as $application_type)
							{
								if(isset($stat[$i][$application_type]))
									$chart_data[$application_type][] = $stat[$i][$application_type];
								else
									$chart_data[$application_type][] = 0;
							}
						}
					}
					else
						return;
					
					break;
					
				case 'application_status':
					$this->load->model('delegate_model');
					
					$ids = $this->delegate_model->get_delegate_ids('status !=', 'quitted');
					if($ids)
					{
						$stat = array();
						
						//统计数据
						foreach($ids as $id)
						{
							$status = $this->delegate_model->get_delegate($id, 'status');

							if(isset($stat[$status]))
								$stat[$status]++;
							else
								$stat[$status] = 1;
						}
						
						//从高到底排序
						arsort($stat);
						
						//处理记录
						$left = count($ids);
						$min = 0.05 * count($ids);
						
						foreach($stat as $status => $count)
						{
							if($left <= $min)
							{
								$chart_legend[] = '其他';
								$chart_data[] = array(
									'value' => $left,
									'name' => '其他'
								);
								
								break;
							}
							
							$status_name = $this->delegate_model->status_text($status);
							
							$chart_legend[] = $status_name;
							$chart_data[] = array(
								'value' => $count,
								'name' => $status_name
							);
							
							$left -= $count;
						}
					}
					else
						return;
			}
			
			$json['category'] = $chart_category;
			$json['legend'] = $chart_legend;
			$json['series'] = $chart_data;
		}
		
		echo json_encode($json);
	}
	
	/**
	 * 增加待办事项数据
	 */
	private function _task($item, $count = 0)
	{
		$this->task[$item] = $count;
		$this->has_task = true;
	}
	
	/**
	 * 获取统计图属性
	 */
	private function _get_chart_option($type)
	{
		switch($type)
		{
			case 'application_increment':
				$data_string = array();
				
				foreach(array('代表', '观察员', '志愿者', '指导老师') as $application_name)
				{
					$one_string = "{
						name: '{$application_name}',
						type: 'line',
						data: [],
						markPoint: {
							data: [
								{
									type: 'max',
									name: '周最大{$application_name}增值'
								},
								{
									type: 'min',
									name: '周最小{$application_name}增值'
								}
							]
						}";
					
					if($application_name == '代表')
					{
						$one_string .= ",
							markLine: {
								data: [
									{
										type: 'average',
										name: '周平均{$application_name}增值'
									}
								]
							}";
					}
					
					$one_string .= '}';
									
					$data_string[] = $one_string;
				}

				return "{
					tooltip: {
						trigger: 'axis'
					},
					legend: {
						y: 'bottom',
						data: ['代表', '观察员', '志愿者', '指导老师']
					},
					xAxis: [
						{
							type: 'category',
							boundaryGap: false,
							data: []
						}
					],
					yAxis: [
						{
							axisLabel: { show: false },
						}
					],
					series: [
						".join(',', $data_string)."
					]
				}";
				
			case 'application_status':
				return "{
					tooltip: {
						trigger: 'item',
						formatter: \"{b}：{c} ({d}%)\"
					},
					legend: {
						orient: 'vertical',
						x: 'right',
						y: 'center',
						data: []
					},
					calculable: true,
					series: [
						{
							name: '申请状态',
							type: 'pie',
							radius: ['50%', '70%'],
							itemStyle: {
								emphasis: {
									label: {
										show: true,
										position: 'center',
										textStyle: {
											fontSize: '20',
											fontWeight: 'bold'
										}
									}
								}
							},
							data: []
						}
					]
				};";
		}
		
		return '';
	}
	
	/**
	 * 获取群发对象
	 */
	private function _get_target_delegates($target)
	{
		$param = array();
		
		//代表类型
		if(!empty($target['type']))
			$param['application_type'] = $target['type'];

		//申请状态
		if(!empty($target['status']))
			$param['status'] = $target['status'];

		//委员会
		if(!empty($target['committee']))
		{
			$this->load->model('seat_model');

			$param['id'] = array();

			$sids = $this->seat_model->get_seat_ids('committee', $target['committee'], 'status', array('assigned', 'approved', 'locked'));
			if($sids)
			{
				$dids = $this->seat_model->get_delegates_by_seats($sids);
				if($dids)
					$param['id'] = $dids;
			}

			//无委员会代表
			if(in_array(0, $target['committee']))
			{
				$asids = $this->seat_model->get_seat_ids('status', array('assigned', 'approved', 'locked'));
				if($asids)
				{
					$ndids = $this->seat_model->get_delegates_by_seats($asids);
					if($ndids)
					{
						$adids = $this->delegate_model->get_delegate_ids('id NOT', $ndids);
						if($adids)
							$param['id'] = array_merge($param['id'], $adids);
					}
				}
			}

			if(empty($param['id']))
				$param['id'] = array(NULL);
		}

		$args = array();
		if(!empty($param))
		{
			foreach($param as $item => $value)
			{
				$args[] = $item;
				$args[] = $value;
			}
		}
		else
			return array();

		$ids = call_user_func_array(array($this->delegate_model, 'get_delegate_ids'), $args);
		if(!$ids)
			$ids = array();

		return $ids;
	}
	
	/**
	 * 获取群发对象
	 */
	private function _get_target_admins($target)
	{
		$all = array();

		//代表类型
		if(!empty($target['role']))
		{
			foreach($target['role'] as $role)
			{
				if($role == 'zero')
				{
					$ids = $this->admin_model->get_admin_ids('role_reviewer', false, 'role_dais', false, 'role_interviewer', false, 'role_cashier', false, 'role_administrator', false, 'role_bureaucrat', false);
				}
				else
				{
					$ids = $this->admin_model->get_admin_ids("role_{$role}", true);
				}

				if($ids)
					$all = array_merge($all, $ids);
			}
			
			if(!empty($all))
				return $all;
		}
		
		return array();
	}
}

/* End of file admin.php */
/* Location: ./application/controllers/admin.php */