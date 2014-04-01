<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 申请者界面控制器
 * @package iPlacard
 * @since 2.0
 */
class Apply extends CI_Controller
{
	/**
	 * @var array 当前登录代表UID
	 */
	var $uid = 0;
	
	/**
	 * @var array 当前登录代表信息
	 */
	var $delegate = array();
	
	function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->library('ui', array('side' => 'delegate'));
		$this->load->model('admin_model');
		$this->load->model('delegate_model');
		$this->load->helper('date');
		
		//检查登录情况
		if(!is_logged_in())
		{
			login_redirect();
			return;
		}
		
		//检查权限
		if(!$this->user_model->is_delegate(uid()))
		{
			redirect('');
			return;
		}
		
		//当前代表信息
		$this->uid = uid();
		$this->delegate = $this->delegate_model->get_delegate($this->uid);
		$this->delegate['application_type_text'] = $this->delegate_model->application_type_text($this->delegate['application_type']);
		$this->delegate['status_text'] = $this->delegate_model->status_text($this->delegate['status']);
		$this->delegate['status_code'] = $this->delegate_model->status_code($this->delegate['status']);
	}
	
	/**
	 * 个人信息
	 */
	function profile()
	{
		$this->load->model('committee_model');
		
		$profile = $this->delegate_model->get_delegate($this->uid);
		
		$pids = $this->delegate_model->get_profile_ids('delegate', $this->uid);
		if($pids)
		{
			foreach($pids as $pid)
			{
				$one = $this->delegate_model->get_profile($pid);
				$profile[$one['name']] = $one['value'];
			}
		}
		
		$vars['profile'] = $profile;
		$vars['delegate'] = $this->delegate;
		
		$this->ui->now('profile');
		$this->ui->title('个人信息');
		$this->load->view('delegate/profile', $vars);
	}
	
	/**
	 * 代表团信息
	 */
	function group()
	{
		$this->load->model('group_model');
		$this->load->model('committee_model');
		$this->load->model('seat_model');
		
		if(empty($this->delegate['group']))
		{
			$this->ui->alert('当前您不是任何代表团成员，请与管理员联系。', 'warning', true);
			back_redirect();
			return;
		}
		
		$group = $this->group_model->get_group($this->delegate['group']);
		if(!$group)
		{
			$this->ui->alert('代表团不存在或已经解散。', 'warning', true);
			back_redirect();
			return;
		}
		
		$vars['group'] = $group;
		
		//是否为领队
		$head_delegate = false;
		if($group['head_delegate'] == $this->uid)
			$head_delegate = true;
		
		$vars['head_delegate'] = $head_delegate;
		
		$delegates = array();
		$ids = $this->delegate_model->get_group_delegates($group['id']);
		foreach($ids as $id)
		{
			$group_delegate = $this->delegate_model->get_delegate($id);
			
			$group_delegate['application_type_text'] = $this->delegate_model->application_type_text($group_delegate['application_type']);
			$group_delegate['status_text'] = $this->delegate_model->status_text($group_delegate['status']);
			switch($this->delegate_model->status_code($group_delegate['status']))
			{
				case 9:
					$group_delegate['status_class'] = 'success';
					break;
				case 10:
					$group_delegate['status_class'] = 'warning';
					break;
				default:
					$group_delegate['status_class'] = 'primary';
			}
			
			//如果是领队载入详细资料
			if($id == $group['head_delegate'])
			{
				$pids = $this->delegate_model->get_profile_ids('delegate', $id);
				if($pids)
				{
					foreach($pids as $pid)
					{
						$one = $this->delegate_model->get_profile($pid);
						$group_delegate[$one['name']] = $one['value'];
					}
				}
			}
			
			//席位信息
			$sid = $this->seat_model->get_seat_id('delegate', $id);
			if($sid)
			{
				$group_delegate['seat'] = $this->seat_model->get_seat($sid);
				$group_delegate['committee'] = $this->committee_model->get_committee($group_delegate['seat']['committee']);
			}
			
			$delegates[$id] = $group_delegate;
		}
		
		$vars['delegates'] = $delegates;
		
		$vars['delegate'] = $this->delegate;
		
		$this->ui->now('group');
		$this->ui->title('代表团');
		$this->load->view('delegate/group', $vars);
	}
	
	/**
	 * 面试信息
	 */
	function interview()
	{
		$this->load->model('interview_model');
		
		$interviews = array();
		
		$iids = $this->interview_model->get_interview_ids('delegate', $this->uid);
		if(!$iids)
		{
			$this->ui->alert('当前您尚无面试安排。', 'warning', true);
			back_redirect();
			return;
		}
		
		$iids = array_reverse($iids);
		
		foreach($iids as $interview_id)
		{
			$interview = $this->interview_model->get_interview($interview_id);
			
			$interview['status_text'] = $this->interview_model->status_text($interview['status']);
			
			$interview['interviewer'] = $this->admin_model->get_admin($interview['interviewer']);
			if(!empty($interview['interviewer']['committee']))
			{
				$this->load->model('committee_model');

				$interview['interviewer']['committee'] = $this->committee_model->get_committee($interview['interviewer']['committee']);
			}
			
			switch($interview['status'])
			{
				case 'completed':
				case 'exempted':
					$status_class = 'success';
					break;
				case 'failed':
					$status_class = 'danger';
					break;
				case 'cancelled':
					$status_class = 'muted';
					break;
				default:
					$status_class = 'primary';
			}
			$interview['status_class'] = $status_class;

			$interviews[$interview['id']] = $interview;
		}

		$vars['current_interview'] = $this->interview_model->get_current_interview_id($this->uid);
		$vars['interviews'] = $interviews;
		$vars['delegate'] = $this->delegate;
		
		$this->ui->now('interview');
		$this->ui->title('面试');
		$this->load->view('delegate/interview', $vars);
	}
}

/* End of file apply.php */
/* Location: ./application/controllers/apply.php */