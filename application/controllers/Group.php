<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 团队管理控制器
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
 * @package iPlacard
 * @since 2.0
 */
class Group extends CI_Controller
{
	/**
	 * @var array 状态信息
	 */
	private $statuses = array(
		'apply' => array('title' => '参会申请', 'short' => '申请', 'class' => 'primary', 'description' => '仍在申请中'),
		'payment' => array('title' => '会费支付', 'short' => '支付', 'class' => 'info', 'description' => '席位获得确认等待代表支付会费'),
		'lock' => array('title' => '申请完成', 'short' => '锁定', 'class' => 'success', 'description' => '席位已经锁定支付已经完成'),
		'wait' => array('title' => '等待队列', 'short' => '等待', 'class' => 'muted', 'description' => '审核或面试未通过在等待队列')
	);
	
	function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->library('form_validation');
		$this->load->library('ui', array('side' => 'admin'));
		$this->load->model('admin_model');
		$this->load->model('delegate_model');
		$this->load->model('group_model');
		$this->load->helper('form');
		
		//检查登录情况
		if(!is_logged_in())
		{
			login_redirect();
			return;
		}
		
		//检查权限
		if(!$this->user_model->is_admin(uid()) || (!$this->admin_model->capable('administrator')))
		{
			redirect('');
			return;
		}
		
		$this->ui->now('group');
	}
	
	/**
	 * 管理页面
	 */
	function manage()
	{
		$vars['status_order'] = array('apply', 'payment', 'lock', 'wait');
		$vars['statuses'] = $this->statuses;
		
		$this->ui->title('代表团列表');
		$this->load->view('admin/group_manage', $vars);
	}
	
	/**
	 * 编辑或添加代表团
	 */
	function edit($id = '')
	{
		//设定操作类型
		$action = 'edit';
		if(empty($id))
			$action = 'add';
		
		if($action == 'edit')
		{
			$group = $this->group_model->get_group($id);
			if(!$group)
				$action = 'add';
		}
		
		if($action == 'edit')
		{
			$vars['group'] = $group;
			
			$this->ui->title($group['name'], '代表团管理');
		}
		else
		{
			$this->ui->title('添加代表团');
		}
		
		$this->form_validation->set_error_delimiters('<div class="help-block">', '</div>');
		
		$this->form_validation->set_rules('name', '团队名称', 'trim|required');
		
		if($this->form_validation->run() == true)
		{
			$post = $this->input->post();
			
			$data = array(
				'name' => $post['name']
			);
			
			$new_id = $this->group_model->edit_group($data, $action == 'add' ? '' : $id);
			
			if($action == 'add')
			{
				$this->ui->alert("已经成功添加新代表团 #{$new_id}。", 'success', true);
				
				$this->system_model->log('group_added', array('id' => $new_id, 'data' => $data));
			}
			else
			{
				$this->ui->alert('代表团已编辑。', 'success', true);

				$this->system_model->log('group_edited', array('id' => $id, 'data' => $data));
			}
			
			redirect('group/manage');
			return;
		}
		
		$vars['action'] = $action;
		$this->load->view('admin/group_edit', $vars);
	}
	
	/**
	 * 删除代表团
	 */
	function delete($id)
	{
		//代表团检查
		$group = $this->group_model->get_group($id);
		if(!$group)
		{
			$this->ui->alert('指定删除的代表团不存在。', 'warning', true);
			redirect('group/manage');
			return;
		}
		
		$this->form_validation->set_rules('admin_password', '密码', 'trim|required|callback__check_admin_password[密码验证错误导致删除操作未执行，请重新尝试。]');
		
		if($this->form_validation->run() == true)
		{
			//删除数据
			$this->group_model->delete_group($id);
			
			//邮件通知
			$this->load->library('email');
			$this->load->library('parser');
			$this->load->helper('date');

			$email_data = array(
				'group' => $group['name'],
				'time' => unix_to_human(time())
			);
			
			//通知领队
			if(!empty($group['head_delegate']))
			{
				$head_delegate = $this->delegate_model->get_delegate($group['head_delegate']);
				$email_data['head_delegate'] = $head_delegate['name'];
				
				$this->email->to($head_delegate['email']);
				$this->email->subject('您领队的代表团已经删除');
				$this->email->html($this->parser->parse_string(option('email_group_manage_group_deleted', "您领队的代表团{group}已经于 {time} 被管理员删除，如存在误操作请立即联系管理员。"), $email_data, true));
				$this->email->send();
			}
			
			$group_delegates = $this->delegate_model->get_delegate_ids('group', $id);
			if($group_delegates)
			{
				$this->email->subject('您所在的代表团已经解散');
				$this->email->html($this->parser->parse_string(option('email_group_delegate_dissolved', "您所在的代表团{group}已经于 {time} 解散，请与您的领队联系了解详情。"), $email_data, true));
				
				foreach($group_delegates as $delegate)
				{
					//重置代表团信息
					$this->delegate_model->edit_delegate(array('group' => NULL), $delegate);
					
					//通知代表团成员
					$this->email->to($this->user_model->get_user($delegate, 'email'));
					$this->email->send(false);
				}
			}
			
			//日志
			$this->system_model->log('group_deleted', array('ip' => $this->input->ip_address(), 'group' => $id));
			
			$this->ui->alert("代表团 #{$id} 已经成功删除。", 'success', true);
			redirect('group/manage');
		}
		else
		{
			redirect("group/edit/{$id}");
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
			$ids = $this->group_model->get_group_ids();
			
			if($ids)
			{
				$groups = $this->group_model->get_groups($ids);
				$delegates = array();
				
				$delegate_ids = $this->delegate_model->get_delegate_ids('group', $ids);
				if($delegate_ids)
					$delegates = $this->delegate_model->get_delegates($delegate_ids);
				
				foreach($groups as $id => $group)
				{
					//操作
					$operation = anchor("delegate/manage/?display_hd=1&group=$id", icon('group', false).'成员').' '.anchor("group/edit/$id", icon('edit', false).'编辑');

					//状态统计
					$status = array(
						'apply' => 0,
						'payment' => 0,
						'lock' => 0,
						'wait' => 0
					);

					//团队人数
					$all_delegate = $this->delegate_model->get_group_delegates($id);
					$only_delegate = $this->delegate_model->get_group_delegates($id, 'delegate');
					$count_text = '空';
					if($all_delegate)
					{
						$count = count($all_delegate);

						if(!$only_delegate)
							$count_text = "{$count} 人";
						else
						{
							$count_delegate = count($only_delegate);
							$count_text = "{$count} 人（{$count_delegate} 代表）";
						}

						//状态统计
						foreach($all_delegate as $delegate_id)
						{
							$status_code = $this->delegate_model->status_code($delegates[$delegate_id]['status']);
							if($status_code <= 5)
								$status['apply']++;
							elseif($status_code <= 7)
								$status['payment']++;
							elseif($status_code == 9)
								$status['lock']++;
							elseif($status_code == 8)
								$status['wait']++;
						}
					}

					//领队
					$head_delegate_line = '空缺';
					if(!empty($group['head_delegate']))
					{
						$head_delegate_line = anchor("delegate/profile/{$delegates[$group['head_delegate']]['id']}", icon('user').$delegates[$group['head_delegate']]['name']);
					}

					$data = array(
						$group['id'], //ID
						$group['name'], //团队名称
						$count_text, //团队人数
						$status['apply'] ? "<span class=\"text-{$this->statuses['apply']['class']}\">{$status['apply']} 人</span>" : '', //申请状态
						$status['payment'] ? "<span class=\"text-{$this->statuses['payment']['class']}\">{$status['payment']} 人</span>" : '', //支付状态
						$status['lock'] ? "<span class=\"text-{$this->statuses['lock']['class']}\">{$status['lock']} 人</span>" : '', //锁定状态
						$status['wait'] ? "<span class=\"text-{$this->statuses['wait']['class']}\">{$status['wait']} 人</span>" : '', //等待状态
						$head_delegate_line, //领队
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

/* End of file group.php */
/* Location: ./application/controllers/group.php */