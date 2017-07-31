<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 代表管理控制器
 * @package iPlacard
 * @since 2.0
 */
class Delegate extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->library('form_validation');
		$this->load->library('ui', array('side' => 'admin'));
		$this->load->model('admin_model');
		$this->load->model('delegate_model');
		$this->load->helper('form');
		$this->load->helper('ui');
		
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
		
		$this->ui->now('delegate');
	}
	
	/**
	 * 管理页面
	 */
	function manage()
	{
		//查询过滤
		$post = $this->input->get();
		$param = $this->_filter_check($post);
		
		//显示标题
		$title = '参会代表列表';
		
		if(isset($param['type']))
		{
			$text_type = array();
			foreach($param['type'] as $one)
			{
				$text_type[] = $this->delegate_model->application_type_text($one);
			}
			$title = sprintf("%s列表", join('、', $text_type));
		}
		
		if(isset($param['status']))
		{
			$text_status = array();
			foreach($param['status'] as $one)
			{
				$text_status[] = $this->delegate_model->status_text($one);
			}
			$title = sprintf("%s代表列表", join('、', $text_status));
		}
		
		if(isset($param['committee']))
		{
			$this->load->model('committee_model');
			
			$text_committee = array();
			foreach($param['committee'] as $one)
			{
				$text_committee[] = $this->committee_model->get_committee($one, 'name');
			}
			$title = sprintf("%s代表列表", join('、', $text_committee));
		}
		
		if(isset($param['interviewer']))
		{
			$text_interviewer = array();
			foreach($param['interviewer'] as $one)
			{
				if($one == uid())
					$text_interviewer[] = '我';
				else
					$text_interviewer[] = $this->admin_model->get_admin($one, 'name');
			}
			$title = sprintf("%s面试的代表列表", join('、', $text_interviewer));
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
		
		if(isset($param['enabled']))
		{
			if($param['enabled'][0] == false)
				$title = "停用代表列表";
		}
		
		$vars = array(
			'param_uri' => $this->_filter_build($param),
			'title' => $title,
			'profile_option' => option('profile_list_manage', array())
		);
		
		$this->ui->title($title);
		$this->load->view('admin/delegate_manage', $vars);
	}
	
	/**
	 * 查看代表资料
	 */
	function profile($uid)
	{
		$this->load->model('interview_model');
		$this->load->model('group_model');
		$this->load->model('committee_model');
		$this->load->model('seat_model');
		$this->load->library('event');
		$this->load->helper('unicode');
		$this->load->helper('avatar');
		$this->load->helper('date');
		
		//检查代表是否存在
		if(!$this->user_model->user_exists($uid))
		{
			$this->ui->alert('代表不存在。', 'danger', true);
			back_redirect();
			return;
		}
		elseif(!$this->user_model->is_delegate($uid))
		{
			$this->ui->alert('请求的用户 ID 不是代表。', 'danger', true);
			back_redirect();
			return;
		}
		
		//代表资料数据
		$profile = $this->delegate_model->get_delegate($uid);
		$profile['pinyin'] = pinyin($profile['name']);
		$profile['application_type_text'] = $this->delegate_model->application_type_text($profile['application_type']);
		$profile['status_text'] = $this->delegate_model->status_text($profile['status']);
		$profile['status_code'] = $this->delegate_model->status_code($profile['status']);
		
		$pids = $this->delegate_model->get_profile_ids('delegate', $uid);
		if($pids)
		{
			foreach($pids as $pid)
			{
				$one = $this->delegate_model->get_profile($pid);
				
				//学术测试为空
				if($one['name'] == 'test' && !array_filter($one['value']))
						$one['value'] = false;
				
				$profile[$one['name']] = $one['value'];
			}
		}
		
		$vars['profile'] = $profile;
		
		//退会提示
		if($profile['status'] == 'quitted')
		{
			$quit_time = user_option('quit_time', time(), $uid);
			
			$this->ui->alert(sprintf('此代表已经于%s退会。', date('Y年m月d日', $quit_time)));
		}
		
		//计划删除提示
		if($profile['status'] == 'deleted')
		{
			$delete_time = user_option('delete_time', time(), $uid);
			
			$this->ui->alert(sprintf('管理员已经于%1$s计划删除了此帐户，此帐户数据将在%2$s删除。', date('Y年m月d日', $delete_time), nicetime(user_option('delete_time', time(), $uid) + option('delegate_delete_lock', 7) * 24 * 60 * 60, true)));
		}
		
		//帐户停用提示
		if(!$profile['enabled'])
		{
			$disable_time = user_option('disable_time', time(), $uid);
			
			$this->ui->alert(sprintf('此代表帐户已经于%s停用，帐户停用期间代表将无法登录 iPlacard。', date('Y年m月d日', $disable_time)));
		}
		
		//面试数据
		$interviews = array();
		$current_interview = NULL;
		
		$iids = $this->interview_model->get_interview_ids('delegate', $uid);
		if($iids)
		{
			$iids = array_reverse($iids);
			
			foreach($iids as $interview_id)
			{
				$interview = $this->interview_model->get_interview($interview_id);
				
				$interview['status_text'] = $this->interview_model->status_text($interview['status']);
				
				$interview['interviewer'] = $this->admin_model->get_admin($interview['interviewer']);
				if(!empty($interview['interviewer']['committee']))
				{
					$interview['interviewer']['committee'] = $this->committee_model->get_committee($interview['interviewer']['committee']);
				}
				
				switch($interview['status'])
				{
					case 'completed':
					case 'exempted':
						$status_class = 'success';
						break;
					case 'failed':
						$status_class = 'danger';
						break;
					case 'cancelled':
						$status_class = 'muted';
						break;
					default:
						$status_class = 'primary';
				}
				$interview['status_class'] = $status_class;
				
				$interviews[$interview['id']] = $interview;
			}
			
			$current_interview = $this->interview_model->get_current_interview_id($uid);
		}
		
		$vars['current_interview'] = $current_interview;
		$vars['interviews'] = $interviews;
		
		//用户事件数据
		$events = array();
		
		$eids = $this->delegate_model->get_delegate_events($uid);
		if($eids)
		{
			foreach($eids as $eid)
			{
				if($this->event->load($eid))
				{
					$event = array(
						'title' => $this->event->get('title'),
						'class' => $this->event->get('level'),
						'icon' => $this->event->get('icon'),
						'text' => $this->event->get('text'),
						'time' => $this->event->get('time')
					);
					
					$events[] = $event;
				}
				
				$this->event->clear();
			}
		}
		
		$vars['events'] = $events;
		
		//所有团队数据
		$groups = array();
		
		$gids = $this->group_model->get_group_ids();
		if($gids)
		{
			foreach($gids as $gid)
			{
				$one = $this->group_model->get_group($gid);
				$groups[$gid] = $one['name'];
			}
		}
		
		$vars['groups'] = $groups;
		$vars['group_count'] = count($groups);
		
		//代表团队数据
		$group = false;
		$head_delegate = false;
		
		if(!empty($profile['group']))
		{
			$group = $this->group_model->get_group($profile['group']);
			
			//团队人数统计
			$group['count'] = 0;
			$guids = $this->delegate_model->get_delegate_ids('group', $profile['group']);
			if($guids)
				$group['count'] = count($guids);
			
			//是否为领队
			if($group['head_delegate'] == $uid)
				$head_delegate = true;
			elseif($group['head_delegate'])
				$group['head_delegate'] = $this->delegate_model->get_delegate($group['head_delegate']);
		}
		
		$vars['group'] = $group;
		$vars['head_delegate'] = $head_delegate;
		
		//席位分配模式
		$seat_mode = option('seat_mode', 'select');
		$vars['seat_mode'] = $seat_mode;
		
		//席位选择阶段
		$seat_open = false;
		if($profile['application_type'] == 'delegate' && $this->_check_seat_select_open($profile))
			$seat_open = true;
		$vars['seat_open'] = $seat_open;
		
		//显示席位选择
		$seat_assignable = false;
		if((in_array(uid(), option('seat_global_admin', array())) || (!$this->_check_interview_enabled($profile['application_type']) && $this->admin_model->capable('reviewer')) || ($seat_mode == 'select' ? $this->_check_interviewer_assign_right($uid, uid()) : (!empty($current_interview) && $interviews[$current_interview]['interviewer']['id'] == uid()))) && $profile['status'] != 'locked' && $profile['status'] != 'quitted')
		{
			$seat_assignable = true;
		}
		$vars['seat_assignable'] = $seat_assignable;
		
		//席位数据
		$seat = array();
		$seat_id = $this->seat_model->get_delegate_seat($uid);
		if($seat_id)
		{
			$seat = $this->seat_model->get_seat($seat_id);
			$seat['committee'] = $this->committee_model->get_committee($seat['committee']);
		}
		$vars['seat'] = $seat;

		//席位选择数据
		$vars['selectabilities'] = $this->seat_model->get_delegate_selectability($uid, false, 'seat');
		
		//检查权限
		$profile_editable = false;
		if($this->admin_model->capable('administrator') && $profile['status'] != 'deleted')
		{
			$profile_editable = true;
		}
		$vars['profile_editable'] = $profile_editable;
		
		$vars['uid'] = $uid;
		
		//跨实例查询
		$ciq = array();
		$instances = option('ciq_instance', array());
		if(!empty($instances))
		{
			$this->load->library('ciq');
			
			foreach($instances as $instance_id => $instance)
			{
				$this->ciq->clear();
				$this->ciq->set_api($instance['api'], $instance['access_token']);
				$this->ciq->set_request('delegate', 'info');
				$this->ciq->set_post(array('key' => $profile['unique_identifier']));
				$this->ciq->parse();
				
				$instance_data = $this->ciq->get();
				
				if(!$instance_data)
					continue;
				
				$instance_data['application_type_text'] = $this->delegate_model->application_type_text($instance_data['application_type']);
				$instance_data['status_text'] = $this->delegate_model->status_text($instance_data['status']);
				switch($this->delegate_model->status_code($instance_data['status']))
				{
					case 9:
						$instance_data['status_class'] = 'success';
						break;
					case 10:
						$instance_data['status_class'] = 'warning';
						break;
					case 100:
						$instance_data['status_class'] = 'danger';
						break;
					default:
						$instance_data['status_class'] = 'primary';
				}
				
				$ciq[$instance_id] = array(
					'name' => $instance['name'],
					'url' => $instance['api'],
					'data' => $instance_data
				);
			}
		}
		$vars['ciq'] = $ciq;
		
		$this->ui->title($profile['name'], '代表资料');
		$this->load->view('admin/delegate_profile', $vars);
	}
	
	/**
	 * 添加笔记
	 */
	function note($action, $uid, $id = '')
	{
		$this->load->model('note_model');
		
		//设定操作类型
		if(empty($id))
			$action = 'add';
		
		//添加笔记
		if($action == 'add')
		{
			$this->form_validation->set_error_delimiters('<div class="help-block">', '</div>');

			$this->form_validation->set_rules('note', '笔记内容', 'trim|required');

			if($this->form_validation->run() == true)
			{
				$note = $this->input->post('note', true);
				$category = $this->input->post('category');
				
				if(empty($category) || !$this->note_model->get_category($category))
					$category = NULL;
				
				$new_id = $this->note_model->add_note(intval($uid), $note, $category);
				
				if($new_id)
				{
					//获取提及
					$mentions = $this->input->post('mention');
					if($mentions && !empty($mentions))
					{
						$this->load->helper('string');
						
						$users = array();
						
						foreach(array_unique($mentions) as $mention)
						{
							//如果提及仍存在
							if(strpos($note, $mention) !== false)
							{
								$user = extract_mention($mention);
								if(!$user)
									continue;
								
								if(!$this->user_model->user_exists($user))
									continue;
								
								$users[] = $user;
							}
						}
						
						//处理提及
						if(!empty($users))
						{
							$this->note_model->add_mention($new_id, $users);
							
							$admin = $this->admin_model->get_admin(uid());
							
							//邮件通知
							$this->load->library('email');
							$this->load->library('parser');
							$this->load->helper('date');
							
							foreach($users as $user_id)
							{
								$user = $this->user_model->get_user($user_id);
								$delegate = $this->delegate_model->get_delegate($uid);
								
								$this->user_model->add_message($user_id, "{$admin['name']}在{$delegate['name']}的笔记中提及了你。");

								$data = array(
									'id' => $new_id,
									'note' => $note,
									'delegate' => $delegate['name'],
									'delegate_id' => $uid,
									'admin' => $admin['name'],
									'admin_id' => $admin['id'],
									'admin_title' => $admin['title'],
									'user' => $user['name'],
									'user_id' => $user_id,
									'url' => base_url("delegate/profile/{$uid}"),
									'time' => unix_to_human(time())
								);
								
								$this->email->to($user['email']);
								$this->email->subject('一条笔记提及了您');
								$this->email->html($this->parser->parse_string(option('email_note_user_mentioned', "{admin}于 {time} 在{delegate}代表的笔记中提及了您。\n\n"
										. "笔记内容如下：\n\n"
										. "\t{note}\n\n"
										. "请访问 {url} 阅读笔记。"), $data, true));
								$this->email->send();
							}
							
							$count = count($users);
							$this->ui->alert("已经向笔记中提及的 {$count} 位用户发送了通知。", 'success', true);
							
							$this->system_model->log('note_mentioned', array('note' => $new_id, 'user' => $users));
						}
					}
					
					$this->ui->alert("已经成功添加笔记。", 'success', true);

					$this->system_model->log('note_added', array('id' => $new_id));
				}
				else
				{
					$this->ui->alert("出现未知错误导致笔记未能添加，请重试。", 'danger', true);
				}
			}
			
			back_redirect();
			return;
		}
	}
	
	/**
	 * 团队操作
	 */
	function group($action, $uid)
	{
		$this->load->model('group_model');
		
		$delegate = $this->delegate_model->get_delegate($uid);
				
		if(!$delegate)
		{
			$this->ui->alert('代表不存在。', 'danger', true);
			back_redirect();
			return;
		}
		
		//转换为个人代表
		if($action == 'remove')
		{
			$this->form_validation->set_error_delimiters('<div class="help-block">', '</div>');

			$this->form_validation->set_rules('confirm', '确认删除', 'trim|required');

			if($this->form_validation->run() == true)
			{
				if(is_null($delegate['group']))
				{
					$this->ui->alert('需要转换的代表是个人代表。', 'warning', true);
					back_redirect();
					return;
				}
				
				$group = $this->group_model->get_group($delegate['group']);
				
				//取消团队
				$this->delegate_model->edit_delegate(array('group' => NULL), $uid);
				
				//是领队
				if($group['head_delegate'] == $uid)
				{
					$this->group_model->edit_group(array('head_delegate' => NULL), $group['id']);
					
					$this->system_model->log('group_head_delegate_removed', array('id' => $group['id'], 'head_delegate' => $uid));
				}
				
				//发送邮件
				$this->load->library('email');
				$this->load->library('parser');
				$this->load->helper('date');
				
				$data = array(
					'uid' => $uid,
					'delegate_name' => $delegate['name'],
					'group_old_name' => $group['name'],
					'time' => unix_to_human(time()),
				);
				
				//通知此代表
				$this->email->to($delegate['email']);
				$this->email->subject('您已调整为个人代表');
				$this->email->html($this->parser->parse_string(option('email_group_delegate_removed', "您已于 {time} 由管理员操作退出{group_old_name}代表团，如为误操作请立即与管理员取得联系。"), $data, true));
				$this->email->send();
				
				//通知团队领队
				if($group['head_delegate'] != $uid)
				{
					$this->email->to($this->delegate_model->get_delegate($group['head_delegate'], 'email'));
					$this->email->subject('代表退出了您领队的代表团');
					$this->email->html($this->parser->parse_string(option('email_group_manage_delegate_removed', "{delegate_name}代表已于 {time} 由管理员操作退出您领队的{group_old_name}代表团，如为误操作请立即与管理员取得联系。"), $data, true));
					$this->email->send();
				}
				
				$this->ui->alert('已经转换为个人代表。', 'success', true);
				
				$this->system_model->log('group_delegate_removed', array('id' => $group['id'], 'delegate' => $uid));
			}
			else
			{
				$this->ui->alert('转换操作未获确认。', 'danger', true);
			}
		}
		
		//加入或调整团队
		if($action == 'edit')
		{
			$this->form_validation->set_error_delimiters('<div class="help-block">', '</div>');

			$this->form_validation->set_rules('group', '所属团队', 'trim|required');

			if($this->form_validation->run() == true)
			{
				$group_id = intval($this->input->post('group'));
				$group_new = $this->group_model->get_group($group_id);
				
				if(!$group_new)
				{
					$this->ui->alert('代表团不存在。', 'danger', true);
					back_redirect();
					return;
				}
				
				//已经为团队代表
				if(!is_null($delegate['group']))
				{
					$group_old = $this->group_model->get_group($delegate['group']);
					
					//取消旧领队属性
					if($group_old['head_delegate'] == $uid)
					{
						$this->group_model->edit_group(array('head_delegate' => NULL), $group_old['id']);

						$this->system_model->log('group_head_delegate_removed', array('id' => $group_old['id'], 'head_delegate' => $uid));
					}
				}
				
				//更新团队
				if(is_null($delegate['group']) || $delegate['group'] != $group_id)
					$this->delegate_model->edit_delegate(array('group' => $group_id), $uid);
				
				//设为领队
				if($this->input->post('head_delegate') == true)
				{
					$this->group_model->edit_group(array('head_delegate' => $uid), $group_id);
					
					$this->ui->alert("{$group_new['name']}的领队设置已经更新。", 'success', true);
					
					$this->system_model->log('group_head_delegate_changed', array('id' => $group_id, 'head_delegate_new' => $uid, 'head_delegate_old' => $group_old['head_delegate']));
				}
				
				//发送邮件
				$this->load->library('email');
				$this->load->library('parser');
				$this->load->helper('date');
				
				$data = array(
					'uid' => $uid,
					'delegate_name' => $delegate['name'],
					'group_new_name' => $group_new['name'],
					'group_old_name' => !is_null($delegate['group']) ? $group_old['name'] : NULL,
					'time' => unix_to_human(time()),
				);
				
				//通知此代表
				if(is_null($delegate['group']) || $delegate['group'] != $group_id)
				{
					$this->email->to($delegate['email']);
					if(!is_null($delegate['group']))
					{
						$this->email->subject('您的代表团已经调整');
						$this->email->html($this->parser->parse_string(option('email_group_delegate_changed', "您已于 {time} 由管理员操作退出{group_old_name}代表团，加入{group_new_name}代表团代表。"), $data, true));
					}
					else
					{
						$this->email->subject('您已加入代表团');
						$this->email->html($this->parser->parse_string(option('email_group_delegate_joined', "您已于 {time} 由管理员操作加入{group_new_name}代表团代表。"), $data, true));
					}
					$this->email->send();
				}
				
				//通知旧团队领队
				if(!is_null($delegate['group']) && $delegate['group'] != $group_id && $group_old['head_delegate'] != $uid)
				{
					$this->email->to($this->delegate_model->get_delegate($group_old['head_delegate'], 'email'));
					$this->email->subject('代表退出了您领队的代表团');
					$this->email->html($this->parser->parse_string(option('email_group_manage_delegate_removed', "{delegate_name}代表已于 {time} 由管理员操作退出您领队的{group_old_name}代表团，如为误操作请立即与管理员取得联系。"), $data, true));
					$this->email->send();
				}
				
				//通知新团队领队
				if($delegate['group'] != $group_id && $this->input->post('head_delegate') != true)
				{
					$this->email->to($this->delegate_model->get_delegate($group_new['head_delegate'], 'email'));
					$this->email->subject('代表加入您领队的代表团');
					$this->email->html($this->parser->parse_string(option('email_group_manage_delegate_joined', "{delegate_name}代表已于 {time} 由管理员操作加入您领队的{group_new_name}代表团。"), $data, true));
					$this->email->send();
				}
				
				//通知领队变更
				if(($delegate['group'] != $group_id && $this->input->post('head_delegate') == true) || ($delegate['group'] == $group_id && !$group_new['head_delegate'] != $uid && $this->input->post('head_delegate') == true))
				{
					//通知成为领队
					$this->email->to($delegate['email']);
					$this->email->subject('您已经成为代表团领队');
					$this->email->html($this->parser->parse_string(option('email_group_manage_delegate_granted', "您已于 {time} 由管理员操作成为{group_new_name}代表团的领队。"), $data, true));
					$this->email->send();
					
					//通知取消原领队
					if(!is_null($group_new['head_delegate']))
					{
						$this->email->to($this->delegate_model->get_delegate($group_new['head_delegate'], 'email'));
						$this->email->subject('代表团领队已经更换');
						$this->email->html($this->parser->parse_string(option('email_group_manage_delegate_changed', "您领队的{group_new_name}代表团已于 {time} 由管理员操作更换领队为{delegate_name}代表，如为误操作请立即与管理员取得联系。"), $data, true));
						$this->email->send();
					}
				}
				
				//通知领队取消
				if($delegate['group'] == $group_id && !$group_new['head_delegate'] == $uid && $this->input->post('head_delegate') == false)
				{
					//通知成为领队
					$this->email->to($delegate['email']);
					$this->email->subject('您的代表团领队已经取消');
					$this->email->html($this->parser->parse_string(option('email_group_manage_delegate_removed', "您领队的{group_new_name}代表团已于 {time} 由管理员操作取消了领队，目前{group_new_name}代表团没有领队，如为误操作请立即与管理员取得联系。"), $data, true));
					$this->email->send();
				}
				
				if(is_null($delegate['group']) || $delegate['group'] != $group_id || ($group_new['head_delegate'] != $uid && $this->input->post('head_delegate') == true) || ($group_new['head_delegate'] == $uid && $this->input->post('head_delegate') == false))
					$this->ui->alert('团队转换调整操作完成。', 'success', true);
				else
					$this->ui->alert('未执行团队转换调整操作。', 'info', true);
				
				$this->system_model->log('group_delegate_removed', array('id' => $group['id'], 'delegate' => $uid));
			}
			else
			{
				$this->ui->alert('转换操作未完成。', 'danger', true);
			}
		}
		
		back_redirect();
	}
	
	/**
	 * AJAX 操作
	 */
	function operation($action, $uid)
	{
		if(empty($uid))
			return;

		$delegate = $this->delegate_model->get_delegate($uid);
		if(!$delegate)
			return;
		
		//禁止操作已删除帐户
		if($delegate['status'] == 'deleted' && $action != 'recover_account')
		{
			$this->ui->alert('此代表帐户已停用删除，无法完成操作。', 'danger', true);
			back_redirect();
			
			return;
		}
		
		switch($action)
		{
			//通过审核
			case 'pass_application':
				if($delegate['status'] != 'application_imported')
					break;
				
				$this->delegate_model->change_status($uid, 'review_passed');
				
				$this->delegate_model->add_event($uid, 'review_passed');
				
				if($this->_check_interview_enabled($delegate['application_type']))
					$this->user_model->add_message($uid, '您的参会申请已经通过审核，我们将在近期内为您分配面试官。');
				else
					$this->user_model->add_message($uid, '您的参会申请已经通过审核。');
				
				//邮件通知
				$this->load->library('email');
				$this->load->library('parser');
				$this->load->helper('date');
				
				$data = array(
					'uid' => $uid,
					'delegate' => $delegate['name'],
					'time' => unix_to_human(time())
				);
				
				$this->email->to($delegate['email']);
				$this->email->subject('参会申请审核通过');
				$this->email->html($this->parser->parse_string(option("email_delegate_application_passed_{$delegate['application_type']}", "您的参会申请已经于 {time} 通过审核，请登录 iPlacard 系统查看申请状态。"), $data, true));
				$this->email->send();
				
				//短信通知
				if(option('sms_enabled', false))
				{
					$this->load->model('sms_model');
					$this->load->library('sms');

					$this->sms->to($uid);
					$this->sms->message('您的参会申请已经通过审核，请登录 iPlacard 系统查看申请状态。');
					$this->sms->queue();
				}
				
				$this->ui->alert('参会申请已经通过。', 'success', true);
				
				$this->system_model->log('review_passed', array('delegate' => $uid));
				
				//无席位分配情况
				if($delegate['application_type'] != 'delegate' || !option('seat_enabled', true))
				{
					$fee = option("invoice_amount_{$delegate['application_type']}", 0);
					
					if($fee > 0)
					{
						$this->load->library('invoice');
					
						$this->delegate_model->change_status($uid, 'invoice_issued');

						//生成账单
						$this->invoice->title(option("invoice_title_fee_{$delegate['application_type']}", option('invoice_title_fee', '参会会费')));
						$this->invoice->to($uid);
						$this->invoice->item(option("invoice_title_fee_{$delegate['application_type']}", option('invoice_title_fee', '参会会费')), $fee, option("invoice_item_fee_{$delegate['application_type']}", option('invoice_item_fee', array())));
						$this->invoice->due_time(time() + option('invoice_due_fee', 15) * 24 * 60 * 60);

						$this->invoice->trigger('receive', 'change_status', array('delegate' => $uid, 'status' => 'locked'));
						$this->invoice->trigger('receive', 'notice_application_lock', array('delegate' => $uid));

						$this->invoice->generate();
					}
					else
					{
						$this->delegate_model->change_status($uid, 'locked');
				
						$this->delegate_model->add_event($uid, 'locked', array('admin' => uid()));
						
						$this->user_model->add_message($uid, '恭喜您！您的参会申请流程已经完成。');
						
						//发送邮件
						$this->load->library('email');
						$this->load->library('parser');
						$this->load->helper('date');

						$data = array(
							'id' => $uid,
							'name' => $delegate['name'],
							'time' => unix_to_human(time())
						);

						$this->email->to($delegate['email']);
						$this->email->subject('申请已经完成');
						$this->email->html($this->parser->parse_string(option('email_application_locked', '感谢参与申请，您的申请流程已经于 {time} 锁定完成，请登录 iPlacard 查看申请状态。'), $data, true));
						$this->email->send();

						//短信通知代表
						if(option('sms_enabled', false))
						{
							$this->load->model('sms_model');
							$this->load->library('sms');

							$this->sms->to($uid);
							$this->sms->message("感谢参与申请，您的申请流程已经完成，请登录 iPlacard 查看申请状态。");
							$this->sms->queue();
						}
					}
				}
				break;
			
			//拒绝通过申请
			case 'refuse_application':
				if($delegate['status'] != 'application_imported')
					break;
				
				$reason = $this->input->post('reason', true);
				
				$this->delegate_model->change_status($uid, 'review_refused');
				
				$this->delegate_model->add_event($uid, 'review_refused', array('reason' => $reason));
				
				$this->user_model->add_message($uid, '您的参会申请未能通过审核，感谢您的参与。');
				
				//邮件通知
				$this->load->library('email');
				$this->load->library('parser');
				$this->load->helper('date');
				
				$data = array(
					'uid' => $uid,
					'delegate' => $delegate['name'],
					'reason' => $reason,
					'time' => unix_to_human(time()),
				);
				
				$this->email->to($delegate['email']);
				$this->email->subject('参会申请审核未通过');
				
				if(empty($reason))
					$this->email->html($this->parser->parse_string(option('email_delegate_application_refused_no_reason', "很遗憾，您的参会申请未能通过审核，感谢您的参与。"), $data, true));
				else
					$this->email->html($this->parser->parse_string(option('email_delegate_application_refused', "很遗憾，您的参会申请由于以下原因：\n\n\t{reason}\n\n未能通过审核，感谢您的参与。"), $data, true));
				
				$this->email->send();
				
				//短信通知
				if(option('sms_enabled', false))
				{
					$this->load->model('sms_model');
					$this->load->library('sms');

					$this->sms->to($uid);
					$this->sms->message('您的参会申请未能通过审核，如有疑问请与我们联系，感谢您的参与。');
					$this->sms->queue();
				}
				
				$this->ui->alert('参会申请已经拒绝。', 'success', true);
				
				$this->system_model->log('review_refused', array('delegate' => $uid));
				break;
				
			//分配面试官
			case 'assign_interview':
				if($delegate['status'] != 'review_passed')
					break;
				
				$this->load->model('interview_model');
				
				$interviewer = $this->admin_model->get_admin($this->input->post('interviewer'));
				if(!$interviewer)
				{
					$this->ui->alert('面试官不存在。', 'warning', true);
					break;
				}
				if(!$this->admin_model->capable('interviewer', $interviewer['id']))
				{
					$this->ui->alert('指派的面试官没有对应面试权限。', 'warning', true);
					break;
				}
				$queue = $this->interview_model->get_interviewer_interviews($interviewer['id'], array('assigned', 'arranged'));
				
				//面试官是否已经面试过此代表
				if($this->interview_model->get_interview_id('status', array('completed', 'failed'), 'delegate', $uid, 'interviewer', $interviewer['id']))
				{
					$this->ui->alert('指派的面试官是已经面试过此代表。', 'warning', true);
						break;
				}
				
				$this->interview_model->assign_interview($uid, $interviewer['id']);
				
				$this->delegate_model->change_status($uid, 'interview_assigned');
				
				$this->delegate_model->add_event($uid, 'interview_assigned', array('interviewer' => $interviewer['id']));
				
				$this->user_model->add_message($uid, "我们已经为您分配了面试官，面试官{$interviewer['name']}将在近期内与您取得联系安排面试。");
				
				//邮件通知
				$this->load->library('email');
				$this->load->library('parser');
				$this->load->helper('date');
				
				$data = array(
					'uid' => $uid,
					'delegate' => $delegate['name'],
					'interviewer' => $interviewer['name'],
					'queue' => !$queue ? 1 : count($queue) + 1,
					'time' => unix_to_human(time())
				);
				
				//邮件通知代表
				$this->email->to($delegate['email']);
				$this->email->subject('已经为您分配面试官');
				$this->email->html($this->parser->parse_string(option('email_delegate_interview_assigned', "我们已经于 {time} 为您分配了面试官，面试官{interviewer}将会在近期内与您取得联系，您可登录 iPlacard 系统查看面试官联系方式及申请状态。"), $data, true));
				$this->email->send();
				
				//邮件通知面试官
				$this->email->to($interviewer['email']);
				$this->email->subject('新的面试安排');
				$this->email->html($this->parser->parse_string(option('email_interviewer_interview_assigned', "管理员已经于 {time} 安排您面试{delegate}代表。当前您的面试队列共 {queue} 人。"), $data, true));
				$this->email->send();
				
				//短信通知代表
				if(option('sms_enabled', false))
				{
					$this->load->model('sms_model');
					$this->load->library('sms');

					$this->sms->to($uid);
					$this->sms->message('我们已经为您分配了面试官，他将会在近期内与您取得联系，您可登录 iPlacard 系统查看面试官联系方式。');
					$this->sms->queue();
				}
				
				$this->ui->alert("已经指派{$interviewer['name']}面试此代表。", 'success', true);
				
				$this->system_model->log('interview_assigned', array('delegate' => $uid, 'interviewer' => $interviewer['id']));				
				break;
				
			//免试
			case 'exempt_interview':
				if($delegate['status'] != 'review_passed')
					break;
				
				$this->load->model('interview_model');
				
				$interviewer = $this->admin_model->get_admin($this->input->post('interviewer'));
				if(!$interviewer)
				{
					$this->ui->alert('面试官不存在。', 'warning', true);
					break;
				}
				if(!$this->admin_model->capable('interviewer', $interviewer['id']))
				{
					$this->ui->alert('指派的面试官没有对应面试权限。', 'warning', true);
					break;
				}
				
				$this->interview_model->assign_interview($uid, $interviewer['id'], true);
				
				$this->delegate_model->change_status($uid, 'interview_completed');
				
				$this->delegate_model->add_event($uid, 'interview_exempted');
				
				$this->user_model->add_message($uid, "您的参会申请符合免试条件，无需进行面试，面试官{$interviewer['name']}将在近期内为您分配席位。");
				
				//邮件通知
				$this->load->library('email');
				$this->load->library('parser');
				$this->load->helper('date');
				
				$data = array(
					'uid' => $uid,
					'delegate' => $delegate['name'],
					'interviewer' => $interviewer['name'],
					'time' => unix_to_human(time())
				);
				
				//邮件通知代表
				$this->email->to($delegate['email']);
				$this->email->subject('您已获得免试分配资格');
				$this->email->html($this->parser->parse_string(option('email_delegate_interview_exempted', "经过审核，您的参会申请符合免试分配条件，无需进行面试。我们已经于 {time} 安排面试官{interviewer}为您分配席位，请登录 iPlacard 系统查看申请状态。"), $data, true));
				$this->email->send();
				
				//邮件通知面试官
				$this->email->to($interviewer['email']);
				$this->email->subject('新的分配席位请求');
				$this->email->html($this->parser->parse_string(option('email_interviewer_interview_exempted', "管理员已经于 {time} 免试通过{delegate}代表的参会申请并安排您为其直接分配席位。"), $data, true));
				$this->email->send();
				
				//短信通知代表
				if(option('sms_enabled', false))
				{
					$this->load->model('sms_model');
					$this->load->library('sms');

					$this->sms->to($uid);
					$this->sms->message('您的参会申请符合免试分配条件，将有面试官直接为您分配席位，请登录 iPlacard 系统查看申请状态。');
					$this->sms->queue();
				}
				
				$this->ui->alert("已经通过免试分配并指派{$interviewer['name']}分配席位。", 'success', true);
				
				$this->system_model->log('interview_exempted', array('delegate' => $uid, 'interviewer' => $interviewer['id']));		
				break;
				
			//安排面试时间
			case 'arrange_interview':
				if($delegate['status'] != 'interview_assigned')
					break;
				
				$this->load->model('interview_model');
				
				$interview_id = $this->interview_model->get_current_interview_id($uid);
				if(!$interview_id)
				{
					$this->ui->alert('尝试安排的面试不存在。', 'danger', true);
					break;
				}
				
				$interview = $this->interview_model->get_interview($interview_id);
				if($interview['interviewer'] != uid())
				{
					$this->ui->alert('您不是此代表的面试官，因此无权安排此面试。', 'danger', true);
					break;
				}
				
				$time = strtotime($this->input->post('time'));
				if(empty($time))
				{
					$this->ui->alert('输入的时间格式有误。', 'danger', true);
					break;
				}
				
				$this->interview_model->arrange_time($interview['id'], $time);
				
				$this->delegate_model->change_status($uid, 'interview_arranged');
				
				$this->delegate_model->add_event($uid, 'interview_arranged', array('interview' => $interview['id'], 'time' => $time));
				
				$this->user_model->add_message($uid, sprintf("您的面试官已经将面试安排在 %s，届时我们将提前通知您准备面试。", date('Y-m-d H:i', $time)));
				
				//邮件通知
				$this->load->library('email');
				$this->load->library('parser');
				$this->load->helper('date');
				
				$data = array(
					'uid' => $uid,
					'delegate' => $delegate['name'],
					'interviewer' => $this->admin_model->get_admin($interview['interviewer'], 'name'),
					'schedule_time' => unix_to_human($time),
					'time' => unix_to_human(time())
				);
				
				$this->email->to($delegate['email']);
				$this->email->subject('已经安排面试时间');
				$this->email->html($this->parser->parse_string(option('email_delegate_interview_arranged', "您的面试官{interviewer}已经于 {time} 将面试安排于 {schedule_time} 进行，届时我们将提前通知您准备面试，请登录 iPlacard 系统查看申请状态。"), $data, true));
				$this->email->send();
				
				//短信通知代表
				if(option('sms_enabled', false))
				{
					$this->load->model('sms_model');
					$this->load->library('sms');

					$this->sms->to($uid);
					$this->sms->message(sprintf('您的面试官已经将面试安排于 %s 进行，请登录 iPlacard 系统查看申请状态。', $data['schedule_time']));
					$this->sms->queue();
				}
				
				$this->ui->alert("已经安排了与{$delegate['name']}代表的面试时间，届时我们将提前通知准备面试。", 'success', true);
				
				$this->system_model->log('interview_arranged', array('interview' => $interview['id'], 'time' => $time));
				break;
				
			//回退面试
			case 'rollback_interview':
				if($delegate['status'] != 'interview_assigned')
					break;
				
				$this->load->model('interview_model');
				
				$interview_id = $this->interview_model->get_current_interview_id($uid);
				if(!$interview_id)
				{
					$this->ui->alert('尝试回退的面试不存在。', 'danger', true);
					break;
				}
				
				$interview = $this->interview_model->get_interview($interview_id);
				if($interview['interviewer'] != uid())
				{
					$this->ui->alert('您不是此代表的面试官，因此无权回退此面试。', 'danger', true);
					break;
				}
				
				$this->interview_model->cancel_interview($interview['id']);
				
				$this->delegate_model->change_status($uid, 'review_passed');
				
				$this->delegate_model->add_event($uid, 'interview_rollbacked', array('interview' => $interview['id']));
				
				$this->user_model->add_message($uid, "您的面试官已经取消了面试安排，我们将会尽快为您分配新的面试官。");
				
				//邮件通知
				$this->load->library('email');
				$this->load->library('parser');
				$this->load->helper('date');
				
				$data = array(
					'uid' => $uid,
					'delegate' => $delegate['name'],
					'interviewer' => $this->admin_model->get_admin($interview['interviewer'], 'name'),
					'time' => unix_to_human(time())
				);
				
				$this->email->to($delegate['email']);
				$this->email->subject('面试已经取消');
				$this->email->html($this->parser->parse_string(option('email_delegate_interview_rollbacked', "您的面试官{interviewer}已经于 {time} 取消了面试安排，我们将会尽快为您分配新的面试官，请登录 iPlacard 系统查看申请状态。"), $data, true));
				$this->email->send();
				
				//短信通知代表
				if(option('sms_enabled', false))
				{
					$this->load->model('sms_model');
					$this->load->library('sms');

					$this->sms->to($uid);
					$this->sms->message('您的面试官已经取消了面试安排，我们将会尽快为您分配新的面试官，请登录 iPlacard 系统查看申请状态。');
					$this->sms->queue();
				}
				
				$this->ui->alert("已经回退了与{$delegate['name']}代表的面试安排。", 'success', true);
				
				$this->system_model->log('interview_rollbacked', array('interview' => $interview['id']));
				break;
					
			//取消面试
			case 'cancel_interview':
				if($delegate['status'] != 'interview_arranged')
					break;
				
				$this->load->model('interview_model');
				
				$interview_id = $this->interview_model->get_current_interview_id($uid);
				if(!$interview_id)
				{
					$this->ui->alert('尝试重新安排时间的面试不存在。', 'danger', true);
					break;
				}
				
				$interview = $this->interview_model->get_interview($interview_id);
				if($interview['interviewer'] != uid())
				{
					$this->ui->alert('您不是此代表的面试官，因此重新安排此面试的时间。', 'danger', true);
					break;
				}
				
				if($interview['status'] != 'arranged')
				{
					$this->ui->alert('尝试重新安排时间的面试尚未排定或者已经完成。', 'danger', true);
					break;
				}
				
				$this->interview_model->cancel_interview($interview['id']);
				
				$this->interview_model->assign_interview($uid, $interview['interviewer']);
				
				$this->delegate_model->change_status($uid, 'interview_assigned');
				
				$this->delegate_model->add_event($uid, 'interview_cancelled', array('interview' => $interview['id']));
				
				$this->user_model->add_message($uid, "您的面试官已经取消了面试安排，他将在近期内重新安排面试时间。");
				
				//邮件通知
				$this->load->library('email');
				$this->load->library('parser');
				$this->load->helper('date');
				
				$data = array(
					'uid' => $uid,
					'delegate' => $delegate['name'],
					'interviewer' => $this->admin_model->get_admin($interview['interviewer'], 'name'),
					'schedule_time' => unix_to_human($interview['schedule_time']),
					'time' => unix_to_human(time())
				);
				
				$this->email->to($delegate['email']);
				$this->email->subject('面试安排已经取消');
				$this->email->html($this->parser->parse_string(option('email_delegate_interview_cancelled', "您的面试官{interviewer}已经于 {time} 取消了原定 {schedule_time} 开始的面试安排，他将在近期内重新安排面试时间，请登录 iPlacard 系统查看申请状态。"), $data, true));
				$this->email->send();
				
				//短信通知代表
				if(option('sms_enabled', false))
				{
					$this->load->model('sms_model');
					$this->load->library('sms');

					$this->sms->to($uid);
					$this->sms->message('您的面试官已经取消了面试安排，他将在近期内重新安排面试时间，请登录 iPlacard 系统查看申请状态。');
					$this->sms->queue();
				}
				
				$this->ui->alert("已经取消了与{$delegate['name']}代表的面试安排。", 'success', true);
				
				$this->system_model->log('interview_cancelled', array('interview' => $interview['id']));
				break;
				
			//面试
			case 'interview':
				if($delegate['status'] != 'interview_arranged')
					break;
				
				$this->load->model('interview_model');
				
				$interview_id = $this->interview_model->get_current_interview_id($uid);
				if(!$interview_id)
				{
					$this->ui->alert('指定的面试不存在。', 'danger', true);
					break;
				}
				
				$interview = $this->interview_model->get_interview($interview_id);
				if($interview['interviewer'] != uid())
				{
					$this->ui->alert('您不是此代表的面试官，因此进行面试。', 'danger', true);
					break;
				}
				
				if($interview['status'] != 'arranged')
				{
					$this->ui->alert('面试尚未排定或者已经完成。', 'danger', true);
					break;
				}
				
				//代表面试阶段
				$secondary = $this->interview_model->is_secondary($delegate['id'], 'delegate');
				
				//是否通过
				if($this->input->post('pass'))
					$pass = true;
				else
					$pass = false;
				
				//是否需要复试
				if($this->input->post('retest'))
					$retest = true;
				else
					$retest = false;
				
				//计算总分
				$score = (float) 0;
				$score_all = array();
				$score_total = option('interview_score_total', 5);
				
				foreach(option('interview_score_standard', array('score' => array('weight' => 1))) as $sid => $one)
				{
					//存在性验证
					$score_one = $this->input->post("score_$sid");
					if(!empty($score_one))
						$score_one = intval($score_one);
					else
						$score_one = 0;
					
					//有效性验证
					if($score_one > $score_total)
						$score_one = $score_total;
					elseif($score_one < 0)
						$score_one = 0;
					
					$score += (float) $score_one * $one['weight'];
					$score_all[$sid] = $score_one;
				}
				
				//面试反馈
				$remark = $this->input->post('remark');
				$supplement = $this->input->post('supplement');
				
				if(empty($remark) && option('interview_feedback_required', true))
				{
					$this->ui->alert('面试反馈为空，无法提交。', 'danger', true);
					break;
				}
				
				if(empty($remark))
					$remark = NULL;
				
				if(empty($supplement))
					$supplement = NULL;
				
				$feedback = array(
					'score' => $score_all,
					'remark' => $remark,
					'supplement' => $supplement
				);
				
				//执行面试结果
				$this->interview_model->complete_interview($interview_id, $score, $pass, $feedback);
				
				//载入邮件通知
				$this->load->library('email');
				$this->load->library('parser');
				$this->load->helper('date');
				
				$data = array(
					'uid' => $uid,
					'delegate' => $delegate['name'],
					'interviewer' => $this->admin_model->get_admin($interview['interviewer'], 'name'),
					'remark' => $remark,
					'time' => unix_to_human(time())
				);
				
				if(!$pass)
				{
					$this->delegate_model->add_event($uid, 'interview_failed', array('interview' => $interview['id']));
					
					if(!$secondary)
					{
						$this->delegate_model->change_status($uid, 'review_passed');

						$this->user_model->add_message($uid, "您将需要进行二次面试，我们将在近期内为您重新分配面试官。");
						
						//邮件通知
						$this->email->to($delegate['email']);
						$this->email->subject('面试未通过');
						if(is_null($remark))
							$this->email->html($this->parser->parse_string(option('email_delegate_interview_failed', "您的面试官已经于 {time} 认定您未能通过面试，您将需要进行二次面试，我们将在近期内为您重新分配面试官，请登录 iPlacard 系统查看申请状态。"), $data, true));
						else
							$this->email->html($this->parser->parse_string(option('email_delegate_interview_failed_remark', "您的面试官已经于 {time} 认定您未能通过面试并给予如下评价：\n\n\t{remark}\n\n您将需要进行二次面试，我们将在近期内为您重新分配面试官，请登录 iPlacard 系统查看申请状态。"), $data, true));
						$this->email->send();

						//短信通知代表
						if(option('sms_enabled', false))
						{
							$this->load->model('sms_model');
							$this->load->library('sms');

							$this->sms->to($uid);
							$this->sms->message('您将需要进行二次面试，我们将在近期内为您重新分配面试官，请登录 iPlacard 系统查看申请状态。');
							$this->sms->queue();
						}
						
						$this->ui->alert("已经通知{$delegate['name']}代表的准备二次面试。", 'success', true);
					}
					else
					{
						$this->delegate_model->change_status($uid, 'waitlist_entered');
						
						$this->delegate_model->add_event($uid, 'waitlist_entered');

						$this->user_model->add_message($uid, "很遗憾，您未能通过二次面试。您的申请已经移入等待队列。");
						
						//邮件通知
						$this->email->to($delegate['email']);
						$this->email->subject('二次面试未通过');
						if(is_null($remark))
							$this->email->html($this->parser->parse_string(option('email_delegate_interview_failed_2nd', "您的面试官已经于 {time} 认定您未能通过二次面试。您的申请已经移入等待队列，当席位出现空缺时，我们将会为您分配席位，请登录 iPlacard 系统查看申请状态。"), $data, true));
						else
							$this->email->html($this->parser->parse_string(option('email_delegate_interview_failed_2nd_remark', "您的面试官已经于 {time} 认定您未能通过二次面试并给予如下评价：\n\n\t{remark}\n\n您的申请已经移入等待队列，当席位出现空缺时，我们将会为您分配席位，请登录 iPlacard 系统查看申请状态。"), $data, true));
						$this->email->send();

						//短信通知代表
						if(option('sms_enabled', false))
						{
							$this->load->model('sms_model');
							$this->load->library('sms');

							$this->sms->to($uid);
							$this->sms->message('很遗憾您未能通过二次面试，您的申请已经移入等待队列，请登录 iPlacard 系统查看申请状态。');
							$this->sms->queue();
						}
						
						$this->ui->alert("已经将{$delegate['name']}代表移动至等待队列。", 'success', true);
					}
				}
				else
				{
					$this->delegate_model->add_event($uid, 'interview_passed', array('interview' => $interview['id']));
					
					if(!$retest)
					{
						$this->delegate_model->change_status($uid, 'interview_completed');
					
						$this->user_model->add_message($uid, "您已通过面试，我们将在近期内为您分配席位选择。");
						
						//邮件通知
						$this->email->to($delegate['email']);
						$this->email->subject('面试通过');
						if(is_null($remark))
							$this->email->html($this->parser->parse_string(option('email_delegate_interview_passed', "您的面试官已经于 {time} 认定您成功通过面试，我们将在近期内为您分配席位选择，请登录 iPlacard 系统查看申请状态。"), $data, true));
						else
							$this->email->html($this->parser->parse_string(option('email_delegate_interview_passed_remark', "您的面试官已经于 {time} 认定您成功通过面试并给予如下评价：\n\n\t{remark}\n\n我们将在近期内为您分配席位选择，请登录 iPlacard 系统查看申请状态。"), $data, true));
						$this->email->send();

						//短信通知代表
						if(option('sms_enabled', false))
						{
							$this->load->model('sms_model');
							$this->load->library('sms');

							$this->sms->to($uid);
							$this->sms->message('您已成功通过面试，我们将在近期内为您分配席位选择，请登录 iPlacard 系统查看申请状态。');
							$this->sms->queue();
						}

						$this->ui->alert("已经通过{$delegate['name']}代表的面试。", 'success', true);
					}
					else
					{
						$this->delegate_model->change_status($uid, 'review_passed');
						
						$this->user_model->add_message($uid, "您已通过面试，我们将在近期内为您分配复试面试官。");
						
						//邮件通知
						$this->email->to($delegate['email']);
						$this->email->subject('面试通过等待复试分配');
						if(is_null($remark))
							$this->email->html($this->parser->parse_string(option('email_delegate_interview_passed', "您的面试官已经于 {time} 认定您成功通过面试，我们将在近期内为您分配复试面试官，请登录 iPlacard 系统查看申请状态。"), $data, true));
						else
							$this->email->html($this->parser->parse_string(option('email_delegate_interview_passed_remark', "您的面试官已经于 {time} 认定您成功通过面试并给予如下评价：\n\n\t{remark}\n\n我们将在近期内为您分配复试面试官，请登录 iPlacard 系统查看申请状态。"), $data, true));
						$this->email->send();

						//短信通知代表
						if(option('sms_enabled', false))
						{
							$this->load->model('sms_model');
							$this->load->library('sms');

							$this->sms->to($uid);
							$this->sms->message('您已成功通过面试，我们将在近期内为您分配复试面试官，请登录 iPlacard 系统查看申请状态。');
							$this->sms->queue();
						}

						$this->ui->alert("已经通过{$delegate['name']}代表的面试并要求增加复试。", 'success', true);
					}
				}
				
				$this->ui->alert("已经录入面试成绩。", 'success', true);
				
				$this->system_model->log('interview_completed', array('interview' => $interview['id'], 'pass' => $pass));
				break;
			
			//关闭复试请求
			case 'deny_retest':
				if($delegate['status'] != 'review_passed')
					break;
				
				$this->load->model('interview_model');
				
				//是否可关闭
				$current_id = $this->interview_model->get_current_interview_id($uid);
				if(!$current_id)
					break;
				
				$interview = $this->interview_model->get_interview($current_id);
				if(!in_array($interview['status'], array('completed', 'exempted', 'failed')))
				{
					$this->ui->alert('当前情况下无法变更面试状态。', 'danger', true);
					break;
				}
				
				$interviewer = $this->user_model->get_user($interview['interviewer']);
				
				$this->delegate_model->change_status($uid, 'interview_completed');
				
				$this->user_model->add_message($uid, "您将无需复试，面试官将会直接为您分配席位。");
				
				//邮件通知
				$this->load->library('email');
				$this->load->library('parser');
				$this->load->helper('date');
				
				$data = array(
					'uid' => $uid,
					'delegate' => $delegate['name'],
					'interviewer' => $interviewer['name'],
					'time' => unix_to_human(time())
				);
				
				//邮件通知代表
				$this->email->to($delegate['email']);
				$this->email->subject('您将被直接分配席位');
				$this->email->html($this->parser->parse_string(option('email_delegate_retest_denied', "我们已经于 {time} 确定您将无需复试，面试官将会直接为您分配席位，请登录 iPlacard 系统查看申请状态。"), $data, true));
				$this->email->send();
				
				//邮件通知面试官
				$this->email->to($interviewer['email']);
				$this->email->subject('复试请求被拒绝');
				$this->email->html($this->parser->parse_string(option('email_interviewer_retest_denied', "管理员已经于 {time} 拒绝了您对{delegate}代表的复试请求，请登录 iPlacard 系统直接为{delegate}代表分配席位。"), $data, true));
				$this->email->send();
				
				//短信通知代表
				if(option('sms_enabled', false))
				{
					$this->load->model('sms_model');
					$this->load->library('sms');

					$this->sms->to($uid);
					$this->sms->message('我们已经确定您将无需复试，面试官将会直接为您分配席位，请登录 iPlacard 系统查看申请状态。');
					$this->sms->queue();
				}
				
				$this->ui->alert("已经拒绝了{$interviewer['name']}面试官的复试请求。", 'success', true);
				
				$this->system_model->log('interview_retest_denied', array('delegate' => $uid, 'interviewer' => $interviewer['id']));				
				break;
				
			//分配席位
			case 'assign_seat':
				$this->load->model('seat_model');
				
				//全局席位分配权限
				$global_admin = false;
				if(in_array(uid(), option('seat_global_admin', array())))
					$global_admin = true;
				
				//席位分配模式
				$seat_mode = option('seat_mode', 'select');
				
				if($delegate['application_type'] != 'delegate' || !$this->_check_seat_select_open($delegate))
				{
					$this->ui->alert('代表不在席位分配阶段，无法分配席位。', 'danger', true);
					break;
				}
				
				if($this->_check_interview_enabled($delegate['application_type']))
				{
					$this->load->model('interview_model');
					
					if($seat_mode == 'select')
					{
						if(!$global_admin && !$this->_check_interviewer_assign_right($uid, uid()))
						{
							$this->ui->alert('您没有面试过此代表，无法分配席位。', 'danger', true);
							break;
						}
					}
					else
					{
						$interview_id = $this->interview_model->get_current_interview_id($uid);
						if(!$interview_id)
						{
							$this->ui->alert('当前代表尚无面试，无法分配席位。', 'danger', true);
							break;
						}
						
						$interview = $this->interview_model->get_interview($interview_id);
						if(!$global_admin && $interview['interviewer'] != uid())
						{
							$this->ui->alert('您不是此代表的面试官，无法分配席位。', 'danger', true);
							break;
						}
					}
					
					$admin_committee = $this->admin_model->get_admin(uid(), 'committee');
				}
				else
				{
					if(!$global_admin && !$this->admin_model->capable('reviewer'))
					{
						$this->ui->alert('您无权分配席位。', 'danger', true);
						break;
					}
				}
				
				if($seat_mode == 'select')
				{
					$new_seat = $this->input->post('seat_open');
					if(empty($new_seat))
					{
						$this->ui->alert('没有任何席位被分配开放。', 'warning', true);
						break;
					}

					$new_recommended = $this->input->post('recommended');
					if(empty($new_recommended))
						$new_recommended = array();

					//已经开放许可
					$existing = $this->seat_model->get_delegate_selectability($delegate['id'], false, 'seat');
					if(!$existing)
						$existing = array();
					
					//添加席位选择许可
					$new_selectability = array();
					if(isset($new_seat) && !empty($new_seat))
					{
						foreach($new_seat as $sid)
						{
							$seat = $this->seat_model->get_seat($sid);
							if(!in_array($sid, $existing) && ($seat['status'] != 'preserved' || $seat['committee'] == $admin_committee))
							{
								$recommended = false;
								if(in_array($sid, $new_recommended))
									$recommended = true;

								$new_selectability[] = $this->seat_model->grant_selectability($sid, $delegate['id'], uid(), $recommended);
							}
							else
								$this->ui->alert("无法分配席位{$seat['name']}，该席位已经分配或无权分配。", 'warning', true);
						}
					}

					if(!empty($new_selectability))
					{
						//邮件通知
						$this->load->library('email');
						$this->load->library('parser');
						$this->load->helper('date');

						$data = array(
							'uid' => $uid,
							'delegate' => $delegate['name'],
							'count' => count($new_selectability),
							'time' => unix_to_human(time())
						);

						//全新添加
						if(empty($existing))
						{
							$this->delegate_model->change_status($uid, 'seat_assigned');

							$this->delegate_model->add_event($uid, 'seat_added', array('selectability' => $new_selectability, 'new' => true));

							$this->user_model->add_message($uid, "我们已经为您分配了席位，请尽快选择您的席位。");

							$this->email->to($delegate['email']);
							$this->email->subject('席位已分配');
							$this->email->html($this->parser->parse_string(option('email_delegate_seat_added', "我们已经向您分配了总计 {count} 个席位选项，您将可以从中选择 1 个席位为您的席位，请尽快登录 iPlacard 系统选择您的席位。"), $data, true));
							$this->email->send();

							//短信通知代表
							if(option('sms_enabled', false))
							{
								$this->load->model('sms_model');
								$this->load->library('sms');

								$this->sms->to($uid);
								$this->sms->message('我们已经为您分配了席位，请尽快登录 iPlacard 系统选择您的席位。');
								$this->sms->queue();
							}
						}
						else
						{
							$this->delegate_model->add_event($uid, 'seat_added', array('selectability' => $new_selectability, 'new' => false));

							$this->user_model->add_message($uid, "我们已经为您新增了席位分配，您可调整您的席位选择。");

							$this->email->to($delegate['email']);
							$this->email->subject('席位分配已追加');
							$this->email->html($this->parser->parse_string(option('email_delegate_seat_appended', "我们已经为您追加分配了 {count} 个席位，您可以登录 iPlacard 系统调整您的席位设置。"), $data, true));
							$this->email->send();

							//短信通知代表
							if(option('sms_enabled', false))
							{
								$this->load->model('sms_model');
								$this->load->library('sms');

								$this->sms->to($uid);
								$this->sms->message('我们已经为您追加分配了席位，您可以登录 iPlacard 系统调整您的席位设置。');
								$this->sms->queue();
							}
						}

						$this->ui->alert("已经开放选定的席位分配。", 'success', true);

						$this->system_model->log('seat_added', array('selectability' => $new_selectability, 'delegate' => $delegate['id']));
					}
					else
						$this->ui->alert('没有任何席位被分配开放。', 'warning', true);
				}
				else
				{
					$new_seat_id = $this->input->post('assign_id');
					if(empty($new_seat_id))
					{
						$this->ui->alert('未选中席位。', 'warning', true);
						break;
					}
					
					$new_seat = $this->seat_model->get_seat($new_seat_id);
					if($new_seat['status'] == 'preserved' && $this->_check_interview_enabled($delegate['application_type']) && $new_seat['committee'] != $admin_committee)
					{
						$this->ui->alert('席位被保留，只有该委员会面试官可分配此席位。', 'warning', true);
						break;
					}
					
					if($new_seat['status'] == 'assigned' || $new_seat['status'] == 'approved')
					{
						$this->ui->alert('席位已经被分配，无法再次分配。', 'warning', true);
						break;
					}
					
					if($new_seat['status'] == 'unavailable' || $new_seat['status'] == 'locked')
					{
						$this->ui->alert('席位被锁定或无法分配。', 'warning', true);
						break;
					}
					
					$original_seat_id = $this->seat_model->get_delegate_seat($delegate['id']);
					$re_assign = $original_seat_id ? true : false;
					
					if($re_assign && $original_seat_id == $new_seat_id)
					{
						$this->ui->alert('新分配席位和代表原席位相同，无需重复分配。', 'info', true);
						break;
					}
					
					//初次分配
					if(!$re_assign)
					{
						if(option("invoice_amount_{$delegate['application_type']}", 0) > 0)
						{
							$this->load->library('invoice');
							
							$this->delegate_model->change_status($uid, 'invoice_issued');
							
							//生成账单
							$this->invoice->title(option('invoice_title_fee_delegate', option('invoice_title_fee', '参会会费')));
							$this->invoice->to($uid);
							$this->invoice->item(option('invoice_title_fee_delegate', option('invoice_title_fee', '参会会费')), option('invoice_amount_delegate', 1000), option('invoice_item_fee_delegate', option('invoice_item_fee', array())));
							$this->invoice->due_time(time() + option('invoice_due_fee', 15) * 24 * 60 * 60);
							
							$this->invoice->trigger('overdue', 'release_seat', array('delegate' => $uid));
							$this->invoice->trigger('receive', 'change_status', array('delegate' => $uid, 'status' => 'payment_received'));
							
							$this->invoice->generate();
						}
						else
						{
							$this->delegate_model->change_status($uid, 'seat_assigned');
						}
					}
					else
					{
						//回退原席位
						$this->seat_model->change_seat_status($original_seat_id, 'available', NULL);
						$this->seat_model->assign_seat($original_seat_id, NULL);
					}
					
					//分配席位
					$this->seat_model->change_seat_status($new_seat_id, 'assigned', true);
					$this->seat_model->assign_seat($new_seat_id, $delegate['id']);

					$this->delegate_model->add_event($uid, 'seat_assigned', array('seat' => $new_seat_id, 'new' => !$re_assign));

					$this->user_model->add_message($uid, !$re_assign ? "我们已经为您分配了席位。" : "我们已经为您调整了席位。");
					
					//邮件通知
					$this->load->library('email');
					$this->load->library('parser');
					$this->load->helper('date');

					$data = array(
						'uid' => $uid,
						'delegate' => $delegate['name'],
						'seat' => $new_seat['name'],
						'time' => unix_to_human(time())
					);

					$this->email->to($delegate['email']);
					$this->email->subject(!$re_assign ? '席位已分配' : '席位已调整');
					
					if(!$re_assign)
						$this->email->html($this->parser->parse_string(option('email_delegate_seat_assigned', "我们已经于 {time} 为您分配了席位，请登录 iPlacard 系统查看席位信息。"), $data, true));
					else
						$this->email->html($this->parser->parse_string(option('email_delegate_seat_reassigned', "我们已经于 {time} 为您调整了席位，请登录 iPlacard 系统查看席位信息。"), $data, true));
					
					$this->email->send();

					//短信通知代表
					if(option('sms_enabled', false))
					{
						$this->load->model('sms_model');
						$this->load->library('sms');

						$this->sms->to($uid);
						$this->sms->message(!$re_assign ? '我们已经为您分配了席位，请登录 iPlacard 系统查看席位信息。' : '我们已经为您调整了席位，请登录 iPlacard 系统查看席位信息。');
						$this->sms->queue();
					}

					$this->ui->alert("已经向代表分配了席位。", 'success', true);

					$this->system_model->log('seat_assigned', array('seat' => $new_seat_id, 'new' => !$re_assign, 'delegate' => $delegate['id']));
				}
				
				break;
			
			//移出等待队列
			case 'accept_waitlist':
				if($delegate['status'] != 'waitlist_entered')
					break;
				
				$this->load->model('interview_model');
				
				$interviewer = $this->admin_model->get_admin($this->input->post('interviewer'));
				if(!$interviewer)
				{
					$this->ui->alert('面试官不存在。', 'warning', true);
					break;
				}
				if(!$this->admin_model->capable('interviewer', $interviewer['id']))
				{
					$this->ui->alert('指派的面试官没有对应面试权限。', 'warning', true);
					break;
				}
				
				$this->interview_model->assign_interview($uid, $interviewer['id'], true);
				
				$this->delegate_model->change_status($uid, 'interview_completed');
				
				$this->delegate_model->add_event($uid, 'waitlist_accepted');
				
				$this->user_model->add_message($uid, "我们已将您从等待队列中移出，面试官{$interviewer['name']}将在近期内为您分配席位。");
				
				//邮件通知
				$this->load->library('email');
				$this->load->library('parser');
				$this->load->helper('date');
				
				$data = array(
					'uid' => $uid,
					'delegate' => $delegate['name'],
					'interviewer' => $interviewer['name'],
					'time' => unix_to_human(time())
				);
				
				//邮件通知代表
				$this->email->to($delegate['email']);
				$this->email->subject('等待队列通过通知');
				$this->email->html($this->parser->parse_string(option('email_delegate_waitlist_accepted', "我们已经于 {time} 将您从等待队列中移出，面试官{interviewer}将为您分配席位，请登录 iPlacard 系统查看申请状态。"), $data, true));
				$this->email->send();
				
				//邮件通知面试官
				$this->email->to($interviewer['email']);
				$this->email->subject('新的分配席位请求');
				$this->email->html($this->parser->parse_string(option('email_interviewer_waitlist_accepted', "管理员已经于 {time} 将{delegate}代表从等待队列中移出并安排您为其分配席位。"), $data, true));
				$this->email->send();
				
				//短信通知代表
				if(option('sms_enabled', false))
				{
					$this->load->model('sms_model');
					$this->load->library('sms');
					
					$this->sms->to($uid);
					$this->sms->message('您已通过等待队列，将有面试官直接为您分配席位，请登录 iPlacard 系统查看申请状态。');
					$this->sms->queue();
				}
				
				$this->ui->alert("已经将代表从等待队列中移除并安排{$interviewer['name']}为其分配席位。", 'success', true);
				
				$this->system_model->log('waitlist_accepted', array('delegate' => $uid, 'interviewer' => $interviewer['id']));
				break;
				
			//重发欢迎邮件
			case 'resend_email':
				//停用检查
				if(!$delegate['enabled'])
				{
					$this->ui->alert('用户帐户已经停用，代表已无法登录，无法重发欢迎邮件。', 'warning', true);
					break;
				}
				
				//重置密码
				$reset = false;
				if($this->input->post('reset'))
				{
					$reset = true;
					
					//生成随机密码
					$this->load->helper('string');
					$password = random_string('alnum', 8);
					
					//更新密码
					$this->user_model->change_password($uid, $password);
					
					$this->user_model->delete_user_option('account_change_password_time', $uid);
				}
				
				//导入时间
				$time = false;
				
				$event = $this->delegate_model->get_event_id('delegate', $uid, 'event', 'application_imported');
				if($event)
					$time = $this->delegate_model->get_event($event, 'time');
				
				//发送邮件
				$this->load->library('email');
				$this->load->library('parser');
				$this->load->helper('date');

				$data = array(
					'uid' => $uid,
					'name' => $delegate['name'],
					'email' => $delegate['email'],
					'password' => $reset ? $password : '<i>（您原先使用的密码）</i>',
					'time' => unix_to_human($time ? $time : $delegate['reg_time']),
					'url' => base_url(),
				);

				$this->email->to($delegate['email']);
				$this->email->subject('iPlacard 帐户登录信息');
				$this->email->html($this->parser->parse_string(option('email_delegate_account_created', "您的参会申请已经导入 iPlacard 系统并开始审核。您的 iPlacard 帐户已经于 {time} 创建。帐户信息如下：\n\n"
						. "\t登录邮箱：{email}\n"
						. "\t密码：{password}\n\n"
						. "请使用以上信息访问：\n\n"
						. "\t{url}\n\n"
						. "登录并开始通过 iPlacard 了解您的申请进度。"), $data, true));

				if(!$this->email->send())
				{
					$this->system_model->log('notice_failed', array('id' => $uid, 'type' => 'email', 'content' => 'delegate_account_created'));
				}
				
				$this->ui->alert("已经向代表重新发送了欢迎邮件。", 'success', true);
				
				$this->system_model->log('welcome_email_resent', array('delegate' => $uid, 'reset' => $reset));
				break;
				
			//更换申请类型
			case 'change_type':
				$this->load->model('seat_model');
				$this->load->model('interview_model');
				
				//已经删除检查
				if($delegate['status'] == 'deleted')
				{
					$this->ui->alert('用户帐户已被计划删除，无法更换申请类型。', 'warning', true);
					break;
				}
				
				//更换原因
				$reason = $this->input->post('reason');
				if(empty($reason))
				{
					$this->ui->alert('更换原因为空，更换操作未执行。', 'warning', true);
					break;
				}
				
				//更换类型
				$type = $this->input->post('type');
				if(empty($type) || !in_array($type, array('delegate', 'observer', 'volunteer', 'teacher')))
				{
					$this->ui->alert('更换类型有误，更换操作未执行。', 'warning', true);
					break;
				}
				elseif($type == $delegate['application_type'])
				{
					$this->ui->alert('申请类型没有变化，更换操作未执行。', 'warning', true);
					break;
				}
				
				//取消账单
				$cancel_invoice = false;
				if($this->input->post('cancel_invoice'))
				{
					$this->load->model('invoice_model');
					$this->load->library('invoice');
					
					$cancel_invoice = true;
					
					$invoice_ids = $this->invoice_model->get_delegate_invoices($uid, true);
					if($invoice_ids)
					{
						foreach($invoice_ids as $invoice_id)
						{
							$this->invoice->load($invoice_id);
							$this->invoice->cancel(uid());
						}

						$this->user_model->edit_user_option('typechange_affected_invoice', $invoice_ids, $uid);
					}
				}
				
				//取消未完成面试
				$interview_id = $this->interview_model->get_current_interview_id($uid);
				if($interview_id)
				{
					$interview = $this->interview_model->get_interview($interview_id);
					if(!in_array($interview['status'], array('completed', 'exempted', 'cancelled', 'failed')))
					{
						$this->interview_model->cancel_interview($interview['id']);
						$this->delegate_model->add_event($uid, 'interview_cancelled', array('interview' => $interview['id'], 'typechange' => true));
					}
					
					$this->user_model->edit_user_option('typechange_affected_interview', $interview_id, $uid);
				}
				
				//释放席位
				$seat_id = $this->seat_model->get_delegate_seat($uid);
				if($seat_id)
				{
					$this->seat_model->change_seat_status($seat_id, 'available', NULL);
					$this->seat_model->assign_seat($seat_id, NULL);
					
					$this->delegate_model->add_event($uid, 'seat_cancelled', array('seat' => $seat_id));
					
					$this->user_model->edit_user_option('typechange_affected_seat', $seat_id, $uid);
				}
				
				//操作更换申请类型
				$this->delegate_model->edit_delegate(array('application_type' => $type), $uid);
				$this->delegate_model->change_status($uid, 'application_imported');
				$this->delegate_model->add_event($uid, 'type_changed', array('reason' => $reason, 'old' => $delegate['application_type'], 'new' => $type));
				
				$this->user_model->add_message($uid, "您的申请类型已经更换。");
				
				//发送邮件
				$this->load->library('email');
				$this->load->library('parser');
				$this->load->helper('date');

				$data = array(
					'uid' => $uid,
					'name' => $delegate['name'],
					'email' => $delegate['email'],
					'old_type' => $this->delegate_model->application_type_text($delegate['type']),
					'new_type' => $this->delegate_model->application_type_text($type),
					'reason' => $reason,
					'time' => unix_to_human(time())
				);

				$this->email->to($delegate['email']);
				$this->email->subject('申请类型已更换');
				$this->email->html($this->parser->parse_string(option('email_delegate_type_changed', "您的参会申请类型已经于 {time} 因以下原因：\n\n"
						. "\t{reason}\n\n"
						. "由{old_type}变更为{new_type}。请立即登录 iPlacard 查看更换后的申请进度和流程。"), $data, true));
				$this->email->send();
				
				//短信通知代表
				if(option('sms_enabled', false))
				{
					$this->load->model('sms_model');
					$this->load->library('sms');

					$this->sms->to($uid);
					$this->sms->message("您的参会申请类型已经由{$data['old_type']}更换为{$data['new_type']}，请立即登录 iPlacard 查看更换后的申请进度和流程。");
					$this->sms->queue();
				}
				
				$this->ui->alert("已经更换代表申请类型。", 'success', true);
				
				$this->system_model->log('application_type_changed', array('reason' => $reason, 'old' => $delegate['application_type'], 'new' => $type, 'cancel_invoice' => $cancel_invoice));
				break;
			
			//退会
			case 'quit':
				$this->load->model('seat_model');
				$this->load->model('interview_model');
				$this->load->model('invoice_model');
				$this->load->library('invoice');
				
				if($delegate['status'] == 'quitted')
				{
					$this->ui->alert('代表已经退会。', 'danger', true);
					break;
				}
				
				//退会原因
				$reason = $this->input->post('reason');
				if(empty($reason))
				{
					$this->ui->alert('退会原因为空，退会操作未执行。', 'warning', true);
					break;
				}
				
				$lock_time = option('delegate_quit_lock', 7);
				
				//取消未完成面试
				$interview_id = $this->interview_model->get_current_interview_id($uid);
				if($interview_id)
				{
					$interview = $this->interview_model->get_interview($interview_id);
					if(!in_array($interview['status'], array('completed', 'exempted', 'cancelled', 'failed')))
					{
						$this->interview_model->cancel_interview($interview['id']);
						$this->delegate_model->add_event($uid, 'interview_cancelled', array('interview' => $interview['id'], 'quit' => true));
					}
					
					$this->user_model->edit_user_option('quit_affected_interview', $interview_id, $uid);
				}
				
				//释放席位
				$seat_id = $this->seat_model->get_delegate_seat($uid);
				if($seat_id)
				{
					$this->seat_model->change_seat_status($seat_id, 'available', NULL);
					$this->seat_model->assign_seat($seat_id, NULL);
					
					$this->delegate_model->add_event($uid, 'seat_cancelled', array('seat' => $seat_id));
					
					$this->user_model->edit_user_option('quit_affected_seat', $seat_id, $uid);
				}
				
				//取消账单
				$invoice_ids = $this->invoice_model->get_delegate_invoices($uid, true);
				if($invoice_ids)
				{
					foreach($invoice_ids as $invoice_id)
					{
						$this->invoice->load($invoice_id);
						$this->invoice->cancel(uid());
					}
					
					$this->user_model->edit_user_option('quit_affected_invoice', $invoice_ids, $uid);
				}
				
				//操作退会
				$this->delegate_model->change_status($uid, 'quitted');
				
				$this->user_model->edit_user_option('quit_status', $delegate['status'], $uid);
				$this->user_model->edit_user_option('quit_time', time(), $uid);
				$this->user_model->edit_user_option('quit_operator', uid(), $uid);
				$this->user_model->edit_user_option('quit_reason', $reason, $uid);
				
				$this->delegate_model->add_event($uid, 'quitted', array('reason' => $reason));
				
				$this->user_model->add_message($uid, "您已退会，您的帐户数据即将被删除，如有任何疑问请立即与管理员联系。");
				
				//邮件通知
				$this->load->library('email');
				$this->load->library('parser');
				$this->load->helper('date');
				
				$data = array(
					'uid' => $uid,
					'delegate' => $delegate['name'],
					'lock_period' => $lock_time,
					'lock_time' => unix_to_human(time() + $lock_time * 24 * 60 * 60),
					'time' => unix_to_human(time())
				);
				
				$this->email->to($delegate['email']);
				$this->email->subject('您已退会');
				$this->email->html($this->parser->parse_string(option('email_delegate_quitted', "您已于 {time} 退会。\n\n"
						. "您的 iPlacard 帐户将于 {lock_time}（{lock_period} 天内）关闭，请立即登录 iPlacard 查看详情。如果这是管理员的误操作请立即联系管理员恢复帐户。"), $data, true));
				$this->email->send();
				
				//短信通知代表
				if(option('sms_enabled', false))
				{
					$this->load->model('sms_model');
					$this->load->library('sms');

					$this->sms->to($uid);
					$this->sms->message('您已退会，请立即登录 iPlacard 查看详情。如果这是管理员的误操作请立即联系管理员恢复帐户。');
					$this->sms->queue();
				}
				
				$this->ui->alert("已经操作{$delegate['name']}代表退会。", 'success', true);
				
				$this->system_model->log('delegate_quitted', array('delegate' => $uid));
				break;
			
			//取消退会
			case 'unquit':
				$old = user_option('quit_status', 'application_imported', $uid);
				
				//确定新申请状态
				$status = 'application_imported';
				switch($old)
				{
					case 'application_imported':
					case 'review_passed':
					case 'review_refused':
					case 'interview_completed':
					case 'waitlist_entered':
					case 'seat_assigned':
						$status = $old;
						break;
					case 'interview_assigned':
					case 'interview_arranged':
						$status = 'review_passed';
						break;
					case 'seat_selected':
					case 'invoice_issued':
					case 'payment_received':
					case 'locked':
						$status = 'seat_assigned';
				}
				
				$this->delegate_model->change_status($uid, $status);
				
				$this->user_model->delete_user_option('quit_status', $uid);
				$this->user_model->delete_user_option('quit_time', $uid);
				$this->user_model->delete_user_option('quit_operator', $uid);
				$this->user_model->delete_user_option('quit_reason', $uid);
				
				$this->delegate_model->add_event($uid, 'unquitted');
				
				//邮件通知
				$this->load->library('email');
				$this->load->library('parser');
				$this->load->helper('date');
				
				$data = array(
					'uid' => $uid,
					'delegate' => $delegate['name'],
					'time' => unix_to_human(time())
				);
				
				$this->email->to($delegate['email']);
				$this->email->subject('您已取消退会');
				$this->email->html($this->parser->parse_string(option('email_delegate_unquitted', "管理员已经于 {time} 取消了您的退会状态，请登录 iPlacard 系统查看申请状态。"), $data, true));
				$this->email->send();
				
				//短信通知代表
				if(option('sms_enabled', false))
				{
					$this->load->model('sms_model');
					$this->load->library('sms');
					
					$this->sms->to($uid);
					$this->sms->message('管理员已经取消了您的退会状态，您的申请将会继续，请登录 iPlacard 系统查看申请状态。');
					$this->sms->queue();
				}
				
				$this->ui->alert("已经取消{$delegate['name']}代表的退会状态。", 'success', true);
				
				$this->system_model->log('delegate_unquitted', array('delegate' => $uid));
				break;
				
			//永久删除用户帐户
			case 'delete_account':
				//密码验证
				$admin_password = $this->input->post('password');
				if(empty($admin_password) || !$this->user_model->check_password(uid(), $admin_password))
				{
					$this->ui->alert('密码验证错误，删除操作未执行。', 'warning', true);
					break;
				}
				
				//删除原因
				$reason = $this->input->post('reason');
				if(empty($reason))
				{
					$this->ui->alert('删除原因为空，删除操作未执行。', 'warning', true);
					break;
				}
				
				$lock_time = option('delegate_delete_lock', 7);
				
				//操作删除
				$this->delegate_model->change_status($uid, 'deleted');
				
				$this->user_model->edit_user_option('delete_status', $delegate['status'], $uid);
				$this->user_model->edit_user_option('delete_time', time(), $uid);
				$this->user_model->edit_user_option('delete_operator', uid(), $uid);
				$this->user_model->edit_user_option('delete_reason', $reason, $uid);
				
				$this->delegate_model->add_event($uid, 'deleted', array('reason' => $reason));
				
				//邮件通知
				$this->load->library('email');
				$this->load->library('parser');
				$this->load->helper('date');
				
				$data = array(
					'uid' => $uid,
					'delegate' => $delegate['name'],
					'lock_period' => $lock_time,
					'lock_time' => unix_to_human(time() + $lock_time * 24 * 60 * 60),
					'reason' => $reason,
					'ip' => $this->input->ip_address(),
					'time' => unix_to_human(time())
				);
				
				//通知管理员
				$this->email->to($this->user_model->get_user(uid(), 'email'));
				$this->email->subject('您删除了一个 iPlacard 帐户');
				$this->email->html($this->parser->parse_string(option('email_admin_delegate_deleted', "您已经于 {time} 由 IP {ip} 删除了{delegate}代表的 iPlacard 帐户。删除帐户原因是：\n\n"
						. "\t{reason}\n\n"
						. "如为误操作请立即登录 iPlacard 恢复代表帐户。如非本人操作请立即修改密码。"), $data, true));
				$this->email->send();
				
				$this->email->clear();
				
				//通知代表
				$this->email->to($delegate['email']);
				$this->email->subject('您的 iPlacard 帐户将被删除');
				$this->email->html($this->parser->parse_string(option('email_delegate_deleted', "管理员已经于 {time} 停用了您的 iPlacard 帐户。您的 iPlacard 帐户将于 {lock_time}（{lock_period} 天内）删除。请立即联系管理员了解情况。"), $data, true));
				$this->email->send();
				
				//短信通知代表
				if(option('sms_enabled', false))
				{
					$this->load->model('sms_model');
					$this->load->library('sms');

					$this->sms->to($uid);
					$this->sms->message('您的帐户将被删除，请立即联系管理员了解情况。');
					$this->sms->queue();
				}
				
				$this->ui->alert("已经操作计划删除{$delegate['name']}代表帐户。", 'success', true);
				
				$this->system_model->log('delegate_deleted', array('delegate' => $uid));
				break;
				
			//恢复用户帐户
			case 'recover_account':
				//操作恢复
				$this->delegate_model->change_status($uid, user_option('delete_status', 'application_imported', $uid));
				
				$this->user_model->delete_user_option('delete_status', $uid);
				$this->user_model->delete_user_option('delete_time', $uid);
				$this->user_model->delete_user_option('delete_operator', $uid);
				$this->user_model->delete_user_option('delete_reason', $uid);
				
				$this->delegate_model->add_event($uid, 'recovered');
				
				//邮件通知
				$this->load->library('email');
				$this->load->library('parser');
				$this->load->helper('date');
				
				$data = array(
					'uid' => $uid,
					'delegate' => $delegate['name'],
					'time' => unix_to_human(time())
				);
				
				$this->email->to($delegate['email']);
				$this->email->subject('您的 iPlacard 帐户已恢复');
				$this->email->html($this->parser->parse_string(option('email_delegate_recovered', "管理员已经于 {time} 恢复了您的 iPlacard 帐户，请登录 iPlacard 系统查看申请状态。"), $data, true));
				$this->email->send();
				
				//短信通知代表
				if(option('sms_enabled', false))
				{
					$this->load->model('sms_model');
					$this->load->library('sms');

					$this->sms->to($uid);
					$this->sms->message('您的帐户已经恢复，请登录 iPlacard 系统查看申请状态。');
					$this->sms->queue();
				}
				
				$this->ui->alert("已经恢复{$delegate['name']}代表帐户。", 'success', true);
				
				$this->system_model->log('delegate_recovered', array('delegate' => $uid));
				break;
				
			//停用用户帐户
			case 'disable_account':
				//停用检查
				if(!$delegate['enabled'])
				{
					$this->ui->alert('用户帐户已经停用，无需再次停用。', 'warning', true);
					break;
				}
				
				//密码验证
				$admin_password = $this->input->post('password');
				if(empty($admin_password) || !$this->user_model->check_password(uid(), $admin_password))
				{
					$this->ui->alert('密码验证错误，停用操作未执行。', 'warning', true);
					break;
				}
				
				//删除原因
				$reason = $this->input->post('reason');
				if(empty($reason))
				{
					$this->ui->alert('停用原因为空，停用操作未执行。', 'warning', true);
					break;
				}
				
				//停用帐户
				$this->user_model->edit_user_option('disable_time', time(), $uid);
				$this->user_model->edit_user_option('disable_operator', uid(), $uid);
				$this->user_model->edit_user_option('disable_reason', $reason, $uid);
				
				$this->user_model->edit_user(array('enabled' => false), $uid);
				
				//邮件通知
				$this->load->library('email');
				$this->load->library('parser');
				$this->load->helper('date');
				
				$data = array(
					'uid' => $uid,
					'delegate' => $delegate['name'],
					'reason' => $reason,
					'ip' => $this->input->ip_address(),
					'time' => unix_to_human(time())
				);
				
				//通知管理员
				$this->email->to($this->user_model->get_user(uid(), 'email'));
				$this->email->subject('您停用了一个 iPlacard 帐户');
				$this->email->html($this->parser->parse_string(option('email_admin_delegate_deleted', "您已经于 {time} 由 IP {ip} 停用了{delegate}代表的 iPlacard 帐户。停用帐户原因是：\n\n"
						. "\t{reason}\n\n"
						. "如为误操作请立即登录 iPlacard 重新启用代表帐户。如非本人操作请立即修改密码。"), $data, true));
				$this->email->send();
				
				$this->email->clear();
				
				//通知代表
				$this->email->to($delegate['email']);
				$this->email->subject('您的 iPlacard 帐户已停用');
				$this->email->html($this->parser->parse_string(option('email_account_disabled', "管理员已经于 {time} 停用了您的 iPlacard 帐户。以下原因造成了帐户停用：\n\n"
						. "\t{reason}\n\n"
						. "帐户停用期间您将无法登录 iPlacard 系统，请立即联系管理员了解情况。"), $data, true));
				$this->email->send();
				
				//短信通知代表
				if(option('sms_enabled', false))
				{
					$this->load->model('sms_model');
					$this->load->library('sms');

					$this->sms->to($uid);
					$this->sms->message('您的帐户已停用，请立即联系管理员了解情况。');
					$this->sms->queue();
				}
				
				$this->ui->alert("已经停用{$delegate['name']}代表帐户。", 'success', true);
				
				$this->system_model->log('account_disabled', array('user' => $uid, 'reason' => $reason));
				break;
				
			//启用用户帐户
			case 'enable_account':
				//启用检查
				if($delegate['enabled'])
				{
					$this->ui->alert('用户帐户已经启用，无需再次启用。', 'warning', true);
					break;
				}
				
				//操作启用
				$this->user_model->delete_user_option('disable_time', $uid);
				$this->user_model->delete_user_option('disable_operator', $uid);
				$this->user_model->delete_user_option('disable_reason', $uid);
				
				$this->user_model->edit_user(array('enabled' => true), $uid);
				
				//邮件通知
				$this->load->library('email');
				$this->load->library('parser');
				$this->load->helper('date');
				
				$data = array(
					'uid' => $uid,
					'delegate' => $delegate['name'],
					'time' => unix_to_human(time())
				);
				
				$this->email->to($delegate['email']);
				$this->email->subject('您的 iPlacard 帐户已重新启用');
				$this->email->html($this->parser->parse_string(option('email_account_reenabled', "管理员已经于 {time} 重新启用了您的 iPlacard 帐户，请登录 iPlacard 系统查看申请状态。"), $data, true));
				$this->email->send();
				
				//短信通知代表
				if(option('sms_enabled', false))
				{
					$this->load->model('sms_model');
					$this->load->library('sms');

					$this->sms->to($uid);
					$this->sms->message('您的帐户已经恢复启用，请登录 iPlacard 系统查看申请状态。');
					$this->sms->queue();
				}
				
				$this->ui->alert("已经重新启用{$delegate['name']}代表帐户。", 'success', true);
				
				$this->system_model->log('account_reenabled', array('user' => $uid));
				break;
		}
		
		//批量审核申请模式
		if(in_array($action, array('pass_application', 'refuse_application')) && user_option('account_admin_batch_approve_application_enabled', false))
		{
			$next = $this->delegate_model->get_delegate_id('user.id >', $uid, 'status', 'application_imported', 'application_type', option('batch_application_type', array('delegate')));
			if(!$next)
				$next = $this->delegate_model->get_delegate_id('status', 'application_imported', 'application_type', option('batch_application_type', array('delegate')));
			
			if($next)
			{
				$this->ui->alert("批量审核申请模式已经启用，自动跳转到下一位代表。", 'info', true);
				
				redirect("delegate/profile/{$next}");
				return;
			}
		}
		
		//批量分配面试模式
		if((in_array($action, array('refuse_application', 'assign_interview', 'exempt_interview', 'deny_retest')) || ($action == 'pass_application' && !$this->_check_interview_enabled($delegate['application_type']))) && user_option('account_admin_batch_assign_interview_enabled', false))
		{
			$next = $this->delegate_model->get_delegate_id('user.id >', $uid, 'status', array('application_imported', 'review_passed'), 'application_type', option('batch_application_type', array('delegate')));
			if(!$next)
				$next = $this->delegate_model->get_delegate_id('status', array('application_imported', 'review_passed'), 'application_type', option('batch_application_type', array('delegate')));
			
			if($next)
			{
				$this->ui->alert("批量分配面试模式已经启用，自动跳转到下一位代表。", 'info', true);
				
				redirect("delegate/profile/{$next}");
				return;
			}
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
			$this->load->model('interview_model');
			$this->load->model('committee_model');
			$this->load->model('seat_model');
			$this->load->model('group_model');
			$this->load->helper('date');
			
			$param = $this->_filter_check($this->input->get());
			$input_param = array();
			
			//帐户可用性
			if(isset($param['enabled']))
				$input_param['enabled'] = $param['enabled'][0] ? true : false;
			
			//代表类型
			if(isset($param['type']))
				$input_param['application_type'] = $param['type'];
			
			//申请状态
			if(isset($param['status']))
				$input_param['status'] = $param['status'];
			
			//代表团
			if(isset($param['group']))
				$input_param['group'] = $param['group'];
			
			//委员会
			if(isset($param['committee']))
			{
				$sids = $this->seat_model->get_seat_ids('committee', $param['committee'], 'status', array('assigned', 'approved', 'locked'));
				if($sids)
				{
					$dids = $this->seat_model->get_delegates_by_seats($sids);
					if($dids)
						$input_param['id'] = $dids;
					else
						$input_param['id'] = array(NULL);
				}
				else
				{
					$input_param['id'] = array(NULL);
				}
			}
			
			//面试官
			if(isset($param['interviewer']))
			{
				$delegates = array();
				
				foreach($param['interviewer'] as $interviewer)
				{
					$delegate = array();
					
					$iids = $this->interview_model->get_interviewer_interviews($interviewer);
					if($iids)
					{
						$dids = $this->interview_model->get_delegates_by_interviews($iids);
						if($dids)
							$delegate = $dids;
					}
					
					if(!empty($delegate))
						$delegates = array_merge($delegates, $delegate);
				}
				
				if(!empty($delegates))
					$input_param['id'] = isset($input_param['id']) ? array_intersect($input_param['id'], array_unique($delegates)) : array_unique($delegates);
				else
					$input_param['id'] = array(NULL);
			}
			
			$args = array();
			if(!empty($input_param))
			{
				foreach($input_param as $item => $value)
				{
					$args[] = $item;
					$args[] = $value;
				}
			}
			$ids = call_user_func_array(array($this->delegate_model, 'get_delegate_ids'), $args);
			
			if($ids)
			{
				//附加属性
				$profile_option = option('profile_list_manage', array());
				
				$delegates = $this->delegate_model->get_delegates($ids);
				$committees = $this->committee_model->get_committees();
				$seats = array();
				$groups = array();
				$head_delegates = array();
				
				$seat_ids = $this->seat_model->get_seat_ids('delegate', array_column($delegates, 'id'));
				if($seat_ids)
					$seats = $this->seat_model->get_seats($seat_ids);
				
				$delegate_seats = array_column($seats, 'id', 'delegate');
				
				$group_ids = array_unique(array_column($delegates, 'group'));
				if(!empty($group_ids) && $group_ids != array(0 => NULL))
				{
					$groups = $this->group_model->get_groups($group_ids);
					$head_delegates = array_column($groups, 'head_delegate', 'id');
				}
				
				foreach($delegates as $id => $delegate)
				{
					//操作
					$operation = anchor("delegate/profile/$id", icon('info-circle', false).'信息');
					
					//姓名
					$hd_text = '';
					if(isset($param['display_hd']) && $param['display_hd'])
					{
						if(in_array($id, $head_delegates))
							$hd_text = '<span class="label label-primary">领队</span> ';
					}
					$contact_list = '<p>'.icon('phone').$delegate['phone'].'</p><p>'.icon('envelope-o').$delegate['email'].'</p>';
					$name_line = $hd_text.$delegate['name'].'<a style="cursor: pointer;" class="contact_list" data-html="1" data-placement="right" data-trigger="click" data-original-title=\''
							.$delegate['name']
							.'\' data-toggle="popover" data-content=\''.$contact_list.'\'>'.icon('phone-square', false).'</a>';
					
					//团队
					$group_line = '';
					if(!empty($delegate['group']))
					{
						$group_line = anchor("delegate/manage/?group={$groups[$delegate['group']]['id']}", '<span class="shorten">'.$groups[$delegate['group']]['name'].'</span>');
					}
					
					//申请状态
					$status_text = $this->delegate_model->status_text($delegate['status']);
					switch($this->delegate_model->status_code($delegate['status']))
					{
						case 9:
							$status_class = 'success';
							break;
						case 8:
						case 10:
							$status_class = 'warning';
							break;
						case 100:
							$status_class = 'danger';
							break;
						default:
							$status_class = 'primary';
					}
					
					//特殊状态
					if($delegate['status'] == 'review_passed')
					{
						if($this->interview_model->get_interview_ids('delegate', $delegate['id'], 'status', 'completed'))
						{
							$status_text = '请求复试';
							$status_class = 'info';
						}
						elseif($this->interview_model->get_interview_ids('delegate', $delegate['id'], 'status', 'failed'))
						{
							$status_text = '等待二面';
							$status_class = 'info';
						}
					}
					
					$status_line = "<span class='label label-{$status_class}'>{$status_text}</span>";
					
					//委员会
					$committee_line = '';
					if($delegate['application_type'] == 'delegate')
					{
						if(isset($delegate_seats[$id]) && !empty($delegate_seats[$id]))
							$committee_line = $committees[$seats[$delegate_seats[$id]]['committee']]['abbr'];
					}

					$data = array(
						$delegate['id'], //ID
						$name_line, //姓名
						$group_line, //团队
						$this->delegate_model->application_type_text($delegate['application_type']), //申请类型
						$status_line, //申请状态
						sprintf('%1$s（%2$s）', date('n月j日', $delegate['reg_time']), nicetime($delegate['reg_time'])), //申请提交时间
						$committee_line, //委员会
						$operation, //操作
						$delegate['reg_time'] //申请提交时间（排序数据）
					);
					
					//附加属性
					if(!empty($profile_option))
					{
						$this->db->cache_on();
						
						foreach($profile_option as $profile_item)
						{
							$data[] = $this->delegate_model->get_profile_by_name($delegate['id'], $profile_item, '');
						}
						
						$this->db->cache_off();
					}

					$datum[] = $data;
				}
				
				$json = array('aaData' => $datum);
			}
			else
			{
				$json = array('aaData' => array());
			}
		}
		elseif($action == 'profile_edit')
		{
			//检查权限
			if(!$this->admin_model->capable('administrator'))
				return;
			
			$uid = $this->input->get('id');
			if(empty($uid) || $this->input->post('pk') != $uid)
				return;
			
			$delegate = $this->delegate_model->get_delegate($uid);
			if(!$delegate)
				return;
			
			$profile_new = (string) $this->input->post('value');
			$profile_name = $this->input->post('name');
			if(empty($profile_name))
				return;
			
			$profile_id = $this->delegate_model->get_profile_id('delegate', $uid, 'name', $profile_name);
			if(!$profile_id)
			{
				$profile_id = $this->delegate_model->add_profile($uid, $profile_name, $profile_new);
			}
			else
			{
				$profile_old = $this->delegate_model->get_profile($profile_id, 'value');
				if($profile_old == $profile_new)
					return;
				
				$this->delegate_model->edit_profile(array('value' => $profile_new), $profile_id);
			}
			
			$this->system_model->log('delegate_profile_edited', array('id' => $profile_id, 'value' => $profile_new));
			
			$json = array('success' => true);
		}
		elseif($action == 'sidebar')
		{
			$uid = $this->input->get('id');
			if(empty($uid))
				return;
			
			$delegate = $this->delegate_model->get_delegate($uid);
			if(!$delegate)
				return;
			
			$html = $this->_sidebar_admission($delegate);
			$html .= $this->_sidebar_administration($delegate);
			
			if(empty($html))
			{
				$html = '<script>jQuery(function($){
					$("#operation_bar").html( "" );
				});</script>';
			}
			
			$json = array('html' => $html);
		}
		elseif($action == 'note')
		{
			$this->load->model('committee_model');
			$this->load->model('note_model');
			$this->load->helper('date');
			
			$uid = $this->input->get('id');
			if(empty($uid))
				return;
			
			$notes = array();
			
			$ids = $this->note_model->get_delegate_notes($uid);
			if(!empty($ids))
			{
				foreach($ids as $id)
				{
					$note = $this->note_model->get_note($id);
					$note['admin'] = $this->admin_model->get_admin($note['admin']);
					
					//富格式笔记
					$text_rich = nl2br($note['text']);
					
					if($note['mention'])
					{
						foreach($note['mention'] as $mention)
						{
							$user = $this->admin_model->get_admin($mention);
							
							$mention_string = "@{$user['name']}({$mention})";
							
							$mention_tab = !empty($user['title']) ? '<p>'.$user['title'].'</p>' : (!empty($user['committee']) ? '<p>'.$this->committee_model->get_committee($user['committee'], 'name').'</p>' : '');
							$mention_tab .= '<p>'.icon('phone').$user['phone'].'</p><p>'.icon('envelope-o').$user['email'].'</p>';
							
							$mention_link = $this->admin_model->capable('bureaucrat') ? 'href="'.base_url("/user/edit/{$mention}").'"' : 'style="cursor: pointer;"';
							$mention_rich = '<a '.$mention_link.' class="mention_tab" data-html="1" data-placement="top" data-trigger="hover focus" data-original-title=\''
									.$user['name']
									.'\' data-toggle="popover" data-content=\'<div class="user-info">'.$mention_tab.'</div>\'>@'.$user['name'].'</a>';
							
							$text_rich = str_replace($mention_string, $mention_rich, $text_rich);
						}
					}
					
					$note['text_rich'] = $text_rich;
					
					$notes[] = $note;
				}
			}
			
			$categories = array();
			
			$cat_ids = $this->note_model->get_category_ids();
			if(!empty($cat_ids))
			{
				foreach($cat_ids as $id)
				{
					$category = $this->note_model->get_category($id);
					
					$categories[$id] = $category['name'];
				}
			}
			
			$json = array('html' => $this->load->view('admin/delegate_note', array('notes' => $notes, 'categories' => $categories, 'uid' => $uid), true));
		}
		
		echo json_encode($json);
	}
	
	/**
	 * 流程操作栏
	 */
	function _sidebar_admission($delegate)
	{
		$vars = array(
			'uid' => $delegate['id'],
			'delegate' => $delegate
		);
		
		//禁止操作已删除帐户
		if($delegate['status'] == 'deleted')
			return '';
		
		switch($delegate['status'])
		{
			//审核申请界面
			case 'application_imported':
				if(!$this->admin_model->capable('reviewer'))
					break;
				
				$delegate['application_type_text'] = $this->delegate_model->application_type_text($delegate['application_type']);

				$vars['delegate'] = $delegate;

				return $this->load->view('admin/admission/review', $vars, true);
			
			//安排面试界面
			case 'interview_assigned':
				if(!$this->admin_model->capable('interviewer'))
					break;
				
				$this->load->model('interview_model');
				
				$current_id = $this->interview_model->get_current_interview_id($delegate['id']);
				if(!$current_id)
					break;
				
				$interview = $this->interview_model->get_interview($current_id);
				if(!$interview)
					break;
				
				if($interview['interviewer'] != uid())
					break;
				
				//是否为二次面试
				if($this->interview_model->is_secondary($delegate['id'], 'delegate'))
					$vars['is_secondary'] = true;
				else
					$vars['is_secondary'] = false;
				
				//是否有复试请求
				$retesters = array();
				$retest = array();

				$retest_ids = $this->interview_model->get_interview_ids('delegate', $delegate['id'], 'status', 'completed');
				if($retest_ids)
				{
					$this->load->model('committee_model');
					
					$committees = array();
					
					foreach($retest_ids as $id)
					{
						$interviewer_id = $this->interview_model->get_interview($id, 'interviewer');

						if(!in_array($interviewer_id, $retesters))
						{
							$interviewer = $this->admin_model->get_admin($interviewer_id);
							$retest[] = $interviewer;
							$retesters[] = $interviewer_id;
							
							if(!empty($interviewer['committee']) && !isset($committees[$interviewer['committee']]))
								$committees[$interviewer['committee']] = $this->committee_model->get_committee($interviewer['committee']);
						}
					}
					$vars['is_retest_requested'] = true;
					$vars['last_interviewer'] = $interviewer_id;
					
					$vars['committees'] = $committees;
				}
				else
					$vars['is_retest_requested'] = false;

				$vars['retest'] = $retest;
				
				return $this->load->view('admin/admission/arrange_interview', $vars, true);
				
			//面试界面
			case 'interview_arranged':
				if(!$this->admin_model->capable('interviewer'))
					break;
				
				$this->load->model('interview_model');
				
				$current_id = $this->interview_model->get_current_interview_id($delegate['id']);
				if(!$current_id)
					break;
				
				$interview = $this->interview_model->get_interview($current_id);
				if(!$interview)
					break;
				
				if($interview['interviewer'] != uid())
					break;
				
				$vars['interview'] = $interview;
				
				//是否默认显示重新安排页面
				if($interview['status'] == 'arranged' && (($interview['schedule_time'] - 1800) >= time()))
					$vars['pre'] = true;
				else
					$vars['pre'] = false;
				
				//是否为二次面试
				if(!$this->interview_model->is_secondary($delegate['id'], 'delegate'))
					$vars['is_secondary'] = false;
				else
					$vars['is_secondary'] = true;
				
				//分数分布
				$vars['score_standard'] = option('interview_score_standard', array('score' => array(
					'name' => '总分',
					'weight' => 1
				)));
				$vars['score_total'] = option('interview_score_total', 5);
				$vars['score_level'] = $this->interview_model->get_score_levels(20);
				
				$vars['feedback_required'] = option('interview_feedback_required', true);
				$vars['feedback_supplement_enabled'] = option('interview_feedback_supplement_enabled', true);
				
				return $this->load->view('admin/admission/do_interview', $vars, true);
			
			//审核通过界面
			case 'review_passed':
				if(!$this->admin_model->capable('reviewer'))
					break;
				
				//分配面试界面
				if($this->_check_interview_enabled($delegate['application_type']))
				{
					$this->load->model('interview_model');
					$this->load->model('committee_model');

					$list_ids = $this->admin_model->get_admin_ids('role_interviewer', true);

					//获取可选择面试官列表
					$select = array();
					$committees = array();
					$count = 0;

					if($list_ids)
					{
						foreach($list_ids as $id)
						{
							$admin = $this->admin_model->get_admin($id);

							//面试官队列数
							$queues = $this->interview_model->get_interviewer_interviews($id, array('assigned', 'arranged'));
							$queue = !$queues ? 0 : count($queues);

							$committee = empty($admin['committee']) ? 0 : $admin['committee'];
							if($committee > 0 && !isset($committees[$committee]))
								$committees[$committee] = $this->committee_model->get_committee($committee);

							$select[$committee][] = array(
								'id' => $id,
								'name' => $admin['name'],
								'title' => $admin['title'],
								'queue' => $queue
							);
						}

						$count = count($list_ids);
					}
					$vars['select'] = $select;
					$vars['interviewer_count'] = $count;
					$vars['committees'] = $committees;

					//高亮面试官
					$primary = array();
					$choice_committee = array();

					$choice_option = option('profile_special_committee_choice');
					if($choice_option)
					{
						$choice_committee = $this->delegate_model->get_profile_by_name($delegate['id'], $choice_option);

						if(!empty($choice_committee))
						{
							foreach($choice_committee as $committee_id)
							{
								$primary['committee'][] = $committee_id;
								foreach($select[$committee_id] as $one)
								{
									$primary['interviewer'][] = $one['id'];
								}

								$choice_committee[] = $this->committee_model->get_committee($committee_id, 'abbr');
							}
						}
					}
					$vars['primary'] = $primary;
					$vars['choice_committee'] = $choice_committee;

					//是否为二次面试
					if(!$this->interview_model->is_secondary($delegate['id'], 'delegate'))
						$vars['is_secondary'] = false;
					else
						$vars['is_secondary'] = true;

					//是否为回退
					$rollbackers = array();
					$rollback = array();

					$rollback_ids = $this->delegate_model->get_event_ids('delegate', $delegate['id'], 'event', 'interview_rollbacked');
					if($rollback_ids)
					{
						foreach($rollback_ids as $id)
						{
							$event = $this->delegate_model->get_event($id, 'info');

							$interviewer = $this->interview_model->get_interview($event['interview'], 'interviewer');

							if(!in_array($interviewer, $rollbackers))
							{
								$rollback[] = $this->admin_model->get_admin($interviewer);
								$rollbackers[] = $interviewer;
							}
						}
						$vars['is_rollbacked'] = true;
					}
					else
						$vars['is_rollbacked'] = false;

					$vars['rollback'] = $rollback;
					
					//是否有复试请求
					$retesters = array();
					$retest = array();

					$retest_ids = $this->interview_model->get_interview_ids('delegate', $delegate['id'], 'status', 'completed');
					if($retest_ids)
					{
						foreach($retest_ids as $id)
						{
							$interviewer = $this->interview_model->get_interview($id, 'interviewer');

							if(!in_array($interviewer, $retesters))
							{
								$retest[] = $this->admin_model->get_admin($interviewer);
								$retesters[] = $interviewer;
							}
						}
						$vars['is_retest_requested'] = true;
						
						$current_id = $this->interview_model->get_current_interview_id($delegate['id']);
						$vars['current_interviewer'] = $this->interview_model->get_interview($current_id, 'interviewer');
					}
					else
						$vars['is_retest_requested'] = false;

					$vars['retest'] = $retest;

					return $this->load->view('admin/admission/assign_interview', $vars, true);
				}
				
			//分配席位选择
			case 'interview_completed':
			case 'seat_assigned':
			case 'invoice_issued':
			case 'payment_received':
				$this->load->model('seat_model');
			
				//全局席位分配权限
				$global_admin = false;
				if(in_array(uid(), option('seat_global_admin', array())))
					$global_admin = true;
				
				$vars['global_admin'] = $global_admin;
				
				//席位分配模式
				$mode = option('seat_mode', 'select');
				
				if(!$global_admin && !$this->admin_model->capable($this->_check_interview_enabled($delegate['application_type']) ? 'interviewer' : 'reviewer'))
					break;
				
				//经过面试
				if($this->_check_interview_enabled($delegate['application_type']))
				{
					$this->load->model('interview_model');

					$current_id = $this->interview_model->get_current_interview_id($delegate['id']);
					if(!$current_id)
						break;

					$current_interview = $this->interview_model->get_interview($current_id);
					if(!$current_interview)
						break;

					if(!in_array($current_interview['status'], array('completed', 'exempted', 'failed')))
						break;
					
					if($mode == 'select')
					{
						//席位选择模式下所有面试过此代表的面试官都可以开放席位选择
						if(!$global_admin && !$this->_check_interviewer_assign_right($delegate['id'], uid()))
							break;
						
						$interview = $this->interview_model->get_interview($this->interview_model->get_interview_id('delegate', $delegate['id'], 'interviewer', uid(), 'status', array('completed', 'exempted', 'failed')));
						
						$vars['current_interview'] = $current_interview;
					}
					else
					{
						//席位分配模式下只有当前面试官可以分配席位
						if(!$global_admin && $current_interview['interviewer'] != uid())
							break;

						$interview = $current_interview;
					}

					$vars['interview'] = $interview;

					//面试成绩排位
					$vars['score_level'] = false;
					if($interview['status'] == 'completed' && !empty($interview['score']))
					{
						$score_level = $this->interview_model->get_score_levels(1);
						if($score_level)
						{
							foreach($score_level as $level => $sample)
							{
								if($interview['score'] >= $sample)
								{
									$vars['score_level'] = $level;
									break;
								}
							}
						}
					}

					$vars['score_total'] = option('interview_score_total', 5);
				}
				else
				{
					$vars['interview'] = false;
				}
				
				$vars['mode'] = $mode;
				
				//已经分配过席位情况
				$assigned = false;
				
				if($mode == 'select')
				{
					$selectabilities = $this->seat_model->get_delegate_selectability($delegate['id']);
					if($selectabilities)
					{
						$vars['selectability_count'] = count($selectabilities);

						$assigned = true;
					}
				}
				else
				{
					$seat_assigned = $this->seat_model->get_delegate_seat($delegate['id']);
					if($seat_assigned)
					{
						$vars['old_id'] = $seat_assigned;
						
						$assigned = true;
					}
				}
				
				$vars['assigned'] = $assigned;
				
				return $this->load->view('admin/admission/assign_seat', $vars, true);
			
			//移除等待队列界面
			case 'waitlist_entered':
				if(!$this->admin_model->capable('reviewer'))
					break;
				
				$this->load->model('interview_model');
				$this->load->model('committee_model');
				
				$list_ids = $this->admin_model->get_admin_ids('role_interviewer', true);
				
				//获取可选择面试官列表
				$select = array();
				$committees = array();
				$count = 0;
				
				if($list_ids)
				{
					foreach($list_ids as $id)
					{
						$admin = $this->admin_model->get_admin($id);
						
						//面试官队列数
						$queues = $this->interview_model->get_interviewer_interviews($id, array('assigned', 'arranged'));
						$queue = !$queues ? 0 : count($queues);
						
						$committee = empty($admin['committee']) ? 0 : $admin['committee'];
						if($committee > 0 && !isset($committees[$committee]))
							$committees[$committee] = $this->committee_model->get_committee($committee);
						
						$select[$committee][] = array(
							'id' => $id,
							'name' => $admin['name'],
							'title' => $admin['title'],
							'queue' => $queue
						);
					}
					
					$count = count($list_ids);
				}
				$vars['select'] = $select;
				$vars['interviewer_count'] = $count;
				$vars['committees'] = $committees;
				
				return $this->load->view('admin/admission/accept_waitlist', $vars, true);
		}
		
		return '';
	}
	
	/**
	 * 管理操作栏
	 */
	function _sidebar_administration($delegate)
	{
		$html = '';
		$title = '<h3 id="administration_operation">管理</h3>';
		
		$vars = array(
			'uid' => $delegate['id'],
			'delegate' => $delegate
		);
		
		//取消退会
		if($this->admin_model->capable('administrator') && $delegate['status'] == 'quitted')
		{
			$html .= $this->load->view('admin/admission/unquit', $vars + array(
				'quit_reason' => user_option('quit_reason', '', $delegate['id'])
			), true);
		}
		
		//恢复帐户
		if($this->admin_model->capable('administrator') && $delegate['status'] == 'deleted')
		{
			$this->load->helper('date');
			
			$delete_time = user_option('delete_time', time(), $delegate['id']) + option('delegate_delete_lock', 7) * 24 * 60 * 60;
			
			$html .= $this->load->view('admin/admission/recover_account', $vars + array(
				'delete_time' => $delete_time,
				'delete_reason' => user_option('delete_reason', '', $delegate['id'])
			), true);
		}
		
		//启用帐户
		if($this->admin_model->capable('administrator') && !$delegate['enabled'])
		{
			$this->load->helper('date');
			
			$html .= $this->load->view('admin/admission/enable_account', $vars + array(
				'disable_time' => user_option('disable_time', time(), $delegate['id']),
				'disable_reason' => user_option('disable_reason', '', $delegate['id'])
			), true);
		}
		
		//SUDO
		if($this->admin_model->capable('administrator') && $delegate['status'] != 'deleted' && ($delegate['status'] != 'quitted' || user_option('quit_time', 0, $delegate['id']) + option('delegate_quit_lock', 7) * 24 * 60 * 60 > time()))
		{
			$html .= $this->load->view('admin/admission/sudo', $vars, true);
		}
		
		//编辑代表资料
		if($this->admin_model->capable('administrator') && $delegate['status'] != 'deleted')
		{
			$html .= $this->load->view('admin/admission/edit_profile', $vars, true);
		}
		
		//账单
		if($this->admin_model->capable('cashier'))
		{
			$this->load->model('invoice_model');
			
			$invoice_ids = $this->invoice_model->get_delegate_invoices($delegate['id']);
			if($invoice_ids)
			{
				$invoice_unpaid = array();
				foreach($invoice_ids as $invoice_id)
				{
					if($this->invoice_model->get_invoice($invoice_id, 'status') == 'unpaid')
						$invoice_unpaid[] = $invoice_id;
				}
				
				$vars['invoice_unpaid'] = $invoice_unpaid;
				$vars['invoice_count'] = count($invoice_ids);
				
				$html .= $this->load->view('admin/admission/list_invoice', $vars, true);
			}
		}
		
		if(!empty($html))
			$html = $title.'<div id="operation_action">'.$html.'</div>';
		
		//危险操作
		$html_danger = '';
		$title_danger = '<p><a style="cursor: pointer;" onclick="$( \'#danger_action\' ).toggle();" class="text-muted" id="danger_button">'.icon('exclamation-triangle').'危险操作</a></p>';
		
		//重发欢迎邮件
		if($this->admin_model->capable('administrator') && $delegate['enabled'] && $delegate['status'] != 'deleted')
		{
			$html_danger .= $this->load->view('admin/admission/resend_email', $vars, true);
		}
		
		//更换申请类型
		if($this->admin_model->capable('administrator') && $delegate['status'] != 'deleted')
		{
			$types = array();
			
			foreach(array('delegate', 'observer', 'volunteer', 'teacher') as $type)
			{
				$types[$type] = $this->delegate_model->application_type_text($type);
			}
			
			unset($types[$delegate['application_type']]);
			
			$html_danger .= $this->load->view('admin/admission/change_type', $vars + array(
				'application_type_text' => $this->delegate_model->application_type_text($delegate['application_type']),
				'types' => $types
			), true);
		}
		
		//退会
		if($this->admin_model->capable('administrator') && $delegate['status'] != 'quitted' && $delegate['status'] != 'deleted')
		{
			$html_danger .= $this->load->view('admin/admission/quit', $vars, true);
		}
		
		//停用帐户登录
		if($this->admin_model->capable('administrator') && $delegate['enabled'] && $delegate['status'] != 'deleted')
		{
			$html_danger .= $this->load->view('admin/admission/disable_account', $vars, true);
		}
		
		//删除帐户
		if($this->admin_model->capable('administrator') && $delegate['status'] != 'deleted')
		{
			$html_danger .= $this->load->view('admin/admission/delete_account', $vars, true);
		}
		
		if(!empty($html_danger))
			$html .= $title_danger.'<div id="danger_action">'.$html_danger.'</div><script>$("#danger_action").hide();</script>';
		
		if(empty($html))
			return '';
		return $html.'<hr />';
	}
	
	/**
	 * 查询过滤
	 */
	function _filter_check($post, $return_uri = false)
	{
		$return = array();
		
		//代表类型
		if(isset($post['type']))
		{
			$type = array();
			foreach(explode(',', $post['type']) as $param_type)
			{
				if(in_array($param_type, array('delegate', 'observer', 'volunteer', 'teacher')))
					$type[] = $param_type;
			}
			if(!empty($type))
				$return['type'] = $type;
		}
		
		//申请状态
		if(isset($post['status']))
		{
			$status = array();
			foreach(explode(',', $post['status']) as $param_status)
			{
				if(in_array($param_status, array('application_imported', 'review_passed', 'review_refused', 'interview_assigned', 'interview_arranged', 'interview_completed', 'waitlist_entered', 'seat_assigned', 'seat_selected', 'invoice_issued', 'payment_received', 'locked', 'quitted', 'deleted')))
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
			$this->load->model('committee_model');
			
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
		
		//帐户可用性
		if(isset($post['enabled']))
		{
			$return['enabled'] = array($post['enabled'] ? true : false);
		}
		
		//显示领队标志
		if(isset($post['display_hd']) && $post['display_hd'])
		{
			$return['display_hd'] = array(true);
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
	
	/**
	 * 检查代表是否符合选择席位条件
	 */
	private function _check_seat_select_open($delegate)
	{
		$status_code = $this->delegate_model->status_code($delegate['status']);
		
		if($status_code == $this->delegate_model->status_code('review_refused'))
			return false;
		
		if($status_code == $this->delegate_model->status_code('waitlist_entered'))
			return false;

		if($status_code == $this->delegate_model->status_code('quitted'))
			return false;

		if($status_code == $this->delegate_model->status_code('deleted'))
			return false;
		
		if($this->_check_interview_enabled($delegate['application_type']) && $status_code >= $this->delegate_model->status_code('interview_completed'))
			return true;
				
		if(!$this->_check_interview_enabled($delegate['application_type']) && $status_code >= $this->delegate_model->status_code('review_passed'))
			return true;
		
		return false;
	}
	
	/**
	 * 检查代表是否需要面试
	 */
	private function _check_interview_enabled($application_type)
	{
		return option("interview_{$application_type}_enabled", option('interview_enabled', $application_type == 'delegate'));
	}
	
	/**
	 * 检查席位选择模式下面试官是否可以分配席位
	 */
	private function _check_interviewer_assign_right($delegate, $interviewer)
	{
		$this->load->model('interview_model');
		
		$interviews = $this->interview_model->get_interview_ids('delegate', $delegate, 'status', array('completed', 'exempted'));
		if(!$interviews)
			return false;

		$interviewers = $this->interview_model->get_interviewers_by_interviews($interviews);
		if(!in_array($interviewer, $interviewers))
			return false;
		
		return true;
	}
}

/* End of file delegate.php */
/* Location: ./application/controllers/delegate.php */