<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 知识库模块
 * @package iPlacard
 * @since 2.0
 */
class Knowledgebase_model extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * 获取知识库文章
	 * @param int $id 文章ID
	 * @param string $part 指定部分
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_article($id, $part = '')
	{
		$this->db->where('id', $id);
		$query = $this->db->get('knowledgebase');
		
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
	 * 查询符合条件的第一个文章ID
	 * @return int|false 符合查询条件的第一个文章ID，如不存在返回FALSE
	 */
	function get_article_id()
	{
		$args = func_get_args();
		array_unshift($args, 'knowledgebase');
		//将参数传递给get_id方法
		return call_user_func_array(array($this->sql_model, 'get_id'), $args);
	}
	
	/**
	 * 查询符合条件的所有文章ID
	 * @return array|false 符合查询条件的所有文章ID，如不存在返回FALSE
	 */
	function get_article_ids()
	{
		$args = func_get_args();
		array_unshift($args, 'knowledgebase');
		//将参数传递给get_ids方法
		return call_user_func_array(array($this->sql_model, 'get_ids'), $args);
	}
	
	/**
	 * 排序结果
	 */
	function get_ordered_articles($order = 'id', $limit = 20)
	{
		if($order == 'order')
			$this->db->order_by('order');
		elseif($order == 'count')
			$this->db->order_by('count', 'desc');
		else
			$this->db->order_by('id');
		
		$this->db->limit($limit);
		
		$query = $this->db->get('knowledgebase');
		
		//如果无结果
		if($query->num_rows() == 0)
			return false;
		
		//返回ID
		foreach($query->result_array() as $data)
		{
			$array[] = $data['id'];
		}
		$query->free_result();
		
		return $array;
	}
	
	/**
	 * 知识库全文搜索
	 */
	function search_article($keyword, $limit = 20)
	{
		$keyword = $this->db->escape_str($keyword);
		
		$this->db->select('id');
		$this->db->where("MATCH (title, content) AGAINST (\"{$keyword}\" IN BOOLEAN MODE)", NULL, false);
		$this->db->limit($limit);
		$query = $this->db->get('knowledgebase');
		
		//如果无结果
		if($query->num_rows() == 0)
			return false;
		
		//返回ID
		foreach($query->result_array() as $data)
		{
			$array[] = $data['id'];
		}
		$query->free_result();
		
		return $array;
	}
	
	/**
	 * 编辑/添加文章
	 * @return int 新的文章ID
	 */
	function edit_article($data, $id = '')
	{
		//新增文章
		if(empty($id))
		{
			$this->db->insert('knowledgebase', $data);
			return $this->db->insert_id();
		}
		
		//更新文章
		$data['update_time'] = time();
		
		$this->db->where('id', $id);
		return $this->db->update('knowledgebase', $data);
	}
	
	/**
	 * 添加文章
	 * @return int 新的文章ID
	 */
	function add_article($title, $content, $order = 100)
	{
		$this->load->helper('string');
		
		//6位代表非系统文章
		do
		{
			$kb = random_string('nozero', '6');
		}
		while($this->get_article_id('kb', $kb));
		
		$data = array(
			'kb' => $kb,
			'title' => $title,
			'content' => $content,
			'create_time' => time(),
			'order' => $order,
			'system' => false
		);
		
		//返回文章ID
		return $this->edit_article($data);
	}
	
	/**
	 * 删除文章
	 * @param int $id 文章ID
	 * @return boolean 是否完成删除
	 */
	function delete_article($id)
	{
		$this->db->where('id', $id);
		return $this->db->delete('knowledgebase');
	}
	
	/**
	 * 增加文章访问量
	 */
	function view_article($id)
	{
		$this->db->where('id', $id);
		$this->db->set('count', 'count + 1', false);
		return $this->db->update('knowledgebase');
	}
	
	/**
	 * 检查文章是否存在
	 */
	function article_exists($id)
	{
		if(!$this->get_article($id))
			return false;
		return true;
	}
	
	/**
	 * 是否为系统知识库文章
	 */
	function is_system_article($id)
	{
		if($this->get_article($id, 'system'))
			return true;
		return false;
	}
}

/* End of file knowledgebase_model.php */
/* Location: ./application/models/knowledgebase_model.php */