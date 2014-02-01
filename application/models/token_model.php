<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 访问令牌模块
 * @package iPlacard
 * @since 2.0
 */
class Token_model extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * 获取访问令牌信息
	 * @param int|string $key ID或访问令牌字串
	 * @param string $part 指定部分
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_token($key, $part = '')
	{
		//判断是否$key查询部分
		if(is_string($key) && strlen($key) == 32)
			$this->db->where('access_token', $key);
		else
			$this->db->where('id', intval($key));
		
		$query = $this->db->get('api_token');
		$data['permission'] = json_decode($data['permission'], true);
		
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
	 * 查询符合条件的第一个令牌ID
	 * @return int|false 符合查询条件的第一个令牌ID，如不存在返回FALSE
	 */
	function get_token_id()
	{
		$args = func_get_args();
		array_unshift($args, 'api_token');
		//将参数传递给get_id方法
		return call_user_func_array(array($this->sql_model, 'get_id'), $args);
	}
	
	/**
	 * 查询符合条件的所有令牌ID
	 * @return array|false 符合查询条件的所有令牌ID，如不存在返回FALSE
	 */
	function get_token_ids()
	{
		$args = func_get_args();
		array_unshift($args, 'api_token');
		//将参数传递给get_ids方法
		return call_user_func_array(array($this->sql_model, 'get_ids'), $args);
	}
	
	/**
	 * 检查令牌是否拥有指定权限
	 * @param string $permission 需要校验的权限
	 * @param int|string|array $id ID（或访问令牌字串）或已有权限信息
	 * @return boolean
	 */
	function capable($permission, $id)
	{
		//获取权限信息
		if(!is_array($id))
			$permissions = $this->get_token($id, 'permission');
		else
			$permissions = $id;
		
		if(!in_array($permission, $permissions))
			return false;
		return true;
	}
	
	/**
	 * 编辑/添加访问令牌
	 * @return int 新的令牌ID
	 */
	function edit_token($data, $id = '')
	{
		//格式化权限
		if(isset($data['permission']) && !empty($data['permission']))
			$data['permission'] = json_encode($data['permission']);
		
		//新增令牌
		if(empty($id))
		{
			$this->db->insert('api_token', $data);
			return $this->db->insert_id();
		}
		
		//更新令牌
		$this->db->where('id', $id);
		return $this->db->update('api_token', $data);
	}
	
	/**
	 * 添加访问令牌
	 * @return int 新的令牌ID
	 */
	function add_token($access_token, $note = '', $ip_range = NULL, $permission = NULL)
	{
		$data = array(
			'access_token' => $access_token,
			'note' => $note,
			'ip_range' => $ip_range,
			'permission' => $permission
		);
		
		//返回新令牌ID
		return $this->edit_token($data);
	}
	
	/**
	 * 删除访问令牌
	 * @param int $id 令牌ID
	 * @return boolean 是否完成删除
	 */
	function delete_token($id)
	{
		$this->db->where('id', $id);
		return $this->db->delete('api_token');
	}
	
	/**
	 * 更新令牌最后活动时间
	 */
	function update_last_activity($id)
	{
		return $this->edit_token(array('last_activity', time()), $id);
	}
	
	/**
	 * 检查IP是否在指定的范围内
	 * @param type $range 范围
	 * @param type $ip 待检查IP地址
	 */
	function check_ip_range($range, $ip = '')
	{
		if(empty($ip))
			$ip = $this->input->ip_address();
			
		//快速检查
		$range = trim($range);
		if(is_null($range) || empty($range))
			return false;
		
		//获取分段范围
		$ranges = explode("\n", $range);
		
		foreach($ranges as $check_range)
		{
			if($check_range == 'all')
				return true;
			elseif(strpos($check_range, '-') !== false)
			{
				list($range_start, $range_end) = explode('-', $check_range);
				if($this->_check_ip_range($range_start, $range_end, $ip))
					return true;
			}
			else
			{
				if($this->_check_ip_wildcard($check_range, $ip))
					return true;
			}
		}
		return false;
	}
	
	/**
	 * 检查IP是否在序列内
	 * @link http://wordpress.org/plugins/wp-ban/
	 */
	private function _check_ip_wildcard($ips, $ip)
	{
		$ips = preg_quote($ips, '#');
		$ips = str_replace('\*', '.*', $ips);
		
		if(preg_match("#^$ips$#", $ip))
			return true;
		return false;
	}
	
	/**
	 * 检查IP是否在范围内
	 * @link http://wordpress.org/plugins/wp-ban/
	 */
	private function _check_ip_range($range_start, $range_end, $ip)
	{
		$range_start = ip2long($range_start);
		$range_end = ip2long($range_end);
		$ip = ip2long($ip);
		
		if($ip !== false && $ip >= $range_start && $ip <= $range_end)
			return true;
		return false;
	}
}

/* End of file token_model.php */
/* Location: ./application/models/token_model.php */