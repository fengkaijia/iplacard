<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 帐户控制器
 * @package iPlacard
 * @since 2.0
 */
class Account extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->library('form_validation');
		$this->load->library('user_agent');
		$this->load->library('ui', array('side' => 'account'));
		$this->load->helper('form');
		
		//IE Postback检查
		$this->session->set_userdata('init', uniqid());
	}
	
	/**
	 * 跳转页
	 */
	function index()
	{
		//登录后跳转到首页
		if(is_logged_in())
		{
			if($this->user_model->is_admin(uid()))
			{
				redirect('admin/dashboard');
				return;
			}
			redirect('apply/status');
			return;
		}
		
		//未登录跳转到登录页面
		redirect('account/login');
	}
	
	/**
	 * 登录
	 */
	function login()
	{
		if(is_logged_in())
		{
			redirect('');
			return;
		}
		
		//不在此页面重定向
		$this->session->keep_flashdata('redirect');
		
		$this->form_validation->set_rules('email', '电子邮箱地址', 'trim|required|valid_email|callback__check_login');
		$this->form_validation->set_rules('password', '密码', 'trim|required');
		$this->form_validation->set_message('valid_email', '电子邮箱地址无效。');
		$this->form_validation->set_error_delimiters('<div class="alert alert-dismissable alert-warning alert-block">'
				. '<button type="button" class="close" data-dismiss="alert">×</button>'
				. '<strong>错误</strong> ', '</div>');
		
		if($this->form_validation->run() == true)
		{
			//获取用户ID
			$id = $this->user_model->login($this->input->post('email'), $this->input->post('password'));
			if($id != false)
			{
				//两步验证
				if(user_option('twostep_enabled', false, $id))
				{
					$this->load->model('twostep_model');
					$this->load->library('twostep');
					
					//需要验证
					if(!$this->twostep->safe_exists($id, $this->input->cookie('iplacardtwostepsafecode', true), $this->agent->agent_string(), 30 * 24 * 60 * 60))
					{
						$this->session->set_userdata('uid_twostep', $id);
						redirect('account/twostep');
					}
				}
				
				//执行登录操作
				$this->_do_login($id);
				return;
			}
			else
			{
				$this->ui->alert('非常抱歉，我们遇到了一个未知的错误无法获取您的帐户信息，请重新尝试登录。', 'danger');
			}
		}
		
		//IE 10 Postback提示
		if($this->session->userdata('dismiss_internet_explorer_postback_notice') != true && option('check_internet_explorer_postback', false))
		{
			if($this->agent->is_browser('Internet Explorer') && $this->agent->version() == '10.0')
				$this->ui->alert(sprintf('您的浏览器被报告存在一个缺陷可能导致无法登录iPlacard，如果您长时间无法正常登录请尝试使用其他浏览器，例如 %1$s 和 %2$s。', anchor('https://www.google.com/chrome/', 'Google Chrome'), anchor('http://www.firefox.com/', 'Mozilla Firefox')), 'info');	
		}
		$this->session->set_userdata('dismiss_internet_explorer_postback_notice', true);
		
		//欧盟Cookie法案提示
		if($this->session->userdata('dismiss_eu_cookie_law_notice') != true && option('check_eu_cookie_law_notice', false))
		{
			$this->ui->alert('Users from the EU: We use cookies to ensure that you can login to iPlacard smoothly. If you continue, we\'ll assume that you are happy to receive all cookies from this website. / 来自欧盟的用户：我们使用 Cookie 以保证您可以正常登录到 iPlacard，如果您继续访问，我们将认为您非常高兴接收本站的 Cookie。', 'success');	
		}
		$this->session->set_userdata('dismiss_eu_cookie_law_notice', true);
		
		//显示警告信息
		if($this->session->userdata('halt') == true)
		{
			$this->load->helper('date');
			$this->ui->alert(sprintf('您的本次登录已于%s被强制登录，请重新登录。', nicetime($this->session->userdata('halt_time'), true)));
			$this->session->unset_userdata('halt');
			$this->session->unset_userdata('halt_time');
		}
		if($this->session->userdata('login_try') == 10)
			$this->ui->alert(sprintf('这将会是您的最后一次登录尝试，如果仍然登录失败您将会被暂停登录 10 分钟，如果您忘记了密码请<a href="%s" class="alert-link">重置密码</a>。', base_url('account/recover')));
		
		$this->ui->title('登录');
		$this->ui->background();
		$this->load->view('account/auth/login');
	}
	
	/**
	 * 登出
	 */
	function logout()
	{
		if(!is_logged_in() && !is_pending_twostep())
		{
			login_redirect();
			return;
		}
		
		//登出后重定向
		$redirect = $this->session->flashdata('redirect');
		
		//执行登出操作
		$this->_do_logout();
		
		$this->ui->alert('您已成功登出。', 'success', true);
		
		if(!empty($redirect))
		{
			redirect(urldecode($redirect));
			return;
		}
		redirect('account/login');
	}
	
	/**
	 * 两步验证
	 */
	function twostep()
	{
		if(is_logged_in() || !is_pending_twostep())
		{
			redirect('');
			return;
		}
		
		//不在此页面重定向
		$this->session->keep_flashdata('redirect');
		
		$this->form_validation->set_rules('code', '验证码', 'trim|required|integer|exact_length[6]|callback__check_twostep_code');
		$this->form_validation->set_message('exact_length', '验证码必须是六位数字。');
		$this->form_validation->set_error_delimiters('<div class="alert alert-dismissable alert-warning alert-block">'
				. '<button type="button" class="close" data-dismiss="alert">×</button>'
				. '<strong>错误</strong> ', '</div>');
		
		if($this->form_validation->run() == true)
		{
			//获取用户ID
			$id = $this->session->userdata('uid_twostep');
			$this->session->unset_userdata('uid_twostep');
			if($this->user_model->user_exists($id))
			{
				//30天内不再验证
				if($this->input->post('safe'))
				{
					$this->load->helper('string');
					$cookie_code = random_string('alnum', 32);
					
					$this->twostep->add_safe($id, $cookie_code, $this->input->ip_address(), $this->agent->agent_string());
					
					$this->input->set_cookie(array(
						'name' => 'iplacardtwostepsafecode',
						'value' => $cookie_code,
						'expire' => 30 * 24 * 60 * 60,
						'secure' => true
					));
				}
				$this->_do_login($id);
				return;
			}
			else
			{
				$this->ui->alert('非常抱歉，我们遇到了一个未知的错误无法获取您的帐户信息，请重新尝试登录。', 'danger');
			}
		}
		
		$this->ui->title('两步验证');
		$this->ui->background();
		$this->load->view('account/auth/twostep');
	}
	
	/**
	 * 短信验证
	 */
	function sms($action = 'request')
	{
		if(is_logged_in() || !is_pending_twostep())
		{
			redirect('');
			return;
		}
		
		//不在此页面重定向
		$this->session->keep_flashdata('redirect');
		
		//获取用户ID
		$id = $this->session->userdata('uid_twostep');
		$user = $this->user_model->get_user($id);
		
		$this->ui->title('短信验证');
		$this->ui->background();
		
		//验证页面
		if($action == 'validate')
		{
			//检查是否有效
			if(!user_option('sms_validate_time', false, $id) || user_option('sms_validate_time', 0, $id) < time() - 60 * 60)
			{
				$this->ui->alert('您的验证码不存在或者已经超过 1 小时有效期，请重新发送验证码。', 'warning', true);
				redirect('account/sms/request');
				return;
			}
			
			$this->form_validation->set_rules('code', '短信验证码', 'trim|required|integer|exact_length[6]|callback__check_sms_code');
			$this->form_validation->set_message('exact_length', '短信验证码必须是六位数字。');
			$this->form_validation->set_error_delimiters('<div class="alert alert-dismissable alert-warning alert-block">'
					. '<button type="button" class="close" data-dismiss="alert">×</button>'
					. '<strong>错误</strong> ', '</div>');

			if($this->form_validation->run() == true)
			{
				//取消验证码
				$this->user_model->delete_user_option('sms_validate_code', $id);
				$this->user_model->delete_user_option('sms_validate_time', $id);
				
				//关闭两步验证
				if($this->input->post('close'))
				{
					$this->user_model->edit_user_option('twostep_enabled', false, $id);
					
					//发送邮件
					$this->load->library('email');
					$this->load->library('parser');
					$this->load->helper('date');

					$data = array(
						'uid' => $id,
						'name' => $user['name'],
						'email' => $user['email'],
						'time' => unix_to_human(time()),
						'ip' => $this->input->ip_address(),
						'url' => base_url('account/recover')
					);

					$this->email->to($user['email']);
					$this->email->subject('您的 iPlacard 两步验证已经关闭');
					$this->email->html($this->parser->parse_string(option('email_account_twostep_disabled_via_sms', "您的 iPlacard 帐户 {email} 的两步验证保护已经于 {time} 由 IP {ip} 的用户通过短信验证方式关闭。如非本人操作，请立即访问：\n\n"
							. "\t{url}\n\n"
							. "修改密码。"), $data, true));
					
					if(!$this->email->send())
					{
						$this->system_model->log('notice_failed', array('id' => $id, 'type' => 'email', 'content' => 'twostep_disabled_via_sms'));
					}	
					
					//记录日志
					$this->system_model->log('twostep_disabled', array('ip' => $this->input->ip_address(), 'via' => 'sms_validate'), $id);
				}
				
				//记录日志
				$this->system_model->log('sms_validate_passed', array('ip' => $this->input->ip_address()), $id);
				
				$this->_do_login($id);
				return;
			}
			
			//上次发送时间
			$code_time = user_option('sms_validate_time', 0, $id);
			
			$data = array(
				'phone_number' => $user['phone'],
				'expire_time' => 3599 - time() + $code_time,
				'resend_time' => 180 - time() + $code_time
			);
			
			$this->load->view('account/auth/sms_validate', $data);
			return;
		}
		
		//发送验证码
		if($this->input->post('request'))
		{
			//检查是否允许发送
			if(!user_option('sms_validate_time', false, $id) || user_option('sms_validate_time', 0, $id) < time() - 3 * 60)
			{
				//生成六位随机数字
				$this->load->helper('string');
				$code = random_string('numeric', 6);

				//记录验证码
				$this->user_model->edit_user_option('sms_validate_code', $code, $id);
				$this->user_model->edit_user_option('sms_validate_time', time(), $id);

				//发送短信通知
				$this->load->model('sms_model');
				$this->load->library('sms');

				$this->sms->to($id);
				$this->sms->message(sprintf('您的 iPlacard 短信验证码为 %s。一小时内有效。', $code));
				$this->sms->send();

				//记录日志
				$this->system_model->log('sms_validate_requested', array('ip' => $this->input->ip_address()), $id);
				
				redirect('account/sms/validate');
				return;
			}

			$this->ui->alert('您已于稍早前请求短信验证，验证码已经发送，请等待 3 分钟后再次请求重新发送验证码。', 'danger');
		}
		
		$this->load->view('account/auth/sms_request');
	}
	
	/**
	 * 请求密码重置
	 */
	function recover()
	{
		if(is_logged_in())
		{
			redirect('');
			return;
		}
		
		$vars = array('sent' => false);
		
		$this->form_validation->set_rules('email', '电子邮箱地址', 'trim|required|valid_email|callback__check_password_recover');
		$this->form_validation->set_rules('name', '姓名', 'trim|required');
		$this->form_validation->set_message('valid_email', '电子邮箱地址无效。');
		$this->form_validation->set_error_delimiters('<div class="alert alert-dismissable alert-warning alert-block">'
				. '<button type="button" class="close" data-dismiss="alert">×</button>'
				. '<strong>错误</strong> ', '</div>');
		
		if($this->form_validation->run() == true)
		{
			//获取用户信息
			$uid = $this->user_model->get_user_id('email', $this->input->post('email'));
			$user = $this->user_model->get_user($uid);
			
			//生成重置信息
			$recover_time = time();
			$recover_key = strtoupper(substr(sha1($uid.$recover_time), 20));
			
			//记录重置信息
			$this->user_model->edit_user_option('account_recover_key', $recover_key, $uid);
			$this->user_model->edit_user_option('account_recover_time', $recover_time, $uid);
			
			//发送邮件
			$this->load->library('email');
			$this->load->library('parser');
			$this->load->helper('date');
			
			$data = array(
				'uid' => $uid,
				'name' => $user['name'],
				'email' => $user['email'],
				'time' => unix_to_human($recover_time),
				'ip' => $this->input->ip_address(),
				'url' => base_url("account/reset/$uid/$recover_key"),
			);
			
			$this->email->to($user['email']);
			$this->email->subject('重置您的 iPlacard 密码');
			$this->email->html($this->parser->parse_string(option('email_account_request_password_reset', "我们于 {time} 收到来自 IP {ip} 的请求重置您的 iPlacard 帐户 {email} 的密码，如果确认操作请点击访问以下链接：\n\n"
					. "\t{url}\n\n"
					. "为安全起见，此链接仅在 24 小时内有效并且仅限使用一次。"), $data, true));
			
			if(!$this->email->send())
			{
				//显示发送失败提示
				$this->ui->alert('非常抱歉，我们遇到了一个未知的错误导致未能成功向您发生密码重置邮件，请重新尝试。', 'danger');
				$this->system_model->log('notice_failed', array('id' => $uid, 'type' => 'email', 'content' => 'request_password_reset'));
			}
			else
			{
				//发送成功记录日志
				$this->system_model->log('requested_password_reset', array('ip' => $this->input->ip_address()), $uid);
				
				$vars = array(
					'sent' => true,
					'email' => $user['email']
				);
			}
		}
		
		$this->ui->title('密码重置');
		$this->ui->background();
		$this->load->view('account/auth/recover', $vars);
	}
	
	/**
	 * 重置密码
	 */
	function reset($uid, $key)
	{
		if(is_logged_in())
		{
			redirect('');
			return;
		}
		
		//验证数据传入
		if(empty($uid) || empty($key))
		{
			$this->ui->alert('无效的密码重置请求。', 'danger', true);
			redirect('account/recover');
			return;
		}
		
		$user = $this->user_model->get_user($uid);
		$recover_key = user_option('account_recover_key', false, $uid);
		$recover_time = user_option('account_recover_time', false, $uid);
		
		//验证用户
		if(!$user || $recover_key != $key)
		{
			$this->ui->alert('无效的密码重置请求。', 'danger', true);
			redirect('account/recover');
			return;
		}

		//验证链接有效性
		if(time() > $recover_time + 60 * 60 * 24)
		{
			$this->ui->alert('您的密码重置链接已经失效，请重新请求重置并在 24 小时内完成操作。', 'danger', true);
			redirect('account/recover');
			return;
		}
		
		$this->form_validation->set_rules('password', '密码', 'trim|required|min_length[8]');
		$this->form_validation->set_rules('password_repeat', '确认密码', 'trim|required|matches[password]');
		$this->form_validation->set_error_delimiters('<div class="alert alert-block">'
				. '<button type="button" class="close" data-dismiss="alert">×</button>'
				. '<strong>错误</strong> ', '</div>');
		
		if($this->form_validation->run() == true)
		{
			$this->session->unset_userdata('login_try');
			$this->session->unset_userdata('login_try_last');
			
			$this->user_model->edit_user(array(
				'password' => trim($this->input->post('password')),
			), $uid);
			
			//记录更改时间
			$this->user_model->edit_user_option('account_change_password_time', time());
			
			$this->user_model->delete_user_option('account_recover_key', $uid);
			$this->user_model->delete_user_option('account_recover_time', $uid);
			
			$this->ui->alert('您的密码已经重置，现在您可以使用新的密码登录。', 'success', true);
			
			//发送邮件通知
			$this->load->library('email');
			$this->load->library('parser');
			$this->load->helper('date');
			
			$data = array(
				'uid' => $uid,
				'name' => $user['name'],
				'email' => $user['email'],
				'time' => unix_to_human(time()),
				'ip' => $this->input->ip_address(),
				'url' => base_url('account/recover'),
			);
			
			$this->email->to($user['email']);
			$this->email->subject('您的 iPlacard 密码已经重置');
			$this->email->html($this->parser->parse_string(option('email_account_password_reset', "您的 iPlacard 帐户 {email} 的密码已经于 {time} 由来自 IP {ip} 的用户重置，如非本人操作请立即访问以下链接重置您的密码：\n\n\t{url}"), $data, true));
			$this->email->send();
			
			$this->system_model->log('password_reset', array('ip' => $this->input->ip_address()), $uid);
			redirect('account/login');
			return;
		}
		
		$this->ui->title('密码重置');
		$this->ui->background();
		$this->load->view('account/auth/reset', array('email' => $user['email'], 'uri' => "$uid/$key"));
	}
	
	/**
	 * 强制注销登录的Session
	 */
	function halt($halt, $md5)
	{
		//需要注销的ID
		$halt_id = intval(substr($halt, 0, -4));
		
		//ID是否为0
		if(!$halt_id)
		{
			$this->ui->alert('无效的强制登出请求。', 'danger', true);
			redirect('account/login');
			return;
		}
		
		//获取session_id
		$this->db->where('id', $halt_id);
		$query = $this->db->get('session');
		
		//已经自行注销
		if($query->num_rows() == 0)
		{
			$no_action = true;
		}
		else
		{
			$no_action = false;
			
			//获取信息
			$sess_data = $query->row_array();
			
			//链接错误
			if(md5($sess_data['session_id']) != strtolower($md5))
			{
				$this->ui->alert('无效的强制登出请求。', 'danger', true);
				redirect('account/login');
				return;
			}
			
			//强制退出
			$this->_do_halt($halt_id);
			$this->system_model->log('session_halted', array('ip' => $this->input->ip_address(), 'session' => $halt_id, 'userdata' => $sess_data['user_data'], 'panel' => false), 0);
		}
		
		$this->ui->title('强制登出');
		$this->ui->background();
		$this->load->view('account/auth/halt', array('no_action' => $no_action));
	}
	
	/**
	 * 启用SUDO模式
	 */
	function sudo($id = '')
	{
		$this->load->model('admin_model');
		
		if(!$this->user_model->is_admin(uid(true)) && !$this->admin_model->capable('administrator', uid(true)))
		{
			$this->ui->alert('权限不足。', 'danger', true);
			back_redirect();
			return;
		}
		
		//退出SUDO模式
		if(is_sudo())
		{
			$sudoer = uid(true);
			
			$this->session->unset_userdata(array(
				'sudoer' => ''
			));
			
			$this->ui->alert('已经退出 SUDO 模式。', 'success', true);
			
			$this->_do_logout('sudo_operation', true, false);
			$this->_do_login($sudoer, true, false);
			redirect('');
			return;
		}
		
		//进入SUDO模式
		if(!$this->user_model->is_delegate($id))
		{
			$this->ui->alert('对象实体不满足 SUDO 条件。', 'danger', true);
			back_redirect();
			return;
		}
		
		$sudoer = uid();
		$this->session->set_userdata(array(
			'sudoer' => $sudoer
		));
		
		$this->ui->alert(sprintf('已经启用 SUDO 模式，当前以%s代表视角登录 iPlacard。', $this->user_model->get_user($id, 'name')), 'success', true);
		
		$this->_do_logout('sudo_operation', true, true);
		$this->_do_login($id, $sudoer, true);
	}
	
	/**
	 * 邮箱更改验证
	 */
	function email($action, $uid, $key)
	{
		if(!in_array($action, array('confirm', 'cancel')))
		{
			$this->ui->alert('无效的请求。', 'danger', true);
			redirect('');
			return;
		}
		
		$this->load->library('email');
		$this->load->library('parser');
		$this->load->helper('date');
		
		//获取信息
		$user = $this->user_model->get_user($uid);
		$new_email = user_option('account_email_pending', false, $uid);
		$change_key = user_option('account_email_change_key', false, $uid);
		$change_time = user_option('account_email_change_time', false, $uid);
		$old_email = user_option('account_email_old', false, $uid);
		$cancel_key = user_option('account_email_cancel_key', false, $uid);
		$sudo = user_option('account_email_pending_sudo', false, $uid);
		
		//验证登录情况
		if(is_logged_in())
		{
			//非请求帐户登录时登出原帐户
			if(uid() != $uid)
			{
				$this->ui->alert('请使用验证请求的帐户登录 iPlacard。', 'warning', true);
				$this->_do_logout('email_change');
				login_redirect();
				return;
			}
		}
		else
		{
			if($action == 'confirm' && !$sudo)
			{
				$this->ui->alert('请登录 iPlacard 以完成验证。在完成验证之前，登录时您的帐户仍然为旧的电子邮箱地址。', 'info', true);
				login_redirect();
				return;
			}
		}
		
		//邮箱有效性确认操作
		if($action == 'confirm')
		{
			//验证用户
			if(!$user || $change_key != $key)
			{
				$this->ui->alert('无效的验证请求。', 'danger', true);
				redirect('account/settings/home');
				return;
			}

			//验证链接有效性
			if(time() > $change_time + 60 * 60 * 24)
			{
				$this->ui->alert('您的邮箱确认链接已经失效，请重新更改邮箱并在 24 小时内完成确认操作。', 'danger', true);
				redirect('account/settings/home');
				return;
			}

			//更改邮箱
			$this->user_model->edit_user(array(
				'email' => $new_email
			), $uid);

			$this->user_model->delete_user_option('account_email_pending', $uid);
			$this->user_model->delete_user_option('account_email_change_key', $uid);
			$this->user_model->delete_user_option('account_email_change_time', $uid);
			$this->user_model->delete_user_option('account_email_pending_sudo', $uid);
			$this->user_model->edit_user_option('account_email_old', $user['email'], $uid);

			$this->ui->alert('您的新邮箱已经完成验证，现在你可以使用新邮箱登录 iPlacard。', 'success', true);

			//发送邮件通知
			$data = array(
				'uid' => $user['id'],
				'name' => $user['name'],
				'old_email' => $user['email'],
				'new_email' => $new_email,
				'time' => unix_to_human(time()),
				'request_time' => unix_to_human($change_time),
				'ip' => $this->input->ip_address(),
				'cancel_url' => base_url("account/email/cancel/$uid/$cancel_key"),
			);

			//通知旧邮箱
			$this->email->clear();
			$this->email->to($user['email']);
			$this->email->subject('此邮箱的 iPlacard 帐户绑定已经取消');
			$this->email->html($this->parser->parse_string(option('email_account_email_verification_lost', "您的 iPlacard 帐户 {old_email} 于 {request_time} 申请将绑定电子邮箱更换为 {new_email}，新的邮箱已经于 {time} 由 IP {ip} 的用户完成验证，此邮箱的 iPlacard 帐户绑定已经取消。请立即访问：\n\n"
					. "\t{cancel_url}\n\n"
					. "取消本次修改，同时请考虑修改密码。"), $data, true));
					
			if(!$this->email->send())
			{
				$this->system_model->log('notice_failed', array('id' => $uid, 'type' => 'email', 'content' => 'email_changed'));
			}
			
			//通知新邮箱
			$this->email->clear();
			$this->email->to($new_email);
			$this->email->subject('此邮箱已经通过 iPlacard 验证');
			$this->email->html($this->parser->parse_string(option('email_account_email_verified', "您的新 iPlacard 帐户邮箱 {new_email} 已经于 {time} 由 IP {ip} 的用户完成验证，旧邮箱 {old_email} 的绑定已经取消。现在您可以通过新邮箱登录 iPlacard。"), $data, true));
					
			if(!$this->email->send())
			{
				$this->system_model->log('notice_failed', array('id' => $uid, 'type' => 'email', 'content' => 'email_changed'));
			}

			$this->system_model->log('email_changed', array('ip' => $this->input->ip_address(), 'new' => $new_email, 'old' => $user['email']), $uid);
		}
		
		//邮箱更改取消
		if($action == 'cancel')
		{
			//验证用户
			if(!$user || $cancel_key != $key)
			{
				$this->ui->alert('无效的撤销请求或者此链接已经失效。', 'danger', true);
				redirect('account/settings/home');
				return;
			}
			
			//是否已经变更
			if(!$new_email && $old_email)
			{
				$new_email = $user['email'];
			}

			//更改邮箱
			$this->user_model->edit_user(array(
				'email' => $old_email
			), $uid);

			$this->user_model->delete_user_option('account_email_old', $uid);
			$this->user_model->delete_user_option('account_email_change_key', $uid);
			$this->user_model->delete_user_option('account_email_change_time', $uid);
			$this->user_model->delete_user_option('account_email_change_time', $uid);
			$this->user_model->delete_user_option('account_email_cancel_key', $uid);
			$this->user_model->delete_user_option('account_email_pending_sudo', $uid);

			$this->ui->alert('您的邮箱更改已经取消。', 'success', true);

			//发送邮件通知
			$data = array(
				'uid' => $user['id'],
				'name' => $user['name'],
				'email' => $user['email'],
				'old_email' => $old_email,
				'new_email' => $new_email,
				'time' => unix_to_human(time()),
				'request_time' => unix_to_human($change_time),
				'ip' => $this->input->ip_address()
			);

			//通知旧邮箱
			$this->email->clear();
			$this->email->to($old_email);
			$this->email->subject('iPlacard 帐户邮箱变更已经取消');
			$this->email->html($this->parser->parse_string(option('email_account_email_change_cancelled', "您的 iPlacard 帐户 {email} 的电子邮箱更换请求已经于 {time} 由 IP {ip} 的用户取消。"), $data, true));
					
			if(!$this->email->send())
			{
				$this->system_model->log('notice_failed', array('id' => $uid, 'type' => 'email', 'content' => 'email_changed'));
			}
			
			//通知新邮箱
			$this->email->clear();
			$this->email->to($new_email);
			$this->email->subject('iPlacard 帐户邮箱验证已经取消');
			$this->email->html($this->parser->parse_string(option('email_account_email_verificaiton_cancelled', "您的 iPlacard 帐户 {email} 的电子邮箱更换请求已经于 {time} 由 IP {ip} 的用户取消，此邮箱的 iPlacard 帐户验证申请已经失效。您将需要使用旧邮箱登录 iPlacard。"), $data, true));
					
			if(!$this->email->send())
			{
				$this->system_model->log('notice_failed', array('id' => $uid, 'type' => 'email', 'content' => 'email_changed'));
			}

			$this->system_model->log('email_change_cancelled', array('ip' => $this->input->ip_address(), 'request' => $new_email, 'current' => $old_email), $uid);
		}
		
		redirect('account/settings/home');
		return;
	}
	
	/**
	 * 当前活动情况
	 */
	function activity()
	{
		if(!is_logged_in())
		{
			login_redirect();
			return;
		}
		
		$this->load->helper('ip');
		$this->load->helper('date');
		
		//获取记录
		$this->db->where('operator', uid());
		$this->db->where('operation', 'logged_in');
		$this->db->order_by('time', 'desc');
		$this->db->limit(20);
		$query = $this->db->get('log');
		
		if($query->num_rows() != 0)
		{
			$data = array();
			foreach($query->result_array() as $data)
			{
				//检查是否仍然在线
				$data['value'] = json_decode($data['value'], true);
				$this->db->where('id', $data['value']['session']);
				$check = $this->db->get('session');
				
				if($check->num_rows() != 0)
				{
					$session = $check->row_array();
					
					if($this->input->post('halt') && $session['session_id'] != $this->session->userdata('session_id'))
					{
						//确认退出其他所有会话
						$this->_do_halt($data['value']['session']);
						$this->system_model->log('session_halted', array('ip' => $this->input->ip_address(), 'session' => $session['session_id'], 'userdata' => $session['user_data'], 'panel' => true), uid());
					}
					else
					{
						//仅显示数据
						$data['last_activity'] = $session['last_activity'];
						$data['value']['place'] = ip_lookup($data['value']['ip']);
						
						//是否是当前位置
						if($session['session_id'] == $this->session->userdata('session_id'))
							$data['current'] = true;
						
						//是否已经关闭
						$user_data = $this->session->_unserialize($session['user_data']);
						if(!isset($user_data['halt']) || !$user_data['halt'])
							$active[] = $data;
					}
				}
				$check->free_result();
			}
			$query->free_result();
			
			if($this->input->post('halt'))
			{
				$this->ui->alert('已经强制登出位于其他位置的会话活动。', 'success');
			}
			
			$vars = array();
			
			//根据登录时间排序
			if(!empty($active))
			{
				foreach($active as $one)
				{
					$time[] = $one['time'];
				}
				array_multisort($time, SORT_DESC, $active);
				$vars['active'] = $active;
			}
		}
		
		$this->ui->title('当前会话活动');
		$this->load->view('account/manage/activity', $vars);
	}
	
	/**
	 * 帐户设置
	 */
	function settings($setting = 'home', $action = '')
	{
		if(!is_logged_in())
		{
			login_redirect();
			return;
		}
		
		$this->load->library('email');
		$this->load->library('parser');
		$this->load->helper('date');
		
		if(!in_array($setting, array('home', 'security', 'password', 'pin', 'twostep', 'avatar')))
			$setting = 'home';
		
		//当前用户信息
		$uid = uid();
		$user = $this->user_model->get_user($uid);
		
		$this->form_validation->set_error_delimiters('<div class="help-block">', '</div>');
		
		//安全设置
		if($setting == 'avatar')
		{
			$this->load->helper('file');
			$this->load->helper('avatar');
			
			if($action == 'upload')
			{
				$this->load->helper('string');
				
				//曾经上传的图像
				$former_uploaded = user_option('account_avatar_uploaded', false);
				$vars = array(
					'path' => 'temp/'.IP_INSTANCE_ID.'/upload/avatar/'
				);
				
				if($this->input->post('change_avatar'))
				{
					$this->load->helper('number');
					$this->load->helper('file');
					
					//操作上传图像
					$random = random_string('alnum', 32);
					$config['file_name'] = "{$uid}_{$random}";
					$config['allowed_types'] = 'gif|jpg|png|bmp';
					$config['max_size'] = ini_max_upload_size(option('avatar_max_size', option('file_max_size', 10 * 1024 * 1024))) / 1024;
					$config['upload_path'] = './temp/'.IP_INSTANCE_ID.'/upload/avatar/';

					if(!file_exists($config['upload_path']))
						mkdir($config['upload_path'], DIR_WRITE_MODE, true);

					$this->load->library('upload', $config);

					//储存上传文件
					if(!$this->upload->do_upload('avatar_file'))
					{
						$this->ui->alert($this->upload->display_errors('', ''), 'danger', true);
						redirect('account/settings/home');
						return;
					}

					$result = $this->upload->data();
					
					//如果曾经已经上传临时图像
					if($former_uploaded)
					{
						delete_files($config['upload_path'].$former_uploaded);
					}
					
					//储存上传的文件名
					$this->user_model->edit_user_option('account_avatar_uploaded', $result['file_name']);
					
					$this->system_model->log('avatar_uploaded', $result);
					
					$vars['width'] = $result['image_width'];
					$vars['height'] = $result['image_height'];
					
					$vars['filename'] = $result['file_name'];
				}
				elseif($former_uploaded)
				{
					//操作使用早前上传的图像
					list($width, $height) = getimagesize($vars['path'].$former_uploaded);
					$vars['width'] = $width;
					$vars['height'] = $height;
					
					$vars['filename'] = $former_uploaded;
				}
				else
				{
					//之前、操作无上传图像
					$this->ui->alert('您未上传图像，请先上传图像再进行设置。', 'warning', true);
					redirect('account/settings/home');
					return;
				}

				$this->ui->title('设置头像');
				$this->load->view('account/manage/avatar', $vars);
				return;
			}
			elseif($action == 'crop')
			{
				if($this->input->post('crop_avatar'))
				{
					$upload = user_option('account_avatar_uploaded', false);
					
					if(!$upload)
					{
						$this->ui->alert('您未上传图像，请先上传图像再进行设置。', 'warning', true);
						redirect('account/settings/home');
						return;
					}
					
					$path = './data/'.IP_INSTANCE_ID.'/avatar/'.$uid.'/';
					
					if(!file_exists($path))
						mkdir($path, DIR_WRITE_MODE, true);
					
					list($raw, $ext) = explode('.', $upload);
					
					$this->load->library('image_lib');
					$config['source_image'] = './temp/'.IP_INSTANCE_ID.'/upload/avatar/'.$upload;
					$this->image_lib->initialize($config);
					
					//删除旧头像
					delete_files($path);
					
					//保存原始文件
					copy($config['source_image'], "{$path}original.{$ext}");
					
					//格式转换
					if($ext != 'jpg')
					{
						$config['new_image'] = './temp/'.IP_INSTANCE_ID.'/upload/avatar/'.$raw.'.jpg';
						$this->image_lib->initialize($config);
						
						$this->image_lib->convert('jpg');
						$this->image_lib->clear();
						
						unlink($config['source_image']);
						
						$config['source_image'] = $config['new_image'];
					}
					
					//裁剪图像
					$crop_config = $config;
					$crop_config['x_axis'] = $this->input->post('x');
					$crop_config['y_axis'] = $this->input->post('y');
					$crop_config['width'] = $this->input->post('w');
					$crop_config['height'] = $this->input->post('h');
					$crop_config['maintain_ratio'] = false;
					
					print_r($crop_config);
					
					$this->image_lib->initialize($crop_config);
					
					if(!$this->image_lib->crop())
					{
						echo $this->image_lib->display_errors();
					}
					$this->image_lib->clear();
					
					//预生成图像
					$target = array(20, 26, 40, 80, 160, 320);
					foreach($target as $size)
					{
						$this->_do_avatar_resize($config['source_image'], $size, $path.$size.'.jpg');
					}
				
					//设置图像
					unlink($config['source_image']);
					$this->user_model->edit_user_option('account_avatar_enabled', true);
					$this->user_model->delete_user_option('account_avatar_uploaded');
					
					$this->ui->alert('头像设置成功。', 'success', true);
					
					$this->system_model->log('avatar_setted', array('crop' => array('x' => $this->input->post('x'), 'y' => $this->input->post('y'), 'w' => $this->input->post('w'))));
				}
			}
			
			redirect('account/settings/home');
			return;
		}
		
		//安全设置
		if($setting == 'security')
		{
			//邮件通知数据
			$notice_options = array(
				'login' => array(
					'name' => '帐户登录',
					'description' => '登录时发送电子邮件通知'
				),
			);
			
			//数据值
			foreach($notice_options as $name => $option)
			{
				$notice_options[$name]['value'] = user_option("account_notice_{$name}_enabled", false);
			}
			
			//密码验证
			$this->form_validation->set_rules('password', '密码', 'trim|required|callback__check_password[密码验证错误导致安全设置更改未完成，请重新尝试。]');
			$this->form_validation->set_message('_check_password', '密码有误，请重新输入。');
			
			if($this->form_validation->run() == true)
			{
				$new_enabled = array();
				$new_disabled = array();
				
				foreach($notice_options as $name => $option)
				{
					$set_option = $this->input->post("notice_$name");
					
					//执行修改
					if($set_option != $option['value'])
					{
						if($set_option)
						{
							//启用设置
							$this->user_model->edit_user_option("account_notice_{$name}_enabled", true);
							$notice_options[$name]['value'] = true;
							$new_enabled[] = $name;
							
							$this->ui->alert("{$option['name']}邮件通知已经启用。", 'success');
						}
						else
						{
							//停用设置
							$this->user_model->edit_user_option("account_notice_{$name}_enabled", false);
							$notice_options[$name]['value'] = false;
							$new_disabled[] = $name;
							
							$this->ui->alert("{$option['name']}邮件通知已经停用。", 'success');
						}
					}
				}
				
				//检查是否存在更改
				if(!empty($new_enabled) || !empty($new_disabled))
				{
					$enable_text = '';
					$disable_text = '';
					if(!empty($new_enabled))
					{
						$this->system_model->log('security_notice_enabled', array('ip' => $this->input->ip_address(), 'enabled' => $new_enabled), $uid);

						$enable_list = "";
						foreach($new_enabled as $option)
						{
							$enable_list .= "\t{$notice_options[$option]['name']}：启用{$notice_options[$option]['description']}\n";
						}
						$enable_text = "新近启用的邮件通知设置：\n\n{$enable_list}\n";
					}

					if(!empty($new_disabled))
					{
						$this->system_model->log('security_notice_disabled', array('ip' => $this->input->ip_address(), 'disabled' => $new_disabled), $uid);

						$disable_list = "";
						foreach($new_disabled as $option)
						{
							$disable_list .= "\t{$notice_options[$option]['name']}：停用{$notice_options[$option]['description']}\n";
						}
						$disable_text = "新近停用的邮件通知设置：\n\n{$disable_list}\n";
					}

					//发送邮件通知
					$data = array(
						'uid' => $user['id'],
						'name' => $user['name'],
						'email' => $user['email'],
						'time' => unix_to_human(time()),
						'ip' => $this->input->ip_address(),
						'enabled' => $enable_text,
						'disabled' => $disable_text
					);

					$this->email->to($user['email']);
					$this->email->subject('iPlacard 邮件通知设置已经变更');
					$this->email->html($this->parser->parse_string(option('email_account_password_change', "您的 iPlacard 帐户 {email} 的邮件通知设置已经于 {time} 由来自 IP {ip} 的用户变更。本邮件列出了变更列表，\n\n"
							. "{enabled}"
							. "{disabled}"
							. "如非本人操作请立即登录 iPlacard 还原以上更改并考虑修改密码。"), $data, true));

					if(!$this->email->send())
					{
						$this->system_model->log('notice_failed', array('id' => $uid, 'type' => 'email', 'content' => 'security_settings_changed'));
					}
				}
				else
				{
					$this->ui->alert('没有设置变更。', 'info');
				}
			}
			
			
			//显示信息部分
			$info = array(
				'password' => user_option('account_change_password_time', false),
				'pin' => user_option('account_change_pin_time', false),
				'twostep' => user_option('twostep_enabled', false)
			);
			
			$this->ui->title('帐户安全设置');
			$this->load->view('account/manage/security', array('notice_options' => $notice_options, 'info' => $info));
			return;
		}
		
		//修改密码
		if($setting == 'password')
		{
			$this->form_validation->set_rules('old_password', '旧密码', 'trim|required|callback__check_password');
			$this->form_validation->set_rules('password', '新密码', 'trim|required|not[matches.old_password]|min_length[8]');
			$this->form_validation->set_rules('password_repeat', '重复密码', 'trim|required|matches[password]');
			$this->form_validation->set_message('_check_password', is_sudo() ? '管理员密码有误，请重新输入。' : '旧密码有误，请重新输入。');
			$this->form_validation->set_message('not', is_sudo() ? '代表新密码不能与您的管理员密码相同。' : '新密码不能与旧密码相同。');

			if($this->form_validation->run() == true)
			{
				//修改密码
				$this->user_model->change_password($uid, trim($this->input->post('password')));
				
				//记录更改时间
				$this->user_model->edit_user_option('account_change_password_time', time());
				
				//发送邮件通知
				$data = array(
					'uid' => $user['id'],
					'name' => $user['name'],
					'email' => $user['email'],
					'time' => unix_to_human(time()),
					'ip' => $this->input->ip_address(),
					'url' => base_url('account/recover'),
				);

				$this->email->to($user['email']);
				$this->email->subject('您的 iPlacard 密码已经修改');
				$this->email->html($this->parser->parse_string(option('email_account_password_change', "您的 iPlacard 帐户 {email} 的密码已经于 {time} 由来自 IP {ip} 的用户修改，如非本人操作请立即访问以下链接重置您的密码：\n\n"
						. "\t{url}"), $data, true));
				
				if(!$this->email->send())
				{
					$this->system_model->log('notice_failed', array('id' => $uid, 'type' => 'email', 'content' => 'password_changed'));
				}
				
				$this->ui->alert('密码修改成功。', 'success');
				$this->system_model->log('password_changed', array('ip' => $this->input->ip_address()), $uid);
			}

			$this->ui->title('修改密码');
			$this->load->view('account/manage/password');
			return;
		}
		
		//修改安全码
		if($setting == 'pin')
		{
			$this->form_validation->set_rules('password', '密码', 'trim|required|callback__check_password');
			$this->form_validation->set_rules('pin', '安全码', 'trim|required|min_length[4]');
			$this->form_validation->set_rules('pin_repeat', '重复安全码', 'trim|required|matches[pin]');
			$this->form_validation->set_message('_check_password', '密码有误，请重新输入。');

			if($this->form_validation->run() == true)
			{
				$new_pin = trim($this->input->post('pin'));
				
				//修改安全码
				$this->user_model->edit_user(array('pin_password' => $new_pin), $uid);
				
				//由于盐变动修改密码
				$this->user_model->change_password($uid, trim($this->input->post('password')));
				
				//记录更改时间
				$this->user_model->edit_user_option('account_change_pin_time', time());
				
				//发送邮件通知
				$data = array(
					'uid' => $user['id'],
					'name' => $user['name'],
					'email' => $user['email'],
					'time' => unix_to_human(time()),
					'ip' => $this->input->ip_address(),
					'pin' => $new_pin
				);

				$this->email->to($user['email']);
				$this->email->subject('您的 iPlacard 安全码已经修改');
				$this->email->html($this->parser->parse_string(option('email_account_pin_change', "您的 iPlacard 帐户 {email} 的安全码已经于 {time} 由来自 IP {ip} 的用户修改，新的安全码为\n\n"
						. "\t{pin}\n\n"
						. "为保证您的 PIN 码安全，如非必要请勿保留此邮件。"), $data, true));
				
				if(!$this->email->send())
				{
					$this->system_model->log('notice_failed', array('id' => $uid, 'type' => 'email', 'content' => 'pin_changed'));
				}
				
				$this->ui->alert('安全码修改成功。', 'success');
				$this->system_model->log('pin_changed', array('ip' => $this->input->ip_address()), uid());
			}

			$this->ui->title('设置安全码');
			$this->load->view('account/manage/pin_password');
			return;
		}
		
		//两步验证
		if($setting == 'twostep')
		{
			$this->ui->title('两步验证');
			
			//显示操作类型
			if(user_option('twostep_enabled', false))
				$action = 'disable';
			elseif($action != 'enable')
				$action = 'intro';
			
			//关闭两步验证
			if($action == 'disable')
			{
				$this->form_validation->set_rules('password', '密码', 'trim|required|callback__check_password');
				$this->form_validation->set_rules('confirm', '确认', 'callback__check_confirm');
				$this->form_validation->set_message('_check_password', '密码验证有误，请重新输入。');
				$this->form_validation->set_message('_check_confirm', '需要选中按钮以确认操作。');
				
				if($this->form_validation->run() == true)
				{
					//禁用验证
					$this->user_model->edit_user_option('twostep_enabled', false);
					
					//发送邮件
					$data = array(
						'uid' => $user['id'],
						'name' => $user['name'],
						'email' => $user['email'],
						'time' => unix_to_human(time()),
						'ip' => $this->input->ip_address(),
						'url' => base_url('account/recover')
					);

					$this->email->to($user['email']);
					$this->email->subject('您已停用 iPlacard 两步验证');
					$this->email->html($this->parser->parse_string(option('email_account_login_twostep_disabled_via_panel', "您的 iPlacard 帐户 {email} 的两步验证保护已经于 {time} 由 IP {ip} 的用户通过设置面板停用。如非本人操作，请立即访问：\n\n"
							. "\t{url}\n\n"
							. "修改密码。"), $data, true));
					
					if(!$this->email->send())
					{
						$this->system_model->log('notice_failed', array('id' => $uid, 'type' => 'email', 'content' => 'twostep_disabled_via_panel'));
					}
					
					//记录日志
					$this->system_model->log('twostep_disabled', array('ip' => $this->input->ip_address(), 'via' => 'panel'), $uid);
					
					$this->ui->alert('两步验证已经停用。', 'success', true);
					
					redirect('account/settings/twostep');
					return;
				}
				
				$this->load->view('account/manage/twostep_disable');
				return;
			}
			
			//启用两步验证
			if($action == 'enable')
			{
				$this->load->model('twostep_model');
				$this->load->library('twostep');
				
				//保留此前生成的密钥
				$secret = $this->input->post('secret');
				//如不存在生成密钥
				if(!$secret)
					$secret = $this->twostep->generate_secret();
				
				$this->form_validation->set_rules('code', '验证码', 'trim|required|integer|exact_length[6]|callback__check_twostep_verify');
				$this->form_validation->set_message('exact_length', '验证码必须是六位数字。');
				$this->form_validation->set_message('_check_twostep_verify', '输入的验证码有误，请重新核对您的手机显示的验证码。');
				
				if($this->form_validation->run() == true)
				{
					//启用验证
					$this->user_model->edit_user_option('twostep_enabled', true);
					$this->user_model->edit_user_option('twostep_secret', $secret);
					
					//发送邮件
					$data = array(
						'uid' => $user['id'],
						'name' => $user['name'],
						'email' => $user['email'],
						'time' => unix_to_human(time()),
						'ip' => $this->input->ip_address(),
					);

					$this->email->to($user['email']);
					$this->email->subject('您已启用 iPlacard 两步验证');
					$this->email->html($this->parser->parse_string(option('email_account_login_twostep_enabled', "您的 iPlacard 帐户 {email} 的两步验证保护已经于 {time} 由 IP {ip} 的用户启用。"), $data, true));
					
					if(!$this->email->send())
					{
						$this->system_model->log('notice_failed', array('id' => $uid, 'type' => 'email', 'content' => 'twostep_enabled'));
					}
					
					//记录日志
					$this->system_model->log('twostep_enabled', array('ip' => $this->input->ip_address()), $uid);
					
					$this->ui->alert('两步验证已经启用。', 'success', true);
					
					redirect('account/settings/twostep');
					return;
				}
				
				$vars = array(
					'secret' => $secret,
					'qr' => $this->twostep->get_qr_url(option('site_name', 'iPlacard'), $secret, 118, 0)
				);
				
				$this->load->view('account/manage/twostep_enable', $vars);
				return;
			}
			
			//显示两步验证功能介绍
			$this->load->view('account/manage/twostep_intro');
			return;
		}
		
		//设置主页
		if($setting == 'home')
		{
			//显示待确认邮箱
			$email_pending = user_option('account_email_pending', false);
			if($email_pending)
				$user['email_pending'] = $email_pending;
			
			//修改邮箱
			if($this->input->post('change_email') && $this->input->post('email') != $user['email'])
			{
				$this->form_validation->set_rules('email', '电子邮箱地址', 'trim|required|valid_email|is_unique[user.email]');
				$this->form_validation->set_message('valid_email', '电子邮箱地址无效。');
			}
			//修改手机
			if($this->input->post('change_phone') && $this->input->post('phone') != $user['phone'])
			{
				$this->form_validation->set_rules('phone', '手机号', 'trim|required|integer|exact_length[11]|is_unique[user.phone]');
				$this->form_validation->set_message('exact_length', '%s长度必须为 %s 位。');
			}
			//密码验证
			$this->form_validation->set_rules('password', '密码', 'trim|required|callback__check_password[密码验证错误导致个人信息更改未完成，请重新尝试。]');
			$this->form_validation->set_message('is_unique', '存在重复的%s。');
			$this->form_validation->set_message('_check_password', '密码有误，请重新输入。');
			
			if($this->form_validation->run() == true)
			{
				//执行修改邮箱
				if($this->input->post('change_email') && $this->input->post('email') != $user['email'])
				{
					$new_email = trim($this->input->post('email'));
					
					$change_time = time();
					$change_key = strtoupper(substr(sha1($uid.$change_time.rand(10000, 49999)), 20));
					$cancel_key = strtoupper(substr(sha1($uid.$change_time.rand(50000, 99999)), 20));
					
					//更新待验证邮箱
					$this->user_model->edit_user_option('account_email_pending', $new_email);
					$this->user_model->edit_user_option('account_email_change_time', $change_time);
					$this->user_model->edit_user_option('account_email_change_key', $change_key);
					$this->user_model->edit_user_option('account_email_cancel_key', $cancel_key);
					
					//确定是否需要强制登录
					if(is_sudo())
						$this->user_model->edit_user_option('account_email_pending_sudo', true);
					else
						$this->user_model->edit_user_option('account_email_pending_sudo', false);
					
					//发送邮件
					$data = array(
						'uid' => $user['id'],
						'name' => $user['name'],
						'old_email' => $user['email'],
						'new_email' => $new_email,
						'time' => unix_to_human(time()),
						'ip' => $this->input->ip_address(),
						'change_url' => base_url("account/email/confirm/$uid/$change_key"),
						'cancel_url' => base_url("account/email/cancel/$uid/$cancel_key"),
					);

					//通知旧邮箱
					$this->email->clear();
					$this->email->to($user['email']);
					$this->email->subject('您的 iPlacard 帐户邮箱已经更改');
					$this->email->html($this->parser->parse_string(option('email_account_email_changed', "您的 iPlacard 帐户 {old_email} 的电子邮箱地址已经于 {time} 由 IP {ip} 的用户更改为 {new_email}。如非本人操作，请立即访问：\n\n"
							. "\t{cancel_url}\n\n"
							. "取消本次修改，同时请考虑修改密码。"), $data, true));
					
					if(!$this->email->send())
					{
						$this->system_model->log('notice_failed', array('id' => $uid, 'type' => 'email', 'content' => 'email_changed'));
					}
					
					//通知新邮箱
					$this->email->clear();
					$this->email->to($new_email);
					$this->email->subject('验证您的新 iPlacard 帐户邮箱');
					$this->email->html($this->parser->parse_string(option('email_account_request_email_confirm', "您的 iPlacard 帐户 {old_email} 的电子邮箱地址已经于 {time} 由 IP {ip} 的用户请求更改为 {new_email}。如果确认操作请点击访问以下链接：\n\n"
							. "\t{change_url}\n\n"
							. "确认您的新邮箱，此链接仅在 24 小时内有效并且仅限使用一次。当前，您的 iPlacard 邮箱仍为 {old_email}，请使用该邮箱登录 iPlacard。"), $data, true));
					
					if(!$this->email->send())
					{
						$this->system_model->log('notice_failed', array('id' => $uid, 'type' => 'email', 'content' => 'email_changed'));
					}
					
					//记录日志
					$this->system_model->log('email_change_requested', array('ip' => $this->input->ip_address(), 'new' => $new_email, 'old' => $user['email']), $uid);
					
					$this->ui->alert('电子邮箱地址修改成功。我们已经向您的新邮箱发送了一封确认邮件，请按照邮件的提示在 24 小时内确认本次更改。', 'success');
					
					$user['email_pending'] = $new_email;
					$email_changed = true;
				}
				
				//执行修改手机
				if($this->input->post('change_phone') && $this->input->post('phone') != $user['phone'])
				{
					$new_phone = trim($this->input->post('phone'));
					
					//记录旧手机信息
					$phone_history = user_option('account_phone_history', array());
					$phone_history[] = $new_phone;
					$this->user_model->edit_user_option('account_phone_history', $phone_history);
					
					//修改手机信息
					$this->user_model->edit_user(array('phone' => $new_phone), $uid);
					
					//发送邮件
					$data = array(
						'uid' => $user['id'],
						'name' => $user['name'],
						'email' => $user['email'],
						'old_phone' => $user['phone'],
						'new_phone' => $new_phone,
						'time' => unix_to_human(time()),
						'ip' => $this->input->ip_address(),
					);

					$this->email->clear();
					$this->email->to($user['email']);
					$this->email->subject('您的 iPlacard 帐户手机号码已经更改');
					$this->email->html($this->parser->parse_string(option('email_account_phone_changed', "您的 iPlacard 帐户 {email} 的手机号码已经于 {time} 由 IP {ip} 的用户更改为 {new_phone}。"), $data, true));
					
					if(!$this->email->send())
					{
						$this->system_model->log('notice_failed', array('id' => $uid, 'type' => 'email', 'content' => 'phone_changed'));
					}
					
					//记录日志
					$this->system_model->log('phone_changed', array('ip' => $this->input->ip_address(), 'new' => $new_phone, 'old' => $user['phone']), $uid);
					
					$this->ui->alert('绑定的手机号码修改成功。', 'success');
					
					$user['phone'] = $new_phone;
				}
			}
			else
			{
				$email_changed = false;
				
				//默认启用编辑
				if($this->input->post('change_email'))
					$this->ui->js('footer', "edit_item('email');");
				
				if($this->input->post('change_phone'))
					$this->ui->js('footer', "edit_item('phone');");
			}
		}
		
		//头像功能
		$this->load->helper('avatar');
		$this->load->helper('number');
		$this->load->helper('file');
		
		$vars = array(
			'data' => $user,
			'email_changed' => $email_changed,
			'avatar' => user_option('account_avatar_enabled', false),
			'avatar_max_size' => byte_format(ini_max_upload_size(option('avatar_max_size', option('file_max_size', 10 * 1024 * 1024))), 0)
		);
		
		$this->ui->title('个人信息');
		$this->load->view('account/manage/detail', $vars);
	}
	
	/**
	 * 输出用户头像
	 */
	function avatar($uid, $size = 20)
	{
		if(!is_logged_in())
		{
			return;
		}
		
		$target = array(20, 26, 40, 80, 160, 320);
		
		$size = intval($size);
		
		$path = './data/'.IP_INSTANCE_ID.'/avatar/'.$uid.'/';
		
		//如果不是标准尺寸
		if(!in_array($size, $target) || !file_exists("{$path}{$size}.jpg"));
		{
			//启用动态输出可能造成负载增加和暴露 data 文件夹位置
			if(option('avatar_resizable', false))
			{
				//限制大小以避免潜在攻击威胁
				if($size > 640)
					$size = 640;
				
				$this->_do_avatar_resize("{$path}320.jpg", $size);
				return;
			}
			else
			{
				$size = 320;
			}
		}
		
		$this->output->set_content_type('jpg');
		$this->output->set_output(file_get_contents("{$path}{$size}.jpg"));
	}
	
	/**
	 * 执行登录操作
	 * @param int $id 用户ID
	 * @param int $sudo SUDO管理员ID
	 * @param boolean $sudo_in 是否启动SUDO
	 */
	function _do_login($id, $sudo = false, $sudo_in = false)
	{
		//如果成功登录则重定向
		$redirect = $this->session->flashdata('redirect');
		
		$user = $this->user_model->get_user($id);

		//检查是否设置安全码
		if(option('check_pin', false) && $user['pin_password'] == option('default_pin_password', 'iPlacard'))
			$this->ui->alert(sprintf('您尚未更改您的初始安全码，请在您的<a href="%s" class="alert-link">帐号管理</a>页面设置新的安全码。', base_url('account/pin')), 'info', true);

		//设置Session数据
		$this->session->set_userdata(array(
			'uid' => intval($user['id']),
			'sudo' => $sudo_in ? true : false,
			'name' => $user['name'],
			'email' => $user['email'],
			'type' => $user['type'],
			'logged_in' => true));

		//获取Session信息
		$session_id = $this->session->userdata('session_id');
		$system_sess_id = $this->system_model->get_session_id($session_id);

		//写入日志
		if(!$sudo)
		{
			$is_mobile = $this->agent->is_mobile();
			$this->system_model->log('logged_in', array(
				'ip' => $this->input->ip_address(),
				'session' => $system_sess_id,
				'type' => ($is_mobile) ? 'mobile' : 'desktop',
				'browser' => ($is_mobile) ? $this->agent->mobile() : $this->agent->browser(),
				'ua' => $this->agent->agent_string()
			), $id);
		}
		elseif($sudo_in)
		{
			$this->system_model->log('sudoed_in', array(
				'session' => $system_sess_id,
				'delegate' => $id
			), $sudo);
		}

		//发送登录通知
		if(!$sudo && user_option('account_notice_login_enabled', false))
		{
			//生成链接
			$halt = (string) $system_sess_id.(string) rand(1000, 9999);
			$md5_sess_id = strtoupper(md5($session_id));

			//发送邮件
			$this->load->library('email');
			$this->load->library('parser');
			$this->load->helper('date');

			$data = array(
				'uid' => $id,
				'name' => $user['name'],
				'email' => $user['email'],
				'time' => unix_to_human(time()),
				'ip' => $this->input->ip_address(),
				'url' => base_url("account/halt/$halt/$md5_sess_id"),
			);

			$this->email->to($user['email']);
			$this->email->subject('iPlacard 帐户登录提示');
			$this->email->html($this->parser->parse_string(option('email_account_login_notice', "您的 iPlacard 帐户 {email} 已经于 {time} 在 IP {ip} 登录。如非本人操作，请立即访问：\n\n"
					. "\t{url}\n\n"
					. "强制退出此次登录，如果您持续收到此通知，请考虑修改密码。"), $data, true));
			$this->email->send();
		}

		//根据不同用户类型执行不同操作
		//如果是管理员
		if($this->user_model->is_admin($id))
		{
			//页面跳转
			if(!$sudo && !empty($redirect))
			{
				redirect(urldecode($redirect));
				return;
			}

			redirect('admin/dashboard');
			return;
		}

		//如果是用户
		$this->load->model('delegate_model');

		$delegate = $this->delegate_model->get_delegate($id);

		if($delegate['status'] == 'seat_assigned' && option('notice_check_status_seat_assigned', false))
			$this->ui->alert(sprintf('面试官已经为您分配了席位，请在<a href="%s" class="alert-link">席位信息页面</a>确认您的席位。', base_url('seat/placard')), 'info', true);

		if($delegate['status'] == 'invoice_issued' && option('notice_check_status_invoice_issued', false))
			$this->ui->alert(sprintf('您有<a href="%s" class="alert-link">帐单</a>需要支付，请在帐单到期之前完成支付。', base_url('apply/invoice')), 'info', true);

		if(!$sudo && !empty($redirect))
		{
			redirect(urldecode($redirect));
			return;
		}

		redirect('apply/status');
	}
	
	/**
	 * 执行登出操作
	 * @param string $operation 登出原因
	 * @param int $sudo SUDO管理员ID
	 * @param boolean $sudo_in 是否启动SUDO
	 */
	function _do_logout($operation = 'user_operation', $sudo = false, $sudo_in = true)
	{
		$uid = uid();
		
		if(!$uid)
			return;
		
		//销毁Session
		$this->session->unset_userdata(array(
			'uid' => '',
			'sudo' => '',
			'email' => '',
			'type' => '',
			'logged_in' => ''
		));
		
		//写入日志
		if($sudo && !$sudo_in)
			$this->system_model->log('sudoed_out', array('delegate' => $uid), $sudo);
		elseif(!$sudo)
			$this->system_model->log('logged_out', array('ip' => $this->input->ip_address(), 'operation' => $operation), $uid);
	}
	
	/**
	 * 写入强制登出
	 */
	function _do_halt($halt_id)
	{
		$new_userdata = $this->session->_serialize(array(
			'halt' => true,
			'halt_time' => time()
		));
		$this->db->where('id', $halt_id);
		$this->db->update('session', array('user_data' => $new_userdata));
	}
	
	/**
	 * 头像缩放
	 * @param string $source 头像文件
	 * @param int $size 缩放大小
	 * @param string|false $output 输出类型
	 */
	function _do_avatar_resize($source, $size, $output = false)
	{
		$this->load->library('image_lib');

		$config['source_image'] = $source;
		$config['width'] = $size;
		$config['height'] = $size;
		
		if(!$output)
			$config['dynamic_output'] = true;
		else
			$config['new_image'] = $output;
		
		$this->image_lib->initialize($config);
		
		$this->image_lib->resize();
	}
	
	/**
	 * 登录验证回调函数
	 */
	function _check_login()
	{
		//记录登录尝试
		$login_try = $this->session->userdata('login_try') + 1;
		if(empty($login_try))
			$login_try = 1;
		
		$email = $this->input->post('email');
		$password = $this->input->post('password');
		
		//超过10次登录错误屏蔽十分钟
		if($login_try > 11)
		{
			$last_time = $this->session->userdata('login_try_last');
			
			if(time() < $last_time + 600)
			{
				$this->form_validation->set_message('_check_login', '您因登录尝试过多已被暂时禁止登录，请等待 10 分钟重试。');
				return false;
			}
			else
			{
				$login_try = 5; //限制解除后仅允许再尝试5次
			}
		}
		
		//记录最新尝试
		$this->session->set_userdata('login_try', $login_try);
		$this->session->set_userdata('login_try_last', time());
		
		//检查登录
		if(!$this->user_model->user_exists($email))
		{
			$this->form_validation->set_message('_check_login', '电子邮箱地址不存在！');
			$this->ui->alert('稍早前提交的报名将会需要至多 1 小时以导入系统，请稍等并查收您的邮件。通常情况下将您将在 1 小时内收到一封包含登录信息的确认邮件。', 'info');
			return false;
		}
		elseif(!$this->user_model->check_password($email, $password))
		{
			$this->form_validation->set_message('_check_login', '电子邮箱地址或密码错误！');
			return false;
		}
		
		//如果登录验证通过
		$this->session->unset_userdata('login_try');
		$this->session->unset_userdata('login_try_last');
		return true;
	}
	
	/**
	 * 密码重置验证回调函数
	 */
	function _check_password_recover()
	{
		$email = $this->input->post('email');
		$user = $this->user_model->get_user($email);
		
		if(!$user)
		{
			$this->form_validation->set_message('_check_password_recover', '电子邮箱地址不存在！');
			$this->ui->alert('稍早前提交的报名将会需要至多 1 小时以导入系统，请稍等并查收您的邮件。通常情况下将您将在 1 小时内收到一封包含登录信息的确认邮件。', 'info');
			return false;
		}
		elseif(!$user['name'] || empty($user['name']) || $user['name'] != $this->input->post('name'))
		{
			$this->form_validation->set_message('_check_password_recover', '电子邮箱和姓名信息不符，请重新检查。');
			return false;
		}
		return true;
	}
	
	/**
	 * 两步验证码登录检查回调函数
	 */
	function _check_twostep_code()
	{
		$this->load->model('twostep_model');
		$this->load->library('twostep');
		
		$code = $this->input->post('code');
		
		$uid = $this->session->userdata('uid_twostep');
		
		//如未开启两步验证自动通过
		if(!user_option('twostep_enabled', false, $uid))
			return true;
		
		//如未设置验证密钥自动通过
		$secret = user_option('twostep_secret', false, $uid);
		if(!$secret)
			return true;
		
		if($this->twostep->check_code($secret, $code))
		{
			if($this->twostep->recode_exists($uid, $code, option('twostep_time_range', 60)))
			{
				$this->form_validation->set_message('_check_twostep_code', '此验证码已经失效，请等待 Google Authenticator 生成新的验证码后重试。');
				return false;
			}
			
			//记录验证码
			$this->twostep->add_recode($uid, $code);
			return true;
		}
		else
		{
			$this->form_validation->set_message('_check_twostep_code', '验证码错误，请重新尝试。如果错误持续，请校正安装有 Google Authenticator 设备的时间。');
		}
		return false;
	}
	
	/**
	 * 两步验证码有效性检查回调函数
	 */
	function _check_twostep_verify($code)
	{
		$secret = $this->input->post('secret');
		
		if($this->twostep->check_code($secret, $code))
			return true;
		return false;
	}
	
	/**
	 * 短信验证码检查回调函数
	 */
	function _check_sms_code()
	{
		$code = $this->input->post('code');
		
		$uid = $this->session->userdata('uid_twostep');
		
		$true_code = user_option('sms_validate_code', false, $uid);
		$send_time = user_option('sms_validate_time', false, $uid);
		
		if(!$true_code || !$send_time || $send_time < time() - 60 * 60)
		{
			$this->form_validation->set_message('_check_sms_code', '短信验证码已经失效，请尝试重新发送验证码。');
			return false;
		}
		
		if($code != $true_code)
		{
			$this->form_validation->set_message('_check_sms_code', '验证码错误，请重新尝试。');
			return false;
		}
		
		return true;
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
	 * 确认 Checkbox 检查回调函数
	 */
	function _check_confirm($checkbox)
	{
		if($checkbox == true)
			return true;
		return false;
	}
}

/* End of file account.php */
/* Location: ./application/controllers/account.php */