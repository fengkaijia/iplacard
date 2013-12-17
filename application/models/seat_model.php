<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 席位模块
 * @package iPlacard
 * @since 2.0
 */
class Seat_model extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * 获取席位信息
	 * @param int $id 席位ID
	 * @param string $part 指定部分
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_seat($id, $part = '')
	{
		$this->db->where('id', $id);
		$query = $this->db->get('seat');
		
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
	 * 查询符合条件的第一个席位ID
	 * @return int|false 符合查询条件的第一个席位ID，如不存在返回FALSE
	 */
	function get_seat_id()
	{
		$args = func_get_args();
		array_unshift($args, 'seat');
		//将参数传递给get_id方法
		return call_user_func_array(array($this->sql_model, 'get_id'), $args);
	}
	
	/**
	 * 查询符合条件的所有席位ID
	 * @return array|false 符合查询条件的所有席位ID，如不存在返回FALSE
	 */
	function get_seat_ids()
	{
		$args = func_get_args();
		array_unshift($args, 'seat');
		//将参数传递给get_ids方法
		return call_user_func_array(array($this->sql_model, 'get_ids'), $args);
	}
	
	/**
	 * 获取指定席位的全部子席位
	 * @param boolean $include_primary 是否包含主席位
	 */
	function get_attached_seat_ids($id, $include_primary = true)
	{
		$attach = $this->get_seat_ids('primary', $id);
		
		//无子席位且不含主席位
		if(!$attach && !$include_primary)
			return false;
		
		//无子席位且包含主席位
		if(!$attach && $include_primary)
			return array($id);
		
		//有子席位且不含主席位
		if(!$include_primary)
			return $attach;
		
		//有席位且包含主席位
		return array($id) + $attach;
	}
	
	/**
	 * 根据一组席位ID获取对应代表ID
	 */
	function get_delegates_by_seats($ids, $only_locked = false)
	{
		//仅单个席位ID
		if(is_string($ids))
			$ids = array($ids);
		
		$this->db->where_in('id', $ids);
		
		if($only_locked)
			$this->db->where('status', 'locked');
		
		$query = $this->db->get('seat');
		
		//如果无结果
		if($query->num_rows() == 0)
			return false;
		
		//返回ID
		foreach($query->result_array() as $data)
		{
			$array[] = $data['delegate'];
		}
		$query->free_result();
		
		return $array;
	}
	
	/**
	 * 编辑/添加席位
	 * @return int 新的席位ID
	 */
	function edit_seat($data, $id = '')
	{
		//新增席位
		if(empty($id))
		{
			$this->db->insert('seat', $data);
			return $this->db->insert_id();
		}
		
		//更新席位
		$this->db->where('id', $id);
		return $this->db->update('seat', $data);
	}
	
	/**
	 * 添加席位
	 * @return int 新的席位ID
	 */
	function add_seat($committee, $name, $level, $iso = '')
	{
		$data = array(
			'committee' => $committee,
			'name' => $name,
			'level' => $level,
			'status' => 'available'
		);
		if(!empty($iso))
			$data['iso'] = $iso;
		
		//返回新席位ID
		return $this->edit_seat($data);
	}
	
	/**
	 * 添加子席位
	 * @return int 新的席位ID
	 */
	function add_attached_seat($id, $name = '', $level = '', $iso = '')
	{
		if(!$this->is_primary_seat($id))
			return false;
		
		$primary = $this->get_seat($id);
		
		//席位名称
		if(!empty($name))
			$data['name'] = $name;
		
		//席位级别
		if(!empty($level))
			$data['level'] = $level;
		else
			$data['level'] = $primary['level'];
		
		//国家代码
		if(!empty($iso))
			$data['iso'] = $iso;
		
		$data['committee'] = $primary['committee'];
		$data['primary'] = $id;
		
		//返回新席位ID
		return $this->edit_seat($data);
	}
	
	/**
	 * 删除席位
	 * @param int $id 席位ID
	 * @return boolean 是否完成删除
	 */
	function delete_seat($id)
	{
		$this->db->where('id', $id);
		return $this->db->delete('seat');
	}
	
	/**
	 * 更改席位分配状态
	 */
	function change_status($id, $status, $change_time = false)
	{
		$available = array(
			'unavailable',
			'available',
			'preserved',
			'assigned',
			'approved', //分配兼容模式
			'locked'
		);
		
		if(!in_array($status, $available))
			return false;
		
		$data = array('status' => $status);
		if($change_time)
			$data['time'] = time();
		
		return $this->edit_seat($data, $id);
	}
	
	/**
	 * 分配席位
	 * 此方法仅修改`delegate`属性
	 */
	function assign_seat($id, $delegate)
	{
		$data = array(
			'delegate' => $delegate
		);
		
		return $this->edit_seat($data, $id);
	}
	
	/**
	 * 检查席位是否可分配
	 * @param int $committee 对保留席位检查委员会
	 */
	function is_seat_available($id, $committee = '')
	{
		$seat = $this->get_seat($id);
		
		//席位可用
		if($seat['status'] == 'available')
			return true;
		
		//席位保留
		if($seat['status'] == 'preserved' && !empty($committee) && $seat['committee'] == $committee)
			return true;
		
		return false;
	}
	
	/**
	 * 检查席位是否是主席位
	 */
	function is_primary_seat($id)
	{
		$primary = $this->get_seat($id, 'primary');
		
		if(empty($primary))
			return true;
		return false;
	}
	
	/**
	 * 检查席位是否是子席位
	 */
	function is_attached_seat($id)
	{
		$primary = $this->get_seat($id, 'primary');
		
		if(!empty($primary))
			return true;
		return false;
	}
	
	/**
	 * 是否为单代表席位
	 */
	function is_single_seat($id)
	{
		//是主席位且不存在子席位
		if($this->is_primary_seat($id) && !$this->get_seat_ids('primary', $id))
			return true;
		return false;
	}
}

/* End of file seat_model.php */
/* Location: ./application/models/seat_model.php */