<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 席位管理控制器
 * @package iPlacard
 * @since 2.0
 */
class Seat extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->library('form_validation');
		$this->load->library('ui', array('side' => 'admin'));
		$this->load->model('admin_model');
		$this->load->model('delegate_model');
		$this->load->model('committee_model');
		$this->load->model('seat_model');
		$this->load->helper('form');
		
		//检查登录情况
		if(!is_logged_in())
		{
			login_redirect();
			return;
		}
		
		//检查权限
		if(!$this->user_model->is_admin(uid()) || (!$this->admin_model->capable('administrator') && !$this->admin_model->capable('interviewer') && !$this->admin_model->capable('dais')))
		{
			redirect('');
			return;
		}
		
		$this->ui->now('seat');
	}
	
	/**
	 * 跳转页
	 */
	function manage()
	{
		//查询过滤
		$post = $this->input->get();
		$param = $this->_filter_check($post);
		
		//显示标题
		$title = '全部席位列表';
		
		if(isset($param['status']))
		{
			$text_status = array();
			foreach($param['status'] as $one)
			{
				$text_status[] = $this->seat_model->status_text($one);
			}
			$title = sprintf("%s席位列表", join('、', $text_status));
			
			if($param['status'] == array('unavailable', 'available', 'preserved'))
				$part = 'available';
			elseif($param['status'] == array('assigned', 'approved', 'locked'))
				$part = 'assigned';
			else
				$part = '';
		}
		else
		{
			$part = 'all';
		}
		
		if(isset($param['group']))
		{
			$this->load->model('group_model');
			
			$text_group = array();
			foreach($param['group'] as $one)
			{
				$text_group[] = $this->group_model->get_group($one, 'name');
			}
			$title = sprintf("%s代表团成员列表", join('、', $text_group));
		}
		
		if(isset($param['interviewer']))
		{
			$text_group = array();
			foreach($param['interviewer'] as $one)
			{
				if($one == uid())
					$text_interviewer[] = '我';
				else
					$text_interviewer[] = $this->admin_model->get_admin($one, 'name');
			}
			$title = sprintf("%s面试代表的席位队列", join('、', $text_interviewer));
		}
		
		if(isset($param['committee']))
		{
			$text_committee = array();
			foreach($param['committee'] as $one)
			{
				$text_committee[] = $this->committee_model->get_committee($one, 'name');
			}
			$title = sprintf("%s委员会席位列表", join('、', $text_committee));
		}
		
		//标签地址
		$params = $param;
		
		$params['status'] = array('unavailable', 'available', 'preserved');
		$param_tab['available'] = $this->_filter_build($params);
		
		$params['status'] = array('assigned', 'approved', 'locked');
		$param_tab['assigned'] = $this->_filter_build($params);
		
		unset($params['status']);
		$param_tab['all'] = $this->_filter_build($params);
		
		$vars = array(
			'param_uri' => $this->_filter_build($param),
			'param_tab' => $param_tab,
			'part' => $part,
			'title' => $title,
		);
		
		$this->ui->title($title, '席位列表');
		$this->load->view('admin/seat_manage', $vars);
	}

	/**
	 * AJAX
	 */
	function ajax($action = 'list')
	{
		$json = array();
		
		if($action == 'list')
		{
			$param = $this->_filter_check($this->input->get());
			$input_param = array();
			
			//席位状态
			if(isset($param['status']))
				$input_param['status'] = $param['status'];
			
			//代表团
			if(isset($param['group']))
			{
				$gids = $this->delegate_model->get_group_delegates($param['group']);
				if($gids)
					$input_param['delegate'] = $gids;
				else
					$input_param['delegate'] = array(NULL);
			}
			
			//面试官
			if(isset($param['interviewer']))
				$input_param['interviewer'] = $param['interviewer'];
			
			//委员会
			if(isset($param['committee']))
				$input_param['committee'] = $param['committee'];
			
			$args = array();
			if(!empty($input_param))
			{
				foreach($input_param as $item => $value)
				{
					$args[] = $item;
					$args[] = $value;
				}
			}
			$ids = call_user_func_array(array($this->seat_model, 'get_seat_ids'), $args);
			
			$admin_committee = $this->admin_model->get_admin(uid(), 'committee');
			
			if($ids)
			{
				foreach($ids as $id)
				{
					$seat = $this->seat_model->get_seat($id);
					
					$delegate = false;
					if(!empty($seat['delegate']))
						$delegate = $this->delegate_model->get_delegate($seat['delegate']);

					//操作
					$operation = '';
					if($this->admin_model->capable('administrator'))
						$operation .= anchor("seat/edit/$id", icon('edit', false).'编辑').' ';
					if(in_array($seat['status'], array('available', 'preserved')) && ($seat['committee'] == $admin_committee || $this->admin_model->capable('administrator')))
					{
						if($seat['status'] == 'preserved')
							$operation .= '<a href="#" data-toggle="modal" data-target="#open_seat" onclick="set_seat_box('.$seat['id'].', \'open\');">'.icon('eye', false).'开放</a>';
						else
							$operation .= '<a href="#" data-toggle="modal" data-target="#preserve_seat" onclick="set_seat_box('.$seat['id'].', \'preserve\');">'.icon('eye-slash', false).'保留</a>';
					}
					
					//席位名称
					$name_line = flag($seat['iso'], true).$seat['name'];
					if(!empty($seat['primary']))
						$name_line .= ' <span class="label label-primary">子席位</span>';
					elseif(!$this->seat_model->is_single_seat($id))
						$name_line .= ' <span class="label label-primary">多代席位</span>';
					
					//代表
					$delegate_line = '';
					if($delegate)
					{
						$contact_list = '<p>'.icon('phone').$delegate['phone'].'</p><p>'.icon('envelope-o').$delegate['email'].'</p><p>'.icon('male').'ID '.$delegate['id'].'</p>';
						$delegate_line = $delegate['name'].'<a href="#" class="contact_list" data-html="1" data-placement="right" data-trigger="click" data-original-title=\''
							.$delegate['name']
							.'\' data-toggle="popover" data-content=\''.$contact_list.'\'>'.icon('info-circle', false).'</a>';
					}
					
					//席位状态
					$status_text = $this->seat_model->status_text($seat['status']);
					switch($seat['status'])
					{
						case 'unavailable':
							$status_class = 'default';
							break;
						case 'available':
						case 'assigned':
							$status_class = 'primary';
							break;
						case 'preserved':
							$status_class = 'info';
							break;
						case 'approved':
						case 'locked':
							$status_class = 'success';
							break;
						default:
							$status_class = 'primary';
					}
					$status_line = "<span class='label label-{$status_class}'>{$status_text}</span>";
					
					//可分配情况
					$condition_line = '';
					
					$selectability = $this->seat_model->get_selectability_ids('seat', $seat['id']);
					if($selectability)
						$condition_line .= '<span class="label label-primary">可选</span> '.count($selectability);
					
					$backorder = $this->seat_model->get_seat_backorders($seat['id']);
					if($backorder)
						$condition_line .= '<span class="label label-success">预约</span> '.count($backorder);
					
					$data = array(
						$seat['id'], //ID
						$name_line, //席位名称
						$this->committee_model->get_committee($seat['committee'], 'abbr'), //委员会
						$status_line, //席位状态
						$seat['level'], //席位等级
						!empty($seat['delegate']) ? $delegate_line : 'N/A', //代表
						$condition_line, //可分配情况
						$operation, //操作
					);
					
					$datum[] = $data;

					$json = array('aaData' => $datum);
				}
			}
			else
			{
				$json = array('aaData' => array());
			}
			
			echo json_encode($json);
		}
	}
	
	/**
	 * 密码检查回调函数
	 */
	function _check_admin_password($str, $global_message = '')
	{
		if($this->user_model->check_password(uid(), $str))
			return true;
		
		//全局消息
		if(!empty($global_message))
			$this->ui->alert($global_message, 'warning', true);
		
		return false;
	}
	
	/**
	 * 查询过滤
	 */
	function _filter_check($post, $return_uri = false)
	{
		$return = array();
		
		//申请状态
		if(isset($post['status']))
		{
			$status = array();
			foreach(explode(',', $post['status']) as $param_status)
			{
				if(in_array($param_status, array('unavailable', 'available', 'preserved', 'assigned', 'approved', 'locked')))
					$status[] = $param_status;
			}
			if(!empty($status))
				$return['status'] = $status;
		}
		
		//代表团
		if(isset($post['group']))
		{
			$this->load->model('group_model');
			
			$group = array();
			foreach(explode(',', $post['group']) as $param_group)
			{
				if(in_array($param_group, $this->group_model->get_group_ids()))
					$group[] = $param_group;
			}
			if(!empty($group))
				$return['group'] = $group;
		}
		
		//委员会
		if(isset($post['committee']))
		{
			$committee = array();
			foreach(explode(',', $post['committee']) as $param_committee)
			{
				if(in_array($param_committee, $this->committee_model->get_committee_ids()))
					$committee[] = $param_committee;
			}
			if(!empty($committee))
				$return['committee'] = $committee;
		}
		
		//面试官
		if(isset($post['interviewer']))
		{
			$interviewer = array();
			foreach(explode(',', $post['interviewer']) as $param_interviewer)
			{
				if($param_interviewer == 'u')
					$param_interviewer = uid();
				
				if($this->admin_model->capable('interviewer', $param_interviewer))
					$interviewer[] = $param_interviewer;
			}
			if(!empty($interviewer))
				$return['interviewer'] = $interviewer;
		}
		
		if(!$return_uri)
			return $return;
		
		if(empty($return))
			return '';
		
		return $this->_filter_build($return);
	}
	
	/**
	 * 建立查询URI
	 */
	function _filter_build($param)
	{
		foreach($param as $name => $value)
		{
			$param[$name] = join(',', $value);
		}
		return http_build_query($param);
	}
}

/* End of file seat.php */
/* Location: ./application/controllers/seat.php */