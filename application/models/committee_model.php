<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 委员会模块
 * @package iPlacard
 * @since 2.0
 */
class Committee_model extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * 获取委员会信息
	 * @param int $id 委员会ID
	 * @param string $part 指定部分
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_committee($id, $part = '')
	{
		$this->db->where('id', $id);
		$query = $this->db->get('committee');
		
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
	 * 批量获取委员会信息
	 * @param int $ids 委员会IDs
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_committees($ids)
	{
		$this->db->where_in('id', $ids);
		$query = $this->db->get('committee');
		
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
	 * 查询符合条件的第一个委员会ID
	 * @return int|false 符合查询条件的第一个委员会ID，如不存在返回FALSE
	 */
	function get_committee_id()
	{
		$args = func_get_args();
		array_unshift($args, 'committee');
		//将参数传递给get_id方法
		return call_user_func_array(array($this->sql_model, 'get_id'), $args);
	}
	
	/**
	 * 查询符合条件的所有委员会ID
	 * @return array|false 符合查询条件的所有委员会ID，如不存在返回FALSE
	 */
	function get_committee_ids()
	{
		$args = func_get_args();
		array_unshift($args, 'committee');
		//将参数传递给get_ids方法
		return call_user_func_array(array($this->sql_model, 'get_ids'), $args);
	}
	
	/**
	 * 编辑/添加委员会
	 * @return int 新的委员会ID
	 */
	function edit_committee($data, $id = '')
	{
		//新增委员会
		if(empty($id))
		{
			$this->db->insert('committee', $data);
			return $this->db->insert_id();
		}
		
		//更新委员会
		$this->db->where('id', $id);
		return $this->db->update('committee', $data);
	}
	
	/**
	 * 添加委员会
	 * @return int 新的委员会ID
	 */
	function add_committee($name, $abbr, $description = '', $type = 'standard', $seat_width = 1)
	{
		$data = array(
			'name' => $name,
			'abbr' => $abbr,
			'description' => $description,
			'type' => $type,
			'seat_width' => $seat_width
		);
		
		//返回新委员会ID
		return $this->edit_committee($data);
	}
	
	/**
	 * 删除委员会
	 * @param int $id 委员会ID
	 * @return boolean 是否完成删除
	 */
	function delete_committee($id)
	{
		$this->db->where('id', $id);
		return $this->db->delete('committee');
	}
}

/* End of file committee_model.php */
/* Location: ./application/models/committee_model.php */