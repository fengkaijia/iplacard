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
	 * 编辑或添加席位
	 */
	function edit($id = '')
	{
		//检查权限
		if(!$this->admin_model->capable('administrator'))
		{
			$this->ui->alert('需要管理员权限以编辑席位。', 'warning', true);
			redirect('seat/manage');
			return;
		}
		
		//设定操作类型
		$action = 'edit';
		if(empty($id))
			$action = 'add';
		
		if($action == 'edit')
		{
			$seat = $this->seat_model->get_seat($id);
			if(!$seat)
				$action = 'add';
			
			//席位类型
			if(!empty($seat['primary']))
				$seat['type'] = 'sub';
			else
			{
				if($this->seat_model->is_primary_seat($seat['id']))
					$seat['type'] = 'primary';
				
				if($this->seat_model->is_single_seat($seat['id']))
					$seat['type'] = 'single';
			}
		}
		
		//委员会信息
		$committees = array();
		
		$committee_ids = $this->committee_model->get_committee_ids();
		foreach($committee_ids as $committee_id)
		{
			$committee = $this->committee_model->get_committee($committee_id);
			$committees[$committee_id] = "{$committee['name']}（{$committee['abbr']}）";
		}
		
		$vars['committees'] = $committees;
		
		//全部国家列表
		$this->load->config('iso');
		$iso = $this->config->item('iso_3166_1');
		$vars['iso'] = $iso;
		
		//全部可供选择主席位
		$seats = array();
		
		$primary_seats = $this->seat_model->get_seat_ids('primary', NULL);
		if($primary_seats)
		{
			foreach($primary_seats as $seat_id)
			{
				$primary_seat = $this->seat_model->get_seat($seat_id);
				
				$seats[$primary_seat['committee']][] = array(
					'id' => $seat_id,
					'name' => $primary_seat['name'],
					'iso' => $primary_seat['iso']
				);
			}
		}
		$vars['seats'] = $seats;
		
		if($action == 'edit')
		{
			$vars['seat'] = $seat;
			
			$this->ui->title($seat['name'], '编辑席位');
		}
		else
		{
			$this->ui->title('添加席位');
		}
		
		$this->form_validation->set_error_delimiters('<div class="help-block">', '</div>');
		
		$this->form_validation->set_rules('seat_type', '席位类型', 'trim|required');
		
		$seat_type = $this->input->post('seat_type');
		if($seat_type != 'sub')
		{
			$this->form_validation->set_rules('name', '席位名称', 'trim|required');
			$this->form_validation->set_rules('committee', '委员会', 'required');
		}
		else
		{
			$this->form_validation->set_rules('primary', '主席位', 'callback__check_primary_seat');
			$this->form_validation->set_message('_check_primary_seat', '主席位不可选。');
		}
		
		if($this->form_validation->run() == true)
		{
			$post = $this->input->post();
			
			if($action == 'add')
			{
				if($seat_type == 'sub')
				{
					$id = $this->seat_model->add_attached_seat(
						$post['primary'],
						empty($post['name']) ? NULL : $post['name'],
						empty($post['level']) ? NULL : $post['level'],
						empty($post['iso']) ? NULL : $post['iso']
					);
					
					$this->ui->alert("已经创建子席位 #{$id}。", 'success', true);

					$this->system_model->log('seat_added', array('id' => $id));
				}
				else
				{
					$id = $this->seat_model->add_seat($post['committee'], $post['name'], $post['level'], $post['iso']);
					
					$new_sub_ids = array();
					if($seat_type == 'primary' && intval($post['sub_num']) > 0)
					{
						for($i = 0; $i < $post['sub_num']; $i++)
						{
							$new_sub_ids = $this->seat_model->add_attached_seat($id);
						}
					}
					
					$this->ui->alert("已经创建席位 #{$id}。", 'success', true);

					$this->system_model->log('seat_added', array('id' => $id, 'sub' => $new_sub_ids));
				}
			}
			else
			{
				//新数据
				$data = array();
				foreach(array('name', 'level', 'committee', 'iso') as $item)
				{
					if($post[$item] != $seat[$item])
						$data[$item] = $post[$item];
				}
				
				if($seat_type == 'sub' && !empty($post['primary']))
					$data['primary'] = $post['primary'];
				else
					$data['primary'] = NULL;
				
				$this->seat_model->edit_seat($data, $id);

				$this->ui->alert("席位 #{$id} 已编辑。", 'success', true);

				$this->system_model->log('seat_edited', array('id' => $id, 'data' => $data));
			}
			
			redirect('seat/manage');
			return;
		}
		
		$vars['action'] = $action;
		$this->load->view('admin/seat_edit', $vars);
	}
	
	/**
	 * 席位操作
	 */
	function operation($action, $id)
	{
		if(empty($id))
			return;
		
		$admin = $this->admin_model->get_admin(uid());
		
		$seat = $this->seat_model->get_seat($id);
		if(!$seat)
			return;
		
		switch($action)
		{
			//保留席位
			case 'preserve_seat':
				if($seat['status'] != 'available' && $seat['status'] != 'preserved')
				{
					$this->ui->alert('席位当前状态无法更改保留设置。', 'warning', true);
					break;
				}
				
				if($seat['status'] == 'preserved')
				{
					$this->ui->alert('席位已经被设置保留，无需更改。', 'info', true);
					break;
				}
				
				if($seat['committee'] != $admin['committee'] && !$this->admin_model->capable('administrator'))
				{
					$this->ui->alert('您不是此席位所在委员会的面试官或主席，因此您无法为调整此席位的属性。', 'warning', true);
					break;
				}
				
				$this->seat_model->change_seat_status($id, 'preserved');
				
				$this->system_model->log('seat_preserved', array('seat' => $id));
				
				$this->ui->alert('席位已设置保留。', 'success', true);
				break;
				
			//开放席位
			case 'open_seat':
				if($seat['status'] != 'available' && $seat['status'] != 'preserved')
				{
					$this->ui->alert('席位当前状态无法更改保留设置。', 'warning', true);
					break;
				}
				
				if($seat['status'] == 'available')
				{
					$this->ui->alert('席位已经被设置开放，无需更改。', 'info', true);
					break;
				}
				
				if($seat['committee'] != $admin['committee'] && !$this->admin_model->capable('administrator'))
				{
					$this->ui->alert('您不是此席位所在委员会的面试官或主席，因此您无法为调整此席位的属性。', 'warning', true);
					break;
				}
				
				$this->seat_model->change_seat_status($id, 'available');
				
				$this->system_model->log('seat_opened', array('seat' => $id));
				
				$this->ui->alert('席位已设置保留。', 'success', true);
				break;
				
			//删除席位
			case 'delete_seat':
				if(!in_array($seat['status'], array('unavailable', 'available', 'preserved')))
				{
					$this->ui->alert('席位当前状态不允许删除。', 'danger', true);
					break;
				}
				
				if(empty($seat['primary']) && !$this->seat_model->is_single_seat($id))
				{
					$this->ui->alert('需要删除的席位包含子席位。', 'danger', true);
					break;
				}
				
				if(!$this->admin_model->capable('administrator'))
				{
					$this->ui->alert('需要管理员权限以删除席位。', 'danger', true);
					break;
				}
				
				$this->form_validation->set_rules('admin_password', '密码', 'trim|required|callback__check_admin_password[密码验证错误导致删除操作未执行，请重新尝试。]');
		
				if($this->form_validation->run() == true)
				{
					$this->seat_model->delete_seat($id);
					
					$this->system_model->log('seat_deleted', array('id' => $id, 'seat' => $seat));
					
					$this->ui->alert("席位 #{$id} 已删除。", 'success', true);
					redirect('seat/manage');
					return;
				}
				break;
		}
		
		back_redirect();
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
	 * 主席位检查回调函数
	 */
	function _check_primary_seat($primary = '')
	{
		if(empty($primary))
			return true;
		
		if($this->seat_model->is_attached_seat($primary))
			return false;
		return true;
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
				if($param_committee == 'u')
				{
					$param_committee = $this->admin_model->get_admin(uid(), 'committee');
					if(!$param_committee)
						$param_committee = 0;
				}
				
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