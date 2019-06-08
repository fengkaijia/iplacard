<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 申请者界面控制器
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
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
		
		//检查非登录页面登录情况
		if($this->uri->segment(2) == 'signup')
		{
			if(is_logged_in())
				redirect('');
			return;
		}
		
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
	 * 报名
	 */
	function signup()
	{
		$this->load->library('form_validation');
		$this->load->helper('form');
		
		$unique_id = option('signup_unique_identifier', 'email');
		if(!in_array($unique_id, array('email', 'phone')))
			$unique_id = "profile_$unique_id";
		
		$types = option('signup_type', array('delegate', 'observer', 'volunteer', 'teacher'));
		
		$vars['type'] = array();
		foreach($types as $type)
		{
			$vars['type'][$type] = $this->delegate_model->application_type_text($type);
		}
		
		$vars['committee'] = array();
		
		$test_questions = option('profile_list_test', array());
		$test_enabled = option('signup_test', false) && count($test_questions) > 0;
		if($test_enabled)
		{
			$this->load->model('committee_model');
			
			$committees = $this->committee_model->get_committees();
			foreach($committees as $committee)
			{
				$vars['committee'][$committee['id']] = $committee['name'];
			}
			
			$committee_tests = array();
			if(option('signup_test_dynamic', false)) //以动态方式从题库中随机取题
			{
				$this->load->library('question');
				
				$previous_test = $this->session->userdata('signup_test');
				if(!is_null($previous_test))
				{
					$committee_tests = unserialize($previous_test);
				}
				else
				{
					$this->question->set_committee_rule(option('signup_test_committee', array())); //规定学测题属于哪些委员会
					$this->question->set_exclusive_rule(option('signup_test_exclusive', array())); //规定学测题不可与哪些学测题同时出现
					
					$test_count = option('signup_test_count', 3); //规定学测题数目
					foreach($committees as $committee)
					{
						$committee_tests[$committee['id']] = $this->question->generate($committee['id'], $test_count);
					}
					
					//缓存题目序列
					$this->session->set_userdata('signup_test', serialize($committee_tests));
				}
			}
			else //展示全部题目
			{
				foreach($committees as $committee) {
					//将全部题目添加到每个意向委员会展示题目中
					$committee_tests[$committee['id']] = range(0, count($test_questions) - 1);
				}
			}
			
			$vars['test_questions'] = $test_questions;
			$vars['test_selected'] = $committee_tests;
			$vars['test_needed'] = array_unique(call_user_func_array('array_merge', $committee_tests));
		}
		
		$profiles = option('profile_list_general', array());
		$vars['profiles'] = $profiles;
		
		$this->form_validation->set_error_delimiters('<div class="help-block">', '</div>');
		$this->form_validation->set_rules('name', '姓名', 'trim|required');
		$this->form_validation->set_rules('email', '电子邮箱地址', 'trim|required|valid_email|is_unique[user.email]'.($unique_id == 'email' ? '|callback__check_unique_id' : ''));
		$this->form_validation->set_rules('phone', '手机号', 'trim|required|integer|exact_length[11]|is_unique[user.phone]'.($unique_id == 'phone' ? '|callback__check_unique_id' : ''));
		$this->form_validation->set_rules('type', '申请类型', 'trim|required|in_list['.join(',', $types).']');
		if(!in_array($unique_id, array('email', 'phone')))
			$this->form_validation->set_rules($unique_id, '此项注册信息', 'trim|required|callback__check_unique_id');
		$this->form_validation->set_message('_check_unique_id', "相同的注册信息已经存在。");
		
		if($this->form_validation->run() == true)
		{
			//生成随机密码
			$this->load->helper('string');
			$password = random_string('alnum', 8);
			
			//新建用户
			$user_data = array(
				'name' => $this->input->post('name'),
				'email' => $this->input->post('email'),
				'type' => 'delegate',
				'password' => $password,
				'pin_password' => option('pin_default_password', 'iPlacard'),
				'phone' => $this->input->post('phone'),
				'reg_time' => time()
			);
			$uid = $this->user_model->edit_user($user_data);
			
			//增加代表数据
			$this->delegate_model->add_delegate($uid);
			$delegate_data = array(
				'status' => 'application_imported',
				'application_type' => $this->input->post('type'),
				'unique_identifier' => $this->input->post($unique_id)
			);
			$this->delegate_model->edit_delegate($delegate_data, $uid);
			
			//导入资料
			foreach($profiles as $name => $item)
			{
				$this->delegate_model->add_profile($uid, $name, trim($this->input->post("profile_$name")));
			}
			
			//导入意向委员会和学术测试
			if($test_enabled)
			{
				//TODO: 格式化
				$committee = intval($this->input->post('committee'));
				$this->delegate_model->add_profile($uid, 'committee_choice', isset($vars['committee'][$committee]) ? $vars['committee'][$committee] : $committee);
				
				$answers = array();
				for($i = 0; $i < count($test_questions); $i++)
				{
					$answers[$i] = '';
					if(isset($committee_tests[$committee]) && in_array($i, $committee_tests[$committee]))
						$answers[$i] = trim($this->input->post("test_$i"));
				}
				
				$this->delegate_model->add_profile($uid, 'test', $answers);
			}
			
			//发送邮件
			$this->load->library('email');
			$this->load->library('parser');
			$this->load->helper('date');
			
			$data = array(
				'uid' => $uid,
				'name' => $this->input->post('name'),
				'email' => $this->input->post('email'),
				'password' => $password,
				'time' => unix_to_human(time()),
				'url' => base_url(),
			);
			
			$this->email->to($this->input->post('email'));
			$this->email->subject('iPlacard 帐户登录信息');
			$this->email->html($this->parser->parse_string(option('email_delegate_account_created', "您已成功报名。您的 iPlacard 帐户已经于 {time} 创建。帐户信息如下：\n\n"
				. "\t登录邮箱：{email}\n"
				. "\t密码：{password}\n\n"
				. "请使用以上信息访问：\n\n"
				. "\t{url}\n\n"
				. "登录并开始通过 iPlacard 了解您的申请进度。"), $data, true));
			
			if(!$this->email->send())
			{
				$this->system_model->log('notice_failed', array('id' => $uid, 'type' => 'email', 'content' => 'delegate_account_created'));
			}
			
			//发送短信通知
			if(option('sms_enabled', false))
			{
				$this->load->model('sms_model');
				$this->load->library('sms');
				
				$this->sms->to($uid);
				$this->sms->message('您已成功报名，一封含有登录信息的邮件已经发送到您的电子邮箱，请通过提供的信息登录 iPlacard 了解申请进度。如果未能收到通知邮件，请与我们联系。');
				
				if(!$this->sms->queue())
				{
					$this->system_model->log('notice_failed', array('id' => $uid, 'type' => 'sms', 'content' => 'delegate_account_created'));
				}
			}
			
			$this->delegate_model->add_event($uid, 'application_imported');
			$this->user_model->add_message($uid, '您已成功报名。您的参会申请已经开始审核。');
			
			$this->system_model->log('application_imported', array('ip' => $this->input->ip_address(), 'id' => $uid, 'ui' => true), 0);
			
			$this->ui->alert('您已成功报名，请使用发送到您刚才登记邮箱中的密码登录 iPlacard。', 'success', true);
			redirect('account/login');
			return;
		}
		
		$this->ui->now('signup');
		$this->ui->title('报名');
		$this->ui->background();
		$this->load->view('delegate/signup', $vars);
	}
	
	/**
	 * 申请首页
	 */
	function status($action = 'view')
	{
		$this->load->model('document_model');
		$this->load->model('seat_model');
		$this->load->model('knowledgebase_model');
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
		if((($this->delegate['status'] == 'payment_received' || $this->delegate['status'] == 'seat_selected') || ($this->delegate['status'] == 'seat_assigned' && option('seat_mode', 'select'))) && option('seat_lock_open', true) && $sid)
		{
			$lock_open = true;
			
			//确认锁定
			$this->form_validation->set_error_delimiters('<div class="help-block">', '</div>');
			
			$this->form_validation->set_rules('password', '密码', 'trim|required|callback__check_password[密码验证错误未能锁定席位，请重新尝试。]');
			$this->form_validation->set_message('_check_password', '密码有误，请重新输入。');
			
			if($action == 'lock' && $this->form_validation->run() == true)
			{
				//记录锁定现有席位
				$this->delegate_model->add_event($this->uid, 'seat_locked', array('seat' => $sid));
				
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
		
		//消息
		$messages = array();
		
		$message_ids = $this->user_model->get_message_ids('receiver', $this->uid, 'status', array('unread', 'read'));
		if($message_ids)
		{
			$picked = array_slice(array_reverse($message_ids), 0, 5);
			
			$messages = array_reverse($this->user_model->get_messages($picked));
			
			foreach($messages as $message)
			{
				if($message['status'] == 'unread')
					$this->user_model->update_message_status($message['id'], 'read');
			}
		}
		
		$vars['messages'] = $messages;
		
		//文件
		$documents = array();
		
		$committee_id = 0;
		if($this->delegate['application_type'] == 'delegate' && $sid)
			$committee_id = $seat['committee']['id'];
		
		$document_ids = $this->document_model->get_committee_documents($committee_id);
		if($document_ids)
		{
			foreach($document_ids as $document_id)
			{
				if(!$this->document_model->get_document_formats($document_id))
					continue;
				
				if($this->document_model->is_user_downloaded($this->uid, $document_id, 'document'))
					continue;
				
				$documents[] = $this->document_model->get_document($document_id);
			}
		}
		
		$vars['documents'] = $documents;
		
		//账单
		$invoices = array();
		
		$invoice_ids = $this->invoice_model->get_delegate_invoices($this->uid, true);
		if($invoice_ids)
		{
			$this->load->model('invoice_model');
			
			foreach($invoice_ids as $invoice_id)
			{
				$invoices[] = $this->invoice_model->get_invoice($invoice_id);
			}
			
			$vars['currency']['sign'] = option('invoice_currency_sign', '￥');
			$vars['currency']['text'] = option('invoice_currency_text', 'RMB');
		}
		
		$vars['invoices'] = $invoices;
		
		//知识库
		$articles = array();
		
		$article_ids = $this->knowledgebase_model->get_ordered_articles('order', 4);
		if($article_ids)
		{
			foreach($article_ids as $article_id)
			{
				$articles[] = $this->knowledgebase_model->get_article($article_id);
			}
		}
		
		$vars['articles'] = $articles;
		
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
		$feed = option('feed_url', 'http://iplacard.com/feed/');
		$feed_enable = false;
		if(!empty($feed))
		{
			$this->load->library('feed');
			
			$this->feed->set_feed_url($feed);
			if($this->feed->parse())
			{
				$feed_enable = true;
				$vars['feed'] = $this->feed->get_feed(2);
			}
		}
		
		$vars['feed_enable'] = $feed_enable;
		
		//公告
		$vars['announcement'] = option('site_announcement', '');
		
		//申请状态介绍
		$vars['status_intro'] = $this->_status_intro($this->delegate['status']);
		
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
			$this->form_validation->set_rules('提交验证', 'edit', 'exist');
			
			//输入检查
			foreach($addition_items as $name => $item)
			{
				if($item['required'])
				{
					$this->form_validation->set_rules($item['title'], "addition_$name", 'exist');
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
								
								$this->invoice->clear();
								
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
			'select_open' => $select_open
		);
		
		if($action == 'select')
		{
			$this->form_validation->set_error_delimiters('<div class="help-block">', '</div>');
		
			$this->form_validation->set_rules('seat', '席位', 'trim|required|callback__check_selectability|callback__check_availability');
			$this->form_validation->set_message('_check_selectability', '席位选择不符合设定条件。');
			$this->form_validation->set_message('_check_availability', '席位已被分配不可选择。');

			if($this->form_validation->run() == true)
			{
				$this->load->library('email');
				$this->load->library('parser');
				$this->load->helper('date');

				$select_seat = $this->input->post('seat');
				
				//已有席位
				$original_seat = $this->seat_model->get_delegate_seat($this->uid);
				if($original_seat)
				{
					if($original_seat == $select_seat)
					{
						$this->ui->alert('席位没有变化。', 'info', true);
						back_redirect();
						return;
					}
					
					$original_seat_info = $this->seat_model->get_seat($original_seat);

					//回退原席位
					$this->seat_model->change_seat_status($original_seat, 'available', NULL);
					$this->seat_model->assign_seat($original_seat, NULL);
					
					$this->delegate_model->add_event($this->uid, 'seat_cancelled', array('seat' => $original_seat));
					$this->user_model->add_message($this->uid, '您已经取消席位。');

					//设定原席位不再可选
					if(option('seat_revert_original', false))
					{
						$original_seat_selectability = $this->seat_model->get_selectability_id('delegate', $this->uid, 'seat', $original_seat);
						if($original_seat_selectability)
							$this->seat_model->remove_selectability($original_seat_selectability);
					}
					
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
				}
				
				//增加现有席位
				$this->seat_model->change_seat_status($select_seat, 'assigned', true);
				$this->seat_model->assign_seat($select_seat, $this->uid);

				$this->delegate_model->add_event($this->uid, 'seat_selected', array('seat' => $select_seat));
				$this->user_model->add_message($this->uid, '您已经选择新席位。');
				
				$select_info = $this->seat_model->get_seat($select_seat);
				
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
				$email_data = array(
					'uid' => $this->uid,
					'delegate' => $this->delegate['name'],
					'seat' => sprintf("\t%s（%s）", $select_info['name'], $this->committee_model->get_committee($select_info['committee'], 'name')),
					'time' => unix_to_human(time()),
				);

				$this->email->to($this->delegate['email']);
				$this->email->subject($this->delegate['status'] == 'seat_assigned' ? '席位已经选择' : '席位选择已经调整');
				if($this->delegate['status'] == 'seat_assigned')
				{
					$this->email->html($this->parser->parse_string(option('email_seat_selected', "您已于 {time} 选定了您的席位，选定的席位为：\n\n"
							. "{seat}\n\n"
							. "请登录 iPlacard 系统查看申请状态。"), $email_data, true));
				}
				else
				{
					$this->email->html($this->parser->parse_string(option('email_seat_changed', "您已于 {time} 调整了您的席位选择，调整后的席位选择为：\n\n"
							. "{seat}\n\n"
							. "请登录 iPlacard 系统查看申请状态。"), $email_data, true));
				}
				$this->email->send();
				
				$this->ui->alert('您的席位选择已经保存。', 'success');
			}
		}
		
		//席位信息
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
				$vars['attached_double'] = $this->seat_model->is_double_seat($seat_id);

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
		if((($this->delegate['status'] == 'payment_received' || $this->delegate['status'] == 'seat_selected') || ($this->delegate['status'] == 'seat_assigned' && option('seat_mode', 'select'))) && option('seat_lock_open', true) && $seat_id)
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
		
		//席位选择
		$selectability_available = array();
		$selectability_available_count = 0;
		
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
				if(empty($selectability['seat']['delegate']) || ($selectability['seat']['status'] == 'assigned' && $selectability['seat']['delegate'] == $this->uid))
				{
					$selectability_available[] = intval($selectability['seat']['id']);
					$selectability_available_count++;
				}

				$committee = $selectability['seat']['committee'];
				if(!isset($committees[$committee]))
					$committees[$committee] = $this->committee_model->get_committee($committee);

				$selectabilities[] = $selectability;
			}
		}
		
		$vars['committees'] = $committees;
		$vars['selectabilities'] = $selectabilities;
		$vars['selectability_available'] = $selectability_available;
		$vars['selectability_available_count'] = $selectability_available_count;
		
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
		elseif($action == 'archive_message')
		{
			$message_id = $this->input->get('id');
			if(empty($message_id))
				return;
			
			$message = $this->user_model->get_message($message_id);
			if($message['receiver'] != $this->uid)
				return;
			
			if($message['status'] != 'archived')
			{
				$this->user_model->update_message_status($message_id, 'archived');
				
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
			case 'waitlist_entered':
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
			case 'waitlist_entered':
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
			case 'waitlist_entered':
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
			case 'waitlist_entered':
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
			case 'waitlist_entered':
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
			case 'waitlist_entered':
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
			case 'waitlist_entered':
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
			case 'waitlist_entered':
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
			case 'waitlist_entered':
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
			case 'waitlist_entered':
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
			case 'waitlist_entered':
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
			case 'waitlist_entered':
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
			case 'waitlist_entered':
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
		
		$interview_enabled = option("interview_{$this->delegate['application_type']}_enabled", option('interview_enabled', $this->delegate['application_type'] == 'delegate'));
		$seat_enabled = $this->delegate['application_type'] == 'delegate' && option('seat_enabled', true);
		$seat_mode = option('seat_mode', 'select');
		$seat_lock = option('seat_lock_open', true);
		
		switch($status)
		{
			//进度面板显示提示
			case 'signin':
				$intro = "<p style='margin-bottom: 0;'>iPlacard 成功导入您的申请并建立帐号。</p>";
				break;
			case 'admit':
				$intro = "<p style='margin-bottom: 0;'>您的材料将在此阶段通过审核。</p>";
				break;
			case 'interview':
				$intro = "<p>我们将根据意向指派一位面试官进行面试。面试官可以根据面试情况决定是否通过面试及是否需要进行复试。</p><p style='margin-bottom: 0;'>如果您未能通过面试，您将进入等待队列，我们将在后续为您提供空余席位。</p>";
				break;
			case 'seat':
				if($seat_mode == 'select')
				{
					if($interview_enabled)
						$intro = "<p>您的面试官将在此阶段向您开放席位，您可以从中选择参会席位。</p><p style='margin-bottom: 0;'>如果对您的席位选项不满意，您可以联系面试官，他将可以向您提供更多的席位供您选择。</p>";
					else
						$intro = "<p>我们将在此阶段向您开放席位，您可以从中选择参会席位。</p><p style='margin-bottom: 0;'>如果对您的席位选项不满意，您可以联系我们，我们将可以向您提供更多的席位供您选择。</p>";
				}
				else
				{
					if($interview_enabled)
						$intro = "<p style='margin-bottom: 0;'>您的面试官将在此阶段为您分配参会席位。如果对席位分配不满意，您可以联系我们更换您的席位分配。</p>";
					else
						$intro = "<p style='margin-bottom: 0;'>我们将在此阶段为您分配参会席位。如果对席位分配不满意，您可以联系我们更换您的席位分配。</p>";
				}
				break;
			case 'pay':
				$intro = "<p style='margin-bottom: 0;'>您的会费账单将在此阶段生成，您将可以通过多种方式完成会费支付。</p>";
				break;
			case 'lock':
				if($this->delegate['status'] == 'waitlist_entered')
					$intro = "<p style='margin-bottom: 0;'>您未能通过面试，现在您正在等待队列中。我们将在后续为您提供空余席位，请等待进一步通知。</p>";
				else
				{
					if($seat_enabled)
						$intro = "<p style='margin-bottom: 0;'>您将可以在此阶段完成申请并锁定您的席位。</p>";
					else
						$intro = "<p style='margin-bottom: 0;'>您的申请将在此阶段完成。</p>";
				}
				break;
			case 'quit':
				$lock_time = option('delegate_quit_lock', 7);
				
				$intro = "<p style='margin-bottom: 0;'>您已退会，您的 iPlacard 帐户数据将在 {$lock_time} 天内被停用，届时您将无法登录系统。</p>";
				break;
			//申请状态面板显示提示
			case 'application_imported':
				$link_settings = anchor('account/settings/home', '设置');
				$link_contact = safe_mailto(option('site_contact_email', 'contact@iplacard.com'), '联系我们');
				
				$intro = "<p>我们将在近期内审核您的申请材料。在此期间，请核对您的申请材料，如果有误请{$link_contact}修改信息。同时，你可以在{$link_settings}中修改密码并更改您的邮箱、手机等信息。</p><p style='margin-bottom: 0;'>材料审核通过之后，您将进入下一申请流程。</p>";
				break;
			case 'review_refused':
				$intro = "<p style='margin-bottom: 0;'>很遗憾，您的申请未通过审核，再次感谢您参与申请本次会议。</p>";
				break;
			case 'review_passed':
				$this->load->model('interview_model');
				
				$interview_status = '';
				$current_interview = $this->interview_model->get_current_interview_id($this->uid);
				if($interview_enabled && $current_interview)
					$interview_status = $this->interview_model->get_interview($current_interview, 'status');
				
				if($interview_enabled && $interview_status == 'failed')
					$intro = "<p style='margin-bottom: 0;'>您未能通过上次面试，接下来我们将会为您指派新面试官，他将联系您安排二次面试。</p>";
				elseif($interview_enabled && $interview_status == 'completed')
					$intro = "<p style='margin-bottom: 0;'>您已通过面试，按照面试官要求，我们将为您安排新的面试官进行复试。</p>";
				elseif($interview_enabled)
					$intro = "<p style='margin-bottom: 0;'>我们已经审核通过了您的申请材料，接下来我们将会为您指派面试官，他将联系您安排面试。</p>";
				elseif($seat_enabled)
					$intro = "<p style='margin-bottom: 0;'>我们已经审核通过了您的申请材料，接下来我们将会为您分配席位。</p>";
				else
					$intro = "<p style='margin-bottom: 0;'>我们已经审核通过了您的申请材料。</p>";
				break;
			case 'interview_assigned':
				$link_interview = anchor('apply/interview', '面试信息页面');
				
				$intro = "<p>我们已经为您分配了面试官，他将在近期联系您安排面试时间。</p><p style='margin-bottom: 0;'>您可以在{$link_interview}中查看您的面试官信息。</p>";
				break;
			case 'interview_arranged':
				$link_interview = anchor('apply/interview', '面试信息页面');
				
				$intro = "<p>您的面试官已经将安排了面试时间。您可以在{$link_interview}中查看面试安排。</p><p style='margin-bottom: 0;'>在面试开始前一小时，iPlacard 将会通过邮件或短信通知您相关面试。</p>";
				break;
			case 'interview_completed':
				$link_interview = anchor('apply/interview', '面试结果');
				
				if($seat_enabled)
				{
					if($seat_mode == 'select')
						$intro = "<p>您已完成面试，您可以查看{$link_interview}。</p><p style='margin-bottom: 0;'>稍后您的面试官将根据您的面试表现为您提供合适的席位，您将可以从中选择参会席位。</p>";
					else
						$intro = "<p>您已完成面试，您可以查看{$link_interview}。</p><p style='margin-bottom: 0;'>接下来，您的面试官将会为您分配参会席位。</p>";
				}
				else
					$intro = "<p style='margin-bottom: 0;'>您已完成面试，您可以查看{$link_interview}。</p>";
				break;
			case 'seat_assigned':
				$link_seat = anchor('apply/seat', '席位');
				
				if($seat_mode == 'select')
				{
					if($interview_enabled)
						$intro = "<p>您的面试官已经开放了席位选项，请查看可选的{$link_seat}并选择您的参会席位。</p><p style='margin-bottom: 0;'>如果对您的席位选项不满意，您可以联系面试官，他将可以向您提供更多的席位供您选择。请注意，单个席位可能同时向多位代表开放，请尽快选择席位。</p>";
					else
						$intro = "<p>我们已经向您开放了席位选项，请查看可选的{$link_seat}并选择您的参会席位。</p><p style='margin-bottom: 0;'>如果对您的席位选项不满意，您可以联系我们向您提供更多的席位供您选择。请注意，单个席位可能同时向多位代表开放，请尽快选择席位。</p>";
				}
				else
				{
					if($seat_lock)
						$intro = "<p>我们已经为您分配了席位，请查看您的{$link_seat}。</p><p style='margin-bottom: 0;'>现在，您可以锁定您的席位。如果对席位分配不满意，您可以联系我们更换您的席位分配。</p>";
					else
						$intro = "<p>我们已经为您分配了席位，请查看您的{$link_seat}。</p><p style='margin-bottom: 0;'>如果对席位分配不满意，您可以联系我们更换您的席位分配。</p>";
				}
				break;
			case 'seat_selected':
				$link_seat = anchor('apply/seat', '席位');
				
				if($seat_lock)
					$intro = "<p>您已经选择了席位，您可以随时在{$link_seat}中调整您的席位。</p><p style='margin-bottom: 0;'>现在，您可以锁定您的席位。</p>";
				else
					$intro = "<p style='margin-bottom: 0;'>您已经选择了席位，您可以随时在{$link_seat}中调整您的席位。</p>";
				break;
			case 'invoice_issued':
				$payment_time = option('invoice_due_fee', 15);
				$link_invoice = anchor('apply/invoice', '账单');
				
				$intro = "<p>您的会费{$link_invoice}已经生成，您需要在 {$payment_time} 天内支付您的账单。</p><p style='margin-bottom: 0;'>如果您使用线下方式支付，请在支付完成后访问{$link_invoice}页面填写支付信息，我们将在稍后确认您的汇款。</p>";
				break;
			case 'payment_received':
				if($seat_enabled)
				{
					if($seat_lock)
						$intro = "<p>我们已经收到并确认了您的汇款，您已经完成了申请流程。</p><p style='margin-bottom: 0;'>现在，您可以锁定您的席位并完成申请，在此之前，您仍可以调整您席位。</p>";
					else
						$intro = "<p style='margin-bottom: 0;'>我们已经收到并确认了您的汇款，您已经完成了申请流程。稍后，您将可以锁定您的席位，在此之前，您仍可以调整您席位。</p>";
				}
				else
					$intro = "<p style='margin-bottom: 0;'>我们已经收到并确认了您的汇款，您已经完成了申请流程。</p>";
				break;
			case 'locked':
				if($seat_enabled)
					$intro = "<p style='margin-bottom: 0;'>您已经完成了申请流程并锁定了席位。我们将会通过邮件和短信通知后续事项。会场见！</p>";
				else
					$intro = "<p style='margin-bottom: 0;'>您已经完成了申请流程。我们将会通过邮件和短信通知后续事项。会场见！</p>";
				break;
			case 'waitlist_entered':
				$intro = "<p style='margin-bottom: 0;'>很遗憾，您未能通过面试，现在您正在等待队列中。我们将在后续为您提供空余席位，请等待进一步通知。</p>";
				break;
			case 'quitted':
				$this->load->helper('date');
				$lock_time = user_option('quit_time', time()) + option('delegate_quit_lock', 7) * 24 * 60 * 60;
				$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/jquery.countdown.js' : 'static/js/jquery.countdown.min.js').'"></script>');
				$this->ui->js('footer', "$('#clock_lock').countdown({$lock_time} * 1000, function(event) {
					$(this).html(event.strftime('%-D 天 %H:%M:%S'));
				});");
				
				$lock_nicetime = nicetime($lock_time);
				$link_contact = safe_mailto(option('site_contact_email', 'contact@iplacard.com'), '联系管理员');
				
				$intro = "<p style='margin-bottom: 0;'>您已退会，您的 iPlacard 帐户数据将在 <span id='clock_lock'>{$lock_nicetime}</span> 秒内被停用，届时您将无法登录系统。如果这是管理员的误操作请立即{$link_contact}恢复帐户。</p>";
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
	function _check_selectability($array)
	{
		$this->load->model('seat_model');
		
		if(empty($array))
			return false;
		
		if(is_string($array))
			$array = array($array);
		
		$selectabilities = $this->seat_model->get_delegate_selectability($this->uid, false, 'seat');
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
	 * 检查席位是否可选
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
	
	/**
	 * 检查唯一身份标识符是否已经存在
	 */
	function _check_unique_id($str)
	{
		if(empty($str))
			return true;
		
		return !$this->delegate_model->get_delegate_id('unique_identifier', $str);
	}
}

/* End of file apply.php */
/* Location: ./application/controllers/apply.php */