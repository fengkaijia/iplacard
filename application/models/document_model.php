<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 文件模块
 * @package iPlacard
 * @since 2.0
 */
class Document_model extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * 获取文件信息
	 * @param int $id 用户ID
	 * @param string $part 指定部分
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_document($id, $part = '')
	{
		$this->db->where('document.id', intval($id));
		$this->db->join('document_file', 'document.file = file.id');
		$query = $this->db->get('document');
		
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
	 * 查询符合条件的第一个文件ID
	 * @return int|false 符合查询条件的第一个文件ID，如不存在返回FALSE
	 */
	function get_document_id()
	{
		$args = func_get_args();
		array_unshift($args, 'document');
		//将参数传递给get_id方法
		return call_user_func_array(array($this->sql_model, 'get_id'), $args);
	}
	
	/**
	 * 查询符合条件的所有文件ID
	 * @return array|false 符合查询条件的所有文件ID，如不存在返回FALSE
	 */
	function get_document_ids()
	{
		$args = func_get_args();
		array_unshift($args, 'document');
		//将参数传递给get_ids方法
		return call_user_func_array(array($this->sql_model, 'get_ids'), $args);
	}
	
	/**
	 * 编辑/添加文件
	 * @return int 新的文件ID
	 */
	function edit_committee($data, $id = '')
	{
		//新增文件
		if(empty($id))
		{
			$this->db->insert('document', $data);
			return $this->db->insert_id();
		}
		
		//更新文件
		$this->db->where('id', $id);
		return $this->db->update('document', $data);
	}
	
	/**
	 * 添加文件
	 * @return int 新的文件ID
	 */
	function add_document($title, $description = '', $highlight = false, $user = '')
	{
		if(empty($user))
			$user = uid();
		
		$data = array(
			'title' => $title,
			'description' => $description,
			'highlight' => $highlight
		);
		
		//返回新文件ID
		return $this->edit_document($data);
	}
	
	/**
	 * 删除文件
	 * @param int $id 文件ID
	 * @return boolean 是否完成删除
	 */
	function delete_document($id)
	{
		$this->db->where('id', $id);
		$this->db->or_where('document', $id);
		return $this->db->delete(array('document', 'document_access', 'document_file'));
	}
}

/* End of file document_model.php */
/* Location: ./application/models/document_model.php */