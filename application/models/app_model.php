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

	/**
	 * 获取用户应用权限
	 * @param int $app 应用ID
	 * @param int $user 用户ID
	 * @return string|false 权限信息
	 */
	function get_user_role($app, $user)
	{
		$this->db->where('app', $app);
		$this->db->where('type', 'user');
		$this->db->where('key', $user);
		$query = $this->db->get('app_role');

		//如果无结果
		if($query->num_rows() == 0)
		{
			if($this->user_model->is_delegate($user))
			{
				$this->load->model('seat_model');

				$seat = $this->seat_model->get_delegate_seat($user);
				if(!$seat)
				{
					return $this->get_seat_role($app, $seat);
				}
			}

			return false;
		}

		$data = $query->row_array();

		//返回权限结果
		return $data['role'];
	}

	/**
	 * 获取用户席位应用权限
	 * @param int $app 应用ID
	 * @param int $seat 席位ID
	 * @return string|false 权限信息
	 */
	function get_seat_role($app, $seat)
	{
		$this->db->where('app', $app);
		$this->db->where('type', 'seat');
		$this->db->where('key', $seat);
		$query = $this->db->get('app_role');

		//如果无结果
		if($query->num_rows() == 0)
			return false;

		$data = $query->row_array();

		//返回权限结果
		return $data['role'];
	}

	/**
	 * 添加用户权限
	 * @param int $app 应用ID
	 * @param string $role 权限
	 * @param string $type 权限类型
	 * @param int|array $keys 一个或一组用户或席位ID
	 * @return boolean 是否完成添加
	 */
	function add_role($app, $role, $type, $keys)
	{
		if(!is_array($keys))
			$keys = array($keys);

		//生成数据
		foreach($keys as $key)
		{
			$data[] = array(
				'app' => $app,
				'role' => $role,
				'type' => $type,
				'key' => $key
			);
		}

		return $this->db->insert_batch('app_role', $data);
	}

	/**
	 * 删除用户权限
	 * @param int $app 应用ID
	 * @param string $type 权限类型，如为空全部选择
	 * @param int|array $keys 一个或一组用户或席位ID，如为空全部删除
	 * @return boolean 是否完成删除
	 */
	function delete_role($app, $type = '', $keys = '')
	{
		$this->db->where('app', $app);

		if(!empty($type))
			$this->db->where('type', $type);

		if(!empty($keys))
		{
			if(is_array($keys))
				$this->db->where_in('key', $keys);
			else
				$this->db->where('key', $keys);
		}

		return $this->db->delete('app_role');
	}
}

/* End of file app_model.php */
/* Location: ./application/models/app_model.php */