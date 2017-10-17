<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 注释笔记模块
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
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
		
		$data['mention'] = $this->get_note_mentions($id);
		
		//返回结果
		if(empty($part))
			return $data;
		return $data[$part];
	}
	
	/**
	 * 批量获取注释笔记信息
	 * @param array $ids 注释笔记IDs
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_notes($ids = array())
	{
		if(!empty($ids))
			$this->db->where_in('id', $ids);
		$query = $this->db->get('note');
		
		//如果无结果
		if($query->num_rows() == 0)
			return false;
		
		$return = array();
		
		foreach($query->result_array() as $data)
		{
			$data['mention'] = $this->get_note_mentions($data['id']);
			
			$return[$data['id']] = $data;
		}
		$query->free_result();
		
		//返回结果
		return $return;
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
	function add_category($name)
	{
		$data = array(
			'name' => $name
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
	
	/**
	 * 获取指定注释笔记中提及的所有用户ID
	 */
	function get_note_mentions($note)
	{
		$this->db->where('note', $note);
		
		$query = $this->db->get('note_mention');
		
		//如果无结果
		if($query->num_rows() == 0)
			return false;
		
		//返回ID
		foreach($query->result_array() as $data)
		{
			$array[] = $data['user'];
		}
		$query->free_result();
		
		return $array;
	}
	
	/**
	 * 获取指定用户被提及的所有笔记ID
	 */
	function get_user_mentions($user)
	{
		$this->db->where('user', $user);
		
		$query = $this->db->get('note_mention');
		
		//如果无结果
		if($query->num_rows() == 0)
			return false;
		
		//返回ID
		foreach($query->result_array() as $data)
		{
			$array[] = $data['note'];
		}
		$query->free_result();
		
		return $array;
	}
	
	/**
	 * 添加笔记中提及
	 * @param int $note 笔记ID
	 * @param int|array $users 一个或一组用户ID
	 * @return boolean 是否完成添加
	 */
	function add_mention($note, $users)
	{
		if(!is_array($users))
			$users = array($users);
		
		//生成数据
		foreach($users as $user)
		{
			$data[] = array(
				'note' => $note,
				'user' => $user
			);
		}
		
		return $this->db->insert_batch('note_mention', $data);
	}
	
	/**
	 * 删除笔记中提及
	 * @param int $note 笔记ID
	 * @param int|array $users 一个或一组用户ID，如为空全部删除
	 * @return boolean 是否完成删除
	 */
	function delete_mention($note, $users = '')
	{
		$this->db->where('note', $note);
		
		if(!empty($users))
			$this->db->where('user', $users);
		
		return $this->db->delete('note_mention');
	}
}

/* End of file note_model.php */
/* Location: ./application/models/note_model.php */