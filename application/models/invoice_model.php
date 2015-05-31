<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 账单模块
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
	 * 获取账单信息
	 * @param int $id 账单ID
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
		$data['trigger'] = json_decode($data['trigger'], true);
		
		//返回结果
		if(empty($part))
			return $data;
		return $data[$part];
	}
	
	/**
	 * 批量获取账单信息
	 * @param int $ids 账单IDs
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_invoices($ids)
	{
		$this->db->where_in('id', $ids);
		$query = $this->db->get('invoice');
		
		//如果无结果
		if($query->num_rows() == 0)
			return false;
		
		$return = array();
		
		foreach($query->result_array() as $data)
		{
			$data['items'] = json_decode($data['items'], true);
			$data['discounts'] = json_decode($data['discounts'], true);
			$data['transaction'] = json_decode($data['transaction'], true);
			$data['trigger'] = json_decode($data['trigger'], true);
			
			$return[$data['id']] = $data;
		}
		$query->free_result();
		
		//返回结果
		return $return;
	}
	
	/**
	 * 查询符合条件的第一个账单ID
	 * @return int|false 符合查询条件的第一个账单ID，如不存在返回FALSE
	 */
	function get_invoice_id()
	{
		$args = func_get_args();
		array_unshift($args, 'invoice');
		//将参数传递给get_id方法
		return call_user_func_array(array($this->sql_model, 'get_id'), $args);
	}
	
	/**
	 * 查询符合条件的所有账单ID
	 * @return array|false 符合查询条件的所有账单ID，如不存在返回FALSE
	 */
	function get_invoice_ids()
	{
		$args = func_get_args();
		array_unshift($args, 'invoice');
		//将参数传递给get_ids方法
		return call_user_func_array(array($this->sql_model, 'get_ids'), $args);
	}
	
	/**
	 * 获取指定代表的所有账单
	 */
	function get_delegate_invoices($delegate, $only_unpaid = false)
	{
		if($only_unpaid)
			return $this->get_invoice_ids('delegate', $delegate, 'status', 'unpaid');
		return $this->get_invoice_ids('delegate', $delegate);
	}
	
	/**
	 * 转换状态为文本
	 * @param string|int $status 状态或账单ID
	 * @return string 状态文本
	 */
	function status_text($status)
	{
		//如果为账单ID
		if(is_int($status) || intval($status) != 0)
			$status = $this->get_invoice($status, 'status');
		
		switch($status)
		{
			case 'unpaid':
				return '未支付';
			case 'paid':
				return '已支付';
			case 'cancelled':
				return '已取消';
			case 'refunded':
				return '已退款';
		}
		return false;
	}
	
	/**
	 * 编辑/添加账单
	 * @return int 新的账单ID
	 */
	function edit_invoice($data, $id = '')
	{
		//账单明细
		if(isset($data['items']))
		{
			$data['items'] = json_encode($data['items'], JSON_UNESCAPED_UNICODE);
		}
		
		//折扣明细
		if(isset($data['discounts']))
		{
			$data['discounts'] = json_encode($data['discounts'], JSON_UNESCAPED_UNICODE);
		}
		
		//转账记录
		if(isset($data['transaction']))
		{
			$data['transaction'] = json_encode($data['transaction'], JSON_UNESCAPED_UNICODE);
		}
		
		//触发器
		if(isset($data['trigger']))
		{
			$data['trigger'] = json_encode($data['trigger'], JSON_UNESCAPED_UNICODE);
		}
		
		//新增账单
		if(empty($id))
		{
			$this->db->insert('invoice', $data);
			return $this->db->insert_id();
		}
		
		//更新账单
		$this->db->where('id', $id);
		return $this->db->update('invoice', $data);
	}
	
	/**
	 * 添加账单
	 * @return int 新的账单ID
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
		
		//返回新账单ID
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
	 * 取消账单
	 */
	function cancel_invoice($id, $cashier = 0)
	{
		if(!$this->is_unpaid($id))
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
		if($this->is_unpaid($id))
			return false;
		
		$data = array(
			'status' => 'refunded',
			'cashier' => $cashier
		);
		
		return $this->edit_invoice($data, $id);
	}
	
	/**
	 * 删除账单
	 * @param int $id 账单ID
	 * @return boolean 是否完成删除
	 */
	function delete_invoice($id)
	{
		$this->db->where('id', $id);
		return $this->db->delete('invoice');
	}
	
	/**
	 * 检查账单是否尚未已经支付完成
	 */
	function is_unpaid($id)
	{
		if($this->get_invoice($id, 'status') == 'unpaid')
			return true;
		return false;
	}
}

/* End of file invoice_model.php */
/* Location: ./application/models/invoice_model.php */