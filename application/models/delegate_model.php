<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 代表模块
 * @package iPlacard
 * @since 2.0
 */
class Delegate_model extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * 获取代表信息
	 * @param int $id 用户ID
	 * @param string $part 指定部分
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_delegate($id, $part = '')
	{
		$this->db->where('user.id', intval($id));
		$this->db->join('delegate', 'user.id = delegate.id');
		$query = $this->db->get('user');
		
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
	 * 查询符合条件的第一个代表ID
	 * @return int|false 符合查询条件的第一个代表ID，如不存在返回FALSE
	 */
	function get_delegate_id()
	{
		$args = func_get_args();
		for($i = 0; $i < count($args); $i += 2)
		{
			if($args[$i] == 'id')
				$args[$i] = 'user.id';
		}
		
		array_unshift($args, array('user', 'delegate', 'user.id = delegate.id'));
		//将参数传递给get_id方法
		return call_user_func_array(array($this->sql_model, 'get_id'), $args);
	}
	
	/**
	 * 查询符合条件的所有代表ID
	 * @return array|false 符合查询条件的所有代表ID，如不存在返回FALSE
	 */
	function get_delegate_ids()
	{
		$args = func_get_args();
		for($i = 0; $i < count($args); $i += 2)
		{
			if($args[$i] == 'id')
				$args[$i] = 'user.id';
		}
		
		array_unshift($args, array('user', 'delegate', 'user.id = delegate.id'));
		//将参数传递给get_ids方法
		return call_user_func_array(array($this->sql_model, 'get_ids'), $args);
	}
	
	/**
	 * 获取指定代表团的所有代表
	 */
	function get_group_delegates($group, $limit = '')
	{
		$this->db->where('group', $group);
		if(!empty($limit))
			$this->db->where('application_type', $limit);
		
		$query = $this->db->get('delegate');
		
		//如果无结果
		if($query->num_rows() == 0)
			return false;
		
		//返回代表ID
		foreach($query->result_array() as $data)
		{
			$array[] = $data['id'];
		}
		$query->free_result();
		
		return $array;
	}
	
	/**
	 * 搜索代表
	 */
	function search_delegate($keyword, $limit = 20)
	{
		$this->db->select('user.id');
		
		$this->db->like('user.id', $keyword, 'none');
		
		if(intval($keyword) > 1000 || intval($keyword) == 0)
		{
			$this->db->or_like('user.name', $keyword);
			$this->db->or_like('user.email', $keyword);
			$this->db->or_like('user.phone', $keyword);
			$this->db->or_like('delegate.unique_identifier', $keyword);
		}
		
		$this->db->join('delegate', 'user.id = delegate.id');
		$this->db->limit($limit);
		$query = $this->db->get('user');
		
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
	 * 编辑代表信息
	 */
	function edit_delegate($data, $id)
	{
		$this->db->where('id', $id);
		return $this->db->update('delegate', $data);
	}
	
	/**
	 * 添加代表信息
	 */
	function add_delegate($id)
	{
		return $this->db->insert('delegate', array('id' => $id));
	}
	
	/**
	 * 更改代表申请状态
	 */
	function change_status($id, $status)
	{
		$available = array(
			'application_imported', //申请资料已导入
			'review_passed', //审核通过
			'review_refused', //审核未通过
			'interview_assigned', //在面试队列
			'interview_arranged', //等待面试（面试时间已经确定）
			'interview_completed', //面试通过
			'moved_to_waiting_list', //在等待队列
			'seat_assigned', //席位已分配
			'seat_selected', //席位已选择
			'invoice_issued', //等待支付账单
			'payment_received', //付款已收到
			'locked', //操作锁定
			'quitted', //退会
			'deleted' //计划删除
		);
		
		if(!in_array($status, $available))
			return false;
		
		return $this->edit_delegate(array('status' => $status), $id);
	}
	
	/**
	 * 转换状态为可比较代码
	 * @param string|int $status 状态或代表ID
	 * @return int 可比较的状态代码（0为最早状态）
	 */
	function status_code($status)
	{
		$all = array(
			'application_imported' => 0, //申请资料已导入
			'review_passed' => 1, //初审通过
			'review_refused' => 10, //初审未通过
			'interview_assigned' => 2, //在面试队列
			'interview_arranged' => 3, //等待面试（面试时间已经确定）
			'interview_completed' => 4, //面试通过
			'moved_to_waiting_list' => 8, //在等待队列
			'seat_assigned' => 5, //席位已分配
			'seat_selected' => 7, //席位已选择
			'invoice_issued' => 6, //等待支付账单
			'payment_received' => 7, //付款已收到
			'locked' => 9, //操作锁定
			'quitted' => 10, //退会
			'deleted' => 100 //计划删除
		);
		
		//如果为代表ID
		if(is_int($status) || intval($status) != 0)
			$status = $this->get_delegate($status, 'status');
		
		if(!$status)
			return false;
		return $all[$status];
	}
	
	/**
	 * 转换状态为文本
	 * @param string|int $status 状态或代表ID
	 * @return string 状态文本
	 */
	function status_text($status)
	{
		//如果为代表ID
		if(is_int($status) || intval($status) != 0)
			$status = $this->get_delegate($status, 'status');
		
		switch($status)
		{
			case 'application_imported':
				return '申请已导入';
			case 'review_passed':
				return '审核通过';
			case 'review_refused':
				return '审核未通过';
			case 'interview_assigned':
				return '已分配面试';
			case 'interview_arranged':
				return '已安排面试';
			case 'interview_completed':
				return '面试通过';
			case 'moved_to_waiting_list':
				return '在等待队列';
			case 'seat_assigned':
				return '席位已分配';
			case 'seat_selected':
				return '席位已选择';
			case 'invoice_issued':
				return '等待支付';
			case 'payment_received':
				return '已支付会费';
			case 'locked':
				return '申请已完成';
			case 'quitted':
				return '已经退会';
			case 'deleted':
				return '计划删除';
		}
		return false;
	}
	
	/**
	 * 转换申请类型为文本
	 * @param string|int $type 申请类型或代表ID
	 * @return string 状态文本
	 */
	function application_type_text($type)
	{
		//如果为代表ID
		if(is_int($type) || intval($type) != 0)
			$type = $this->get_delegate($type, 'application_type');
		
		switch($type)
		{
			case 'delegate':
				return '代表';
			case 'observer':
				return '观察员';
			case 'volunteer':
				return '志愿者';
			case 'teacher':
				return '指导老师';
		}
		return false;
	}
	
	/**
	 * 获取代表资料
	 * @param int $id 资料ID
	 * @param string $part 指定部分
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_profile($id, $part = '')
	{
		$this->db->where('id', $id);
		$query = $this->db->get('delegate_profile');
		
		//如果无结果
		if($query->num_rows() == 0)
			return false;
		
		$data = $query->row_array();
		$data['value'] = json_decode($data['value'], true);
		
		//返回结果
		if(empty($part))
			return $data;
		return $data[$part];
	}
	
	/**
	 * 查询符合条件的第一个资料ID
	 * @return int|false 符合查询条件的第一个资料ID，如不存在返回FALSE
	 */
	function get_profile_id()
	{
		$args = func_get_args();
		array_unshift($args, 'delegate_profile');
		//将参数传递给get_id方法
		return call_user_func_array(array($this->sql_model, 'get_id'), $args);
	}
	
	/**
	 * 查询符合条件的所有资料ID
	 * @return array|false 符合查询条件的所有资料ID，如不存在返回FALSE
	 */
	function get_profile_ids()
	{
		$args = func_get_args();
		array_unshift($args, 'delegate_profile');
		//将参数传递给get_ids方法
		return call_user_func_array(array($this->sql_model, 'get_ids'), $args);
	}
	
	/**
	 * 通过代表ID和资料项类型获取资料内容
	 */
	function get_profile_by_name($delegate, $name, $default = NULL)
	{
		$this->db->where('delegate', $delegate);
		$this->db->where('name', $name);
		$query = $this->db->get('delegate_profile');
		
		//如果无结果返回默认值
		if($query->num_rows() == 0)
			return $default;
		
		//返回结果
		$data = $query->row_array();
		return json_decode($data['value'], true);
	}
	
	/**
	 * 获取指定代表的所有资料
	 */
	function get_delegate_profiles($delegate, $return = 'value')
	{
		$this->db->where_in('delegate', $delegate);
		$query = $this->db->get('delegate_profile');
		
		//如果无结果
		if($query->num_rows() == 0)
			return false;
		
		//返回数据
		foreach($query->result_array() as $data)
		{
			if($return == 'id')
				$array[] = $data['id'];
			else
				$array[$data['name']] = json_decode($data['value'], true);
		}
		$query->free_result();
		
		return $array;
	}
	
	/**
	 * 编辑/添加代表资料
	 * @return int 新的资料ID
	 */
	function edit_profile($data, $id = '')
	{
		//格式化事件信息
		if(isset($data['value']))
			$data['value'] = json_encode($data['value'], JSON_UNESCAPED_UNICODE);
		
		//更新时间
		$data['last_modified'] = time();
		
		//新增资料
		if(empty($id))
		{
			$this->db->insert('delegate_profile', $data);
			return $this->db->insert_id();
		}
		
		//更新资料
		$this->db->where('id', $id);
		return $this->db->update('delegate_profile', $data);
	}
	
	/**
	 * 添加代表资料
	 * @return int 新的代表资料ID
	 */
	function add_profile($delegate, $name, $value)
	{
		$data = array(
			'delegate' => $delegate,
			'name' => $name,
			'value' => $value
		);
		
		//返回新资料ID
		return $this->edit_profile($data);
	}
	
	/**
	 * 删除代表资料
	 * @param int $id 代表资料ID
	 * @return boolean 是否完成删除
	 */
	function delete_profile($id)
	{
		$this->db->where('id', $id);
		return $this->db->delete('delegate_profile');
	}
	
	/**
	 * 获取代表事件
	 * @param int $id 事件ID
	 * @param string $part 指定部分
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_event($id, $part = '')
	{
		$this->db->where('id', $id);
		$query = $this->db->get('delegate_event');
		
		//如果无结果
		if($query->num_rows() == 0)
			return false;
		
		$data = $query->row_array();
		$data['info'] = json_decode($data['info'], true);
		
		//返回结果
		if(empty($part))
			return $data;
		return $data[$part];
	}
	
	/**
	 * 查询符合条件的第一个事件ID
	 * @return int|false 符合查询条件的第一个事件ID，如不存在返回FALSE
	 */
	function get_event_id()
	{
		$args = func_get_args();
		array_unshift($args, 'delegate_event');
		//将参数传递给get_id方法
		return call_user_func_array(array($this->sql_model, 'get_id'), $args);
	}
	
	/**
	 * 查询符合条件的所有事件ID
	 * @return array|false 符合查询条件的所有事件ID，如不存在返回FALSE
	 */
	function get_event_ids()
	{
		$args = func_get_args();
		array_unshift($args, 'delegate_event');
		//将参数传递给get_ids方法
		return call_user_func_array(array($this->sql_model, 'get_ids'), $args);
	}
	
	/**
	 * 获取指定代表的所有事件
	 */
	function get_delegate_events($delegate)
	{
		return $this->get_event_ids('delegate', $delegate);
	}
	
	/**
	 * 编辑/添加代表事件
	 * @return int 新的事件ID
	 */
	function edit_event($data, $id = '')
	{
		//格式化事件信息
		if(isset($data['info']))
			$data['info'] = json_encode($data['info'], JSON_UNESCAPED_UNICODE);
		
		//新增事件
		if(empty($id))
		{
			$this->db->insert('delegate_event', $data);
			return $this->db->insert_id();
		}
		
		//更新事件
		$this->db->where('id', $id);
		return $this->db->update('delegate_event', $data);
	}
	
	/**
	 * 添加代表事件
	 * @return int 新的代表事件ID
	 */
	function add_event($delegate, $event, $info = NULL)
	{
		$data = array(
			'delegate' => $delegate,
			'time' => time(),
			'event' => $event
		);
		if(!is_null($info))
			$data['info'] = $info;
		
		//返回新事件ID
		return $this->edit_event($data);
	}
}

/* End of file delegate_model.php */
/* Location: ./application/models/delegate_model.php */