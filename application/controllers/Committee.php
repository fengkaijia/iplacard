<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 委员会管理控制器
 * @package iPlacard
 * @since 2.0
 */
class Committee extends CI_Controller
{
	/**
	 * @var array 席位宽度信息
	 */
	private $widths = array(
		1 => '单代席位',
		2 => '双代席位',
		3 => '三代席位',
		4 => '四代席位',
	);
	
	function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->library('form_validation');
		$this->load->library('ui', array('side' => 'admin'));
		$this->load->model('admin_model');
		$this->load->model('committee_model');
		$this->load->helper('form');
		
		//检查登录情况
		if(!is_logged_in())
		{
			login_redirect();
			return;
		}
		
		//检查权限
		if(!$this->user_model->is_admin(uid()))
		{
			redirect('');
			return;
		}
		
		$this->ui->now('committee');
	}
	
	/**
	 * 管理页面
	 */
	function manage()
	{
		$vars = array(
			'show_admin' => $this->admin_model->capable('administrator')
		);
		
		$this->ui->title('委员会列表');
		$this->load->view('admin/committee_manage', $vars);
	}
	
	/**
	 * 编辑或添加委员会
	 */
	function edit($id = '')
	{
		//检查权限
		if(!$this->admin_model->capable('administrator'))
		{
			redirect('');
			return;
		}
		
		//设定操作类型
		$action = 'edit';
		if(empty($id))
			$action = 'add';
		
		$committee = $this->committee_model->get_committee($id);
		if(!$committee)
			$action = 'add';
		
		//设定委员会信息
		$vars['widths'] = $this->widths;
		
		if($action == 'edit')
		{
			$vars['committee'] = $committee;
			
			$this->ui->title($committee['name'], '委员会管理');
		}
		else
		{
			$this->ui->title('添加委员会');
		}
		
		$this->form_validation->set_error_delimiters('<div class="help-block">', '</div>');
		
		$this->form_validation->set_rules('name', '委员会名称', 'trim|required');
		
		if($action == 'add' || $this->input->post('abbr') != $committee['abbr'])
		{
			$this->form_validation->set_rules('abbr', '委员会缩写', 'trim|required|is_unique[committee.abbr]');
		}
		
		if($this->form_validation->run() == true)
		{
			$post = $this->input->post();
			
			//编辑`committee`表数据
			$data = array(
				'name' => $post['name'],
				'abbr' => $post['abbr'],
				'seat_width' => $post['seat_width']
			);
			
			$new_id = $this->committee_model->edit_committee($data, $action == 'add' ? '' : $id);
			
			if($action == 'add')
			{
				$this->ui->alert("已经成功添加新委员会 #{$new_id}。", 'success', true);
				
				$this->system_model->log('committee_added', array('id' => $new_id, 'data' => $data));
			}
			else
			{
				$this->ui->alert('委员会已编辑。', 'success', true);

				$this->system_model->log('committee_edited', array('id' => $id, 'data' => $data));
			}
			
			redirect('committee/manage');
			return;
		}
		
		$vars['action'] = $action;
		$this->load->view('admin/committee_edit', $vars);
	}
	
	/**
	 * 删除委员会
	 */
	function delete($id)
	{
		//检查权限
		if(!$this->admin_model->capable('administrator'))
		{
			redirect('');
			return;
		}
		
		//委员会检查
		$committee = $this->committee_model->get_committee($id);
		if(!$committee)
		{
			$this->ui->alert('指定删除的委员会不存在。', 'warning', true);
			redirect('committee/manage');
			return;
		}
		
		$this->form_validation->set_rules('admin_password', '密码', 'trim|required|callback__check_admin_password[密码验证错误导致删除操作未执行，请重新尝试。]');
		
		if($this->form_validation->run() == true)
		{
			//删除数据
			$this->committee_model->delete_committee($id);
			
			//邮件通知
			$this->load->library('email');
			$this->load->library('parser');
			$this->load->helper('date');

			$email_data = array(
				'uid' => $id,
				'committee' => $committee['name'],
				'time' => unix_to_human(time()),
			);
			
			$admins = $this->admin_model->get_admin_ids('committee', $id);
			if($admins)
			{
				foreach($admins as $admin)
				{
					//重置委员会信息
					$this->admin_model->edit_profile(array('committee' => NULL), $admin);
					
					//通知委员会主席
					$this->email->to($this->user_model->get_user($admin, 'email'));
					$this->email->subject('您的委员会已被删除');
					$this->email->html($this->parser->parse_string(option('email_admin_committee_deleted', "您的委员会{committee}已经于 {time} 被管理员删除，如存在误操作请立即与管理团队取得联系。"), $email_data, true));
					$this->email->send();
				}
			}
			
			//日志
			$this->system_model->log('committee_deleted', array('ip' => $this->input->ip_address(), 'committee' => $id));
			
			$this->ui->alert("委员会 #{$id} 已经成功删除。", 'success', true);
			redirect('committee/manage');
		}
		else
		{
			redirect("committee/edit/{$id}");
		}
	}
	
	/**
	 * AJAX
	 */
	function ajax($action = 'list')
	{
		$json = array();
		
		if($action == 'list')
		{
			$this->load->model('seat_model');
			
			$ids = $this->committee_model->get_committee_ids();
			
			if($ids)
			{
				foreach($this->committee_model->get_committees() as $id => $committee)
				{
					//操作
					$operation = anchor("delegate/manage/?committee=$id", icon('user', false).'代表').' '.anchor("seat/manage/?committee=$id", icon('th-list', false).'席位').' '.anchor("committee/edit/$id", icon('edit', false).'编辑');

					//主席团
					$dais_inteviewer_ids = $this->admin_model->get_admin_ids('role_dais', true, 'role_interviewer', true, 'committee', $id);
					$dais_only_ids = $this->admin_model->get_admin_ids('role_dais', true, 'role_interviewer', false, 'committee', $id);
					$inteviewer_only_ids = $this->admin_model->get_admin_ids('role_dais', false, 'role_interviewer', true, 'committee', $id);

					$dais_count = ($dais_inteviewer_ids ? count($dais_inteviewer_ids) : 0) + ($dais_only_ids ? count($dais_only_ids) : 0) + ($inteviewer_only_ids ? count($inteviewer_only_ids) : 0);

					$dais = array();

					if($dais_inteviewer_ids)
					{
						foreach($dais_inteviewer_ids as $dais_id)
						{
							$dais[] = anchor("user/edit/$dais_id", icon('user', false).$this->user_model->get_user($dais_id, 'name'));
						}
					}

					if($dais_only_ids)
					{
						foreach($dais_only_ids as $dais_id)
						{
							$dais[] = anchor("user/edit/$dais_id", icon('user', false).$this->user_model->get_user($dais_id, 'name')).'（仅主席权限）';
						}
					}

					if($inteviewer_only_ids)
					{
						foreach($inteviewer_only_ids as $dais_id)
						{
							$dais[] = anchor("user/edit/$dais_id", icon('user', false).$this->user_model->get_user($dais_id, 'name')).'（仅面试官权限）';
						}
					}

					if(empty($dais))
						$dais_list = '无';
					else
						$dais_list = join("<br />", $dais);
					$dais_text = $dais_count.' 位主席<a style="cursor: pointer;" class="dais_list" data-html="1" data-placement="right" data-trigger="click" data-original-title="主席团列表" data-toggle="popover" data-content=\''.$dais_list.'\'>'.icon('info-circle', false).'</a>';

					//委员会类型
					if(!empty($committee['seat_width']))
					{
						if(in_array($committee['seat_width'], array_keys($this->widths)))
							$seat_width_text = $this->widths[$committee['seat_width']];
						else
							$seat_width_text = "每席位 {$committee['seat_width']} 代表";
					}
					else
						$seat_width_text = '';

					//席位
					$seat_available = $this->seat_model->get_seat_ids('committee', $id, 'status', array('unavailable', 'available', 'preserved'));
					$seat_assigned = $this->seat_model->get_seat_ids('committee', $id, 'status', array('assigned', 'approved'));
					$seat_locked = $this->seat_model->get_seat_ids('committee', $id, 'status', array('locked'));

					$seat_line = $seat_available ? anchor("seat/manage/?status=available,unavailable,preserved&committee={$id}", '<span class="label label-warning">未选择</span>').' '.count($seat_available).' ' : '';
					$seat_line .= $seat_assigned ? anchor("seat/manage/?status=assigned,approved&committee={$id}", '<span class="label label-primary">已选择</span>').' '.count($seat_assigned).' ' : '';
					$seat_line .= $seat_locked ? anchor("seat/manage/?status=locked&committee={$id}", '<span class="label label-success">已锁定</span>').' '.count($seat_locked).' ' : '';

					$data = array(
						$committee['id'], //ID
						$committee['name'], //委员会名称
						$committee['abbr'], //委员会缩写
						$dais_text, //主席团
						$seat_width_text, //委员会类型
						$seat_line, //席位
						$operation, //操作
					);

					$datum[] = $data;
				}
				
				$json = array('aaData' => $datum);
			}
			else
			{
				$json = array('aaData' => array());
			}
		}
		
		echo json_encode($json);
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
}

/* End of file user.php */
/* Location: ./application/controllers/user.php */