<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 短信推送类库
 * @package iPlacard
 * @since 2.0
 */
class Sms extends Sms_model
{
	/**
	 * @var string 短信接口
	 */
	private $api = 'https://sms.api.iplacard.com/';
	
	/**
	 * @var string 短信发信人
	 */
	private $identity = 'iPlacard';
	
	/**
	 * @var string 是否设置为群发
	 */
	private $mass = false;
	
	/**
	 * @var array 接收人ID列表
	 */
	private $receiver = array();
	
	/**
	 * @var array 接收人手机列表
	 */
	private $phone = array();
	
	/**
	 * @var string 短信内容
	 */
	private $message = '';
	
	function __construct()
	{
		parent::__construct();
		
		$this->identity = option('sms_identity', 'iPlacard');
	}

	/**
	 * 增加接收人ID
	 * @param string $to 以逗号分割的ID
	 */
	function to($to)
	{
		if($this->mass)
			$this->receiver = array_merge($this->receiver, $this->_str_to_array($to));
		else
			$this->receiver = $this->_str_to_array($to);
	}
	
	/**
	 * 设置短信内容
	 * @param string $message 短信内容
	 */
	function message($message)
	{
		$this->message = $message;
	}
	
	/**
	 * 加入短信队列
	 * @param boolean $send_now 是否立即发送
	 */
	function queue($send_now = false)
	{
		$ids = array();
		
		foreach($this->receiver as $uid)
		{
			$phone = $this->user_model->get_user($uid, 'phone');
			
			if(!$phone)
				break;
			
			$this->phone = $phone;
			
			$ids[] = $this->add_sms($uid, $phone, $this->message, $send_now);
		}
		
		if($send_now)
		{
			foreach($ids as $sms_id)
			{
				$this->api_send($sms_id, true);
			}
		}
	}
	
	/**
	 * 加入短信队列并立即发送
	 */
	function send()
	{
		return $this->queue(true);
	}
	
	/**
	 * 清理当前数据
	 */
	function clean()
	{
		$this->receiver = array();
		$this->phone = array();
		$this->message = '';
	}
	
	/**
	 * 设置是否开启群发模式
	 * @param boolean $mass 是否群发
	 */
	function set_mass($mass = true)
	{
		$this->mass = $mass;
	}
	
	/**
	 * 调用API发送短信
	 * @param int $id 短信ID
	 * @param boolean $high_priority 是否立即发送
	 * @return boolean 是否成功送信
	 */
	function api_send($id, $high_priority = false)
	{
		$this->load->library('curl');
		
		$sms = $this->get_sms($id);
		
		if(!$sms)
			return false;
		
		if($sms['status'] == 'sent')
			return false;
		
		//生成数据
		$data = array(
			'access_key' => IP_INSTANCE_API_ACCESS_KEY,
			'secret_key' => IP_INSTANCE_API_SECRET_KEY,
			'message' => $sms['message']."【{$this->identity}】",
			'receiver' => $sms['phone']
		);
		//不加入API队列直接发送
		if($high_priority)
			$data['send_now'] = true;
		
		//获取结果
		$api_return = $this->curl->simple_post($this->api, $data);
		
		if(!$api_return)
		{
			$result = false;
			$api_return = NULL;
		}
		else
		{
			$return = json_decode($api_return);
			$result = $return->result ? true : false;
		}
		
		//记录数据
		$store_data = array(
			'time_out' => time(),
			'response' => $api_return,
			'status' => $result ? 'sent' : 'failed'
		);
		$this->edit_sms($store_data, $id);
		
		return $result;
	}
	
	/**
	 * 将以逗号分割的ID分割为数组
	 * @param type $id 以逗号分割的ID
	 * @return array ID列表
	 */
	private function _str_to_array($id)
	{
		if(!is_array($id))
		{
			if(strpos($id, ',') !== FALSE)
			{
				$id = preg_split('/[\s,]/', $id, -1, PREG_SPLIT_NO_EMPTY);
			}
			else
			{
				$id = trim($id);
				settype($id, "array");
			}
		}
		return $id;
	}
}

/* End of file Sms.php */
/* Location: ./application/libraries/Sms.php */