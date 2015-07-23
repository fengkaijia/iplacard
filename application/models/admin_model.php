<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 管理员模块
 * @package iPlacard
 * @since 2.0
 */
class Admin_model extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * 获取管理员信息
	 * @param int $id 用户ID
	 * @param string $part 指定部分
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_admin($id, $part = '')
	{
		$this->db->where('user.id', intval($id));
		$this->db->join('admin', 'user.id = admin.id', 'left outer');
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
	 * 批量获取管理员信息
	 * @param array $ids 用户IDs
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_admins($ids = array())
	{
		if(!empty($ids))
			$this->db->where_in('user.id', $ids);
		$this->db->join('admin', 'user.id = admin.id', 'left outer');
		$query = $this->db->get('user');
		
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
	 * 查询符合条件的第一个管理员ID
	 * @return int|false 符合查询条件的第一个管理员ID，如不存在返回FALSE
	 */
	function get_admin_id()
	{
		$args = func_get_args();
		array_unshift($args, 'admin');
		//将参数传递给get_id方法
		return call_user_func_array(array($this->sql_model, 'get_id'), $args);
	}
	
	/**
	 * 查询符合条件的所有管理员ID
	 * @return array|false 符合查询条件的所有管理员ID，如不存在返回FALSE
	 */
	function get_admin_ids()
	{
		$args = func_get_args();
		array_unshift($args, 'admin');
		//将参数传递给get_ids方法
		return call_user_func_array(array($this->sql_model, 'get_ids'), $args);
	}
	
	/**
	 * 检查管理员是否拥有指定权限
	 * @param string|array $role 一个或一组权限（满足单一条件）
	 * @return boolean
	 */
	function capable($role, $id = '')
	{
		//如$id为空使用当前登录者ID
		if(empty($id))
			$id = uid(true);
		
		//权限
		$roles = array('reviewer', 'dais', 'interviewer', 'cashier', 'administrator', 'bureaucrat');
		
		//多项权限
		if(is_string($role))
			$role = array($role);
		
		foreach($role as $one)
		{
			if(!in_array($one, $roles))
				continue;
			
			if($this->get_admin($id, "role_$one"))
				return true;
		}
		
		return false;
	}
	
	/**
	 * 编辑管理员信息
	 */
	function edit_profile($data, $id)
	{
		$this->db->where('id', $id);
		return $this->db->update('admin', $data);
	}
	
	/**
	 * 添加管理员信息
	 */
	function add_profile($id)
	{
		return $this->db->insert('admin', array('id' => $id));
	}
}

/* End of file admin_model.php */
/* Location: ./application/models/admin_model.php */