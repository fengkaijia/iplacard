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
		$this->load->helper('text');
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
	 * 申请首页
	 */
	function status()
	{
		//欢迎界面
		if(!user_option('ui_dismiss_welcome', false))
			$vars['welcome'] = true;
		else
			$vars['welcome'] = false;
		
		//状态信息
		$application_type_function = "_status_{$this->delegate['application_type']}";
		$status_info = $this->$application_type_function();
		$vars += $status_info;
		
		//席位
		$this->load->model('seat_model');
		
		$seat = array();
		
		$sid = $this->seat_model->get_seat_id('delegate', $this->uid);
		if($sid)
		{
			$this->load->model('committee_model');
			
			$seat = $this->seat_model->get_seat($sid);
			$seat['committee'] = $this->committee_model->get_committee($seat['committee']);
		}
		
		$vars['seat'] = $seat;
		
		//团队
		$group = array();
		if($this->delegate['group'])
		{
			$this->load->model('group_model');
			
			$group = $this->group_model->get_group($this->delegate['group']);
		}
		
		$vars['group'] = $group;
		
		//RSS
		$this->load->library('feed');
		
		$this->feed->set_feed_url(option('feed_url', 'http://iplacard.com/feed/'));
		
		$feed_enable = false;
		if($this->feed->parse())
		{
			$feed_enable = true;
			$vars['feed'] = $this->feed->get_feed(2);
		}
		
		$vars['feed_enable'] = $feed_enable;
		
		$vars['delegate'] = $this->delegate;
		
		$this->ui->now('status');
		$this->ui->title('申请');
		$this->load->view('delegate/status', $vars);
	}
	
	/**
	 * 个人信息
	 */
	function profile()
	{
		$delegate = $this->delegate;
		
		$pids = $this->delegate_model->get_profile_ids('delegate', $this->uid);
		if($pids)
		{
			foreach($pids as $pid)
			{
				$one = $this->delegate_model->get_profile($pid);
				$delegate[$one['name']] = $one['value'];
			}
		}
		
		$vars['delegate'] = $delegate;
		
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
	
	/**
	 * AJAX
	 */
	function ajax($action = '')
	{
		$json = array();
		
		if($action == 'dismiss_welcome')
		{
			if($this->user_model->is_delegate($this->uid))
			{
				$this->user_model->edit_user_option('ui_dismiss_welcome', true);
				
				$json['result'] = true;
			}
			else
			{
				$json['result'] = false;
			}
		}
		
		echo json_encode($json);
	}
	
	/**
	 * 代表申请状态信息
	 */
	function _status_delegate()
	{
		$status = $this->delegate['status'];
		
		$w['signin'] = '提交申请';
		$w['admit'] = '等待通过审核';
		$w['interview'] = '等待安排面试';
		$w['seat'] = '等待分配席位';
		$w['pay'] = '等待支付会费';
		$w['lock'] = '等待确认完成';
		
		//是否存在二次面试
		$this->load->model('interview_model');
		$interviews = $this->interview_model->get_interview_ids('delegate', $this->uid, 'status', 'failed');
		if($interviews && count($interviews) == 1)
			$w['interview'] = '等待二次面试';
		
		//进度条状态文字
		switch($status)
		{
			case 'locked':
				$w['lock'] = '申请已完成';
			case 'payment_received':
				$w['pay'] = '已支付会费';
			case 'invoice_issued':
			case 'seat_assigned':
				$w['seat'] = '席位已分配';
			case 'interview_assigned':
				$w['interview'] = '已分配面试';
			case 'interview_arranged':
				$w['interview'] = '已安排面试';
			case 'interview_completed':
				$w['interview'] = '面试已通过';
			case 'review_passed':
				$w['admit'] = '初审已通过';
			case 'application_imported':
				$w['signin'] = '申请已录入';
				break;
			case 'review_refused':
				$w['signin'] = '申请已录入';
				$w['admit'] = '材料初审未通过';
				$w['interview'] = NULL;
				$w['seat'] = NULL;
				$w['pay'] = NULL;
				$w['lock'] = NULL;
				break;
			case 'moved_to_waiting_list':
				$w['signin'] = '申请已录入';
				$w['admit'] = '初审已通过';
				$w['interview'] = '面试未通过';
				$w['seat'] = NULL;
				$w['pay'] = NULL;
				$w['lock'] = '队列等待中';
				break;
		}
		if($status == 'quitted')
			$w['lock'] = '已经退会';
		
		//进度条状态显示
		switch($status)
		{
			case 'application_imported':
			case 'review_refused':
				$current = 'admit';
				break;
			case 'review_passed':
			case 'interview_assigned':
			case 'interview_arranged':
			case 'interview_completed':
				$current = 'interview';
				break;
			case 'seat_assigned':
				$current = 'seat';
				break;
			case 'invoice_issued':
			case 'payment_received':
				$current = 'pay';
				break;
			case 'locked':
			case 'moved_to_waiting_list':
				$current = 'lock';
				break;
			case 'quitted':
				$current = 'quit';
				break;
		}
		
		//生成状态组信息
		foreach(array('signin', 'admit', 'interview', 'seat', 'pay', 'lock') as $one)
		{
			if(!empty($w[$one]))
			{
				if($current == $one || ($one == 'lock' && $status == 'quitted'))
					$wizard[] = array('text' => $w[$one], 'intro' => $this->_status_intro($one), 'current' => true);
				else
					$wizard[] = array('text' => $w[$one], 'intro' => $this->_status_intro($one), 'current' => false);
			}
		}
		
		$return['wizard'] = $wizard;
		$return['current'] = $current;
		return $return;
	}
	
	/**
	 * 观察员申请状态信息
	 */
	function _status_observer()
	{
		$status = $this->delegate['status'];
		
		$w['signin'] = '提交申请';
		$w['admit'] = '等待通过审核';
		$w['pay'] = '等待支付会费';
		$w['lock'] = '等待确认完成';
		
		//进度条状态文字
		switch($status)
		{
			case 'locked':
				$w['lock'] = '申请已完成';
			case 'payment_received':
				$w['pay'] = '已支付会费';
			case 'invoice_issued':
			case 'review_passed':
				$w['admit'] = '审核已通过';
			case 'application_imported':
				$w['signin'] = '申请已录入';
				break;
			case 'review_refused':
				$w['signin'] = '申请已录入';
				$w['admit'] = '材料审核未通过';
				$w['pay'] = NULL;
				$w['lock'] = NULL;
				break;
		}
		if($status == 'quitted')
			$w['lock'] = '已经退会';
		
		//进度条状态显示
		switch($status)
		{
			case 'application_imported':
			case 'review_refused':
				$current = 'admit';
				break;
			case 'review_passed':
			case 'interview_assigned':
			case 'interview_arranged':
			case 'interview_completed':
				$current = 'interview';
				break;
			case 'seat_assigned':
				$current = 'seat';
				break;
			case 'invoice_issued':
			case 'payment_received':
				$current = 'pay';
				break;
			case 'locked':
			case 'moved_to_waiting_list':
				$current = 'lock';
				break;
			case 'quitted':
				$current = 'quit';
				break;
		}
		
		//生成状态组信息
		foreach(array('signin', 'admit', 'pay', 'lock') as $one)
		{
			if(!empty($w[$one]))
			{
				if($current == $one || ($one == 'lock' && $status == 'quitted'))
					$wizard[] = array('text' => $w[$one], 'intro' => $this->_status_intro($one), 'current' => true);
				else
					$wizard[] = array('text' => $w[$one], 'intro' => $this->_status_intro($one), 'current' => false);
			}
		}
		
		$return['wizard'] = $wizard;
		$return['current'] = $current;
		return $return;
	}
	
	/**
	 * 面试官申请状态信息
	 */
	function _status_volunteer()
	{
		$status = $this->delegate['status'];
		
		$w['signin'] = '提交申请';
		$w['admit'] = '等待通过审核';
		$w['lock'] = '等待完成';
		
		//进度条状态文字
		switch($status)
		{
			case 'locked':
				$w['lock'] = '申请已完成';
				$w['admit'] = '审核已通过';
			case 'application_imported':
				$w['signin'] = '申请已录入';
				break;
			case 'review_refused':
				$w['signin'] = '申请已录入';
				$w['admit'] = '材料审核未通过';
				$w['lock'] = NULL;
				break;
		}
		if($status == 'quitted')
			$w['lock'] = '已经退会';
		
		//进度条状态显示
		switch($status)
		{
			case 'application_imported':
				$current = 'admit';
				break;
			case 'review_refused':
				$current = 'admit';
				break;
			case 'locked':
				$current = 'lock';
				break;
			case 'quitted':
				$current = 'quit';
				break;
		}
		
		//生成状态组信息
		foreach(array('signin', 'admit', 'lock') as $one)
		{
			if(!empty($w[$one]))
			{
				if($current == $one || ($one == 'lock' && $status == 'quitted'))
					$wizard[] = array('text' => $w[$one], 'intro' => $this->_status_intro($one), 'current' => true);
				else
					$wizard[] = array('text' => $w[$one], 'intro' => $this->_status_intro($one), 'current' => false);
			}
		}
		
		$return['wizard'] = $wizard;
		$return['current'] = $current;
		return $return;
	}
	
	/**
	 * 申请状态介绍文字
	 */
	function _status_intro($status = '')
	{
		if(empty($status))
			$status = $this->delegate['status'];
		
		switch($status)
		{
			case 'signin':
				$intro = '<p>您的申请已经成功导入到 iPlacard。</p>';
				break;
			case 'application_imported':
			case 'review_refused':
			case 'admit':
				$intro = '<p>您的申请已经成功导入到 iPlacard，我们将在近期内审核您提交的申请材料。在此期间，请核对您的申请材料，如果内容有误，您将可以修改部分数据。</p>'
					. '<p>材料审核通过之后，您将进入下一申请流程。</p>';
				break;
			case 'review_passed':
			case 'interview_assigned':
			case 'interview_arranged':
			case 'interview_completed':
			case 'interview':
				$intro = '<p>在面试阶段中，我们将会根据您的委员会意向指派一位面试官，他将与您取得联系并且确定面试时间和面试方式。</p>'
					. '<p>请尽量为面试预留足够的时间，如果您无法在约定的时间进行面试，请联系您的面试官，他将会重新为您安排面试时间。</p>'
					. '<p>如果面试没有通过，您将进入等待队列。</p>';
				break;
			case 'seat_assigned':
			case 'seat':
				$intro = '<p>面试通过之后，面试官将会根据您的面试表现为您分配适合的席位选择。通常情况下，面试官将在完成面试后立即为您分配合适的席位选择。</p>'
					. '<p>席位分配后，您将可以在席位信息页面选择并确认席位；如果您认为分配的席位不适合您，请联系您的面试官，他将可以为您重新分配席位。</p>';
				break;
			case 'invoice_issued':
			case 'payment_received':
			case 'pay':
				$intro = '<p>您将可以通过银行转账、网银支付或者邮政汇款完成会费支付，我们将在收到汇款之后确定完成支付。</p>';
				break;
			case 'locked':
			case 'moved_to_waiting_list':
			case 'lock':
				if($this->delegate['status'] == 'moved_to_waiting_list')
					$intro = '<p>您已经移动到等待队列。我们将会通过邮件通知您后续事宜，请频繁检查您的电子邮箱并定期登录 iPlacard 了解更新。</p>';
				else
					$intro = '<p>您已经完成申请流程。我们将会通过邮件通知您后续事宜，请频繁检查您的电子邮箱并定期登录 iPlacard 了解更新。</p>';
				break;
			case 'quitted':
			case 'quit':
				$intro = '<p>您已退会，帐号即将删除，如果有任何疑问，请立即联系管理员。</p>';
				break;
			default:
				$intro = '';
		}
		
		return $intro;
	}
}

/* End of file apply.php */
/* Location: ./application/controllers/apply.php */