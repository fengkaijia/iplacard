<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 两步验证模块
 * @package iPlacard
 * @since 2.0
 */
class Twostep_model extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * 获取验证码信息
	 * @param int $id ID
	 * @param string $part 指定部分
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_recode($id, $part = '')
	{
		$this->db->where('id', intval($id));
		$query = $this->db->get('twostep_recode');
		
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
	 * 查询符合条件的第一个验证码ID
	 * @return int|false 符合查询条件的第一个验证码ID，如不存在返回FALSE
	 */
	function get_recode_id()
	{
		$args = func_get_args();
		array_unshift($args, 'twostep_recode');
		//将参数传递给get_id方法
		return call_user_func_array(array($this->sql_model, 'get_id'), $args);
	}
	
	/**
	 * 查询符合条件的所有验证码ID
	 * @return array|false 符合查询条件的所有验证码ID，如不存在返回FALSE
	 */
	function get_recode_ids()
	{
		$args = func_get_args();
		array_unshift($args, 'twostep_recode');
		//将参数传递给get_ids方法
		return call_user_func_array(array($this->sql_model, 'get_ids'), $args);
	}
	
	/**
	 * 编辑/添加验证码
	 * @return int 新的验证码ID
	 */
	function edit_recode($data, $id = '')
	{
		$data['time'] = time();
		
		//新增验证码
		if(empty($id))
		{
			$this->db->insert('twostep_recode', $data);
			return $this->db->insert_id();
		}
		
		//更新验证码
		$this->db->where('id', $id);
		return $this->db->update('twostep_recode', $data);
	}
	
	/**
	 * 添加验证码
	 * @return int 新的验证码ID
	 */
	function add_recode($user, $code)
	{
		$data = array(
			'user' => $user,
			'code' => $code
		);
		
		//返回验证码ID
		return $this->edit_recode($data);
	}
	
	/**
	 * 检查验证码是否存在并且仍在有效期
	 * @return boolean
	 */
	function recode_exists($user, $code, $time_range)
	{
		if($this->get_recode_id('user', $user, 'code', $code, 'time >', time() - $time_range))
			return true;
		return false;
	}
	
	/**
	 * 获取不再要求两步验证记录
	 * @param int $id ID
	 * @param string $part 指定部分
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_safe($id, $part = '')
	{
		$this->db->where('id', intval($id));
		$query = $this->db->get('twostep_safe');
		
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
	 * 查询符合条件的第一个不再要求两步验证记录ID
	 * @return int|false 符合查询条件的第一个不再要求两步验证记录ID，如不存在返回FALSE
	 */
	function get_safe_id()
	{
		$args = func_get_args();
		array_unshift($args, 'twostep_safe');
		//将参数传递给get_id方法
		return call_user_func_array(array($this->sql_model, 'get_id'), $args);
	}
	
	/**
	 * 查询符合条件的所有不再要求两步验证记录ID
	 * @return array|false 符合查询条件的所有不再要求两步验证记录ID，如不存在返回FALSE
	 */
	function get_safe_ids()
	{
		$args = func_get_args();
		array_unshift($args, 'twostep_safe');
		//将参数传递给get_ids方法
		return call_user_func_array(array($this->sql_model, 'get_ids'), $args);
	}
	
	/**
	 * 编辑/添加不再要求两步验证记录
	 * @return int 新的不再要求两步验证记录ID
	 */
	function edit_safe($data, $id = '')
	{
		$data['auth_time'] = time();
		
		//新增记录
		if(empty($id))
		{
			$this->db->insert('twostep_safe', $data);
			return $this->db->insert_id();
		}
		
		//更新记录
		$this->db->where('id', $id);
		return $this->db->update('twostep_safe', $data);
	}
	
	/**
	 * 添加不再要求两步验证记录
	 * @return int 新的不再要求两步验证记录ID
	 */
	function add_safe($user, $code, $ip, $ua)
	{
		$data = array(
			'user' => $user,
			'code' => $code,
			'auth_ip' => $ip,
			'ua' => $ua
		);
		
		//返回验证码ID
		return $this->edit_safe($data);
	}
	
	/**
	 * 检查验证码是否存在并且仍在有效期
	 * @return boolean
	 */
	function safe_exists($user, $code, $ua, $time_range)
	{
		if($this->get_safe_id('user', $user, 'code', $code, 'ua', $ua, 'auth_time >', time() - $time_range))
			return true;
		return false;
	}
}

/* End of file twostep_model.php */
/* Location: ./application/models/twostep_model.php */