<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 面试模块
 * @package iPlacard
 * @since 2.0
 */
class Interview_model extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * 获取面试信息
	 * @param int $id 面试ID
	 * @param string $part 指定部分
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_interview($id, $part = '')
	{
		$this->db->where('id', $id);
		$query = $this->db->get('interview');
		
		//如果无结果
		if($query->num_rows() == 0)
			return false;
		
		$data = $query->row_array();
		$data['feedback'] = json_decode($data['feedback'], true);
		
		//返回结果
		if(empty($part))
			return $data;
		return $data[$part];
	}
	
	/**
	 * 查询符合条件的第一个面试ID
	 * @return int|false 符合查询条件的第一个面试ID，如不存在返回FALSE
	 */
	function get_interview_id()
	{
		$args = func_get_args();
		array_unshift($args, 'interview');
		//将参数传递给get_id方法
		return call_user_func_array(array($this->sql_model, 'get_id'), $args);
	}
	
	/**
	 * 查询符合条件的所有面试ID
	 * @return array|false 符合查询条件的所有面试ID，如不存在返回FALSE
	 */
	function get_interview_ids()
	{
		$args = func_get_args();
		array_unshift($args, 'interview');
		//将参数传递给get_ids方法
		return call_user_func_array(array($this->sql_model, 'get_ids'), $args);
	}
	
	/**
	 * 查询代表当前面试状态下的面试ID
	 * @return int|false 符合查询条件的第一个面试ID，如不存在返回FALSE
	 */
	function get_current_interview_id($delegate)
	{
		return $this->get_interview_id('delegate', $delegate, 'status', array('assigned', 'arranged', 'completed', 'exempted'));
	}
	
	/**
	 * 查询面试官面试队列ID
	 */
	function get_interviewer_interviews($interviewer, $status = array())
	{
		if(empty($status))
			return $this->get_interview_ids('interviewer', $interviewer);
		
		return $this->get_interview_ids('status', $status, 'interviewer', $interviewer);
	}
	
	/**
	 * 根据一组面试ID获取对应代表ID
	 * @param int|array $ids 一个或一组面试ID
	 */
	function get_delegates_by_interviews($ids)
	{
		//仅单个面试ID
		if(is_int($ids) || is_string($ids))
			$ids = array($ids);
		
		$this->db->where_in('id', $ids);
		
		$query = $this->db->get('interview');
		
		//如果无结果
		if($query->num_rows() == 0)
			return false;
		
		//返回ID
		foreach($query->result_array() as $data)
		{
			$array[] = $data['delegate'];
		}
		$query->free_result();
		
		return $array;
	}
	
	/**
	 * 显示当前面试分数平均分布
	 * @param int $min 最小样本
	 * @return array|false 数据分布数组，如达不到最小样本返回FALSE
	 */
	function get_score_levels($min = 20)
	{
		$this->db->where('score IS NOT NULL', NULL, false);
		$this->db->select('score');
		$this->db->order_by('score', 'desc');
		$query = $this->db->get('interview');
		
		//如果达不到最小样本
		$count = $query->num_rows();
		if($count < $min)
			return false;
		
		//生成分数
		$scores = array();
		foreach($query->result_array() as $row)
		{
			$scores[] = $row['score'];
		}
		
		//最低分数
		$scores[] = 0;
		
		//生成分布数组
		$level = array();
		foreach(array(1, 5, 10, 15, 20, 30, 40, 50, 60, 70, 80, 90, 100) as $i)
		{
			$point = round($count / 100 * $i);
			$level[$i] = $scores[$point];
		}
		
		//返回分布数组
		return $level;
	}
	
	/**
	 * 转换状态为文本
	 * @param string|int $status 状态或面试ID
	 * @return string 状态文本
	 */
	function status_text($status)
	{
		//如果为代表ID
		if(is_int($status) || intval($status) != 0)
			$status = $this->get_interview($status, 'status');
		
		switch($status)
		{
			case 'assigned':
				return '等待安排时间';
			case 'arranged':
				return '已经安排时间';
			case 'completed':
				return '面试通过';
			case 'exempted':
				return '免试通过';
			case 'cancelled':
				return '面试取消';
			case 'failed':
				return '面试未通过';
		}
		return false;
	}
	
	/**
	 * 编辑/添加面试
	 * @return int 新的面试ID
	 */
	function edit_interview($data, $id = '')
	{
		//新增面试
		if(empty($id))
		{
			$this->db->insert('interview', $data);
			return $this->db->insert_id();
		}
		
		//更新面试
		if(isset($data['feedback']) && !empty($data['feedback']))
		{
			$data['feedback'] = json_encode($data['feedback']);
		}
		$this->db->where('id', $id);
		return $this->db->update('interview', $data);
	}
	
	/**
	 * 分配面试
	 * @return int 新的面试ID
	 */
	function assign_interview($delegate, $interviewer, $exempt = false)
	{
		$data = array(
			'delegate' => $delegate,
			'interviewer' => $interviewer,
			'status' => 'assigned',
			'assign_time' => time()
		);
		
		//是否为免试通过
		if($exempt)
			$data['status'] = 'exempted';
		
		//返回新面试ID
		return $this->edit_interview($data);
	}
	
	/**
	 * 安排/重新安排时间
	 */
	function arrange_time($id, $time)
	{
		$data = array(
			'schedule_time' => $time,
			'status' => 'arranged',
		);
		return $this->edit_interview($data, $id);
	}
	
	/**
	 * 取消面试
	 */
	function cancel_interview($id)
	{
		$data = array(
			'finish_time' => time(),
			'status' => 'cancelled',
		);
		return $this->edit_interview($data, $id);
	}
	
	/**
	 * 完成面试并反馈信息
	 * @param float $score 分数
	 * @param boolean $pass 是否通过
	 * @param array $feedback 反馈信息
	 */
	function complete_interview($id, $score, $pass = true, $feedback = array())
	{
		$data = array(
			'score' => $score,
			'feedback' => $feedback,
			'finish_time' => time(),
			'status' => $pass ? 'completed': 'failed'
		);
		return $this->edit_interview($data, $id);
	}
	
	/**
	 * 查询面试是否为二次面试或查询代表是否有二次面试
	 */
	function is_secondary($id, $type = 'interview')
	{
		$delegate = $id;
		if($type == 'interview')
		{
			$interview = $this->get_interview($id);
			if(!$interview)
				return false;
			
			$time = $interview['assign_time'];
			
			$delegate = $interview['delegate'];
		}
		else
		{
			$time = time();
		}
		
		if($this->get_interview_id('delegate', $delegate, 'status', 'failed', 'finish_time <', $time))
			return true;
		return false;
	}
}

/* End of file interview_model.php */
/* Location: ./application/models/interview_model.php */