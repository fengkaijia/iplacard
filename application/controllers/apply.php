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