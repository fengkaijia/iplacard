<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 代表模块
 * @package iPlacard
 * @since 2.0
 */
class Delegate_model extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * 获取代表信息
	 * @param int $id 用户ID
	 * @param string $part 指定部分
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_delegate($id, $part = '')
	{
		$this->db->where('user.id', intval($id));
		$this->db->join('delegate', 'user.id = delegate.id', 'left outer');
		$query = $this->db->get('user');
		
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
	 * 查询符合条件的第一个代表ID
	 * @return int|false 符合查询条件的第一个代表ID，如不存在返回FALSE
	 */
	function get_delegate_id()
	{
		$args = func_get_args();
		array_unshift($args, 'delegate');
		//将参数传递给get_id方法
		return call_user_func_array(array($this->sql_model, 'get_id'), $args);
	}
	
	/**
	 * 查询符合条件的所有代表ID
	 * @return array|false 符合查询条件的所有代表ID，如不存在返回FALSE
	 */
	function get_delegate_ids()
	{
		$args = func_get_args();
		array_unshift($args, 'delegate');
		//将参数传递给get_ids方法
		return call_user_func_array(array($this->sql_model, 'get_ids'), $args);
	}
	
	/**
	 * 编辑代表信息
	 */
	function edit_delegate($data, $id)
	{
		$this->db->where('id', $id);
		return $this->db->update('delegate', $data);
	}
	
	/**
	 * 添加代表信息
	 */
	function add_delegate($id)
	{
		return $this->db->insert('delegate', array('id' => $id));
	}
	
	/**
	 * 更改代表申请状态
	 */
	function change_status($id, $status)
	{
		$available = array(
			'application_imported', //申请资料已导入
			'review_passed', //审核通过
			'review_refused', //审核未通过
			'interview_assigned', //在面试队列
			'interview_arranged', //等待面试（面试时间已经确定）
			'interview_completed', //面试通过
			'moved_to_waiting_list', //在等待队列
			'seat_assigned', //席位已分配
			'invoice_issued', //等待支付帐单
			'payment_received', //付款已收到
			'locked', //操作锁定
			'quitted' //退会
		);
		
		if(!in_array($status, $available))
			return false;
		
		return $this->edit_delegate(array('status' => $status), $id);
	}
	
	/**
	 * 转换状态为可比较代码
	 * @param string|int $status 状态或代表ID
	 * @return int 可比较的状态代码（0为最早状态）
	 */
	function status_code($status)
	{
		$all = array(
			'application_imported' => 0, //申请资料已导入
			'review_passed' => 1, //初审通过
			'review_refused' => 10, //初审未通过
			'interview_assigned' => 2, //在面试队列
			'interview_arranged' => 3, //等待面试（面试时间已经确定）
			'interview_completed' => 4, //面试通过
			'moved_to_waiting_list' => 8, //在等待队列
			'seat_assigned' => 5, //席位已分配
			'invoice_issued' => 6, //等待支付帐单
			'payment_received' => 7, //付款已收到
			'locked' => 9, //操作锁定
			'quitted' => 10 //退会
		);
		
		//如果为代表ID
		if(is_int($status) || intval($status) != 0)
			$status = $this->get_delegate($status, 'status');
		
		if(!$status)
			return false;
		return $all[$status];
	}
}

/* End of file delegate_model.php */
/* Location: ./application/models/delegate_model.php */