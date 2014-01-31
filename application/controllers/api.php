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
		
		$post = $this->input->post();
		
		//令牌有效性校验
		if(!isset($post['access_token']) || strlen($post['access_token']) != 32)
		{
			$this->_error('Access token incorrect.');
			$this->_echo();
		}
		
		$token = $this->token_model->get_token($post['access_token']);
		if(!$token)
		{
			$this->_error('Access token doesn\'t exist.');
			$this->_echo();
		}
		
		if(!$this->token_model->check_ip_range($token['ip_range']))
		{
			$this->_error('Request not allowed from this IP address.');
			$this->_echo();
		}
		
		//数据完整性校验
		if($post['crypt'] != crypt(sha1($post['data']), $post['access_token']))
		{
			$this->_error('CRYPT data incorrect.');
			$this->_echo();
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
	private function _error($error)
	{
		$this->result = false;
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
			$result['error'] = $this->error;
		
		echo json_encode($result);
		exit;
	}
}

/* End of file api.php */
/* Location: ./application/controllers/api.php */