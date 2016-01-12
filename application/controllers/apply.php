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
	function status($action = 'view')
	{
		$this->load->model('seat_model');
		$this->load->library('form_validation');
		$this->load->helper('form');
		
		//欢迎界面
		if(!user_option('ui_dismiss_welcome', false))
			$vars['welcome'] = true;
		else
			$vars['welcome'] = false;
		
		//席位
		$seat = array();
		
		$sid = $this->seat_model->get_delegate_seat($this->uid);
		if($sid)
		{
			$this->load->model('committee_model');
			
			$seat = $this->seat_model->get_seat($sid);
			$seat['committee'] = $this->committee_model->get_committee($seat['committee']);
		}
		
		$vars['seat'] = $seat;
		
		//锁定
		$lock_open = false;
		if((($this->delegate['status'] == 'payment_received' || $this->delegate['status'] == 'seat_selected') || ($this->delegate['status'] == 'seat_assigned' && option('seat_mode', 'assign'))) && option('seat_lock_open', true) && $sid)
		{
			$lock_open = true;
			
			$backorders = array();
			
			$backorder_ids = $this->seat_model->get_delegate_backorder($this->uid);
			if($backorder_ids)
			{
				$this->load->model('committee_model');
				
				foreach($backorder_ids as $backorder_id)
				{
					$backorder = $this->seat_model->get_backorder($backorder_id);
					$backorder['seat'] = $this->seat_model->get_seat($backorder['seat']);
					$backorder['seat']['committee'] = $this->committee_model->get_committee($backorder['seat']['committee']);
					
					$backorders[] = $backorder;
				}
			}
			
			$vars['backorders'] = $backorders;
			
			//确认锁定
			$this->form_validation->set_error_delimiters('<div class="help-block">', '</div>');
			
			$this->form_validation->set_rules('password', '密码', 'trim|required|callback__check_password[密码验证错误未能锁定席位，请重新尝试。]');
			$this->form_validation->set_message('_check_password', '密码有误，请重新输入。');
			
			if($action == 'lock' && $this->form_validation->run() == true)
			{
				//记录锁定现有席位
				$this->delegate_model->add_event($this->uid, 'seat_locked', array('seat' => $sid));
				
				//取消席位候补请求
				if($backorder_ids)
				{
					foreach($backorder_ids as $backorder_id)
					{
						$this->seat_model->change_backorder_status($backorder_id, 'cancelled');
						
						$this->delegate_model->add_event($this->uid, 'backorder_cancelled', array('backorder' => $backorder_id));
					}
				}
				
				//更改申请状态
				$this->delegate_model->change_status($this->uid, 'locked');
				
				$this->delegate_model->add_event($this->uid, 'locked');
				
				$this->user_model->add_message($this->uid, "您的申请已获确认锁定。");
				
				//邮件通知
				$this->load->library('email');
				$this->load->library('parser');
				$this->load->helper('date');
				
				$data = array(
					'uid' => $this->uid,
					'delegate' => $this->delegate['name'],
					'time' => unix_to_human(time())
				);
				
				$this->email->to($this->delegate['email']);
				$this->email->subject('申请已经完成');
				$this->email->html($this->parser->parse_string(option('email_application_locked', '感谢参与申请，您的申请流程已经于 {time} 锁定完成，请登录 iPlacard 查看申请状态。'), $data, true));
				$this->email->send();
				
				//短信通知
				if(option('sms_enabled', false))
				{
					$this->load->model('sms_model');
					$this->load->library('sms');

					$this->sms->to($this->uid);
					$this->sms->message('感谢参与申请，您的申请流程已经完成，请登录 iPlacard 查看申请状态。');
					$this->sms->queue();
				}

				$this->system_model->log('application_locked', array());

				$this->ui->alert("您的申请已获确认锁定。", 'success', true);
				redirect('apply/status');
				return;
			}
		}
		$vars['lock_open'] = $lock_open;
		
		//状态信息
		$application_fee_amount = option("invoice_amount_{$this->delegate['application_type']}", 0);
		
		$application_fee = 'nonfee';
		if($application_fee_amount > 0)
			$application_fee = 'fee';
		
		$application_seat = 'nonseat';
		if($this->delegate['application_type'] == 'delegate' && option('seat_enabled', true))
			$application_seat = 'seat';
		
		$application_interview = 'noninterview';
		if(option("interview_{$this->delegate['application_type']}_enabled", option('interview_enabled', $this->delegate['application_type'] == 'delegate')))
			$application_interview = 'interview';
		
		$application_type_function = "_status_{$application_seat}_{$application_fee}_{$application_interview}";
		$status_info = $this->$application_type_function();
		$vars += $status_info;
		
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
		
		$seat_mode = option('seat_mode', 'select');
		$vars['seat_mode'] = $seat_mode;
		
		$vars['delegate'] = $this->delegate;
		
		$this->ui->now('status');
		$this->ui->title('申请');
		$this->load->view('delegate/status', $vars);
	}
	
	/**
	 * 个人信息
	 */
	function profile($action = 'view')
	{
		$this->load->library('form_validation');
		$this->load->helper('form');
		
		//附加信息
		$addition_items = option('profile_addition_general', array()) + option("profile_addition_{$this->delegate['application_type']}", array());

		foreach($addition_items as $addition_name => $addition_item)
		{
			if(isset($addition_item['max']) && !empty($addition_item['max']))
			{
				$this->load->library('parser');

				foreach($addition_item['max'] as $item => $max)
				{
					$current = $this->delegate_model->get_profile_ids('name', "addition_{$addition_name}", 'value', json_encode($item));
					if(!$current)
						$current = array();

					$addition_items[$addition_name]['current'][$item] = count($current);
				}
			}
		}

		$vars['addition'] = $addition_items;

		$invoice_notice = false;
		foreach($addition_items as $item)
		{
			if(isset($item['invoice']) && !empty($item['invoice']))
				$invoice_notice = true;
		}
		$vars['invoice_notice'] = $invoice_notice;
		
		if($action == 'edit' && !empty($addition_items))
		{
			$this->form_validation->set_error_delimiters('<div class="help-block">', '</div>');
			$this->form_validation->set_rules('提交验证', 'edit', 'isset');
			
			//输入检查
			foreach($addition_items as $name => $item)
			{
				if($item['required'])
				{
					$this->form_validation->set_rules($item['title'], "addition_$name", 'isset');
				}
			}
			
			if($this->form_validation->run() == true)
			{
				$edited_ids = array();
				foreach($addition_items as $name => $item)
				{
					//项目未启用
					if(isset($item['enabled']) && !$item['enabled'])
						continue;
					
					//账单项目且已经填写
					if(isset($item['invoice']) && $this->delegate_model->get_profile_id('delegate', $this->uid, 'name', "addition_$name"))
						continue;
					
					$post = $this->input->post("addition_$name");

					//已选项已停用并不更改
					if($item['type'] == 'choice' && !$post)
						continue;
					
					switch($item['type'])
					{
						//选项
						case 'checkbox':
							if($post)
								$new = true;
							else
								$new = false;
							break;
						
						//单选框
						case 'choice':
							if(in_array($post, array_keys($item['item'])))
								$new = $post;
							else
								$new = $item['default'];
							break;
							
						//文本输入
						case 'text':
						case 'textarea':
							$new = trim($post);
							break;
					}
					
					$original_id = $this->delegate_model->get_profile_id('delegate', $this->uid, 'name', "addition_$name");
					
					if(!$original_id)
					{
						$edited_ids[] = $this->delegate_model->add_profile($this->uid, "addition_$name", $new);
					}
					elseif($new != $this->delegate_model->get_profile($original_id, 'value'))
					{
						$this->delegate_model->edit_profile(array('value' => $new), $original_id);
						$edited_ids[] = $original_id;
					}
					
					//账单项目
					if(isset($item['invoice']))
					{
						foreach($item['invoice'] as $invoice)
						{
							//生成账单
							if($invoice['on'] == $new)
							{
								$this->load->library('invoice');
								
								//调整状态
								if(isset($invoice['status']) && !empty($invoice['status']))
									$this->delegate_model->change_status($this->uid, $invoice['status']);
								
								$this->invoice->title(!empty($invoice['title']) ? $invoice['title'] : '附加账单');
								$this->invoice->to($this->uid);
								$this->invoice->item(!empty($invoice['title']) ? $invoice['title'] : '附加账单', $invoice['amount'], isset($invoice['detail']) ? $invoice['detail'] : array());
								$this->invoice->due_time(time() + (isset($invoice['due']) ? intval($invoice['due']) : option('invoice_due_fee', 15)) * 24 * 60 * 60);
								
								if(isset($invoice['trigger']) && !empty($invoice['trigger']))
								{
									foreach($invoice['trigger'] as $trigger)
									{
										$this->invoice->trigger($trigger['on'], $trigger['action'], array_merge(array('delegate' => $this->uid), $trigger['data']));
									}
								}
								
								$this->invoice->generate();
								
								$this->ui->alert('新的账单已经生成，请访问账单页面查看。', 'info');
							}
						}
					}
				}
				
				if(!empty($edited_ids))
				{
					$this->system_model->log('profile_addition_edited', array('profile' => $edited_ids));
					
					$this->ui->alert('您的附加信息变更已经保存。', 'success');
				}
				else
				{
					$this->ui->alert('没有附加信息需要更新。', 'info');
				}
			}
		}

		//获取代表信息
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
				case 100:
					$group_delegate['status_class'] = 'danger';
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
		
		if($this->delegate['status'] == 'quitted' || $this->delegate['status'] == 'locked')
			$select_open = false;
		
		$seat_mode = option('seat_mode', 'select');
		
		if($seat_mode == 'select')
			$slids = $this->seat_model->get_delegate_selectability($this->uid);
		else
			$slids = array();
		
		$selectability_count = count($slids);
		
		if($seat_mode == 'select' ? !$slids : !$this->seat_model->get_delegate_seat($this->uid))
		{
			$this->ui->alert('面试官或管理员尚未为您分配席位。', 'warning', true);
			back_redirect();
			return;
		}
		
		$vars = array(
			'delegate' => $this->delegate,
			'seat_mode' => $seat_mode,
			'selectability_count' => $selectability_count,
			'select_open' => $select_open,
			'select_backorder_max' => $select_backorder_max
		);
		
		if($action == 'select')
		{
			$this->form_validation->set_error_delimiters('<div class="help-block">', '</div>');
		
			$this->form_validation->set_rules('primary', '主席位', 'trim|required|callback__check_selectability[primary]|callback__check_availability');
			$this->form_validation->set_rules('backorder', '候补席位', "max_count[{$select_backorder_max}]|callback__check_primary_backorder|callback__check_selectability[backorder]");
			$this->form_validation->set_message('max_count', "最多可以选择 {$select_backorder_max} 个席位。");
			$this->form_validation->set_message('_check_selectability', '席位选择不符合设定条件。');
			$this->form_validation->set_message('_check_primary_backorder', '主席位不能同时被选定为候补席位。');
			$this->form_validation->set_message('_check_availability', '主席位已被分配不可选择。');

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

					//调整原席位选择许可为候补许可
					if(option('seat_revert_original', false))
					{
						$original_seat_selectability = $this->seat_model->get_selectability_id('delegate', $this->uid, 'seat', $original_seat);
						if($original_seat_selectability)
						{
							$this->seat_model->edit_selectability(array(
								'primary' => false
							), $original_seat_selectability);
						}
					}
					
					//TODO: 候补席位调整

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
				
				//候补席位
				$backorder_add = array();
				$backorder_remove = array();
				
				$original_backorder = $this->seat_model->get_delegate_backorder($this->uid);
				if($original_backorder)
					$backorder_remove = $this->seat_model->get_seats_by_backorders($original_backorder);
				
				$select_backorder_info = array();
				
				//新候补
				if(!empty($select_backorders))
				{
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
					if(option("invoice_amount_{$this->delegate['application_type']}", 0) > 0)
					{
						$this->load->library('invoice');
					
						$this->delegate_model->change_status($this->uid, 'invoice_issued');

						//生成账单
						$this->invoice->title(option('invoice_title_fee_delegate', option('invoice_title_fee', '参会会费')));
						$this->invoice->to($this->uid);
						$this->invoice->item(option('invoice_title_fee_delegate', option('invoice_title_fee', '参会会费')), option('invoice_amount_delegate', 1000), option('invoice_item_fee_delegate', option('invoice_item_fee', array())));
						$this->invoice->due_time(time() + option('invoice_due_fee', 15) * 24 * 60 * 60);

						$this->invoice->trigger('overdue', 'release_seat', array('delegate' => $this->uid));
						$this->invoice->trigger('receive', 'change_status', array('delegate' => $this->uid, 'status' => 'payment_received'));

						$this->invoice->generate();
					}
					else
					{
						$this->delegate_model->change_status($this->uid, 'seat_selected');
					}
				}
				
				//邮件通知
				$primary_text = sprintf("\t%s（%s）", $select_primary_info['name'], $this->committee_model->get_committee($select_primary_info['committee'], 'name'));
				
				$backorder_texts = array();
				foreach($select_backorder_info as $info)
				{
					$backorder_texts[] = sprintf("\t%s（%s）", $info['name'], $this->committee_model->get_committee($info['committee'], 'name'));
				}
				
				if(empty($backorder_texts))
					$backorder_text = "\t无候补席位";
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
							. "选定的候补席位为：\n\n"
							. "{backorder_seat}\n\n"
							. "请登录 iPlacard 系统查看申请状态。"), $email_data, true));
				}
				else
				{
					$this->email->html($this->parser->parse_string(option('email_seat_changed', "您已于 {time} 调整了您的席位选择，调整后的席位选择为：\n\n"
							. "{primary_seat}\n\n"
							. "调整后的候补席位为：\n\n"
							. "{backorder_seat}\n\n"
							. "请登录 iPlacard 系统查看申请状态。"), $email_data, true));
				}
				$this->email->send();
				$this->email->clear();
				
				$this->ui->alert('您的席位选择已经保存。', 'success');
			}
		}
		
		//席位和候补信息
		$seat = array();
		$attached_seats = array();
		$attached_primary = array();
		
		$seat_id = $this->seat_model->get_delegate_seat($this->uid);
		if($seat_id)
		{
			$seat = $this->seat_model->get_seat($seat_id);
			$seat['committee'] = $this->committee_model->get_committee($seat['committee']);
			
			//多代席位
			if(!$this->seat_model->is_single_seat($seat_id))
			{
				//附加属性
				$profile_option = option('profile_list_related', array());
				$vars['profile_extra'] = $profile_option;
				
				//确认席位类型
				if(!empty($seat['primary']))
					$primary_id = $seat['primary'];
				else
					$primary_id = $seat_id;

				$vars['attached_primary_id'] = $primary_id;

				//席位信息
				$attached_ids = $this->seat_model->get_attached_seat_ids($primary_id, true);
				foreach($attached_ids as $attached_id)
				{
					$attached_seat = $this->seat_model->get_seat($attached_id);
					$attached_seat['committee'] = $this->committee_model->get_committee($attached_seat['committee']);

					if(!empty($attached_seat['delegate']))
					{
						$attached_delegate = $this->delegate_model->get_delegate($attached_seat['delegate']);
						
						//附加属性
						if(!empty($profile_option))
						{
							foreach($profile_option as $profile_item => $profile_title)
							{
								$attached_delegate[$profile_item] = $this->delegate_model->get_profile_by_name($attached_delegate['id'], $profile_item, '');
							}
						}
						
						$attached_seat['delegate'] = $attached_delegate;
					}

					if($attached_id == $primary_id)
						$attached_primary = $attached_seat;
					else
						$attached_seats[] = $attached_seat;
				}
			}
		}
		
		//是否可以锁定席位
		$lock_open = false;
		if((($this->delegate['status'] == 'payment_received' || $this->delegate['status'] == 'seat_selected') || ($this->delegate['status'] == 'seat_assigned' && option('seat_mode', 'assign'))) && option('seat_lock_open', true) && $seat_id)
			$lock_open = true;
		
		$vars['lock_open'] = $lock_open;
		
		$vars['seat'] = $seat;
		$vars['attached_seats'] = $attached_seats;
		$vars['attached_primary'] = $attached_primary;
		
		//席位是否可调整
		$change_open = false;
		if($seat_id && $this->delegate['status'] != 'locked')
			$change_open = true;
		$vars['change_open'] = $change_open;
		
		$backorders = array();
		$backorder_ids = $this->seat_model->get_delegate_backorder($this->uid, true);
		if($backorder_ids)
		{
			foreach($backorder_ids as $backorder_id)
			{
				$backorder = $this->seat_model->get_backorder($backorder_id);
				
				$seat = $this->seat_model->get_seat($backorder['seat']);
				$seat['committee'] = $this->committee_model->get_committee($seat['committee']);
				$backorder['seat'] = $seat;
				
				$backordered_seats[] = $seat['id'];
				$backorders[] = $backorder;
			}
			
			$vars['backordered_seats'] = $backordered_seats;
		}
		$vars['backorders'] = $backorders;
		
		//席位选择
		$selectability_primary = array();
		$selectability_primary_count = 0;
		
		$selectabilities = array();
		$committees = array();
		
		if(!empty($slids))
		{
			foreach($slids as $slid)
			{
				$selectability = $this->seat_model->get_selectability($slid);

				//席位详细信息
				$selectability['seat'] = $this->seat_model->get_seat($selectability['seat']);

				//主要席位统计
				if($selectability['primary'])
				{
					if(empty($selectability['seat']['delegate']) || ($selectability['seat']['status'] == 'assigned' && $selectability['seat']['delegate'] == $this->uid))
					{
						$selectability_primary[] = intval($selectability['seat']['id']);
						$selectability_primary_count++;
					}
					elseif($selectability['seat']['delegate'] != $this->uid)
					{
						$selectability['primary'] = false;
					}
				}

				$committee = $selectability['seat']['committee'];
				if(!isset($committees[$committee]))
					$committees[$committee] = $this->committee_model->get_committee($committee);

				$selectabilities[] = $selectability;
			}
		}
		
		$vars['committees'] = $committees;
		$vars['selectabilities'] = $selectabilities;
		$vars['selectability_primary'] = $selectability_primary;
		$vars['selectability_primary_count'] = $selectability_primary_count;
		
		$this->ui->now('seat');
		$this->ui->title('席位');
		$this->load->view('delegate/seat', $vars);
	}
	
	/**
	 * 账单信息
	 */
	function invoice($id = '', $action = 'view')
	{
		$this->load->model('invoice_model');
		$this->load->library('invoice');
		$this->load->library('form_validation');
		$this->load->helper('form');
		
		$gateways = option('invoice_payment_gateway', array('汇款', '网银转账', '支付宝', '其他'));
		$gateways = array_combine($gateways, $gateways);
		
		$invoices = $this->invoice_model->get_delegate_invoices($this->uid);
		
		if(!$invoices)
		{
			$this->ui->alert('您当前没有账单需要处理。', 'success', true);
			back_redirect();
			return;
		}
		
		//显示代表最后一份账单
		if(empty($id))
		{
			$id = end($invoices);
		}
		
		//检查账单是否为对应代表所有
		if(!in_array($id, $invoices))
		{
			$this->ui->alert('无权访问账单信息。', 'danger', true);
			back_redirect();
			return;
		}
		
		$this->invoice->load($id);
		
		//编辑转账信息
		if($action == 'transaction')
		{
			$this->form_validation->set_error_delimiters('<div class="help-block">', '</div>');

			$this->form_validation->set_rules('time', '转账时间', 'trim|required|strtotime');
			$this->form_validation->set_rules('gateway', '交易渠道', 'trim|required');
			$this->form_validation->set_rules('transaction', '交易流水号', 'trim');
			$this->form_validation->set_rules('amount', '转账金额', 'trim|required|numeric');

			if($this->form_validation->run() == true)
			{
				$time = $this->input->post('time');
				$gateway = $this->input->post('gateway');
				$transaction = $this->input->post('transaction');
				$amount = (float) $this->input->post('amount');
				
				$this->invoice->transaction(
					!empty($gateway) ? $gateway : '',
					!empty($transaction) ? $transaction : '',
					!empty($amount) ? $amount : '',
					!empty($time) ? $time : '',
					false
				);
				
				$this->invoice->update();
				
				$this->system_model->log('invoice_updated', array('invoice' => $id, 'transaction' => $this->invoice->get('transaction')));
				
				$this->ui->alert('转账信息已经保存。', 'success');
			}
		}
		
		//计算前后账单
		$previous = NULL;
		$next = NULL;
		
		if(count($invoices) > 1)
		{
			$current = array_search($id, $invoices);
			
			if(isset($invoices[$current - 1]))
				$previous = $invoices[$current - 1];
			
			if(isset($invoices[$current + 1]))
				$next = $invoices[$current + 1];
		}
		
		$vars['previous'] = $previous;
		$vars['next'] = $next;
		$vars['invoice_html'] = $this->invoice->display();
		$vars['due_time'] = $this->invoice->get('due_time');
		$vars['transaction'] = $this->invoice->get('transaction');
		$vars['gateway'] = $gateways;
		$vars['unpaid'] = $this->invoice_model->is_unpaid($id);
		
		$this->ui->now('invoice');
		$this->ui->title('账单');
		$this->load->view('delegate/invoice_item', $vars);
	}
	
	/**
	 * 文件
	 */
	function document()
	{
		$this->load->model('document_model');
		$this->load->helper('form');
		$this->load->helper('file');
		$this->load->helper('number');
		
		//禁止审核未通过代表访问文件
		if($this->delegate['status'] == 'review_refused' && !option('document_enable_refused', false))
		{
			$this->ui->alert('您无法访问文件下载。', 'danger', true);
			back_redirect();
			return;
		}
		
		//仅允许访问全局分发文件
		$committee_id = 0;

		//代表可访问委员会文件
		if($this->delegate['application_type'] == 'delegate')
		{
			$this->load->model('committee_model');
			$this->load->model('seat_model');

			$seat = $this->seat_model->get_delegate_seat($this->uid);
			if($seat)
			{
				$committee_id = $this->seat_model->get_seat($seat, 'committee');
				
				$committee = $this->committee_model->get_committee($committee_id);
				$vars['committee'] = $committee;
			}
		}

		//所有可显示的文件
		$document_ids = $this->document_model->get_committee_documents($committee_id);
		if(!$document_ids)
		{
			$this->ui->alert('无文件可供访问。', 'danger', true);
			back_redirect();
			return;
		}
		
		//获取文件格式
		$formats = $this->document_model->get_formats();
		if(!$formats)
			$formats = array();
		
		//去除空格式
		foreach($formats as $format_id => $format)
		{
			$files = $this->document_model->get_file_ids('format', $format_id, 'document', $document_ids, array('group_by' => 'document'));
			if(!$files)
				unset($formats[$format_id]);
			else
				$formats[$format_id]['count'] = count($files);
		}
		
		$vars['formats'] = $formats;
		
		$documents = array();
		
		//导入文件
		foreach($document_ids as $document_id)
		{
			$document = $this->document_model->get_document($document_id);
			
			$formats = $this->document_model->get_document_formats($document_id);
			if($formats)
			{
				foreach($formats as $format)
				{
					$file_id = $this->document_model->get_document_file($document_id, $format);
					$document['formats'][$format] = $file_id;
					$document['files'][$file_id] = $this->document_model->get_file($file_id);
				}
			}
			elseif(!option('document_show_empty', false))
			{
				continue;
			}
			
			$document['downloaded'] = $this->document_model->is_user_downloaded($this->uid, $document_id, 'document');
			
			$documents[] = $document;
		}

		if(empty($documents) && !option('document_show_empty', false))
		{
			$this->ui->alert('当前无文件可供下载。', 'danger', true);
			back_redirect();
			return;
		}
		
		$vars['documents'] = $documents;
		$vars['count'] = count($documents);
		
		$this->ui->now('document');
		$this->ui->title('文件');
		$this->load->view('delegate/document', $vars);
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
	 * 含席位含会费含面试申请状态信息
	 */
	function _status_seat_fee_interview()
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
		
		//退会显示原状态
		$quitted = false;
		if($status == 'quitted')
		{
			$quitted = true;
			$status = user_option('quit_status', 'application_imported', $this->uid);
		}
		
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
		if($quitted)
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
		}
		if($quitted)
			$current = 'lock';
		
		//生成状态组信息
		foreach(array('signin', 'admit', 'interview', 'seat', 'pay', 'lock') as $one)
		{
			if(!empty($w[$one]))
			{
				if($current == $one)
					$wizard[] = array('level' => $one, 'text' => $w[$one], 'intro' => $this->_status_intro(($one == 'lock' && $quitted) ? 'quit' : $one), 'current' => true);
				else
					$wizard[] = array('level' => $one, 'text' => $w[$one], 'intro' => $this->_status_intro($one), 'current' => false);
			}
		}
		
		$return['wizard'] = $wizard;
		$return['current'] = $current;
		return $return;
	}
	
	/**
	 * 含席位含会费无面试申请状态信息
	 */
	function _status_seat_fee_noninterview()
	{
		$status = $this->delegate['status'];
		
		$w['signin'] = '提交申请';
		$w['admit'] = '等待通过审核';
		$w['seat'] = '等待分配席位';
		$w['pay'] = '等待支付会费';
		$w['lock'] = '等待确认完成';
		
		//退会显示原状态
		$quitted = false;
		if($status == 'quitted')
		{
			$quitted = true;
			$status = user_option('quit_status', 'application_imported', $this->uid);
		}
		
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
			case 'review_passed':
				$w['admit'] = '初审已通过';
			case 'application_imported':
				$w['signin'] = '申请已录入';
				break;
			case 'review_refused':
				$w['signin'] = '申请已录入';
				$w['admit'] = '材料初审未通过';
				$w['seat'] = NULL;
				$w['pay'] = NULL;
				$w['lock'] = NULL;
				break;
			case 'moved_to_waiting_list':
				$w['signin'] = '申请已录入';
				$w['admit'] = '初审已通过';
				$w['seat'] = NULL;
				$w['pay'] = NULL;
				$w['lock'] = '队列等待中';
				break;
		}
		if($quitted)
			$w['lock'] = '已经退会';
		
		//进度条状态显示
		switch($status)
		{
			case 'application_imported':
			case 'review_refused':
				$current = 'admit';
				break;
			case 'review_passed':
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
		}
		if($quitted)
			$current = 'lock';
		
		//生成状态组信息
		foreach(array('signin', 'admit', 'seat', 'pay', 'lock') as $one)
		{
			if(!empty($w[$one]))
			{
				if($current == $one)
					$wizard[] = array('level' => $one, 'text' => $w[$one], 'intro' => $this->_status_intro(($one == 'lock' && $quitted) ? 'quit' : $one), 'current' => true);
				else
					$wizard[] = array('level' => $one, 'text' => $w[$one], 'intro' => $this->_status_intro($one), 'current' => false);
			}
		}
		
		$return['wizard'] = $wizard;
		$return['current'] = $current;
		return $return;
	}
	
	/**
	 * 含席位无会费含面试申请状态信息
	 */
	function _status_seat_nonfee_interview()
	{
		$status = $this->delegate['status'];
		
		$w['signin'] = '提交申请';
		$w['admit'] = '等待通过审核';
		$w['interview'] = '等待安排面试';
		$w['seat'] = '等待分配席位';
		$w['lock'] = '等待确认完成';
		
		//是否存在二次面试
		$this->load->model('interview_model');
		$interviews = $this->interview_model->get_interview_ids('delegate', $this->uid, 'status', 'failed');
		if($interviews && count($interviews) == 1)
			$w['interview'] = '等待二次面试';
		
		//退会显示原状态
		$quitted = false;
		if($status == 'quitted')
		{
			$quitted = true;
			$status = user_option('quit_status', 'application_imported', $this->uid);
		}
		
		//进度条状态文字
		switch($status)
		{
			case 'locked':
				$w['lock'] = '申请已完成';
			case 'seat_selected':
			case 'seat_assigned':
				$w['seat'] = $status == 'seat_assigned' ? '席位已分配' : '席位已选择';
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
				$w['lock'] = NULL;
				break;
			case 'moved_to_waiting_list':
				$w['signin'] = '申请已录入';
				$w['admit'] = '初审已通过';
				$w['interview'] = '面试未通过';
				$w['seat'] = NULL;
				$w['lock'] = '队列等待中';
				break;
		}
		if($quitted)
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
			case 'locked':
			case 'moved_to_waiting_list':
				$current = 'lock';
				break;
		}
		if($quitted)
			$current = 'lock';
		
		//生成状态组信息
		foreach(array('signin', 'admit', 'interview', 'seat', 'lock') as $one)
		{
			if(!empty($w[$one]))
			{
				if($current == $one)
					$wizard[] = array('level' => $one, 'text' => $w[$one], 'intro' => $this->_status_intro(($one == 'lock' && $quitted) ? 'quit' : $one), 'current' => true);
				else
					$wizard[] = array('level' => $one, 'text' => $w[$one], 'intro' => $this->_status_intro($one), 'current' => false);
			}
		}
		
		$return['wizard'] = $wizard;
		$return['current'] = $current;
		return $return;
	}
	
	/**
	 * 含席位无会费无面试申请状态信息
	 */
	function _status_seat_nonfee_noninterview()
	{
		$status = $this->delegate['status'];
		
		$w['signin'] = '提交申请';
		$w['admit'] = '等待通过审核';
		$w['seat'] = '等待分配席位';
		$w['lock'] = '等待确认完成';
		
		//退会显示原状态
		$quitted = false;
		if($status == 'quitted')
		{
			$quitted = true;
			$status = user_option('quit_status', 'application_imported', $this->uid);
		}
		
		//进度条状态文字
		switch($status)
		{
			case 'locked':
				$w['lock'] = '申请已完成';
			case 'seat_selected':
			case 'seat_assigned':
				$w['seat'] = $status == 'seat_assigned' ? '席位已分配' : '席位已选择';
			case 'review_passed':
				$w['admit'] = '初审已通过';
			case 'application_imported':
				$w['signin'] = '申请已录入';
				break;
			case 'review_refused':
				$w['signin'] = '申请已录入';
				$w['admit'] = '材料初审未通过';
				$w['seat'] = NULL;
				$w['lock'] = NULL;
				break;
			case 'moved_to_waiting_list':
				$w['signin'] = '申请已录入';
				$w['admit'] = '初审已通过';
				$w['seat'] = NULL;
				$w['lock'] = '队列等待中';
				break;
		}
		if($quitted)
			$w['lock'] = '已经退会';
		
		//进度条状态显示
		switch($status)
		{
			case 'application_imported':
			case 'review_refused':
				$current = 'admit';
				break;
			case 'review_passed':
			case 'seat_assigned':
				$current = 'seat';
				break;
			case 'locked':
			case 'moved_to_waiting_list':
				$current = 'lock';
				break;
		}
		if($quitted)
			$current = 'lock';
		
		//生成状态组信息
		foreach(array('signin', 'admit', 'seat', 'lock') as $one)
		{
			if(!empty($w[$one]))
			{
				if($current == $one)
					$wizard[] = array('level' => $one, 'text' => $w[$one], 'intro' => $this->_status_intro(($one == 'lock' && $quitted) ? 'quit' : $one), 'current' => true);
				else
					$wizard[] = array('level' => $one, 'text' => $w[$one], 'intro' => $this->_status_intro($one), 'current' => false);
			}
		}
		
		$return['wizard'] = $wizard;
		$return['current'] = $current;
		return $return;
	}
	
	/**
	 * 非席位含会费含面试申请状态信息
	 */
	function _status_nonseat_fee_interview()
	{
		$status = $this->delegate['status'];
		
		$w['signin'] = '提交申请';
		$w['admit'] = '等待通过审核';
		$w['interview'] = '等待安排面试';
		$w['pay'] = '等待支付会费';
		$w['lock'] = '等待确认完成';
		
		//是否存在二次面试
		$this->load->model('interview_model');
		$interviews = $this->interview_model->get_interview_ids('delegate', $this->uid, 'status', 'failed');
		if($interviews && count($interviews) == 1)
			$w['interview'] = '等待二次面试';
		
		//退会显示原状态
		$quitted = false;
		if($status == 'quitted')
		{
			$quitted = true;
			$status = user_option('quit_status', 'application_imported', $this->uid);
		}
		
		//进度条状态文字
		switch($status)
		{
			case 'locked':
				$w['lock'] = '申请已完成';
			case 'payment_received':
				$w['pay'] = '已支付会费';
			case 'invoice_issued':
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
				$w['pay'] = NULL;
				$w['lock'] = NULL;
				break;
			case 'moved_to_waiting_list':
				$w['signin'] = '申请已录入';
				$w['admit'] = '初审已通过';
				$w['interview'] = '面试未通过';
				$w['pay'] = NULL;
				$w['lock'] = '队列等待中';
				break;
		}
		if($quitted)
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
			case 'invoice_issued':
			case 'payment_received':
				$current = 'pay';
				break;
			case 'locked':
			case 'moved_to_waiting_list':
				$current = 'lock';
				break;
		}
		if($quitted)
			$current = 'lock';
		
		//生成状态组信息
		foreach(array('signin', 'admit', 'interview', 'pay', 'lock') as $one)
		{
			if(!empty($w[$one]))
			{
				if($current == $one)
					$wizard[] = array('level' => $one, 'text' => $w[$one], 'intro' => $this->_status_intro(($one == 'lock' && $quitted) ? 'quit' : $one), 'current' => true);
				else
					$wizard[] = array('level' => $one, 'text' => $w[$one], 'intro' => $this->_status_intro($one), 'current' => false);
			}
		}
		
		$return['wizard'] = $wizard;
		$return['current'] = $current;
		return $return;
	}
	
	/**
	 * 非席位含会费无面试申请状态信息
	 */
	function _status_nonseat_fee_noninterview()
	{
		$status = $this->delegate['status'];
		
		$w['signin'] = '提交申请';
		$w['admit'] = '等待通过审核';
		$w['pay'] = '等待支付会费';
		$w['lock'] = '等待确认完成';
		
		//退会显示原状态
		$quitted = false;
		if($status == 'quitted')
		{
			$quitted = true;
			$status = user_option('quit_status', 'application_imported', $this->uid);
		}
		
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
		if($quitted)
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
		}
		if($quitted)
			$current = 'lock';
		
		//生成状态组信息
		foreach(array('signin', 'admit', 'pay', 'lock') as $one)
		{
			if(!empty($w[$one]))
			{
				if($current == $one)
					$wizard[] = array('text' => $w[$one], 'intro' => $this->_status_intro(($one == 'lock' && $quitted) ? 'quit' : $one), 'current' => true);
				else
					$wizard[] = array('text' => $w[$one], 'intro' => $this->_status_intro($one), 'current' => false);
			}
		}
		
		$return['wizard'] = $wizard;
		$return['current'] = $current;
		return $return;
	}
	
	/**
	 * 非席位无会费含面试申请状态信息
	 */
	function _status_nonseat_nonfee_interview()
	{
		$status = $this->delegate['status'];
		
		$w['signin'] = '提交申请';
		$w['admit'] = '等待通过审核';
		$w['interview'] = '等待安排面试';
		$w['lock'] = '等待确认完成';
		
		//是否存在二次面试
		$this->load->model('interview_model');
		$interviews = $this->interview_model->get_interview_ids('delegate', $this->uid, 'status', 'failed');
		if($interviews && count($interviews) == 1)
			$w['interview'] = '等待二次面试';
		
		//退会显示原状态
		$quitted = false;
		if($status == 'quitted')
		{
			$quitted = true;
			$status = user_option('quit_status', 'application_imported', $this->uid);
		}
		
		//进度条状态文字
		switch($status)
		{
			case 'locked':
				$w['lock'] = '申请已完成';
			case 'invoice_issued':
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
				$w['lock'] = NULL;
				break;
			case 'moved_to_waiting_list':
				$w['signin'] = '申请已录入';
				$w['admit'] = '初审已通过';
				$w['interview'] = '面试未通过';
				$w['lock'] = '队列等待中';
				break;
		}
		if($quitted)
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
			case 'locked':
			case 'moved_to_waiting_list':
				$current = 'lock';
				break;
		}
		if($quitted)
			$current = 'lock';
		
		//生成状态组信息
		foreach(array('signin', 'admit', 'interview', 'lock') as $one)
		{
			if(!empty($w[$one]))
			{
				if($current == $one)
					$wizard[] = array('level' => $one, 'text' => $w[$one], 'intro' => $this->_status_intro(($one == 'lock' && $quitted) ? 'quit' : $one), 'current' => true);
				else
					$wizard[] = array('level' => $one, 'text' => $w[$one], 'intro' => $this->_status_intro($one), 'current' => false);
			}
		}
		
		$return['wizard'] = $wizard;
		$return['current'] = $current;
		return $return;
	}
	
	/**
	 * 非席位无会费无面试申请状态信息
	 */
	function _status_nonseat_nonfee_noninterview()
	{
		$status = $this->delegate['status'];
		
		$w['signin'] = '提交申请';
		$w['admit'] = '等待通过审核';
		$w['lock'] = '等待完成';
		
		//退会显示原状态
		$quitted = false;
		if($status == 'quitted')
		{
			$quitted = true;
			$status = user_option('quit_status', 'application_imported', $this->uid);
		}
		
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
		if($quitted)
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
		}
		if($quitted)
			$current = 'lock';
		
		//生成状态组信息
		foreach(array('signin', 'admit', 'lock') as $one)
		{
			if(!empty($w[$one]))
			{
				if($current == $one)
					$wizard[] = array('text' => $w[$one], 'intro' => $this->_status_intro(($one == 'lock' && $quitted) ? 'quit' : $one), 'current' => true);
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
				$intro = '<p style=\'margin-bottom: 0;\'>您的申请已经成功导入到 iPlacard。</p>';
				break;
			case 'application_imported':
			case 'review_refused':
			case 'admit':
				$intro = '<p>您的申请已经成功导入到 iPlacard，我们将在近期内审核您提交的申请材料。在此期间，请核对您的申请材料，如果内容有误，您将可以修改部分数据。</p>'
					. '<p style=\'margin-bottom: 0;\'>材料审核通过之后，您将进入下一申请流程。</p>';
				break;
			case 'review_passed':
			case 'interview_assigned':
			case 'interview_arranged':
			case 'interview_completed':
			case 'interview':
				$intro = '<p>在面试阶段中，我们将会根据您的委员会意向指派一位面试官，他将与您取得联系并且确定面试时间和面试方式。</p>'
					. '<p>请尽量为面试预留足够的时间，如果您无法在约定的时间进行面试，请联系您的面试官，他将会重新为您安排面试时间。</p>'
					. '<p style=\'margin-bottom: 0;\'>如果面试没有通过，您将进入等待队列。</p>';
				break;
			case 'seat_assigned':
			case 'seat_selected':
			case 'seat':
				$intro = '<p>面试通过之后，面试官将会根据您的面试表现为您分配适合的席位选择。通常情况下，面试官将在完成面试后立即为您分配合适的席位选择。</p>'
					. '<p style=\'margin-bottom: 0;\'>席位分配后，您将可以在席位信息页面选择并确认席位；如果您认为分配的席位不适合您，请联系您的面试官，他将可以为您重新分配席位。</p>';
				break;
			case 'invoice_issued':
			case 'payment_received':
			case 'pay':
				$intro = '<p style=\'margin-bottom: 0;\'>您将可以通过银行转账、网银支付或者邮政汇款完成会费支付，我们将在收到汇款之后确定完成支付。</p>';
				break;
			case 'locked':
			case 'moved_to_waiting_list':
			case 'lock':
				if($this->delegate['status'] == 'moved_to_waiting_list')
					$intro = '<p style=\'margin-bottom: 0;\'>您已经移动到等待队列。我们将会通过邮件通知您后续事宜，请频繁检查您的电子邮箱并定期登录 iPlacard 了解更新。</p>';
				else
					$intro = '<p style=\'margin-bottom: 0;\'>您已经完成申请流程。我们将会通过邮件通知您后续事宜，请频繁检查您的电子邮箱并定期登录 iPlacard 了解更新。</p>';
				break;
			case 'quitted':
			case 'quit':
				$this->load->helper('date');
				
				$lock_time = user_option('quit_time', time()) + option('delegate_quit_lock', 7) * 24 * 60 * 60;
				$intro = '<p style=\'margin-bottom: 0;\'>您已退会，您的 iPlacard 帐户数据将在 <span id=\'clock_lock\'>'.nicetime($lock_time).'</span> 秒内删除，届时您将无法登录系统。如果这是管理员的误操作请立即联系管理员恢复帐户。</p>';
				
				$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/jquery.countdown.js' : 'static/js/jquery.countdown.min.js').'"></script>');
				$this->ui->js('footer', "$('#clock_lock').countdown({$lock_time} * 1000, function(event) {
					$(this).html(event.strftime('%-D 天 %H:%M:%S'));
				});");
				break;
			default:
				$intro = '';
		}
		
		return $intro;
	}
	
	/**
	 * 密码检查回调函数
	 */
	function _check_password($str, $global_message = '')
	{
		if($this->user_model->check_password(uid(true), $str))
			return true;
		
		//全局消息
		if(!empty($global_message))
			$this->ui->alert($global_message);
		
		return false;
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
	 * 主席位为候补席位检查回调函数
	 */
	function _check_primary_backorder($array)
	{
		$primary = $this->input->post('primary');
		
		if(is_null($array))
			return true;
		
		if(!in_array($primary, $array))
			return true;
		
		return true;
	}
	
	/**
	 * 检查主席位是否可选
	 */
	function _check_availability($str)
	{
		$seat = $this->seat_model->get_seat($str);
		if(!$seat)
			return false;
		
		if($seat['status'] == 'locked')
			return false;
		
		if($seat['status'] == 'assigned' && $seat['delegate'] != $this->uid)
			return false;
		
		return true;
	}
}

/* End of file apply.php */
/* Location: ./application/controllers/apply.php */