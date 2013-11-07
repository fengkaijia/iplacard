<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 帐户
 * @package iPlacard
 * @subpackage Client
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
		$this->load->helper('ui');
		
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
				redirect('manage/dashboard');
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
		$this->form_validation->set_rules('password', '密码', 'trim');
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
		
		$uid = $this->session->userdata('uid');
		
		//销毁Session
		$this->session->unset_userdata(array(
			'uid' => '',
			'sudo' => '',
			'email' => '',
			'type' => '',
			'logged_in' => ''
		));
		$this->ui->alert('您已成功登出。', 'success', true);
		
		//写入日志
		$this->system_model->log('logged_out', array('ip' => $this->input->ip_address()), $uid);
		
		if(!empty($redirect))
		{
			redirect(urldecode($redirect));
			return;
		}
		redirect('account/login');
	}
	
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
		$this->load->view('account/auth/twostep');
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
			$this->user_model->edit_user(array(
				'recover_key' => $recover_key,
				'recover_time' => $recover_time
			), $uid);
			
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
				$this->load->view('account/auth/recover', array('sent' => true, 'email' => $user['email']));
				return;
			}
		}
		
		$this->ui->title('密码重置');
		$this->load->view('account/auth/recover', array('sent' => false));
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
		
		$user = $this->user_model->get_user($uid);
		
		//验证用户
		if(!$user || $user['recover_key'] != $key)
		{
			$this->ui->alert('无效的密码重置请求。', 'danger', true);
			redirect('account/recover');
			return;
		}

		//验证链接有效性
		if(time() > $user['recover_time'] + 60 * 60 * 24)
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
				'recover_key' => NULL,
				'recover_time' => NULL
			), $uid);
			
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
			
			//写入强制登出
			$new_userdata = $this->session->_serialize(array(
				'halt' => true,
				'halt_time' => time()
			));
			$this->db->where('id', $halt_id);
			$this->db->update('session', array('user_data' => $new_userdata));

			//记录日志
			$this->system_model->log('session_halted', array('ip' => $this->input->ip_address(), 'session' => $halt_id, 'userdata' => $sess_data['user_data']), 0);
		}
		
		$this->ui->title('强制登出');
		$this->load->view('account/auth/halt', array('no_action' => $no_action));
	}
		
	/**
	 * 执行登录操作
	 * @param int $id 用户ID
	 */
	function _do_login($id)
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
			'sudo' => false,
			'email' => $user['email'],
			'type' => $user['type'],
			'logged_in' => true));

		//获取Session信息
		$session_id = $this->session->userdata('session_id');
		$system_sess_id = $this->system_model->get_session_id($session_id);

		//写入日志
		$is_mobile = $this->agent->is_mobile();
		$this->system_model->log('logged_in', array(
			'ip' => $this->input->ip_address(),
			'session' => $system_sess_id,
			'type' => ($is_mobile) ? 'mobile' : 'desktop',
			'browser' => ($is_mobile) ? $this->agent->mobile() : $this->agent->browser(),
			'ua' => $this->agent->agent_string()
		), $id);

		//发送登录通知
		if(user_option('account_login_notice_enabled', false))
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
			if(!empty($redirect))
			{
				redirect(urldecode($redirect));
				return;
			}

			redirect('manage/dashboard');
			return;
		}

		//如果是用户
		$this->load->model('delegate_model');

		$delegate = $this->delegate_model->get_delegate($id);

		if($delegate['status'] == 'seat_assigned' && option('notice_check_status_seat_assigned', false))
			$this->ui->alert(sprintf('面试官已经为您分配了席位，请在<a href="%s" class="alert-link">席位信息页面</a>确认您的席位。', base_url('seat/placard')), 'info', true);

		if($delegate['status'] == 'invoice_issued' && option('notice_check_status_invoice_issued', false))
			$this->ui->alert(sprintf('您有<a href="%s" class="alert-link">帐单</a>需要支付，请在帐单到期之前完成支付。', base_url('apply/invoice')), 'info', true);

		$addition = option('additional_information_all', array());
		if(!empty($addition) && !$this->delegate_model->get_editable_profile_ids($delegate['id'], 'delegate'))
		{
			$this->ui->alert(sprintf('您有<a href="%s" class="alert-link">附加信息</a>需要补充。', base_url('apply/profile')), 'info', true);
		}

		if(!empty($redirect))
		{
			redirect(urldecode($redirect));
			return;
		}

		redirect('apply/status');
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
	 * 验证码检查回调函数
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
			$this->form_validation->set_message('_check_twostep_code', '验证码错误。');
		}
		return false;
	}
}

/* End of file account.php */
/* Location: ./application/controllers/account.php */