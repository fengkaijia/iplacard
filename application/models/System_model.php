<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 系统模块
 * @package iPlacard
 * @since 2.0
 */
class System_model extends CI_Model
{
	private $options = array();
	
	function __construct()
	{
		parent::__construct();
		
		$this->options = $this->get_options();
	}
	
	/**
	 * 获取站点设置
	 * @param string $name 项目
	 * @param mixed $default 默认值
	 * @return array|false 值，如不存在返回FALSE
	 */
	function option($name, $default = NULL)
	{
		if(empty($this->options))
		{
			//获取设置
			$this->db->where('name', $name);
			$query = $this->db->get('option');

			//如果设置不存在
			if($query->num_rows() == 0)
			{
				//如果存在默认值
				if(!is_null($default))
					return $default;
				return false;
			}

			//返回结果
			$data = $query->row_array();
			return json_decode($data['value'], true);
		}
		else
		{
			//如果设置不存在
			if(!isset($this->options[$name]))
			{
				//如果存在默认值
				if(!is_null($default))
					return $default;
				return false;
			}
			
			//返回结果
			return json_decode($this->options[$name], true);
		}
	}
	
	/**
	 * option的同名函数
	 * @param string $name 项目
	 * @param mixed $default 默认值
	 * @return array|false 值，如不存在返回FALSE
	 */
	function get_option($name, $default = NULL)
	{
		return $this->option($name, $default);
	}
	
	/**
	 * 载入所有站点设置
	 */
	function get_options()
	{
		//获取设置
		$query = $this->db->get('option');
		
		//如果无结果
		if($query->num_rows() == 0)
			return false;
		
		//返回设置信息
		foreach($query->result_array() as $data)
		{
			$array[$data['name']] = $data['value'];
		}
		$query->free_result();
		
		return $array;
	}
	
	/**
	 * 编辑/添加站点设置
	 * @param string $name 项目
	 * @param array $value 值
	 */
	function edit_option($name, $value)
	{
		$value = json_encode($value, JSON_UNESCAPED_UNICODE);
		
		//如不存在项目将添加
		if(!$this->option($name))
		{
			$data = array(
				'name' => $name,
				'value' => $value
			);
			return $this->db->insert('option', $data);
		}
		
		//更新设置
		$this->db->where('name', $name);
		return $this->db->update('option', array('value' => $value));
	}
	
	/**
	 * 删除站点设置
	 * @param string $name 项目
	 * @return boolean 是否完成删除
	 */
	function delete_option($name)
	{
		$this->db->where('name', $name);
		return $this->db->delete('option');
	}
	
	/**
	 * 添加日志
	 * @param string $operation 操作名称
	 * @param array $value 日志详细信息
	 * @param int $uid 用户ID，0为系统操作
	 * @return int 日志ID
	 */
	function log($operation, $value, $uid = '')
	{
		//未登录且未指定UID情况下视为系统操作
		if($uid == '')
			$uid = !uid() ? 0 : uid();
		$uid = intval($uid);
		
		$data = array(
			'time' => time(),
			'operator' => $uid,
			'operation' => $operation,
			'value' => json_encode($value, JSON_UNESCAPED_UNICODE)
		);
		
		$this->db->insert('log', $data);
		return $this->db->insert_id();
	}
	
	/**
	 * 获取当前CodeIgniter生成session_id对应的Session表ID
	 * @param type $session 记录在Cookies中的session_id
	 * @return int|false Session表ID，如不存在返回FALSE
	 */
	function get_session_id($session)
	{
		$this->db->where('session_id', $session);
		$query = $this->db->get('session');
		
		//如不存在
		if($query->num_rows() == 0)
			return false;
		
		$data = $query->row_array();
		$query->free_result();
		
		return intval($data['id']);
	}
}

/* End of file system_model.php */
/* Location: ./application/models/system_model.php */