<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 应用模块
 * @package iPlacard
 * @since 2.0
 */
class App_model extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * 获取应用信息
	 * @param int $id 应用ID
	 * @param string $part 指定部分
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_app($id, $part = '')
	{
		$this->db->where('id', $id);
		$query = $this->db->get('app');
		
		//如果无结果
		if($query->num_rows() == 0)
			return false;
		
		$data = $query->row_array();
		$data['info'] = json_decode($data['info'], true);
		
		//返回结果
		if(empty($part))
			return $data;
		return $data[$part];
	}
	
	/**
	 * 批量获取应用信息
	 * @param array $ids 应用IDs
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_apps($ids = array())
	{
		if(!empty($ids))
			$this->db->where_in('id', $ids);
		$query = $this->db->get('app');
		
		//如果无结果
		if($query->num_rows() == 0)
			return false;
		
		$return = array();
		
		foreach($query->result_array() as $data)
		{
			$data['info'] = json_decode($data['info'], true);
			
			$return[$data['id']] = $data;
		}
		$query->free_result();
		
		//返回结果
		return $return;
	}
	
	/**
	 * 查询符合条件的第一个应用ID
	 * @return int|false 符合查询条件的第一个应用ID，如不存在返回FALSE
	 */
	function get_app_id()
	{
		$args = func_get_args();
		array_unshift($args, 'app');
		//将参数传递给get_id方法
		return call_user_func_array(array($this->sql_model, 'get_id'), $args);
	}
	
	/**
	 * 查询符合条件的所有应用ID
	 * @return array|false 符合查询条件的所有应用ID，如不存在返回FALSE
	 */
	function get_app_ids()
	{
		$args = func_get_args();
		array_unshift($args, 'app');
		//将参数传递给get_ids方法
		return call_user_func_array(array($this->sql_model, 'get_ids'), $args);
	}
	
	/**
	 * 编辑/添加应用
	 * @return int 新的应用ID
	 */
	function edit_app($data, $id = '')
	{
		//新增应用
		if(empty($id))
		{
			$this->db->insert('app', $data);
			return $this->db->insert_id();
		}
		
		//更新应用
		$this->db->where('id', $id);
		return $this->db->update('app', $data);
	}
	
	/**
	 * 添加应用
	 * @return int 新的应用ID
	 */
	function add_app($name, $type, $token, $secret = '', $info = array())
	{
		$data = array(
			'name' => $name,
			'type' => $type,
			'token' => $token,
			'secret' => $secret,
			'info' => $info
		);
		
		//返回新应用ID
		return $this->edit_app($data);
	}
	
	/**
	 * 删除应用
	 * @param int $id 应用ID
	 * @return boolean 是否完成删除
	 */
	function delete_app($id)
	{
		$this->db->where('id', $id);
		return $this->db->delete('app');
	}
}

/* End of file app_model.php */
/* Location: ./application/models/app_model.php */