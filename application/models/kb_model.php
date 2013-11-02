<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 知识库模块
 * @package iPlacard
 * @since 2.0
 */
class Kb_model extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * 根据规则获取所有知识库文章ID
	 * @param string|false $order 规则，如知识库中无文章返回FALSE
	 */
	function get_all_article_ids($order = 'id')
	{
		//排序
		if($order == 'order')
			$this->db->order_by('order'); //根据用户设置
		elseif($order == 'count')
			$this->db->order_by('count', 'desc'); //根据访问量
		else
			$this->db->order_by('id'); //根据ID
		
		$query = $this->db->get('knowledgebase');
		
		//如果无结果
		if($query->num_rows() == 0)
			return false;
		
		foreach($query->result_array() as $data)
		{
			$array[] = $data['id'];
		}
		$query->free_result();
		
		//返回ID
		return $array;
	}
	
	/**
	 * 获取知识库文章信息
	 * @param int $id ID
	 * @param string $part 指定部分
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_article($id, $part = '')
	{
		$this->db->where('id', intval($id));
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
		array_unshift($args, 'article');
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
		array_unshift($args, 'article');
		//将参数传递给get_ids方法
		return call_user_func_array(array($this->sql_model, 'get_ids'), $args);
	}
	
	/**
	 * 知识库全文搜索
	 * @param sting $keyword 关键词
	 * @return array 符合搜索的文章ID
	 */
	function search_article($keyword)
	{
		//过滤关键词
		$keyword = $this->security->xss_clean($keyword);
		
		//全文搜索
		$this->db->select('id');
		$this->db->where("MATCH (title, content) AGAINST (\"{$keyword}\" IN BOOLEAN MODE)", NULL, false);
		$query = $this->db->get('knowledgebase');
		
		//如果无结果
		if($query->num_rows() == 0)
			return false;
		
		foreach($query->result_array() as $data)
		{
			$array[] = $data['id'];
		}
		$query->free_result();
		
		//返回ID
		return $array;
	}
	
	/**
	 * 编辑/添加知识库文章
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
	 * 添加知识库文章
	 * @return int 新的文章ID
	 */
	function add_article($title, $content, $order = 100)
	{
		$data = array(
			'order' => $order,
			'title' => $title,
			'content' => $content,
			'create_time' => time(),
			'system' => false //不是系统知识库文章
		);
		
		//返回新文章ID
		return $this->edit_article($data);
	}
	
	/**
	 * 删除知识库文章
	 * @param int $id 文章ID
	 * @return boolean 是否完成删除
	 */
	function delete_article($id)
	{
		//不允许删除系统知识库文章
		if($this->is_system_article($id))
			return false;
		
		$this->db->where('id', $id);
		return $this->db->delete('knowledgebase');
	}
	
	/**
	 * 增加知识库文章访问量统计
	 * @param int $id 文章ID
	 */
	function view_article($id)
	{
		$this->db->set('count', 'count + 1', false);
		$this->db->where('id', $id);
		return $this->db->update('knowledgebase');
	}

	/**
	 * 检查文章是否存在
	 * @param int $id 文章ID
	 * @return boolean
	 */
	function article_exists($id)
	{
		if($this->get_article($id))
			return true;
		return false;
	}
	
	/**
	 * 检查文章是否为系统知识库文章
	 * @return boolean
	 */
	function is_system_article($id)
	{
		if($this->get_article($id, 'system'))
			return true;
		return false;
	}
}

/* End of file kb_model.php */
/* Location: ./application/models/kb_model.php */