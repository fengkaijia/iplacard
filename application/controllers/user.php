<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 管理用户管理控制器
 * @package iPlacard
 * @since 2.0
 */
class User extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->library('form_validation');
		$this->load->library('ui', array('side' => 'admin'));
		$this->load->model('admin_model');
		$this->load->model('committee_model');
		$this->load->model('interview_model');
		$this->load->helper('form');
		$this->load->helper('ui');
		
		//检查登录情况
		if(!is_logged_in())
		{
			login_redirect();
			return;
		}
		
		//检查权限
		if(!$this->user_model->is_admin(uid()) || (!$this->admin_model->capable('bureaucrat') && !$this->admin_model->capable('administrator')))
		{
			redirect('');
			return;
		}
		
		$this->ui->now('admin');
	}
	
	/**
	 * 跳转页
	 */
	function manage()
	{
		$this->ui->title('管理用户列表');
		$this->load->view('admin/user_manage');
	}
	
	/**
	 * 编辑或添加用户
	 */
	function edit($uid = '')
	{
		//检查权限
		if(!$this->admin_model->capable('bureaucrat'))
		{
			$this->ui->alert('需要行政员权限以编辑用户。', 'warning', true);
			redirect('user/manage');
			return;
		}
		
		//设定操作类型
		$action = 'edit';
		if(empty($uid) || !$this->user_model->user_exists($uid))
			$action = 'add';
		
		//设定权限信息
		$roles = array(
			'reviewer' => array('title' => '资料审核', 'description' => '审核参会申请|分配面试安排'),
			'dais' => array('title' => '主席', 'description' => '查看指定委员会代表信息|向委员会群发信息|向文件中心发布文件'),
			'interviewer' => array('title' => '面试官', 'description' => '面试代表|分配席位'),
			'cashier' => array('title' => '财务管理', 'description' => '核查和确认账单'),
			'administrator' => array('title' => '会务管理', 'description' => '站点信息管理|管理委员会、席位信息|SUDO|管理支持单'),
			'bureaucrat' => array('title' => '行政员', 'description' => '用户管理')
		);
		$vars['roles'] = $roles;
		
		//委员会信息
		$committee_ids = $this->committee_model->get_committee_ids();
		foreach($committee_ids as $committee_id)
		{
			$committee = $this->committee_model->get_committee($committee_id);
			$committees[$committee_id] = "{$committee['name']}（{$committee['abbr']}）";
		}
		$vars['committees'] = $committees;
		
		if($action == 'edit')
		{
			//获取用户信息
			$user = $this->admin_model->get_admin($uid);
			$vars['user'] = $user;
			
			$this->ui->title($user['name'], '用户管理');
		}
		else
		{
			$this->ui->title('添加用户');
		}
		
		$this->form_validation->set_error_delimiters('<div class="help-block">', '</div>');
		
		//修改姓名
		if($action == 'add' || ($this->input->post('change_name') && $this->input->post('name') != $user['name']))
		{
			$changed_name = true;
			$this->form_validation->set_rules('name', '姓名', 'trim|required');
		}
		
		//修改邮箱
		if($action == 'add' || ($this->input->post('change_email') && $this->input->post('email') != $user['email']))
		{
			$changed_email = true;
			
			$this->form_validation->set_rules('email', '电子邮箱地址', 'trim|required|valid_email|is_unique[user.email]');
			$this->form_validation->set_message('valid_email', '电子邮箱地址无效。');
		}
		
		//修改手机
		if($action == 'add' || ($this->input->post('change_phone') && $this->input->post('phone') != $user['phone']))
		{
			$changed_phone = true;
			
			$this->form_validation->set_rules('phone', '手机号', 'trim|required|integer|exact_length[11]|is_unique[user.phone]');
			$this->form_validation->set_message('exact_length', '%s长度必须为 %s 位。');
		}
		
		//修改密码
		if($action == 'add' || $this->input->post('password'))
		{
			$changed_password = true;
			
			$this->form_validation->set_rules('password', '新密码', 'trim|required|min_length[8]');
			$this->form_validation->set_rules('password_repeat', '重复密码', 'trim|required|matches[password]');
		}
		
		//检查管理员提权
		if($action == 'edit' && $user['id'] == uid() && !$user['role_administrator'] && $this->admin_model->get_admin_ids('role_bureaucrat', true, 'id !=', $user['id']))
		{
			//除非仅有一位行政员，编辑者为编辑对象时禁止授予会务管理权限
			$this->form_validation->set_rules('role_administrator', '会务管理权限', 'callback__check_admin_role');
		}
		
		//避免出现只改动权限时无法提交
		$this->form_validation->set_rules('committee', '委员会', 'trim');
		
		if($this->form_validation->run() == true)
		{
			$post = $this->input->post();
			
			//编辑`user`表数据
			$data = array();
			
			if($changed_name)
				$data['name'] = $post['name'];
			if($changed_email)
				$data['email'] = $post['email'];
			if($changed_password)
				$data['password'] = $post['password'];
			if($changed_phone)
				$data['phone'] = $post['phone'];
			
			if($action == 'add')
			{
				$data['type'] = 'admin';
				$data['reg_time'] = time();
			}
			
			if(!empty($data))
				$new_id = $this->user_model->edit_user($data, $action == 'edit' ? $user['id'] : '');
			
			if($action == 'add')
				$uid = $new_id;
			
			//编辑`admin`表数据
			$admin_data = array();
			
			//权限信息
			foreach(array_keys($roles) as $role)
			{
				$admin_data["role_{$role}"] = !empty($post["role_{$role}"]);
			}
			
			//委员会信息
			if(!empty($post['committee']))
				$admin_data['committee'] = $post['committee'];
			else
				$admin_data['committee'] = NULL;
			
			//职务信息
			$admin_data['title'] = $post['title'];
			
			if($action == 'add')
				$this->admin_model->add_profile($uid);
			$this->admin_model->edit_profile($admin_data, $uid);
			
			//新用户数据
			$new_user = $this->admin_model->get_admin($uid);
			
			//发送新密码
			if($post['sendmail'] && !empty($post['password']))
			{
				$this->load->library('email');
				$this->load->library('parser');
				$this->load->helper('date');

				$email_data = array(
					'uid' => $uid,
					'name' => $new_user['name'],
					'email' => $new_user['email'],
					'password' => $data['password'],
					'time' => unix_to_human(time()),
					'url' => base_url('account/login')
				);

				$this->email->to($new_user['email']);
				
				if($action == 'edit')
				{
					$this->email->subject('新的 iPlacard 登录密码');
					$this->email->html($this->parser->parse_string(option('email_admin_password_changed', "您 iPlacard 帐户 {email} 的密码已经于 {time} 由行政员更改为：\n\n"
							. "\t{password}\n\n"
							. "请使用新的密码登录 iPlacard。"), $email_data, true));
				}
				else
				{
					$this->email->subject('iPlacard 帐户登录信息');
					$this->email->html($this->parser->parse_string(option('email_admin_account_created', "您的 iPlacard 管理帐户已经于 {time} 创建。帐户信息如下：\n\n"
							. "\t登录邮箱：{email}\n"
							. "\t密码：{password}\n\n"
							. "请使用以上信息访问：\n\n"
							. "\t{url}\n\n"
							. "登录并开始使用 iPlacard。"), $email_data, true));
				}
			
				$this->email->send();
			}
			
			if($action == 'add')
			{
				$this->ui->alert("已经成功添加新管理用户 #{$uid}。", 'success', true);
				
				$this->system_model->log('user_added', array('id' => $uid, 'type' => 'admin'));
			}
			else
			{
				$this->ui->alert('用户已编辑。', 'success', true);
				
				unset($data['password']);
				$this->system_model->log('user_edited', array('id' => $uid, 'data' => $data + $admin_data));
			}
			
			redirect('user/manage');
			return;
		}
		
		$vars['action'] = $action;
		$this->load->view('admin/user_edit', $vars);
	}
	
	/**
	 * AJAX
	 */
	function ajax($action = 'list')
	{
		$json = array();
		
		if($action == 'list')
		{
			$this->load->helper('date');
			
			$ids = $this->user_model->get_user_ids('type', 'admin');
			
			foreach($ids as $id)
			{
				$user = $this->admin_model->get_admin($id);
				
				//操作
				$operation = '';
				if($this->admin_model->capable('bureaucrat'))
					$operation .= anchor("user/edit/$id", icon('edit', false).'编辑');
				
				//委员会
				if($user['committee'])
				{
					$committee = $this->committee_model->get_committee($user['committee']);
					$committee_text = icon('sitemap').$committee['abbr'];
					$committee_line = anchor("committee/edit/{$committee['id']}", $committee_text);
				}
				
				//面试队列统计
				$interview_queue_count = $this->interview_model->get_interview_ids('interviewer', $id, 'status', array('assigned', 'arranged'));
				$interview_done_count = $this->interview_model->get_interview_ids('interviewer', $id, 'status', array('completed', 'failed'));
				$interview_line = $interview_queue_count ? "<span class=\"label label-primary\">队列</span> {$interview_queue_count} " : '';
				$interview_line .= $interview_done_count ? "<span class=\"label label-success\">结束</span> {$interview_done_count} " : '';
				
				//权限统计
				$role_count = 0;
				foreach(array('reviewer', 'dais', 'interviewer', 'cashier', 'administrator', 'bureaucrat') as $role)
				{
					if($user["role_$role"])
						$role_count++;
				}
				$role_line = '<span class="text-success">'.icon('check-circle', false).'</span>';
				
				$data = array(
					$user['id'], //ID
					$user['name'], //姓名
					$user['title'], //职位
					$user['committee'] ? $committee_line : '', //委员会
					$interview_line, //面试队列
					$role_count ? "{$role_count} 项权限" : "无权限", //权限统计
					$user['role_bureaucrat'] ? $role_line : '', //行政员
					$user['role_administrator'] ? $role_line : '', //会务管理
					$user['role_dais'] ? $role_line : '', //主席
					$user['role_interviewer'] ? $role_line : '', //面试官
					$user['role_reviewer'] ? $role_line : '', //申请审核
					$user['role_cashier'] ? $role_line : '', //财务管理
					$user['last_login'] ? sprintf('%1$s（%2$s）', date('n月j日', $user['last_login']), nicetime($user['last_login'])) : '', //最后登录
					$operation, //操作
				);
				
				$datum[] = $data;
				
				$json = array('aaData' => $datum);
			}
		}
		
		echo json_encode($json);
	}

	/**
	 * 会务管理权限检查
	 */
	function _check_admin_role($input)
	{
		if(!empty($input))
		{
			$this->ui->alert('由于当前系统中有多位行政员，为了保证权限系统安全，您将不能为自己授予会务管理权限。此项通用规则被包括维基百科在内的大量网站采用以增强系统的安全性，如果需要，您可以联系其他行政员为您授予会务管理权限。');
			
			return false;
		}
			
		return true;
	}
}

/* End of file user.php */
/* Location: ./application/controllers/user.php */