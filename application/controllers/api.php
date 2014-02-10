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
		
		$post = $this->input->get_post(NULL, true);
		
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
		
		//数据完整性校验
		if($post['crypt'] != crypt(sha1($post['data']), $post['access_token']))
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
		
		if($action == 'import')
		{
			//权限检查
			if(!$this->token_model->capable('delegate:import', $this->token['permission']))
			{
				$this->_error(6, 'Permission denied.');
				return;
			}
			
			//检查导入类型
			if(!in_array($this->data['type'], array('delegate', 'observer', 'volunteer')))
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
			$this->load->helper('email');
			if(!valid_email($this->data['email']))
			{
				$this->_error(24, 'Invalid email address.');
				return;
			}
			
			//生成随机密码
			$this->load->helper('string');
			$password = random_string('alnum', 8);
			
			//新建用户
			$user_data = array(
				'name' => trim($this->data['name']),
				'email' => trim($this->data['email']),
				'type' => 'delegate',
				'password' => $password,
				'pin_password' => option('default_pin_password', 'iPlacard'),
				'phone' => trim($this->data['phone']),
				'reg_time' => time()
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

			$this->email->to($user['email']);
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
				$this->sms->message('您的参会申请已导入 iPlacard 系统并开始审核，一封含有登录信息的邮件已经发送到您的电子邮箱，请通过提供的信息登录 iPlacard 了解申请进度。');
				
				if(!$this->sms->send())
				{
					$this->system_model->log('notice_failed', array('id' => $uid, 'type' => 'sms', 'content' => 'delegate_account_created'));
				}
			}
			
			$this->delegate_model->add_event($uid, 'application_imported');
			$this->user_model->add_message($uid, '您的参会申请已经成功导入 iPlacard 系统并开始审核。');
			
			$this->system_model->log('application_imported', array('ip' => $this->input->ip_address()), $uid);
			
			//返回数据
			$this->return['id'] = $uid;
			return;
		}
		
		$this->_error(5, 'Unknown action.');
	}
}

/* End of file api.php */
/* Location: ./application/controllers/api.php */