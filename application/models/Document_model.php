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
	 * @param int $id 文件ID
	 * @param string $part 指定部分
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_document($id, $part = '')
	{
		$this->db->where('id', intval($id));
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
	 * 批量获取文件信息
	 * @param array $ids 文件IDs
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_documents($ids = array())
	{
		if(!empty($ids))
			$this->db->where_in('id', $ids);
		$query = $this->db->get('document');
		
		//如果无结果
		if($query->num_rows() == 0)
			return false;
		
		$return = array();
		
		foreach($query->result_array() as $data)
		{
			$return[$data['id']] = $data;
		}
		$query->free_result();
		
		//返回结果
		return $return;
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
	 * 获取指定委员会的所有可查看文件
	 */
	function get_committee_documents($committee)
	{
		if($committee != 0)
			$this->db->where_in('access', array($committee, 0));
		else
			$this->db->where('access', 0);
		
		$query = $this->db->get('document_access');
		
		//如果无结果
		if($query->num_rows() == 0)
			return false;
		
		//返回文件ID
		foreach($query->result_array() as $data)
		{
			$array[] = $data['document'];
		}
		$query->free_result();
		
		return $array;
	}
	
	/**
	 * 获取指定文件的访问范围
	 */
	function get_document_accessibility($document)
	{
		$this->db->where('document', $document);
		$query = $this->db->get('document_access');
		
		//如果无结果
		if($query->num_rows() == 0)
			return false;
		
		//返回文件ID
		foreach($query->result_array() as $data)
		{
			if($data['access'] == 0)
				return true;
			
			$array[] = $data['access'];
		}
		$query->free_result();
		
		return $array;
	}
	
	/**
	 * 获取指定文件的可用格式
	 */
	function get_document_formats($document)
	{
		$this->db->where('document', $document);
		$query = $this->db->get('document_file');
		
		//如果无结果
		if($query->num_rows() == 0)
			return false;
		
		//返回格式ID
		$array = array();
		foreach($query->result_array() as $data)
		{
			if(!in_array($data['format'], $array))
				$array[] = $data['format'];
		}
		$query->free_result();
		
		sort($array);
		return $array;
	}
	
	/**
	 * 编辑/添加文件
	 * @return int 新的文件ID
	 */
	function edit_document($data, $id = '')
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
			'highlight' => $highlight,
			'create_time' => time(),
			'user' => $user
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
		return $this->db->delete('document');
	}
	
	/**
	 * 添加访问权限
	 * @param int $document 文件ID
	 * @param int|array $committees 一个或一组委员会ID或0
	 * @return boolean 是否完成添加
	 */
	function add_access($document, $committees)
	{
		if(!is_array($committees))
			$committees = array($committees);
		
		//生成数据
		foreach($committees as $committee)
		{
			$data[] = array(
				'document' => $document,
				'access' => $committee
			);
		}
		
		return $this->db->insert_batch('document_access', $data);
	}
	
	/**
	 * 删除访问权限
	 * @param int $document 文件ID
	 * @param int|array $committees 一个或一组委员会ID或0，如为空全部删除
	 * @return boolean 是否完成删除
	 */
	function delete_access($document, $committees = '')
	{
		$this->db->where('document', $document);

		if(!empty($committees))
		{
			if(is_array($committees))
				$this->db->where_in('access', $committees);
			else
				$this->db->where('access', $committees);
		}
		
		return $this->db->delete('document_access');
	}
	
	/**
	 * 检查文件是否存在
	 * @param int $id 文件ID
	 * @return boolean
	 */
	function document_exists($id)
	{
		if($this->get_document($id))
			return true;
		return false;
	}
	
	/**
	 * 检查指定的委员会是否可访问指定文件
	 * @return boolean
	 */
	function is_accessible($document, $committee)
	{
		$access = $this->get_document_accessibility($document);
		
		if($access === true)
			return true;
		
		if(in_array($committee, $access))
			return true;
		return false;
	}
	
	/**
	 * 检查文件是否可全局访问
	 * @return boolean
	 */
	function is_global_accessible($document)
	{
		$access = $this->get_document_accessibility($document);
		
		if($access === true)
			return true;
		return false;
	}
		
	/**
	 * 获取文件版本信息
	 * @param int $id 文件版本ID
	 * @param string $part 指定部分
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_file($id, $part = '')
	{
		$this->db->where('id', intval($id));
		$query = $this->db->get('document_file');
		
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
	 * 批量获取文件版本信息
	 * @param array $ids 文件版本IDs
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_files($ids = array())
	{
		if(!empty($ids))
			$this->db->where_in('id', $ids);
		$query = $this->db->get('document_file');
		
		//如果无结果
		if($query->num_rows() == 0)
			return false;
		
		$return = array();
		
		foreach($query->result_array() as $data)
		{
			$return[$data['id']] = $data;
		}
		$query->free_result();
		
		//返回结果
		return $return;
	}
	
	/**
	 * 查询符合条件的第一个文件版本ID
	 * @return int|false 符合查询条件的第一个文件版本ID，如不存在返回FALSE
	 */
	function get_file_id()
	{
		$args = func_get_args();
		array_unshift($args, 'document_file');
		//将参数传递给get_id方法
		return call_user_func_array(array($this->sql_model, 'get_id'), $args);
	}
	
	/**
	 * 查询符合条件的所有文件版本ID
	 * @return array|false 符合查询条件的所有文件版本ID，如不存在返回FALSE
	 */
	function get_file_ids()
	{
		$args = func_get_args();
		array_unshift($args, 'document_file');
		//将参数传递给get_ids方法
		return call_user_func_array(array($this->sql_model, 'get_ids'), $args);
	}
	
	/**
	 * 查询指定文件的最新文件版本ID
	 * @return int|false 指定文件的最新文件版本ID，如不存在返回FALSE
	 */
	function get_document_file($document, $format = '')
	{
		if(!empty($format))
			$ids = $this->get_document_files($document, array($format));
		else
			$ids = $this->get_document_files($document);
		
		if(!$ids)
			return false;
		
		return max($ids);
	}
	
	/**
	 * 查询指定文件的所有文件版本ID
	 * @return array|false 指定文件的所有文件版本ID，如不存在返回FALSE
	 */
	function get_document_files($document, $format = array())
	{
		if(!empty($format))
			return $this->get_file_ids('document', $document, 'format', $format);
		
		return $this->get_file_ids('document', $document);
	}
	
	/**
	 * 编辑/添加版本
	 * @return int 新的文件版本ID
	 */
	function edit_file($data, $id = '')
	{
		//新增文件版本
		if(empty($id))
		{
			$this->db->insert('document_file', $data);
			return $this->db->insert_id();
		}
		
		//更新文件版本
		$this->db->where('id', $id);
		return $this->db->update('document_file', $data);
	}
	
	/**
	 * 添加文件版本
	 * @param int $document 文件ID
	 * @param string $file_path 上传文件路径
	 * @param int $format 文件格式ID
	 * @param string $version 版本号
	 * @param string $identifier 文献标识保护
	 * @param int $user 上传用户ID
	 */
	function add_file($document, $file_path, $format = 1, $version = '', $identifier = '', $user = '')
	{
		$this->load->helper('file');
		
		//文件属性
		$file = get_file_info($file_path);
		if(!$file)
			return false;
		
		if(empty($user))
			$user = uid();
		
		//保护设置
		$type = pathinfo($file_path, PATHINFO_EXTENSION);
		
		$data = array(
			'document' => $document,
			'format' => $format,
			'version' => $version,
			'filetype' => $type,
			'filesize' => $file['size'],
			'hash' => sha1_file($file_path),
			'identifier' => !empty($identifier) ? $identifier : NULL,
			'user' => $user,
			'upload_time' => time()
		);
		
		//返回新文件版本ID
		return $this->edit_file($data);
	}
	
	/**
	 * 删除文件版本
	 * @param int $id 文件版本ID
	 * @return boolean 是否完成删除
	 */
	function delete_file($id)
	{
		$this->db->where('id', $id);
		return $this->db->delete('document_file');
	}
	
	/**
	 * 检查文件版本是否存在
	 * @param int $id 文件版本ID
	 * @return boolean
	 */
	function file_exists($id)
	{
		if($this->get_file($id))
			return true;
		return false;
	}
	
	/**
	 * 检查文件版本是否开启标识保护
	 * @param int $file 文件版本ID
	 */
	function is_identifier_enabled($file)
	{
		$identifier = $this->get_file($file, 'identifier');
		return $identifier && !empty($identifier);
	}
	
	/**
	 * 获取文件格式信息
	 * @param int $id 格式ID
	 * @param string $part 指定部分
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_format($id, $part = '')
	{
		$this->db->where('id', $id);
		$query = $this->db->get('document_format');
		
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
	 * 批量获取文件格式信息
	 * @param array $ids 格式IDs
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_formats($ids = array())
	{
		if(!empty($ids))
			$this->db->where_in('id', $ids);
		$query = $this->db->get('document_format');
		
		//如果无结果
		if($query->num_rows() == 0)
			return false;
		
		$return = array();
		
		foreach($query->result_array() as $data)
		{
			$return[$data['id']] = $data;
		}
		$query->free_result();
		
		//返回结果
		return $return;
	}
	
	/**
	 * 查询符合条件的第一个文件格式ID
	 * @return int|false 符合查询条件的第一个格式ID，如不存在返回FALSE
	 */
	function get_format_id()
	{
		$args = func_get_args();
		array_unshift($args, 'document_format');
		//将参数传递给get_id方法
		return call_user_func_array(array($this->sql_model, 'get_id'), $args);
	}
	
	/**
	 * 查询符合条件的所有文件格式ID
	 * @return array|false 符合查询条件的所有格式ID，如不存在返回FALSE
	 */
	function get_format_ids()
	{
		$args = func_get_args();
		array_unshift($args, 'document_format');
		//将参数传递给get_ids方法
		return call_user_func_array(array($this->sql_model, 'get_ids'), $args);
	}
	
	/**
	 * 编辑/添加文件格式
	 * @return int 新的格式ID
	 */
	function edit_format($data, $id = '')
	{
		//新增文件格式
		if(empty($id))
		{
			$this->db->insert('document_format', $data);
			return $this->db->insert_id();
		}
		
		//更新文件格式
		$this->db->where('id', $id);
		return $this->db->update('document_format', $data);
	}
	
	/**
	 * 添加文件格式
	 * @return int 新的文件格式ID
	 */
	function add_format($name, $detail = '')
	{
		$data = array(
			'name' => $name,
			'detail' => $detail
		);
		
		//返回新格式ID
		return $this->edit_format($data);
	}
	
	/**
	 * 删除文件格式
	 * @param int $id 格式ID
	 * @return boolean 是否完成删除
	 */
	function delete_format($id)
	{
		$this->db->where('id', $id);
		return $this->db->delete('document_format');
	}
	
	/**
	 * 获取文件下载记录
	 * @param int $id 下载记录ID
	 * @param string $part 指定部分
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_download($id, $part = '')
	{
		$this->db->where('id', intval($id));
		$query = $this->db->get('document_download');
		
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
	 * 查询符合条件的第一个文件下载记录ID
	 * @return int|false 符合查询条件的第一个文件下载ID，如不存在返回FALSE
	 */
	function get_download_id()
	{
		$args = func_get_args();
		array_unshift($args, 'document_download');
		//将参数传递给get_id方法
		return call_user_func_array(array($this->sql_model, 'get_id'), $args);
	}
	
	/**
	 * 查询符合条件的所有文件下载记录ID
	 * @return array|false 符合查询条件的所有文件下载ID，如不存在返回FALSE
	 */
	function get_download_ids()
	{
		$args = func_get_args();
		array_unshift($args, 'document_download');
		//将参数传递给get_ids方法
		return call_user_func_array(array($this->sql_model, 'get_ids'), $args);
	}
	
	/**
	 * 获取文件版本的下载记录
	 * @return array|false 指定文件版本的所有下载ID，如不存在返回FALSE
	 */
	function get_file_downloads($file)
	{
		return $this->get_download_ids('file', $file);
	}
	
	/**
	 * 获取文件的下载记录
	 * @return array|false 指定文件的所有下载ID，如不存在返回FALSE
	 */
	function get_document_downloads($document)
	{
		$file = $this->get_document_files($document);
		
		if(!$file)
			return false;
		
		return $this->get_download_ids('file', $file);
	}
	
	/**
	 * 获取用户的下载记录
	 * @return array|false 指定用户的所有下载ID，如不存在返回FALSE
	 */
	function get_user_downloads($user)
	{
		return $this->get_download_ids('user', $user);
	}
	
	/**
	 * 添加文件下载记录
	 * @param int $file 文件版本ID
	 * @param int $user 用户ID
	 * @param string $drm 版权标识
	 * @return int 下载记录ID
	 */
	function add_download($file, $user = '', $drm = '')
	{
		if(empty($user))
			$user = uid();
		
		$data = array(
			'file' => $file,
			'user' => $user,
			'time' => time(),
			'ip' => $this->input->ip_address()
		);
		
		if(!empty($drm))
			$data['drm'] = $drm;
		
		$this->db->insert('document_download', $data);
		return $this->db->insert_id();
	}
	
	/**
	 * 检查用户是否已经下载指定的文件或文件版本
	 * @param int $user 用户ID
	 * @param int $key 文件ID或文件版本ID
	 * @param string $type 搜索的类型
	 */
	function is_user_downloaded($user, $key, $type = 'file')
	{
		if($type == 'document')
			$key = $this->get_document_files($key);
		
		if($this->get_download_ids('user', $user, 'file', $key))
			return true;
		return false;
	}
}

/* End of file document_model.php */
/* Location: ./application/models/document_model.php */