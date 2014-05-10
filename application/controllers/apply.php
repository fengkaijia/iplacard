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
	 * 席位信息
	 */
	function seat($action = 'list')
	{
		$this->load->model('seat_model');
		$this->load->model('committee_model');
		$this->load->library('form_validation');
		$this->load->helper('date');
		$this->load->helper('form');
		
		$select_open = option('seat_select_open', true);
		$select_backorder_max = option('seat_backorder_max', 2);
		
		$slids = $this->seat_model->get_delegate_selectability($this->uid);
		if(!$slids)
		{
			$this->ui->alert('面试官尚未为您分配席位。', 'warning', true);
			back_redirect();
			return;
		}
		$selectability_count = count($slids);
		
		if($action == 'select')
		{
			$this->form_validation->set_error_delimiters('<div class="help-block">', '</div>');
		
			$this->form_validation->set_rules('primary', '主席位', 'trim|required|callback__check_selectability[primary]');
			$this->form_validation->set_rules('backorder', '候选席位', "max_count[{$select_backorder_max}]|callback__check_primary_backorder|callback__check_selectability[backorder]");
			$this->form_validation->set_message('max_count', "最多可以选择 {$select_backorder_max} 个席位。");
			$this->form_validation->set_message('_check_selectability', '席位选择不符合设定条件。');
			$this->form_validation->set_message('_check_primary_backorder', '主席位不能同时被选定为候选席位。');

			if($this->form_validation->run() == true)
			{
				$this->load->library('email');
				$this->load->library('parser');
				$this->load->helper('date');

				$select_primary = $this->input->post('primary');
				$select_backorders = $this->input->post('backorder');
				
				//已有主席位
				$original_seat = $this->seat_model->get_delegate_seat($this->uid);
				if($original_seat && $original_seat != $select_primary)
				{
					$original_seat_info = $this->seat_model->get_seat($original_seat);

					//回退原席位
					$this->seat_model->change_seat_status($original_seat, 'available', NULL);
					$this->seat_model->assign_seat($original_seat, NULL);
					
					$this->delegate_model->add_event($this->uid, 'seat_cancelled', array('seat' => $original_seat));
					$this->user_model->add_message($this->uid, '您已经取消席位。');

					//调整原席位选择许可为候选许可
					$original_seat_selectability = $this->seat_model->get_selectability_id('delegate', $this->uid, 'seat', $original_seat);
					if($original_seat_selectability)
					{
						$this->seat_model->edit_selectability(array(
							'primary' => false
						), $original_seat_selectability);
					}
					
					//TODO: 候选席位调整

					//发送邮件
					$email_data = array(
						'uid' => $this->uid,
						'delegate' => $this->delegate['name'],
						'seat' => $original_seat_info['name'],
						'committee' => $this->committee_model->get_committee($original_seat_info['committee'], 'name'),
						'time' => unix_to_human(time()),
					);

					$this->email->to($this->delegate['email']);
					$this->email->subject('席位已经取消');
					$this->email->html($this->parser->parse_string(option('email_seat_released', "您已于 {time} 取消了原席位，取消的席位信息如下：\n\n"
							. "\t席位名称：{seat}\n\n"
							. "\t委员会：{committee}\n\n"
							. "请登录 iPlacard 系统查看申请状态。"), $email_data, true));
					$this->email->send();
					$this->email->clear();
				}
				
				//增加现有席位
				$this->seat_model->change_seat_status($select_primary, 'assigned', true);
				$this->seat_model->assign_seat($select_primary, $this->uid);

				$this->delegate_model->add_event($this->uid, 'seat_selected', array('seat' => $select_primary));
				$this->user_model->add_message($this->uid, '您已经选择新席位。');
				
				$select_primary_info = $this->seat_model->get_seat($select_primary);
				
				//候选席位
				$backorder_add = array();
				$backorder_remove = array();
				
				$original_backorder = $this->seat_model->get_delegate_backorder($this->uid);
				if($original_backorder)
					$backorder_remove = $this->seat_model->get_seats_by_backorders($original_backorder);
				
				$select_backorder_info = array();
				foreach($select_backorders as $select_backorder)
				{
					//检查席位延期请求是否存在
					if(($key = array_search($select_backorder, $backorder_remove)) !== false)
					{
					    unset($backorder_remove[$key]);
					}
					elseif(!in_array($select_backorder, $backorder_add))
					{
						$backorder_add[] = $select_backorder;
					}
					
					$select_backorder_info[] = $this->seat_model->get_seat($select_backorder);
				}
				
				//处理延期请求
				if(!empty($backorder_remove))
				{
					//取消旧有延期请求
					foreach($backorder_remove as $one)
					{
						$old_backorder_id = $this->seat_model->get_backorder_id('delegate', $this->uid, 'status', 'pending', 'seat', $one);
						
						if($old_backorder_id)
							$this->seat_model->change_backorder_status($old_backorder_id, 'cancelled');
						
						$this->delegate_model->add_event($this->uid, 'backorder_cancelled', array('backorder' => $old_backorder_id));
					}
				}
				
				if(!empty($backorder_add))
				{
					//增加新延期请求
					foreach($backorder_add as $one)
					{
						$new_backorder_id = $this->seat_model->add_backorder($one, $this->uid);
					}
					
					$this->delegate_model->add_event($this->uid, 'backorder_added', array('backorder' => $new_backorder_id));
				}
				
				//初次选择
				if($this->delegate['status'] == 'seat_assigned')
				{
					$this->load->library('invoice');
					
					$this->delegate_model->change_status($this->uid, 'invoice_issued');
					
					//生成帐单
					$this->invoice->title(option('invoice_title_fee_delegate', option('invoice_title_fee', '参会会费')));
					$this->invoice->to($this->uid);
					$this->invoice->item(option('invoice_title_fee_delegate', option('invoice_title_fee', '参会会费')), option('invoice_amount_delegate', 1000), option('invoice_item_fee_delegate', option('invoice_item_fee', array())));
					$this->invoice->due_time(time() + option('invoice_due_fee', 15) * 24 * 60 * 60);
					
					$this->invoice->trigger('overdue', 'release_seat', array('delegate' => $this->uid));
					$this->invoice->trigger('receive', 'change_status', array('delegate' => $this->uid, 'status' => 'payment_received'));
					
					$this->invoice->generate();
				}
				
				//邮件通知
				$primary_text = sprintf("\t%s（%s）", $select_primary_info['name'], $this->committee_model->get_committee($select_primary_info['committee'], 'name'));
				
				$backorder_texts = array();
				foreach($select_backorder_info as $info)
				{
					$backorder_texts[] = sprintf("\t%s（%s）", $info['name'], $this->committee_model->get_committee($info['committee'], 'name'));
				}
				
				if(empty($backorder_texts))
					$backorder_text = "\t无候选席位";
				else
					$backorder_text = join("\n\n", $backorder_texts);
				
				$email_data = array(
					'uid' => $this->uid,
					'delegate' => $this->delegate['name'],
					'primary_seat' => $primary_text,
					'backorder_seat' => $backorder_text,
					'time' => unix_to_human(time()),
				);

				$this->email->to($this->delegate['email']);
				$this->email->subject($this->delegate['status'] == 'seat_assigned' ? '席位已经选择' : '席位选择已经调整');
				if($this->delegate['status'] == 'seat_assigned')
				{
					$this->email->html($this->parser->parse_string(option('email_seat_selected', "您已于 {time} 选定了您的席位，选定的席位为：\n\n"
							. "{primary_seat}\n\n"
							. "选定的候选席位为：\n\n"
							. "{backorder_seat}\n\n"
							. "请登录 iPlacard 系统查看申请状态。"), $email_data, true));
				}
				else
				{
					$this->email->html($this->parser->parse_string(option('email_seat_changed', "您已于 {time} 调整了您的席位选择，调整后的席位选择为：\n\n"
							. "{primary_seat}\n\n"
							. "调整后的候选席位为：\n\n"
							. "{backorder_seat}\n\n"
							. "请登录 iPlacard 系统查看申请状态。"), $email_data, true));
				}
				$this->email->send();
				$this->email->clear();
				
				//短信通知
				if(option('sms_enabled', false))
				{
					$this->load->model('sms_model');
					$this->load->library('sms');

					$this->sms->to($this->uid);
					if($this->delegate['status'] == 'seat_assigned')
						$this->sms->message('您已选定席位，请登录 iPlacard 系统查看申请状态。');
					else
						$this->sms->message('您已调整您的席位选择，请登录 iPlacard 系统查看申请状态。');
					$this->sms->queue();
				}
				
				$this->ui->alert('您的席位选择已经保存。', 'success');
			}
		}
		
		//选定席位
		$selected_seat = $this->seat_model->get_delegate_seat($this->uid);
		$selected_backorder = $this->seat_model->get_delegate_backorder($this->uid);
		if($selected_backorder)
			$selected_backorder = $this->seat_model->get_seats_by_backorders($selected_backorder);
		
		//席位选择
		$selectability_primary = array();
		$selectability_primary_count = 0;
		
		$selectabilities = array();
		$committees = array();
		foreach($slids as $slid)
		{
			$selectability = $this->seat_model->get_selectability($slid);
			
			//主要席位统计
			if($selectability['primary'])
			{
				$selectability_primary[] = intval($selectability['seat']);
				$selectability_primary_count++;
			}
			
			//席位详细信息
			$selectability['seat'] = $this->seat_model->get_seat($selectability['seat']);
			
			$committee = $selectability['seat']['committee'];
			if(!isset($committees[$committee]))
				$committees[$committee] = $this->committee_model->get_committee($committee);
			
			$selectabilities[] = $selectability;
		}
		
		$vars = array(
			'committees' => $committees,
			'selectabilities' => $selectabilities,
			'selectability_count' => $selectability_count,
			'selectability_primary' => $selectability_primary,
			'selectability_primary_count' => $selectability_primary_count,
			'selected_seat' => $selected_seat,
			'selected_backorder' => $selected_backorder,
			'select_open' => $select_open,
			'select_backorder_max' => $select_backorder_max
		);
		
		$this->ui->now('seat');
		$this->ui->title('席位');
		$this->load->view('delegate/seat', $vars);
	}
	
	/**
	 * 帐单信息
	 */
	function invoice($id = '')
	{
		$this->load->model('invoice_model');
		$this->load->library('invoice');
		$this->load->helper('form');
		
		//显示代表第一份未支付帐单
		if(empty($id))
		{
			$invoices = $this->invoice_model->get_delegate_invoices($this->uid, true);
			
			if(!$invoices)
			{
				$this->ui->alert('您当前没有帐单需要处理。', 'success', true);
				back_redirect();
				return;
			}
			else
			{
				$id = $invoices[0];
			}
		}
		
		$this->invoice->load($id);
		
		if($this->invoice->get('delegate') != $this->uid)
		{
			$this->ui->alert('无权访问帐单信息。', 'danger', true);
			back_redirect();
			return;
		}
		
		$vars['invoice_html'] = $this->invoice->display();
		$vars['due_time'] = $this->invoice->get('due_time');
		$vars['unpaid'] = $this->invoice_model->is_unpaid($id);
		
		$this->ui->now('invoice');
		$this->ui->title('帐单');
		$this->load->view('delegate/invoice_item', $vars);
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
			case 'interview_arranged':
			case 'interview_completed':
				$w['interview'] = '面试已通过';
				if($status == 'interview_assigned')
					$w['interview'] = '已分配面试';
				elseif($status == 'interview_arranged')
					$w['interview'] = '已安排面试';
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
	
	/**
	 * 席位可选择性检查回调函数
	 */
	function _check_selectability($array, $type = 'primary')
	{
		$this->load->model('seat_model');
		
		if(is_string($array))
			$array = array($array);
		
		$selectabilities = $this->seat_model->get_delegate_selectability($this->uid, $type == 'primary' ? true : false, false, 'seat');
		if(!$selectabilities)
			return false;
		
		foreach($array as $one)
		{
			if(!in_array($one, $selectabilities))
				return false;
		}
		
		return true;
	}
	
	/**
	 * 主席位为候选席位检查回调函数
	 */
	function _check_primary_backorder($array)
	{
		$primary = $this->input->post('primary');
		
		if(in_array($primary, $array))
			return false;
		
		return true;
	}
}

/* End of file apply.php */
/* Location: ./application/controllers/apply.php */