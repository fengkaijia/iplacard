<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 注释笔记模块
 * @package iPlacard
 * @since 2.0
 */
class Note_model extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * 获取注释笔记信息
	 * @param int $id 注释笔记ID
	 * @param string $part 指定部分
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_note($id, $part = '')
	{
		$this->db->where('id', $id);
		$query = $this->db->get('note');
		
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
	 * 查询符合条件的第一个注释笔记ID
	 * @return int|false 符合查询条件的第一个注释笔记ID，如不存在返回FALSE
	 */
	function get_note_id()
	{
		$args = func_get_args();
		array_unshift($args, 'note');
		//将参数传递给get_id方法
		return call_user_func_array(array($this->sql_model, 'get_id'), $args);
	}
	
	/**
	 * 查询符合条件的所有注释笔记ID
	 * @return array|false 符合查询条件的所有注释笔记ID，如不存在返回FALSE
	 */
	function get_note_ids()
	{
		$args = func_get_args();
		array_unshift($args, 'note');
		//将参数传递给get_ids方法
		return call_user_func_array(array($this->sql_model, 'get_ids'), $args);
	}
	
	/**
	 * 获取指定代表的所有注释笔记ID
	 */
	function get_delegate_notes($delegate)
	{
		return $this->get_note_ids('delegate', $delegate);
	}
	
	/**
	 * 编辑/添加注释笔记
	 * @return int 新的注释笔记ID
	 */
	function edit_note($data, $id = '')
	{
		//新增注释笔记
		if(empty($id))
		{
			$this->db->insert('note', $data);
			return $this->db->insert_id();
		}
		
		//更新注释笔记
		$this->db->where('id', $id);
		return $this->db->update('note', $data);
	}
	
	/**
	 * 添加注释笔记
	 * @return int 新的注释笔记ID
	 */
	function add_note($delegate, $text, $category = NULL , $uid = '')
	{
		if(empty($uid))
			$uid = uid();
		
		$data = array(
			'admin' => $uid,
			'delegate' => $delegate,
			'text' => $text,
			'time' => time()
		);
		
		if(!empty($category))
			$data['category'] = $category;
		
		//返回新注释笔记ID
		return $this->edit_note($data);
	}
	
	/**
	 * 删除注释笔记
	 * @param int $id 注释笔记ID
	 * @return boolean 是否完成删除
	 */
	function delete_note($id)
	{
		$this->db->where('id', $id);
		return $this->db->delete('note');
	}
	
	/**
	 * 获取注释笔记分类信息
	 * @param int $id 分类ID
	 * @param string $part 指定部分
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_category($id, $part = '')
	{
		$this->db->where('id', $id);
		$query = $this->db->get('note_category');
		
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
	 * 查询符合条件的第一个注释笔记分类ID
	 * @return int|false 符合查询条件的第一个分类ID，如不存在返回FALSE
	 */
	function get_category_id()
	{
		$args = func_get_args();
		array_unshift($args, 'note_category');
		//将参数传递给get_id方法
		return call_user_func_array(array($this->sql_model, 'get_id'), $args);
	}
	
	/**
	 * 查询符合条件的所有注释笔记分类ID
	 * @return array|false 符合查询条件的所有分类ID，如不存在返回FALSE
	 */
	function get_category_ids()
	{
		$args = func_get_args();
		array_unshift($args, 'note_category');
		//将参数传递给get_ids方法
		return call_user_func_array(array($this->sql_model, 'get_ids'), $args);
	}
	
	/**
	 * 编辑/添加注释笔记分类
	 * @return int 新的分类ID
	 */
	function edit_category($data, $id = '')
	{
		//新增注释笔记分类
		if(empty($id))
		{
			$this->db->insert('note_category', $data);
			return $this->db->insert_id();
		}
		
		//更新注释笔记分类
		$this->db->where('id', $id);
		return $this->db->update('note_category', $data);
	}
	
	/**
	 * 添加注释笔记分类
	 * @return int 新的注释笔记分类ID
	 */
	function add_category($name, $type = '')
	{
		$data = array(
			'name' => $name,
			'type' => $type
		);
		
		//返回新分类ID
		return $this->edit_category($data);
	}
	
	/**
	 * 删除注释笔记分类
	 * @param int $id 分类ID
	 * @return boolean 是否完成删除
	 */
	function delete_category($id)
	{
		$this->db->where('id', $id);
		return $this->db->delete('note_category');
	}
}

/* End of file note_model.php */
/* Location: ./application/models/note_model.php */