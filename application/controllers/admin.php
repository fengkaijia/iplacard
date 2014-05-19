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
		
		$this->ui->sidebar($sidebar);
		$this->ui->title('控制板');
		$this->load->view('admin/dashboard', $vars);
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
}

/* End of file admin.php */
/* Location: ./application/controllers/admin.php */