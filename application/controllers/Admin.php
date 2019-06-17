<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 后台管理控制器
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
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
			'billing' => array(icon('file-text').'账单管理', 'billing/manage', 'cashier'),
			'seat' => array(icon('th-list').'席位管理', 'seat/manage', '', false, false, true),
			
			'account' => array(icon('user').'帐户', 'account/settings/home'),
			'knowledgebase' => array(icon('book').'知识库', 'knowledgebase'),
		);
		
		$admin = $this->admin_model->get_admin(uid());
		$vars['admin'] = $admin;
		
		//欢迎界面
		if(!user_option('ui_dismiss_welcome', false))
			$vars['welcome'] = true;
		else
			$vars['welcome'] = false;
		
		//控制板统计
		
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
			
			if(option('interview_enabled', true))
			{
				//待分配面试
				$task_interview_assign_ids = $this->delegate_model->get_delegate_ids('status', 'review_passed');
				if($task_interview_assign_ids)
					$this->_task('interview_assign', count($task_interview_assign_ids));
			}
			else
			{
				//待分配席位
				$task_reviewer_seat_assign_ids = $this->delegate_model->get_delegate_ids('status', 'review_passed');
				if($task_reviewer_seat_assign_ids)
					$this->_task('reviewer_seat_assign', count($task_reviewer_seat_assign_ids));
			}
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
				$task_seat_assign_ids = $this->delegate_model->get_delegate_ids('user.id', $interviewer_delegate_ids, 'status', 'interview_completed');
				if($task_seat_assign_ids)
					$this->_task('seat_assign', count($task_seat_assign_ids));
				
				//待选择席位
				$task_seat_select_ids = $this->delegate_model->get_delegate_ids('user.id', $interviewer_delegate_ids, 'status', 'seat_assigned');
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
			$task_global_seat_assign_ids = $this->delegate_model->get_delegate_ids('status', option('interview_enabled', true) ? 'interview_completed' : 'review_passed');
			if($task_global_seat_assign_ids)
				$this->_task(option('interview_enabled', true) ? 'seat_global_assign' : 'reviewer_seat_global_assign', count($task_global_seat_assign_ids));

			//全局待选择席位
			if(option('seat_mode', 'select') == 'select')
			{
				$task_global_seat_select_ids = $this->delegate_model->get_delegate_ids('status', 'seat_assigned');
				if($task_global_seat_select_ids)
					$this->_task('seat_global_select', count($task_global_seat_select_ids));
			}
			
			//计划删除帐户
			$task_delete_ids = $this->delegate_model->get_delegate_ids('status', 'deleted');
			if($task_delete_ids)
				$this->_task('delete', count($task_delete_ids));
			
			//停用帐户
			$task_disable_ids = $this->delegate_model->get_delegate_ids('enabled', false);
			if($task_disable_ids)
				$this->_task('disable', count($task_disable_ids));
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
		$feed = option('feed_url', 'http://iplacard.com/feed/');
		$feed_enable = false;
		if(!empty($feed))
		{
			$this->load->library('feed');
			
			$this->feed->set_feed_url($feed);
			if(!$this->feed->parse())
			{
				unset($sidebar['news']);
			}
			else
			{
				$feed_enable = true;
				$vars['feed'] = $this->feed->get_feed(2);
			}
		}
		
		$vars['feed_enable'] = $feed_enable;
		
		//面试模式
		if(!option('interview_enabled', true))
		{
			unset($sidebar['interview']);
		}
		
		//统计
		$stat_enable = false;
		
		if($this->admin_model->capable('administrator'))
		{
			//面试分布图
			$interview_type = count(option('interview_score_standard', array())) <= 2 ? '2d' : '3d';
			$vars['stat_interview'] = $interview_type;
			
			foreach(array('application_increment', 'application_status', "interview_{$interview_type}", 'seat_status') as $type)
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
			$target_delegates = $this->_get_target_delegates($target_delegate);
			
			//管理用户
			$target_admin['role'] = $this->input->post('role');
			$target_admins = $this->_get_target_admins($target_admin);
			
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
		
		$vars['select'] = array(
			'type' => $this->_get_select_option('type'),
			'status' => $this->_get_select_option('status'),
			'committee' => $this->_get_select_option('committee'),
			'role' => $this->_get_select_option('role')
		);
		
		$vars['action'] = $action;
		
		$this->ui->title($title);
		$this->load->view('admin/broadcast', $vars);
	}
	
	/**
	 * 导出数据
	 */
	function export($action = 'guide')
	{
		//检查权限
		if(!$this->admin_model->capable('administrator'))
		{
			redirect('');
			return;
		}
		
		$this->form_validation->set_error_delimiters('<div class="help-block">', '</div>');
		
		$this->form_validation->set_rules('format', '导出文件格式', 'required');
		$this->form_validation->set_rules('reason', '导出原因', 'trim|required');
		
		if($action == 'download' && $this->form_validation->run() == true)
		{
			$this->load->library('excel');
			$this->load->helper('download');
			$this->load->helper('date');
			
			$this->load->model('delegate_model');
			$this->load->model('committee_model');
			$this->load->model('seat_model');
			$this->load->model('interview_model');
			$this->load->model('invoice_model');
			$this->load->model('group_model');
			$this->load->model('note_model');

			sleep(1);
			
			$this->db->cache_on();
			
			//管理员信息
			$admin = $this->user_model->get_user(uid());
			
			//导出类型
			$format = $this->input->post('format');
			
			//导出内容
			$source = $this->input->post('source');
			if(!$source || empty($source))
				$source = array();
			
			//表格描述
			$description = sprintf('此表单数据由管理员%1$s于 %2$s 导出自由 iPlacard 驱动的 %3$s，此表单数据受 %4$s 保护。',
				$admin['name'],
				unix_to_human(time()),
				option('site_name', 'iPlacard Instance'),
				option('organization', 'iPlacard')
			);
			
			$this->excel->getProperties()
					->setCreator(option('organization', 'iPlacard'))
					->setLastModifiedBy($admin['name'])
					->setTitle(sprintf('%s数据导出', option('site_name', 'iPlacard Instance')))
					->setSubject(sprintf('%1$s iPlacard 数据导出 %2$s', option('site_name', 'iPlacard Instance'), date('DATE_W3C')))
					->setDescription($description)
					->setKeywords('iplacard '.strtolower(option('organization', '')));
			
			//当前页面记录
			$current_sheet = -1;
			
			//筛选信息
			$target_status = $this->input->post('status');
			$target_type = $this->input->post('type');
			$target_committee = $this->input->post('committee');
			$target_role = $this->input->post('role');
			
			//筛选用户类型
			$list_types = array();
			if(!empty($target_type))
			{
				foreach($this->input->post('type') as $one_type)
				{
					if(in_array($one_type, array('delegate', 'observer', 'volunteer', 'teacher')))
						$list_types[] = $one_type;
				}
			}
			
			if(!empty($target_role))
				$list_types[] = 'admin';
			
			$list_types = array_unique($list_types);
			foreach($list_types as $list_type)
			{
				//重置笔记统计
				$note_count = 0;
				
				$columns = array();
				
				//获取ID
				if($list_type == 'admin')
				{
					$ids = array_unique($this->_get_target_admins(array(
						'role' => $target_role
					)));
				}
				else
				{
					$ids = array_unique($this->_get_target_delegates(array(
						'status' => $target_status,
						'committee' => $target_committee,
						'type' => array($list_type)
					)));
				}
				
				$count = count($ids);
				if($count != 0)
				{
					//表列
					$columns['id'] = array('order' => 0, 'name' => 'ID');
					$columns['name'] = array('order' => count($columns), 'name' => '姓名');
					$columns['email'] = array('order' => count($columns), 'name' => '电子邮箱');
					$columns['phone'] = array('order' => count($columns), 'name' => '电话', 'type' => 'longtext');
					$columns['reg_time'] = array('order' => count($columns), 'name' => ($list_type == 'admin' ? '注册时间' : '申请导入时间'));
					
					if($list_type == 'admin')
					{
						$columns['title'] = array('order' => count($columns), 'name' => '职务');
						$columns['committee'] = array('order' => count($columns), 'name' => '委员会');
					}
					else
					{
						$columns['status'] = array('order' => count($columns), 'name' => '申请状态');
						$columns['unique_identifier'] = array('order' => count($columns), 'name' => '唯一身份标识', 'type' => 'longtext');
						
						if(in_array('group', $source))
							$columns['group'] = array('order' => count($columns), 'name' => '团队');
						
						if($list_type == 'delegate' && in_array('seat', $source))
						{
							$columns['seat_id'] = array('order' => count($columns), 'name' => '席位 ID');
							$columns['seat_name'] = array('order' => count($columns), 'name' => '席位');
							$columns['committee'] = array('order' => count($columns), 'name' => '委员会');
						}
						
						if($list_type == 'delegate' && in_array('interview', $source))
						{
							$columns['interview_id'] = array('order' => count($columns), 'name' => '当前面试 ID');
							$columns['interview_status'] = array('order' => count($columns), 'name' => '面试状态');
							$columns['interview_score'] = array('order' => count($columns), 'name' => '面试分数');
							$columns['interviewer'] = array('order' => count($columns), 'name' => '面试官');
						}
						
						if(in_array('invoice', $source) && $this->admin_model->capable('cashier') && option("invoice_amount_{$list_type}", 0) > 0)
						{
							$columns['invoice_id'] = array('order' => count($columns), 'name' => '账单 ID');
							$columns['invoice_title'] = array('order' => count($columns), 'name' => '账单标题');
							$columns['invoice_amount'] = array('order' => count($columns), 'name' => '账单金额');
							$columns['invoice_status'] = array('order' => count($columns), 'name' => '支付状态');
							$columns['invoice_generate_time'] = array('order' => count($columns), 'name' => '账单生成时间');
							$columns['invoice_receive_time'] = array('order' => count($columns), 'name' => '账单支付时间');
							$columns['invoice_cashier'] = array('order' => count($columns), 'name' => '确认管理员');
						}
						
						if(in_array('profile', $source))
						{
							$profile_items = option('profile_list_general', array()) + option("profile_list_{$list_type}", array());
							if(!empty($profile_items))
							{
								foreach($profile_items as $name => $title)
								{
									$columns["profile_{$name}"] = array('order' => count($columns), 'name' => $title, 'type' => 'longtext');
								}
							}
						}
						
						if(in_array('addition', $source))
						{
							$addition_items = option('profile_addition_general', array()) + option("profile_addition_{$list_type}", array());
							if(!empty($addition_items))
							{
								foreach($addition_items as $name => $item)
								{
									$columns["addition_{$name}"] = array('order' => count($columns), 'name' => $item['title'], 'type' => 'longtext');
								}
							}
						}
					}
					
					//生成数据
					$list_data = array();
					foreach($ids as $id)
					{
						$single_data = array();
						
						//基本信息
						if($list_type == 'admin')
							$user = $this->admin_model->get_admin($id);
						else
							$user = $this->delegate_model->get_delegate($id);
						
						$single_data['id'] = $id;
						$single_data['name'] = $user['name'];
						$single_data['email'] = $user['email'];
						$single_data['phone'] = $user['phone'];
						$single_data['reg_time'] = unix_to_human($user['reg_time']);
						
						if($list_type == 'admin')
						{
							$single_data['title'] = $user['title'];
							
							//委员会
							if(!empty($user['committee']))
								$user['committee'] = $this->committee_model->get_committee($user['committee'], 'name');
							$single_data['committee'] = $user['committee'];
						}
						else
						{
							$single_data['status'] = $this->delegate_model->status_text($user['status']);
							$single_data['unique_identifier'] = $user['unique_identifier'];
							
							if(in_array('group', $source))
							{
								if(!empty($user['group']))
									$single_data['group'] = $this->group_model->get_group($user['group'], 'name');
								else
									$single_data['group'] = NULL;
							}
							
							if($list_type == 'delegate' && in_array('seat', $source))
							{
								$seat_id = $this->seat_model->get_delegate_seat($id);
								if($seat_id)
								{
									$seat = $this->seat_model->get_seat($seat_id);
									
									$single_data['seat_id'] = $seat_id;
									$single_data['seat_name'] = $seat['name'];
									$single_data['committee'] = $this->committee_model->get_committee($seat['committee'], 'name');
								}
							}

							if($list_type == 'delegate' && in_array('interview', $source))
							{
								$interview_id = $this->interview_model->get_current_interview_id($id);
								if($interview_id)
								{
									$interview = $this->interview_model->get_interview($interview_id);
									
									$single_data['interview_id'] = $interview['id'];
									$single_data['interview_status'] = $this->interview_model->status_text($interview['status']);
									$single_data['interview_score'] = $interview['score'];
									$single_data['interviewer'] = $this->user_model->get_user($interview['interviewer'], 'name');
								}
							}
							
							if(in_array('invoice', $source) && $this->admin_model->capable('cashier') && option("invoice_amount_{$list_type}", 0) > 0)
							{
								//TODO: 多账单
								$invoice_ids = $this->invoice_model->get_delegate_invoices($id);
								if($invoice_ids)
								{
									$invoice = $this->invoice_model->get_invoice(end($invoice_ids));
									
									$single_data['invoice_id'] = $invoice['id'];
									$single_data['invoice_title'] = $invoice['title'];
									$single_data['invoice_amount'] = $invoice['amount'];
									$single_data['invoice_status'] = $this->invoice_model->status_text($invoice['status']);
									$single_data['invoice_generate_time'] = unix_to_human($invoice['generate_time']);
									$single_data['invoice_receive_time'] = unix_to_human($invoice['receive_time']);
									
									if(!empty($invoice['cashier']))
										$single_data['invoice_cashier'] = $this->user_model->get_user($invoice['cashier'], 'name');
								}
							}
							
							if(in_array('profile', $source))
							{
								$profile_items = option('profile_list_general', array()) + option("profile_list_{$list_type}", array());
								if(!empty($profile_items))
								{
									$profiles = $this->delegate_model->get_delegate_profiles($id);
									
									foreach($profile_items as $name => $title)
									{
										$single_data["profile_{$name}"] = isset($profiles[$name]) ? $profiles[$name] : NULL;
									}
								}
							}
							
							if(in_array('addition', $source))
							{
								$addition_items = option('profile_addition_general', array()) + option("profile_addition_{$list_type}", array());
								if(!empty($addition_items))
								{
									$additions = $this->delegate_model->get_delegate_profiles($id);
									
									foreach($addition_items as $name => $item)
									{
										$addition = isset($additions["addition_{$name}"]) ? $additions["addition_{$name}"] : $item['default'];
										
										$value = NULL;
										switch($item['type'])
										{
											case 'choice':
												$value = $item['item'][$addition];
												break;
											
											default:
												$value = $addition;
										}
										
										$single_data["addition_{$name}"] = $value;
									}
								}
							}
							
							if(in_array('note', $source))
							{
								$note_ids = $this->note_model->get_delegate_notes($id);
								if($note_ids)
								{
									$counter = 0;
									foreach($note_ids as $note_id)
									{
										$note = $this->note_model->get_note($note_id);
										
										if(empty($note['category']))
										{
											$single_data["note_{$counter}"] = sprintf('[%1$s %2$s]%3$s',
												$this->user_model->get_user($note['admin'], 'name'),
												unix_to_human($note['time']),
												$note['text']
											);
										}
										else
										{
											$single_data["note_{$counter}"] = sprintf('[%1$s %2$s %3$s]%4$s',
												$this->user_model->get_user($note['admin'], 'name'),
												unix_to_human($note['time']),
												$this->note_model->get_category($note['category'], 'name'),
												$note['text']
											);
										}
										
										$counter++;
									}
										
									if(count($note_ids) > $note_count)
										$note_count = count($note_count);
								}
							}
						}
						
						$list_data[] = $single_data;
					}
					
					//补充表列
					if($note_count > 0)
					{
						for($i = 0; $i < $note_count; $i++)
						{
							$columns["note_{$i}"] = array('order' => count($columns), 'name' => '注释');
						}
					}
					
					//生成页面
					$current_row = 1;
					
					//新建工作表
					$current_sheet++;
					if($current_sheet != 0)
						$this->excel->createSheet();
					
					$this->excel->setActiveSheetIndex($current_sheet);
					$this->excel->getActiveSheet()->setTitle(($list_type == 'admin') ? '管理员' : $this->delegate_model->application_type_text($list_type));
					
					//生成表头
					foreach($columns as $column)
					{
						$this->excel->getActiveSheet()->setCellValueByColumnAndRow($column['order'] + 1, 1, $column['name']);
						$this->excel->getActiveSheet()->getStyleByColumnAndRow($column['order'] + 1, 1)->getFont()->setBold(true);
						$this->excel->getActiveSheet()->getStyleByColumnAndRow($column['order'] + 1, 1)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
						$this->excel->getActiveSheet()->getStyleByColumnAndRow($column['order'] + 1, 1)->getFill()->getStartColor()->setRGB('D3D3D3');
					}
					
					//写入数据
					foreach($list_data as $list_single)
					{
						$current_row++;
						
						foreach($columns as $column_id => $column)
						{
							if(isset($column['type']))
							{
								if($column['type'] == 'longtext')
									$this->excel->getActiveSheet()->getCellByColumnAndRow($column['order'] + 1, $current_row)->setValueExplicit(isset($list_single[$column_id]) ? $list_single[$column_id] : NULL, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
							}
							else
								$this->excel->getActiveSheet()->setCellValueByColumnAndRow($column['order'] + 1, $current_row, isset($list_single[$column_id]) ? $list_single[$column_id] : NULL);
						}
					}
				}
			}
			
			//设置活动工作表
			$this->excel->setActiveSheetIndex(0);
			
			$this->db->cache_delete('/admin', 'export');
			
			switch($format)
			{
				case 'xls':
					$io = 'Xls';
					break;
				case 'xlsx':
					$io = 'Xlsx';
					break;
				case 'ods':
					$io = 'Ods';
					break;
				case 'html':
					$io = 'Html';
					break;
				case 'csv':
					$io = 'Csv';
					break;
				default:
					$this->ui->alert('导出格式不支持。', 'warning', true);
					back_redirect();
					return;
			}
			
			$this->system_model->log('export', array(
				'client' => array(
					'status' => $target_status,
					'type' => $target_type,
					'committee' => $target_committee,
					'role' => $target_role
				),
				'format' => $format,
				'source' => $source,
				'reason' => $this->input->post('reason')
			));
			
			//生成内容
			ob_start();
			
			$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($this->excel, $io);
			
			if($format == 'html')
				$writer->writeAllSheets();
			
			$writer->save('php://output');
			
			$content = ob_get_contents();
			ob_end_clean();
			
			force_download('iPlacard-'.date('Y-m-d-H-i-s').'.'.$format, $content);
			
			return;
		}
		
		$vars['source'] = array(
			'seat' => '席位',
			'group' => '团队',
			'interview' => '面试',
			'invoice' => '账单',
			'note' => '笔记',
			'profile' => '申请材料',
			'addition' => '附加信息'
		);
		
		$vars['format'] = array(
			'xlsx' => 'Microsoft Excel 2007 文档（.xlsx）',
			'xls' => 'Microsoft Excel 97-2003 文档（.xls）',
			'ods' => 'OpenDocument 电子表格（.ods）',
			'html' => 'HTML Calc 文档（.html）',
			'csv' => 'CSV 文本（.csv）',
		);
		
		$vars['select'] = array(
			'type' => $this->_get_select_option('type'),
			'status' => $this->_get_select_option('status'),
			'committee' => $this->_get_select_option('committee'),
			'role' => $this->_get_select_option('role')
		);
		
		$this->ui->title('导出');
		$this->load->view('admin/export', $vars);
	}
	
	/**
	 * 统计数据
	 */
	function stat()
	{
		//检查权限
		if(!$this->admin_model->capable('administrator'))
		{
			redirect('');
			return;
		}
		
		//面试分布图
		$interview_type = count(option('interview_score_standard', array())) <= 2 ? '2d' : '3d';
		$vars['stat_interview'] = $interview_type;

		foreach(array('application_increment', 'application_status', "interview_{$interview_type}", 'seat_status') as $type)
		{
			$option = $this->_get_chart_option($type);
			$this->ui->js('footer', "var chart_option_{$type} = {$option};");
		}
		
		$this->ui->title('统计分析');
		$this->load->view('admin/stat', $vars);
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
			
			$ids = $this->_search_delegate($keyword);
			
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
			
			$this->load->driver('cache', array('adapter' => 'memcached', 'backup' => 'file'));
			
			if(!$json = $this->cache->get(IP_CACHE_PREFIX.'_'.IP_INSTANCE_ID.'_stat_'.$chart))
			{
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
							$increment_source = option('chart_application_increment_source', 'event');
							
							$stat = array();
							$last = 0;
							$first_week = strtotime('Monday this week');

							//导入记录
							foreach($imported_ids as $imported_id)
							{
								$event = $this->delegate_model->get_event($imported_id);
								$delegate = $this->delegate_model->get_delegate($event['delegate']);
								
								$time = ($increment_source == 'reg') ? $delegate['reg_time'] : $event['time'];
								if($time > $last)
									$last = $time;
								
								$week = strtotime('Monday this week', $time);
								if(!$week)
									continue;

								if($week < $first_week)
									$first_week = $week;
								
								if(isset($stat[$week][$delegate['application_type']]))
									$stat[$week][$delegate['application_type']]++;
								else
									$stat[$week][$delegate['application_type']] = 1;
							}

							//退会记录
							$quitted_ids = $this->delegate_model->get_event_ids('event', 'quitted');

							if($quitted_ids)
							{
								foreach($quitted_ids as $quitted_id)
								{
									$event = $this->delegate_model->get_event($quitted_id);
									
									if($event['time'] > $last)
										$last = $event['time'];
									
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

							//确定最后一周
							$last_week = strtotime('Monday this week', $last);
							
							//确定第一周
							if($first_week < $last_week - option('chart_application_increment_week', 8) * 7 * 24 * 60 * 60)
								$first_week = $last_week - option('chart_application_increment_week', 8) * 7 * 24 * 60 * 60;

							//处理记录
							for($i = $first_week; $i <= $last_week; $i += 7 * 24 * 60 * 60)
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

						break;

					case 'interview_2d':
						$this->load->model('interview_model');

						$score_standard = option('interview_score_standard', array());
						if(count($score_standard) != 2)
							break;

						$types = array();
						foreach($score_standard as $type => $item)
						{
							$types[] = $type;
						}

						$interview_ids = $this->interview_model->get_interview_ids('score IS NOT NULL', NULL);
						if($interview_ids)
						{
							$stat = array();

							//统计数据
							foreach($interview_ids as $interview_id)
							{
								$interview = $this->interview_model->get_interview($interview_id);

								if(!isset($interview['feedback']['score']))
									continue;

								$score = $interview['feedback']['score'];

								$result = $interview['status'] == 'failed' ? 'failed' : 'passed';

								if(isset($stat[$result][$score[$types[0]]][$score[$types[1]]]))
									$stat[$result][$score[$types[0]]][$score[$types[1]]]++;
								else
									$stat[$result][$score[$types[0]]][$score[$types[1]]] = 1;
							}

							//处理记录
							foreach(array('failed', 'passed') as $result)
							{
								foreach($stat[$result] as $type_0 => $item_0)
								{
									foreach($item_0 as $type_1 => $count)
									{
										$chart_data[$result][] = array($type_0, $type_1, $count);
									}
								}
							}
						}
						else
							return;

						break;

					case 'seat_status':
						$this->load->model('seat_model');
						$this->load->model('interview_model');

						$stat = array();

						//统计席位数据
						$seat_ids = $this->seat_model->get_seat_ids();
						if($seat_ids)
						{
							foreach($seat_ids as $seat_id)
							{
								$seat = $this->seat_model->get_seat($seat_id);

								$status = in_array($seat['status'], array('available', 'unavailable', 'preserved')) ? 'available' : 'assigned';

								if(isset($stat['seat'][$seat['level']][$status]))
									$stat['seat'][$seat['level']][$status]++;
								else
									$stat['seat'][$seat['level']][$status] = 1;
							}
						}
						else
							return;

						//统计面试评分数据
						$interview_ids = $this->interview_model->get_interview_ids('status', 'completed', 'score IS NOT NULL', NULL);
						if($interview_ids)
						{
							foreach($interview_ids as $interview_id)
							{
								$score = $this->interview_model->get_interview($interview_id, 'score');

								if($score == 0 || empty($score))
									$score = 1;
								else
									$score = round($score, 0, PHP_ROUND_HALF_DOWN);

								if(isset($stat['interview'][$score]))
									$stat['interview'][$score]++;
								else
									$stat['interview'][$score] = 1;
							}
						}

						//处理记录
						for($i = 1; $i <= option('interview_score_total', 5); $i++)
						{
							$chart_category[] = "{$i} 级";

							if(isset($stat['seat'][$i]['available']))
								$chart_data['available'][$i - 1] = $stat['seat'][$i]['available'];
							else
								$chart_data['available'][$i - 1] = 0;

							if(isset($stat['seat'][$i]['assigned']))
								$chart_data['assigned'][$i - 1] = $stat['seat'][$i]['assigned'];
							else
								$chart_data['assigned'][$i - 1] = 0;

							if(isset($stat['interview'][$i]))
								$chart_data['interview'][$i - 1] = $stat['interview'][$i];
							else
								$chart_data['interview'][$i - 1] = 0;
						}

						break;
				}

				$json['category'] = $chart_category;
				$json['legend'] = $chart_legend;
				$json['series'] = $chart_data;
				
				$this->cache->save(IP_CACHE_PREFIX.'_'.IP_INSTANCE_ID.'_stat_'.$chart, $json, 4 * 60 * 60);
			}
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
	 * 搜索代表
	 */
	private function _search_delegate($keyword)
	{
		$this->load->model('delegate_model');
		
		$ids = array();
		$part = explode(' ', $keyword);
		
		//智能选择
		if($part[0] == 'latest')
		{
			if(empty($part[1]))
				$part[1] = 'application_imported';
			
			$event_ids = $this->delegate_model->get_event_ids('event', $part[1]);
			if($event_ids)
			{
				$event_ids = array_slice($event_ids, -5, 5);
				
				foreach($event_ids as $event_id)
				{
					$delegate = $this->delegate_model->get_event($event_id, 'delegate');
					
					if(!in_array($delegate, $ids))
						$ids[] = $delegate;
				}
			}
		}
		
		if(!empty($ids))
			return $ids;
		
		return $this->delegate_model->search_delegate($keyword, 5);
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
				}";
			
			case 'interview_2d':
				$score_standard = option('interview_score_standard', array());
				if(count($score_standard) != 2)
					return "{}";
				
				$names = array();
				foreach($score_standard as $type => $item)
				{
					$names[] = $item['name'];
				}
				
				$this->load->model('interview_model');
				$interview_ids = $this->interview_model->get_interview_ids('score IS NOT NULL', NULL);
				if(!$interview_ids)
					return "{}";
				
				$pow = pow((1000 / count($interview_ids)), 1.6);
				
				return "{
					tooltip: {
						trigger: 'item',
						formatter : function(value) {
							return value[0] + '<br />'
								+ '{$names[0]} ' + value[2][0] + ' / '
								+ '{$names[1]} ' + value[2][1] + '<br />'
								+ value[2][2] + ' 人';
						}
					},
					legend: {
						padding: [20, 5, 5, 5],
						data: ['未过面试', '通过面试']
					},
					xAxis: [
						{
							type: 'value',
							power: 1,
							scale: true,
							name: '{$names[0]}'
						}
					],
					yAxis : [
						{
							type : 'value',
							power: 1,
							scale: true,
							name: '{$names[1]}',
							splitArea: { show: true }
						}
					],
					series : [
						{
							name: '未过面试',
							type: 'scatter',
							symbol: 'circle',
							symbolSize: function(value) {
								return Math.pow(value[2], 1/1.6) * {$pow} + 1
							},
							data: []
						},
						{
							name: '通过面试',
							type: 'scatter',
							symbol: 'circle',
							symbolSize: function(value) {
								return Math.pow(value[2], 1/1.6) * {$pow} + 1
							},
							data: []
						}
					]
				}";
				
			case 'seat_status':
				return "{
					tooltip: {
						trigger: 'axis'
					},
					calculable: true,
					legend: {
						y: 'bottom',
						data: ['未分配席位', '已分配席位', '面试分数段人数']
					},
					xAxis: [
						{
							type: 'category',
							data: []
						}
					],
					yAxis : [
						{
							type: 'value',
							name: '席位数',
							splitArea: { show: true }
						},
						{
							type: 'value',
							name: '区间人数',
							splitLine: { show: false }
						}
					],
					series: [
						{
							name: '未分配席位',
							type: 'bar',
							data: []
						},
						{
							name: '已分配席位',
							type: 'bar',
							data: []
						},
						{
							name: '面试分数段人数',
							type: 'line',
							yAxisIndex: 1,
							data: []
						}
					]
				}";
		}
		
		return '{}';
	}
	
	/**
	 * 返回选择可用选项
	 */
	private function _get_select_option($type)
	{
		$select = array();
		
		switch($type)
		{
			//代表类型
			case 'type':
				$this->load->model('delegate_model');
				
				$application_types = array(
					'delegate',
					'observer',
					'volunteer',
					'teacher'
				);
				
				foreach($application_types as $application_type)
				{
					$select[$application_type] = $this->delegate_model->application_type_text($application_type);
				}
				
				break;
			
			//申请状态
			case 'status':
				$this->load->model('delegate_model');
				
				$status = array(
					'application_imported',
					'review_passed',
					'review_refused',
					'interview_assigned',
					'interview_arranged',
					'interview_completed',
					'waitlist_entered',
					'seat_assigned',
					'invoice_issued',
					'payment_received',
					'locked',
					'quitted'
				);
				
				foreach($status as $status_one)
				{
					$select[$status_one] = $this->delegate_model->status_text($status_one);
				}
				
				break;
				
			//委员会
			case 'committee':
				$this->load->model('committee_model');
				
				$select[0] = '无委员会代表';
				
				$committees = $this->committee_model->get_committee_ids();
				if($committees)
				{
					foreach($committees as $committee)
					{
						$select[$committee] = $this->committee_model->get_committee($committee, 'name');
					}
				}
				
				break;
				
			//用户权限
			case 'role':
				$select = array(
					'reviewer' => '资料审核',
					'dais' => '主席',
					'interviewer' => '面试官',
					'cashier' => '财务管理',
					'administrator' => '会务管理',
					'bureaucrat' => '行政员',
					'zero' => '无权限用户'
				);
				
				break;
			
			default:
				return false;
		}
		
		return $select;
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
					//全局已有席位情况
					$ndids = $this->seat_model->get_delegates_by_seats($asids);
					if(!$ndids)
						$ndids = array(0);
				}
				else
				{
					//全局尚无席位情况
					$ndids = array(0);
				}
				
				$adids = $this->delegate_model->get_delegate_ids('user.id NOT', $ndids);
				if($adids)
					$param['id'] = array_merge($param['id'], $adids);
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