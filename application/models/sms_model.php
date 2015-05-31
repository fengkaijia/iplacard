<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 短信模块
 * @package iPlacard
 * @since 2.0
 */
class Sms_model extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * 获取短信信息
	 * @param int $id 短信ID
	 * @param string $part 指定部分
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_sms($id, $part = '')
	{
		$this->db->where('id', intval($id));
		$query = $this->db->get('sms');
		
		//如果无结果
		if($query->num_rows() == 0)
			return false;
		
		$data = $query->row_array();
		
		//返回结果
		if(empty($part))
			return $data;
		return $data[$part];
	}
	
	/**
	 * 批量获取短信信息
	 * @param int $ids 短信IDs
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_smses($ids)
	{
		$this->db->where_in('id', $ids);
		$query = $this->db->get('sms');
		
		//如果无结果
		if($query->num_rows() == 0)
			return false;
		
		$return = array();
		
		foreach($query->result_array() as $data)
		{
			$return[$data['id']] = $data;
		}
		$query->free_result();
		
		//返回结果
		return $return;
	}
	
	/**
	 * 查询符合条件的第一个短信ID
	 * @return int|false 符合查询条件的第一个短信ID，如不存在返回FALSE
	 */
	function get_sms_id()
	{
		$args = func_get_args();
		array_unshift($args, 'sms');
		//将参数传递给get_id方法
		return call_user_func_array(array($this->sql_model, 'get_id'), $args);
	}
	
	/**
	 * 查询符合条件的所有短信ID
	 * @return array|false 符合查询条件的所有短信ID，如不存在返回FALSE
	 */
	function get_sms_ids()
	{
		$args = func_get_args();
		array_unshift($args, 'sms');
		//将参数传递给get_ids方法
		return call_user_func_array(array($this->sql_model, 'get_ids'), $args);
	}
	
	/**
	 * 编辑/添加短信
	 * @return int 新的短信ID
	 */
	function edit_sms($data, $id = '')
	{
		//新增短信
		if(empty($id))
		{
			$this->db->insert('sms', $data);
			return $this->db->insert_id();
		}
		
		//更新短信
		$this->db->where('id', $id);
		return $this->db->update('sms', $data);
	}
	
	/**
	 * 添加短信
	 * @return int 新的短信ID
	 */
	function add_sms($user, $phone, $message, $send_now = false)
	{
		$data = array(
			'user' => $user,
			'phone' => $phone,
			'message' => $message,
			'time_in' => time()
		);
		
		$data['status'] = $send_now ? 'sending' : 'queue';
		
		//返回短信ID
		return $this->edit_sms($data);
	}
}

/* End of file sms_model.php */
/* Location: ./application/models/sms_model.php */