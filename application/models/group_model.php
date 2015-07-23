<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 代表团模块
 * @package iPlacard
 * @since 2.0
 */
class Group_model extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * 获取代表团信息
	 * @param int $id 代表团ID
	 * @param string $part 指定部分
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_group($id, $part = '')
	{
		$this->db->where('id', $id);
		$query = $this->db->get('group');
		
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
	 * 批量获取代表团信息
	 * @param array $ids 代表团IDs
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_groups($ids = array())
	{
		if(!empty($ids))
			$this->db->where_in('id', $ids);
		$query = $this->db->get('group');
		
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
	 * 查询符合条件的第一个代表团ID
	 * @return int|false 符合查询条件的第一个代表团ID，如不存在返回FALSE
	 */
	function get_group_id()
	{
		$args = func_get_args();
		array_unshift($args, 'group');
		//将参数传递给get_id方法
		return call_user_func_array(array($this->sql_model, 'get_id'), $args);
	}
	
	/**
	 * 查询符合条件的所有代表团ID
	 * @return array|false 符合查询条件的所有代表团ID，如不存在返回FALSE
	 */
	function get_group_ids()
	{
		$args = func_get_args();
		array_unshift($args, 'group');
		//将参数传递给get_ids方法
		return call_user_func_array(array($this->sql_model, 'get_ids'), $args);
	}
	
	/**
	 * 编辑/添加代表团
	 * @return int 新的代表团ID
	 */
	function edit_group($data, $id = '')
	{
		//新增代表团
		if(empty($id))
		{
			$this->db->insert('group', $data);
			return $this->db->insert_id();
		}
		
		//更新代表团
		$this->db->where('id', $id);
		return $this->db->update('group', $data);
	}
	
	/**
	 * 添加代表团
	 * @return int 新的代表团ID
	 */
	function add_group($name, $head_delegate = NULL, $group_payment = true)
	{
		$data = array(
			'name' => $name,
			'group_payment' => $group_payment
		);
		if(!empty($head_delegate))
			$data['head_delegate'] = $head_delegate;
		
		//返回新代表团ID
		return $this->edit_group($data);
	}
	
	/**
	 * 删除代表团（不删除代表属性）
	 * @param int $id 代表团ID
	 * @return boolean 是否完成删除
	 */
	function delete_group($id)
	{
		$this->db->where('id', $id);
		return $this->db->delete('group');
	}
	
	/**
	 * 检查代表团是否支付代表账单
	 */
	function is_group_paying($id)
	{
		$pay = $this->get_group($id, 'group_payment');
		
		if($pay)
			return true;
		return false;
	}
}

/* End of file group_model.php */
/* Location: ./application/models/group_model.php */