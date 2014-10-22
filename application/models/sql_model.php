<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 数据库通用功能模块
 * @package iPlacard
 * @since 2.0
 */
class Sql_model extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * 集成查询并返回单个结果
	 * @return int|false 符合查询条件的第一个ID，如不存在返回FALSE
	 */
	function get_id()
	{
		//将参数传递给get_ids方法
		$args = func_get_args();
		$ids = call_user_func_array(array($this, 'get_ids'), $args);
		
		//如果无结果
		if(!$ids)
			return false;
		
		return intval($ids[0]);
	}
	
	/**
	 * 集成查询功能
	 * @return array|false 符合查询条件的ID，如不存在返回FALSE
	 */
	function get_ids()
	{
		//获取传入参数数量
		$num = func_num_args();
		
		//检查是否缺少参数
		if($num % 2 == 0)
			return false;
		
		//输入查询
		for($i = 1; $i < $num; $i += 2)
		{
			if(is_array(func_get_arg($i + 1)))
				$this->db->where_in(func_get_arg($i), func_get_arg($i + 1));
			else
				$this->db->where(func_get_arg($i), func_get_arg($i + 1));
		}
		
		//获取第一个参数指定的表
		$table = func_get_arg(0);
		
		if(!is_array($table))
		{
			//单表查询
			$query = $this->db->get($table);
		}
		else
		{
			//多表查询
			$table_count = count($table);
			
			//检查是否缺少链接
			if($table_count % 2 == 0)
				return false;
			
			//输入连接
			for($i = 1; $i < $table_count; $i = $i += 2)
			{
				$this->db->join($table[$i], $table[$i + 1]);
			}
			
			$query = $this->db->get($table[0]);
		}
		
		if($query->num_rows() == 0)
			return false;
		
		//返回ID
		foreach($query->result_array() as $data)
		{
			$array[] = intval($data['id']);
		}
		$query->free_result();
		
		return $array;
	}
}
	
/* End of file sql_model.php */
/* Location: ./application/models/sql_model.php */