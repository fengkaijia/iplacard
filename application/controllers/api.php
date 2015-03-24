<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 接口控制器
 * @package iPlacard
 * @since 2.0
 */
class Api extends CI_Controller
{
	/**
	 * @var string 访问令牌
	 */
	private $token;
	
	/**
	 * @var array 提交数据
	 */
	private $data;
	
	/**
	 * @var boolean 运行结果
	 */
	private $result = true;
	
	/**
	 * @var array 返回数据
	 */
	private $return = array();
	
	/**
	 * @var int 运行时间
	 */
	private $time = 0;
	
	/**
	 * @var string 错误代码
	 */
	private $errno = 0;
	
	/**
	 * @var string 错误信息
	 */
	private $error = '';
	
	/**
	 * 接入并验证接口调用数据
	 */
	function __construct()
	{
		parent::__construct();
		$this->load->model('token_model');
		
		//计时开始
		$this->benchmark->mark('exec_start');
		
		//数据
		$post = $this->input->post(NULL, true);
		if(empty($post))
			$post = $this->input->get(NULL, true);
		
		//令牌有效性校验
		if(!isset($post['access_token']) || strlen($post['access_token']) != 32)
		{
			$this->_error(1, 'Access token incorrect.');
			exit;
		}
		
		$token = $this->token_model->get_token($post['access_token']);
		if(!$token)
		{
			$this->_error(2, 'Access token doesn\'t exist.');
			exit;
		}
		
		if(!$this->token_model->check_ip_range($token['ip_range']))
		{
			$this->_error(3, 'Request not allowed from this IP address.');
			exit;
		}
		
		if($post['crypt'] != crypt(sha1($post['data']), '$1$'.substr($post['access_token'], 0, 10).'&'))
		{
			$this->_error(99, 'CRYPT data incorrect.');
			exit;
		}
		
		$this->token_model->update_last_activity($token['id']);
		$this->token = $token;
		$this->data = json_decode($post['data'], true);
	}
	
	/**
	 * 输出接口调用结果
	 */
	function __destruct()
	{
		//计时结束
		$this->benchmark->mark('exec_end');
		$this->time = (int) ($this->benchmark->elapsed_time('exec_start', 'exec_end') * 1000);
		
		//输出
		$this->_echo();
	}
	
	/**
	 * 记录错误信息
	 */
	private function _error($errno, $error)
	{
		$this->result = false;
		$this->errno = $errno;
		$this->error = $error;
	}
	
	/**
	 * 输出错误信息
	 */
	private function _echo()
	{
		$result = array(
			'result' => $this->result,
			'data' => $this->return,
			'time' => $this->time
		);
		
		if(!$this->result)
		{
			$result['errno'] = $this->errno;
			$result['error'] = $this->error;
		}
		
		echo json_encode($result);
		exit;
	}
	
	/**
	 * 代表导入操作
	 */
	function delegate($action = 'import')
	{
		$this->load->model('delegate_model');
		$this->load->helper('date');
		
		if($action == 'import')
		{
			//权限检查
			if(!$this->token_model->capable('delegate:import', $this->token['permission']))
			{
				$this->_error(6, 'Permission denied.');
				return;
			}
			
			//检查导入类型
			if(!in_array($this->data['type'], array('delegate', 'observer', 'volunteer', 'teacher')))
			{
				$this->_error(21, 'Unknown delegate type.');
				return;
			}
			
			//检查重复邮箱
			if($this->user_model->get_user_id('email', $this->data['email']))
			{
				$this->_error(22, 'Delegate email already exists.');
				return;
			}
			
			//检查唯一身份标识符
			if($this->delegate_model->get_delegate_id('unique_identifier', $this->data['unique_id']))
			{
				$this->_error(23, 'Unique Identifier already exists.');
				return;
			}
			
			//验证邮箱
			if(!filter_var($this->data['email'], FILTER_VALIDATE_EMAIL))
			{
				$this->_error(24, 'Invalid email address.');
				return;
			}
			
			//生成随机密码
			$this->load->helper('string');
			$password = random_string('alnum', 8);
			
			//记录导入时间
			$reg_time = time();
			if(option('api_custom_time', true) && isset($this->data['reg_time']) && is_timestamp($this->data['reg_time']))
				$reg_time = $this->data['reg_time'];
			
			//新建用户
			$user_data = array(
				'name' => trim($this->data['name']),
				'email' => trim($this->data['email']),
				'type' => 'delegate',
				'password' => $password,
				'pin_password' => option('default_pin_password', 'iPlacard'),
				'phone' => trim($this->data['phone']),
				'reg_time' => $reg_time
			);
			$uid = $this->user_model->edit_user($user_data);
			
			//增加代表数据
			$this->delegate_model->add_delegate($uid);
			$delegate_data = array(
				'status' => 'application_imported',
				'application_type' => $this->data['type'],
				'unique_identifier' => $this->data['unique_id']
			);
			$this->delegate_model->edit_delegate($delegate_data, $uid);
			
			//导入资料
			if(isset($this->data['profile']) && !empty($this->data['profile']))
			{
				foreach($this->data['profile'] as $name => $value)
				{
					$this->delegate_model->add_profile($uid, $name, $value);
				}
			}

			//发送邮件
			$this->load->library('email');
			$this->load->library('parser');
			$this->load->helper('date');

			$data = array(
				'uid' => $uid,
				'name' => trim($this->data['name']),
				'email' => trim($this->data['email']),
				'password' => $password,
				'time' => unix_to_human(time()),
				'url' => base_url(),
			);

			$this->email->to($this->data['email']);
			$this->email->subject('iPlacard 帐户登录信息');
			$this->email->html($this->parser->parse_string(option('email_delegate_account_created', "您的参会申请已经导入 iPlacard 系统并开始审核。您的 iPlacard 帐户已经于 {time} 创建。帐户信息如下：\n\n"
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
				$this->sms->message('您的参会申请已导入 iPlacard 系统并开始审核，一封含有登录信息的邮件已经发送到您的电子邮箱，请通过提供的信息登录 iPlacard 了解申请进度。如果未能收到通知邮件，请与我们联系。');
				
				if(!$this->sms->queue())
				{
					$this->system_model->log('notice_failed', array('id' => $uid, 'type' => 'sms', 'content' => 'delegate_account_created'));
				}
			}
			
			$this->delegate_model->add_event($uid, 'application_imported');
			$this->user_model->add_message($uid, '您的参会申请已经成功导入 iPlacard 系统并开始审核。');
			
			$this->system_model->log('application_imported', array('ip' => $this->input->ip_address(), 'id' => $uid), 0);
			
			//返回数据
			$this->return['id'] = $uid;
			return;
		}
		elseif($action == 'info')
		{
			//权限检查
			if(!$this->token_model->capable('delegate:info', $this->token['permission']))
			{
				$this->_error(6, 'Permission denied.');
				return;
			}
			
			//输入有效性检查
			if(!isset($this->data['key']) || empty($this->data['key']))
			{
				$this->_error(21, 'Empty key.');
				return;
			}
			
			//查找代表
			$id = $this->delegate_model->search_delegate($this->data['key'], 1);
			if(!$id)
			{
				$this->_error(32, 'Unable to find delegate with provided key.');
				return;
			}
			
			//获取代表信息
			$delegate = $this->delegate_model->get_delegate($id[0]);
			if(!$delegate)
			{
				$this->_error(31, 'Delegate does not exists.');
				return;
			}
			
			$this->load->model('admin_model');
			$this->load->model('committee_model');
			$this->load->model('interview_model');
			$this->load->model('note_model');
			$this->load->model('seat_model');
			
			//生成代表数据
			$this->return = array(
				'id' => $delegate['id'],
				'name' => $delegate['name'],
				'email' => $delegate['email'],
				'phone' => $delegate['phone'],
				'unique_identifier' => $delegate['unique_identifier'],
				'geolocation' => $delegate['geolocation'],
				'application_type' => $delegate['application_type'],
				'status' => $delegate['status'],
				'url' => base_url("delegate/profile/{$delegate['id']}")
			);
			
			//获取资料
			$profile = $this->delegate_model->get_delegate_profiles($delegate['id']);
			if($profile)
			{
				$this->return['profile'] = $profile;
			}
			
			//获取笔记
			$notes = $this->note_model->get_delegate_notes($delegate['id']);
			if($notes)
			{
				foreach($notes as $note_id)
				{
					$note = $this->note_model->get_note($note_id);
					$note['category'] = $this->note_model->get_category($note['category'], 'name');
					
					$admin = $this->admin_model->get_admin($note['admin']);
					$note['admin'] = array(
						'id' => $admin['id'],
						'name' => $admin['name'],
						'email' => $admin['email'],
						'title' => $admin['title'],
						'committee' => !empty($admin['committee']) ? $this->committee_model->get_committee($admin['committee'], 'name') : NULL
					);
					
					$this->return['note'][] = $note;
				}
			}
			
			//获取团队
			if(!empty($delegate['group']))
			{
				$this->load->model('group_model');
				
				$this->return['group'] = $this->group_model->get_group($delegate['group']);
			}
			
			//获取面试
			$interviews = $this->interview_model->get_interview_ids('delegate', $delegate['id']);
			if($interviews)
			{
				$current = $this->interview_model->get_current_interview_id($delegate['id']);

				foreach($interviews as $interview_id)
				{
					$interview = $this->interview_model->get_interview($interview_id);
					$interview['current'] = ($current == $interview['id']);
					
					//面试官信息
					$interviewer = $this->admin_model->get_admin($interview['interviewer']);
					
					$interview['interviewer'] = array(
						'id' => $interviewer['id'],
						'name' => $interviewer['name'],
						'email' => $interviewer['email'],
						'title' => $interviewer['title'],
						'committee' => !empty($interviewer['committee']) ? $this->committee_model->get_committee($interviewer['committee'], 'name') : NULL
					);
					
					$this->return['interview'][] = $interview;
				}
			}
			
			//获取席位
			$seat = $this->seat_model->get_delegate_seat($delegate['id']);
			if($seat)
			{
				$this->return['seat'] = $this->seat_model->get_seat($seat);
				$this->return['seat']['committee'] = $this->committee_model->get_committee($seat['committee'], 'name');
			}
			
			//获取退会信息
			if($delegate['status'] == 'quitted')
			{
				$this->return['quit'] = array(
					'status' => user_option('quit_status', NULL, $delegate['id']),
					'time' => user_option('quit_time', NULL, $delegate['id']),
					'operator' => user_option('quit_operator', NULL, $delegate['id']),
					'reason' => user_option('quit_reason', NULL, $delegate['id']),
				);
			}
			
			//返回代表数据
			return;
		}
		
		$this->_error(5, 'Unknown action.');
	}
	
	/**
	 * 用户信息操作
	 */
	function user($action = 'auth')
	{
		$this->load->model('user_model');
		
		if($action == 'auth')
		{
			//权限检查
			if(!$this->token_model->capable('user:auth', $this->token['permission']))
			{
				$this->_error(6, 'Permission denied.');
				return;
			}
			
			//输入有效性检查
			if(!isset($this->data['email']) || empty($this->data['email']))
			{
				$this->_error(21, 'Empty email.');
				return;
			}
			
			if(!isset($this->data['password']) || empty($this->data['password']))
			{
				$this->_error(22, 'Empty password.');
				return;
			}
			
			//密码验证
			if($this->user_model->check_password($this->data['email'], $this->data['password']))
			{
				$this->return = true;
				return;
			}
			
			$this->return = false;
			return;
		}
		elseif($action == 'info')
		{
			//权限检查
			if(!$this->token_model->capable('user:info', $this->token['permission']))
			{
				$this->_error(6, 'Permission denied.');
				return;
			}
			
			//输入有效性检查
			if(!isset($this->data['key']) || empty($this->data['key']))
			{
				$this->_error(21, 'Empty key.');
				return;
			}
			
			//获取用户信息
			$user = $this->user_model->get_user($this->data['key']);
			if(!$user)
			{
				$this->_error(31, 'User does not exists.');
				return;
			}
			
			//返回用户数据
			$this->return = array(
				'id' => $user['id'],
				'name' => $user['name'],
				'email' => $user['email'],
				'phone' => $user['phone'],
				'type' => $user['type'],
				'pin_password' => $user['pin_password']
			);
			return;
		}
		
		$this->_error(5, 'Unknown action.');
	}
}

/* End of file api.php */
/* Location: ./application/controllers/api.php */