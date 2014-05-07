<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 帐单模块
 * @package iPlacard
 * @since 2.0
 */
class Invoice_model extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * 获取帐单信息
	 * @param int $id 帐单ID
	 * @param string $part 指定部分
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_invoice($id, $part = '')
	{
		$this->db->where('id', $id);
		$query = $this->db->get('invoice');
		
		//如果无结果
		if($query->num_rows() == 0)
			return false;
		
		$data = $query->row_array();
		$data['items'] = json_decode($data['items'], true);
		$data['discounts'] = json_decode($data['discounts'], true);
		$data['transaction'] = json_decode($data['transaction'], true);
		
		//返回结果
		if(empty($part))
			return $data;
		return $data[$part];
	}
	
	/**
	 * 查询符合条件的第一个帐单ID
	 * @return int|false 符合查询条件的第一个帐单ID，如不存在返回FALSE
	 */
	function get_invoice_id()
	{
		$args = func_get_args();
		array_unshift($args, 'invoice');
		//将参数传递给get_id方法
		return call_user_func_array(array($this->sql_model, 'get_id'), $args);
	}
	
	/**
	 * 查询符合条件的所有帐单ID
	 * @return array|false 符合查询条件的所有帐单ID，如不存在返回FALSE
	 */
	function get_invoice_ids()
	{
		$args = func_get_args();
		array_unshift($args, 'invoice');
		//将参数传递给get_ids方法
		return call_user_func_array(array($this->sql_model, 'get_ids'), $args);
	}
	
	/**
	 * 编辑/添加帐单
	 * @return int 新的帐单ID
	 */
	function edit_invoice($data, $id = '')
	{
		//帐单明细
		if(isset($data['items']) && !empty($data['items']))
		{
			$data['items'] = json_encode($data['items']);
		}
		
		//折扣明细
		if(isset($data['discounts']) && !empty($data['discounts']))
		{
			$data['discounts'] = json_encode($data['discounts']);
		}
		
		//转账记录
		if(isset($data['transaction']) && !empty($data['transaction']))
		{
			$data['transaction'] = json_encode($data['transaction']);
		}
		
		//新增帐单
		if(empty($id))
		{
			$this->db->insert('invoice', $data);
			return $this->db->insert_id();
		}
		
		//更新帐单
		$this->db->where('id', $id);
		return $this->db->update('invoice', $data);
	}
	
	/**
	 * 添加帐单
	 * @return int 新的帐单ID
	 */
	function generate_invoice($delegate, $title, $amount, $due_time, $items = array(), $discounts = array(), $trigger = array())
	{
		if($due_time < 365 * 24 * 60 * 60)
			$due_time = time() + $due_time;
		
		$data = array(
			'delegate' => $delegate,
			'title' => $title,
			'items' => $items,
			'discounts' => $discounts,
			'amount' => $amount,
			'generate_time' => time(),
			'due_time' => $due_time,
			'status' => 'unpaid',
			'trigger' => $trigger
		);
		
		//返回新帐单ID
		return $this->edit_invoice($data);
	}
	
	/**
	 * 收到款项
	 */
	function receive_invoice($id, $cashier = 0)
	{
		$data = array(
			'receive_time' => time(),
			'status' => 'paid',
			'cashier' => $cashier
		);
		
		return $this->edit_invoice($data, $id);
	}
	
	/**
	 * 取消帐单
	 */
	function cancel_invoice($id, $cashier = 0)
	{
		if($this->is_paid($id))
			return false;
		
		$data = array(
			'receive_time' => time(),
			'status' => 'cancelled',
			'cashier' => $cashier
		);
		
		return $this->edit_invoice($data, $id);
	}
	
	/**
	 * 退款
	 */
	function refund_invoice($id, $cashier = 0)
	{
		if(!$this->is_paid($id))
			return false;
		
		$data = array(
			'status' => 'refunded',
			'cashier' => $cashier
		);
		
		return $this->edit_invoice($data, $id);
	}
	
	/**
	 * 删除帐单
	 * @param int $id 帐单ID
	 * @return boolean 是否完成删除
	 */
	function delete_invoice($id)
	{
		$this->db->where('id', $id);
		return $this->db->delete('invoice');
	}
	
	/**
	 * 检查帐单是否已经支付完成
	 */
	function is_paid($id)
	{
		if($this->get_invoice($id, 'status') == 'paid')
			return true;
		return false;
	}
}

/* End of file invoice_model.php */
/* Location: ./application/models/invoice_model.php */