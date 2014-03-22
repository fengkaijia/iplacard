<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 后台管理控制器
 * @package iPlacard
 * @since 2.0
 */
class Admin extends CI_Controller
{
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
			'dashboard' => array(icon('dashboard').'控制板', '#ui-dashboard', true, true),
			'task' => array(icon('tasks').'待办事项', '#ui-task', true),
			'spdy' => array(icon('bolt').'快速访问', '#ui-spdy', true),
			'news' => array(icon('globe').'新闻', '#ui-news', true),
		);
		
		$admin = $this->admin_model->get_admin(uid());
		$vars['admin'] = $admin;
		
		//欢迎界面
		if(!user_option('ui_admin_dismiss_welcome', false))
			$vars['welcome'] = true;
		else
			$vars['welcome'] = false;
		
		//统计
		
		//代表数量
		$this->load->model('delegate_model');
		foreach(array('delegate', 'observer', 'volunteer', 'teacher') as $delegate_type)
		{
			$delegate_ids = $this->delegate_model->get_delegate_ids('application_type', $delegate_type);
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
		$has_task = false;
		
		//待审申请
		if($this->admin_model->capable('reviewer'))
		{
			$task_review_ids = $this->delegate_model->get_delegate_ids('status', 'application_imported');
			if($task_review_ids)
			{
				$task['review'] = count($task_review_ids);
				$has_task = true;
			}
			else
				$task['review'] = 0;
		}
		
		//待分配面试
		if($this->admin_model->capable('reviewer'))
		{
			$task_interview_assign_ids = $this->delegate_model->get_delegate_ids('status', 'review_passed');
			if($task_interview_assign_ids)
			{
				$task['interview_assign'] = count($task_interview_assign_ids);
				$has_task = true;
			}
			else
				$task['interview_assign'] = 0;
		}
		
		//待安排时间面试
		if($this->admin_model->capable('interviewer'))
		{
			$this->load->model('interview_model');
			$task_interview_arrange_ids = $this->interview_model->get_interview_ids('interviewer', $admin['id'], 'status', 'assigned');
			if($task_interview_arrange_ids)
			{
				$task['interview_arrange'] = count($task_interview_arrange_ids);
				$has_task = true;
			}
			else
				$task['interview_arrange'] = 0;
		}
		
		//等待面试
		if($this->admin_model->capable('interviewer'))
		{
			$task_interview_do_ids = $this->interview_model->get_interview_ids('interviewer', $admin['id'], 'status', 'arranged');
			if($task_interview_do_ids)
			{
				$task['interview_do'] = count($task_interview_do_ids);

				//最近面试
				$newest = 0;
				foreach($task_interview_do_ids as $interview_id)
				{
					$interview_time = $this->interview_model->get_interview($interview_id, 'schedule_time');

					if($newest > $interview_time || $newest == 0)
						$newest = $interview_time;
				}

				$task['interview_next_schedule'] = $newest;

				$has_task = true;
			}
			else
				$task['interview_do'] = 0;
		}
		
		$vars['task'] = $task;
		
		$vars['has_task'] = $has_task;
		if(!$has_task)
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
				$this->user_model->edit_user_option('ui_admin_dismiss_welcome', true);
				
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
}

/* End of file admin.php */
/* Location: ./application/controllers/admin.php */