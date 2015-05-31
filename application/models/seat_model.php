<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 席位模块
 * @package iPlacard
 * @since 2.0
 */
class Seat_model extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * 获取席位信息
	 * @param int $id 席位ID
	 * @param string $part 指定部分
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_seat($id, $part = '')
	{
		$this->db->where('id', $id);
		$query = $this->db->get('seat');
		
		//如果无结果
		if($query->num_rows() == 0)
			return false;
		
		$data = $query->row_array();
		
		//子席位
		if(!empty($data['primary']))
		{
			//TODO: 递归死循环
			$primary = $this->get_seat($data['primary']);
			
			foreach($data as $key => $value)
			{
				if(empty($value) && !in_array($key, array('delegate', 'status', 'time')))
					$data[$key] = $primary[$key];
			}
		}
		
		//返回结果
		if(empty($part))
			return $data;
		return $data[$part];
	}
	
	/**
	 * 批量获取席位信息
	 * @param int $ids 席位IDs
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_seats($ids)
	{
		$this->db->where_in('id', $ids);
		$query = $this->db->get('seat');
		
		//如果无结果
		if($query->num_rows() == 0)
			return false;
		
		$return = array();
		
		foreach($query->result_array() as $data)
		{
			//子席位
			if(!empty($data['primary']))
			{
				//TODO: 递归死循环
				$primary = $this->get_seat($data['primary']);

				foreach($data as $key => $value)
				{
					if(empty($value) && !in_array($key, array('delegate', 'status', 'time')))
						$data[$key] = $primary[$key];
				}
			}
			
			$return[$data['id']] = $data;
		}
		$query->free_result();
		
		//返回结果
		return $return;
	}
	
	/**
	 * 查询符合条件的第一个席位ID
	 * @return int|false 符合查询条件的第一个席位ID，如不存在返回FALSE
	 */
	function get_seat_id()
	{
		$args = func_get_args();
		array_unshift($args, 'seat');
		//将参数传递给get_id方法
		return call_user_func_array(array($this->sql_model, 'get_id'), $args);
	}
	
	/**
	 * 查询符合条件的所有席位ID
	 * @return array|false 符合查询条件的所有席位ID，如不存在返回FALSE
	 */
	function get_seat_ids()
	{
		$args = func_get_args();
		array_unshift($args, 'seat');
		//将参数传递给get_ids方法
		return call_user_func_array(array($this->sql_model, 'get_ids'), $args);
	}
	
	/**
	 * 获取指定席位的全部子席位
	 * @param boolean $include_primary 是否包含主席位
	 */
	function get_attached_seat_ids($id, $include_primary = true)
	{
		$attach = $this->get_seat_ids('primary', $id);
		
		//无子席位且不含主席位
		if(!$attach && !$include_primary)
			return false;
		
		//无子席位且包含主席位
		if(!$attach && $include_primary)
			return array($id);
		
		//有子席位且不含主席位
		if(!$include_primary)
			return $attach;
		
		//有席位且包含主席位
		return array_merge(array($id), $attach);
	}
	
	/**
	 * 根据一组席位ID获取对应代表ID
	 * @param int|array $ids 一个或一组席位ID
	 */
	function get_delegates_by_seats($ids)
	{
		//仅单个席位ID
		if(is_int($ids) || is_string($ids))
			$ids = array($ids);
		
		$this->db->where_in('id', $ids);
		
		$query = $this->db->get('seat');
		
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
	 * 获取代表选定席位
	 */
	function get_delegate_seat($delegate)
	{
		return $this->get_seat_id('delegate', $delegate);
	}
	
	/**
	 * 转换状态为文本
	 * @param string|int $status 状态或席位ID
	 * @return string 状态文本
	 */
	function status_text($status)
	{
		//如果为代表ID
		if(is_int($status) || intval($status) != 0)
			$status = $this->get_seat($status, 'status');
		
		switch($status)
		{
			case 'unavailable':
				return '不可分配';
			case 'available':
				return '等待分配';
			case 'preserved':
				return '保留分配';
			case 'assigned':
				return '已经分配';
			case 'approved':
				return '已获批准';
			case 'locked':
				return '已经锁定';
		}
		return false;
	}
	
	/**
	 * 编辑/添加席位
	 * @return int 新的席位ID
	 */
	function edit_seat($data, $id = '')
	{
		//新增席位
		if(empty($id))
		{
			$this->db->insert('seat', $data);
			return $this->db->insert_id();
		}
		
		//更新席位
		$this->db->where('id', $id);
		return $this->db->update('seat', $data);
	}
	
	/**
	 * 添加席位
	 * @return int 新的席位ID
	 */
	function add_seat($committee, $name, $level, $iso = '')
	{
		$data = array(
			'committee' => $committee,
			'name' => $name,
			'level' => $level,
			'status' => 'available'
		);
		if(!empty($iso))
			$data['iso'] = $iso;
		
		//返回新席位ID
		return $this->edit_seat($data);
	}
	
	/**
	 * 添加子席位
	 * @return int 新的席位ID
	 */
	function add_attached_seat($id, $name = '', $level = '', $iso = '')
	{
		if(!$this->is_primary_seat($id))
			return false;
		
		$primary = $this->get_seat($id);
		
		$data = array(
			'status' => 'available',
			'committee' => $primary['committee'],
			'primary' => $id
		);
		
		//席位名称
		if(!empty($name))
			$data['name'] = $name;
		
		//席位级别
		if(!empty($level))
			$data['level'] = $level;
		
		//国家代码
		if(!empty($iso))
			$data['iso'] = $iso;
		
		//返回新席位ID
		return $this->edit_seat($data);
	}
	
	/**
	 * 删除席位
	 * @param int $id 席位ID
	 * @return boolean 是否完成删除
	 */
	function delete_seat($id)
	{
		$this->db->where('id', $id);
		return $this->db->delete('seat');
	}
	
	/**
	 * 更改席位分配状态
	 */
	function change_seat_status($id, $status, $change_time = false)
	{
		$available = array(
			'unavailable',
			'available',
			'preserved',
			'assigned',
			'approved', //分配兼容模式
			'locked'
		);
		
		if(!in_array($status, $available))
			return false;
		
		$data = array('status' => $status);
		if($change_time !== false)
		{
			if(is_null($change_time))
				$data['time'] = NULL;
			elseif($change_time != true)
				$data['time'] = $change_time;
			else
				$data['time'] = time();
		}
		
		return $this->edit_seat($data, $id);
	}
	
	/**
	 * 分配席位
	 * 此方法仅修改`delegate`属性
	 */
	function assign_seat($id, $delegate)
	{
		$data = array(
			'delegate' => $delegate
		);
		
		return $this->edit_seat($data, $id);
	}
	
	/**
	 * 检查席位是否可分配
	 * @param int $committee 对保留席位检查委员会
	 */
	function is_seat_available($id, $committee = '')
	{
		$seat = $this->get_seat($id);
		
		//席位可用
		if($seat['status'] == 'available')
			return true;
		
		//席位保留
		if($seat['status'] == 'preserved' && !empty($committee) && $seat['committee'] == $committee)
			return true;
		
		return false;
	}
	
	/**
	 * 检查席位是否是主席位
	 */
	function is_primary_seat($id)
	{
		$primary = $this->get_seat($id, 'primary');
		
		if(empty($primary))
			return true;
		return false;
	}
	
	/**
	 * 检查席位是否是子席位
	 */
	function is_attached_seat($id)
	{
		$primary = $this->get_seat($id, 'primary');
		
		if(!empty($primary))
			return true;
		return false;
	}
	
	/**
	 * 是否为单代表席位
	 */
	function is_single_seat($id)
	{
		//是主席位且不存在子席位
		if($this->is_primary_seat($id) && !$this->get_seat_ids('primary', $id))
			return true;
		return false;
	}
	
	/**
	 * 获取席位延期请求信息
	 * @param int $id 延期请求ID
	 * @param string $part 指定部分
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_backorder($id, $part = '')
	{
		$this->db->where('id', $id);
		$query = $this->db->get('seat_backorder');
		
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
	 * 批量席位延期请求信息
	 * @param int $ids 延期请求IDs
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_backorders($ids)
	{
		$this->db->where_in('id', $ids);
		$query = $this->db->get('seat_backorder');
		
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
	 * 查询符合条件的第一个延期请求ID
	 * @return int|false 符合查询条件的第一个延期请求ID，如不存在返回FALSE
	 */
	function get_backorder_id()
	{
		$args = func_get_args();
		array_unshift($args, 'seat_backorder');
		//将参数传递给get_id方法
		return call_user_func_array(array($this->sql_model, 'get_id'), $args);
	}
	
	/**
	 * 查询符合条件的所有延期请求ID
	 * @return array|false 符合查询条件的所有延期请求ID，如不存在返回FALSE
	 */
	function get_backorder_ids()
	{
		$args = func_get_args();
		array_unshift($args, 'seat_backorder');
		//将参数传递给get_ids方法
		return call_user_func_array(array($this->sql_model, 'get_ids'), $args);
	}
	
	/**
	 * 根据一组延期请求ID获取对应席位ID
	 * @param int|array $ids 一个或一组延期请求ID
	 */
	function get_seats_by_backorders($ids)
	{
		//仅单个延期请求ID
		if(is_int($ids) || is_string($ids))
			$ids = array($ids);
		
		$this->db->where_in('id', $ids);
		
		$query = $this->db->get('seat_backorder');
		
		//如果无结果
		if($query->num_rows() == 0)
			return false;
		
		//返回ID
		foreach($query->result_array() as $data)
		{
			$array[] = $data['seat'];
		}
		$query->free_result();
		
		return $array;
	}
	
	/**
	 * 获取指定席位的所有延期请求
	 */
	function get_seat_backorders($seat, $only_valid = true)
	{
		if(!$only_valid)
			return $this->get_backorder_ids('seat', $seat);
		
		$non_forever = $this->get_backorder_ids('seat', $seat, 'status', 'pending', 'expire_time >=', time());
		if(!$non_forever)
			$non_forever = array();
		
		$forever = $this->get_backorder_ids('seat', $seat, 'status', 'pending', 'expire_time', 0);
		if(!$forever)
			$forever = array();
		
		$all = array_merge($forever, $non_forever);
		return empty($all) ? false : $all;
	}
	
	/**
	 * 获取指定代表的所有候补席位
	 */
	function get_delegate_backorder($delegate, $only_valid = true)
	{
		if(!$only_valid)
			return $this->get_backorder_ids('delegate', $delegate);
		
		$non_forever = $this->get_backorder_ids('delegate', $delegate, 'status', 'pending', 'expire_time >=', time());
		if(!$non_forever)
			$non_forever = array();
		
		$forever = $this->get_backorder_ids('delegate', $delegate, 'status', 'pending', 'expire_time', 0);
		if(!$forever)
			$forever = array();
		
		$all = array_merge($forever, $non_forever);
		return empty($all) ? false : $all;
	}
	
	/**
	 * 编辑/添加席位延期请求
	 * @return int 新的延期请求ID
	 */
	function edit_backorder($data, $id = '')
	{
		//新增延期请求
		if(empty($id))
		{
			$this->db->insert('seat_backorder', $data);
			return $this->db->insert_id();
		}
		
		//更新延期请求
		$this->db->where('id', $id);
		return $this->db->update('seat_backorder', $data);
	}
	
	/**
	 * 添加席位延期请求
	 * @return int 新的延期请求ID
	 */
	function add_backorder($seat, $delegate, $expire = false)
	{
		$data = array(
			'seat' => $seat,
			'delegate' => $delegate,
			'order_time' => time(),
			'expire_time' => 0,
			'status' => 'pending'
		);
		
		if($expire)
			$data['expire_time'] = $expire;
		
		//返回新延期请求ID
		return $this->edit_backorder($data);
	}
	
	/**
	 * 删除席位延期请求
	 * @param int|array $id 一个或一组延期请求ID
	 * @return boolean 是否完成删除
	 */
	function delete_backorder($id)
	{
		if(is_int($id) || is_string($id))
			$id = array($id);
		
		$this->db->where_in('id', $id);
		return $this->db->delete('seat_backorder');
	}
	
	/**
	 * 更改席位延期请求状态
	 */
	function change_backorder_status($id, $status)
	{
		$available = array(
			'pending',
			'accepted',
			'expired',
			'cancelled'
		);
		
		if(!in_array($status, $available))
			return false;
		
		return $this->edit_backorder(array('status' => $status), $id);
	}
	
	/**
	 * 检查席位是否有延期请求
	 */
	function is_backordered($id)
	{
		if(!$this->get_backorder_ids('seat', $id, 'status', 'pending', 'expire_time >=', time()))
			if(!$this->get_backorder_ids('seat', $id, 'status', 'pending', 'expire_time', 0))
				return false;
		return true;
	}
	
	/**
	 * 检查席位延期请求是否有效
	 */
	function is_backorder_valid($id)
	{
		$backorder = $this->get_backorder($id);
		
		//延期请求不存在
		if(!$backorder)
			return false;
		
		//延期请求过期
		if($backorder['expire_time'] < time() && $backorder['expire_time'] > 0)
			return false;
		
		//延期请求状态不可接受
		if($backorder['status'] != 'pending')
			return false;
		
		return true;
	}
	
	/**
	 * 获取席位许可信息
	 * @param int $id 许可ID
	 * @param string $part 指定部分
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_selectability($id, $part = '')
	{
		$this->db->where('id', $id);
		$query = $this->db->get('seat_selectability');
		
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
	 * 批量席位许可信息
	 * @param int $ids 许可IDs
	 * @return array|string|boolean 信息，如不存在返回FALSE
	 */
	function get_selectabilities($ids)
	{
		$this->db->where_in('id', $ids);
		$query = $this->db->get('seat_selectability');
		
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
	 * 查询符合条件的第一个席位许可ID
	 * @return int|false 符合查询条件的第一个席位许可ID，如不存在返回FALSE
	 */
	function get_selectability_id()
	{
		$args = func_get_args();
		array_unshift($args, 'seat_selectability');
		//将参数传递给get_id方法
		return call_user_func_array(array($this->sql_model, 'get_id'), $args);
	}
	
	/**
	 * 查询符合条件的所有席位许可ID
	 * @return array|false 符合查询条件的所有席位许可ID，如不存在返回FALSE
	 */
	function get_selectability_ids()
	{
		$args = func_get_args();
		array_unshift($args, 'seat_selectability');
		//将参数传递给get_ids方法
		return call_user_func_array(array($this->sql_model, 'get_ids'), $args);
	}
	
	/**
	 * 根据一组席位许可ID获取对应席位ID
	 * @param int|array $ids 一个或一组席位许可ID
	 */
	function get_seats_by_selectabilities($ids)
	{
		//仅单个席位许可ID
		if(is_int($ids) || is_string($ids))
			$ids = array($ids);
		
		$this->db->where_in('id', $ids);
		
		$query = $this->db->get('seat_selectability');
		
		//如果无结果
		if($query->num_rows() == 0)
			return false;
		
		//返回ID
		foreach($query->result_array() as $data)
		{
			$array[] = $data['seat'];
		}
		$query->free_result();
		
		return $array;
	}
	
	/**
	 * 获取指定代表的所有席位许可
	 */
	function get_delegate_selectability($delegate, $only_primary = false, $only_recommended = false, $return = 'selectability')
	{
		$this->db->where('delegate', $delegate);
		if($only_primary)
			$this->db->where('primary', true);
		if($only_recommended)
			$this->db->where('recommended', true);
		
		$query = $this->db->get('seat_selectability');
		
		//如果无结果
		if($query->num_rows() == 0)
			return false;
		
		//返回许可或席位ID
		foreach($query->result_array() as $data)
		{
			$array[] = $data[$return == 'selectability' ? 'id' : 'seat'];
		}
		$query->free_result();
		
		return $array;
	}
	
	/**
	 * 编辑席位许可
	 */
	function edit_selectability($data, $id = '')
	{
		$this->db->where('id', $id);
		return $this->db->update('seat_selectability', $data);
	}
	
	/**
	 * 授权许可
	 * @param int|array $seats 一个或一组席位ID
	 * @param type $delegate 代表ID
	 * @param type $admin 授权管理员ID
	 * @param type $primary 是否授权主许可
	 * @param type $recommended 是否推荐
	 * @return int 第一个新许可ID
	 */
	function grant_selectability($seats, $delegate, $admin, $primary = true, $recommended = false)
	{
		if(is_int($seats) || is_string($seats))
			$seats = array($seats);
		
		$data = array();
		
		foreach($seats as $seat)
		{
			$insert = array(
				'seat' => $seat,
				'delegate' => $delegate,
				'admin' => $admin,
				'primary' => $primary,
				'recommended' => $recommended,
				'time' => time()
			);
			
			$data[] = $insert;
		}
		
		//批量插入
		$this->db->insert_batch('seat_selectability', $data);
		return $this->db->insert_id();
	}
	
	/**
	 * 取消授权许可
	 * @param int|array $id 一个或一组许可ID
	 */
	function remove_selectability($id)
	{
		if(is_int($id) || is_string($id))
			$id = array($id);
		
		$this->db->where_in('id', $id);
		return $this->db->delete('seat_selectability');
	}
	
	/**
	 * 检查席位许可是否是主许可
	 */
	function is_primary_selectability($id)
	{
		return $this->get_selectability($id, 'primary') ? true : false;
	}
	
	/**
	 * 检查席位许可是否为推荐
	 */
	function is_recommended_selectability($id)
	{
		return $this->get_selectability($id, 'recommended') ? true : false;
	}
}

/* End of file seat_model.php */
/* Location: ./application/models/seat_model.php */