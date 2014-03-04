<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 代表管理控制器
 * @package iPlacard
 * @since 2.0
 */
class delegate extends CI_Controller
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
		
		$vars = array(
			'param_uri' => $this->_filter_check($post, true),
			'title' => $title,
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
		$this->load->helper('avatar');
		
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
		$profile['application_type_text'] = $this->delegate_model->application_type_text($profile['application_type']);
		$profile['status_text'] = $this->delegate_model->status_text($profile['status']);
		$profile['status_code'] = $this->delegate_model->status_code($profile['status']);
		
		$pids = $this->delegate_model->get_profile_ids('delegate', $uid);
		if($pids)
		{
			foreach($pids as $pid)
			{
				$one = $this->delegate_model->get_profile($pid);
				$profile[$one['name']] = $one['value'];
			}
		}
		
		$vars['profile'] = $profile;
		
		//面试数据
		$interviews = array();
		
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
					$this->load->model('committee_model');
					
					$interview['interviewer']['committee'] = $this->committee_model->get_committee($interview['interviewer']['committee']);
				}
				
				$interviews[$interview['id']] = $interview;
			}
			
			$vars['current_interview'] = $this->interview_model->get_current_interview_id($uid);
		}
		
		$vars['interviews'] = $interviews;
		
		//TODO: 用户事件数据
		
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
		
		//席位数据
		if(!empty($profile['seat']))
		{
			$this->load->model('committee_model');
			$this->load->model('seat_model');
			
			$vars['seat'] = $this->seat_model->get_seat($profile['seat']);
			$vars['committee'] = $this->committee_model->get_committee($vars['seat']['committee']);
		}
		
		$vars['uid'] = $uid;
		
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
				$this->email->to($delegate['name']);
				$this->email->subject('您已调整为个人代表');
				$this->email->html($this->parser->parse_string(option('email_group_delegate_removed', "您已于 {time} 由管理员操作退出{group_old_name}代表团，如为误操作请立即与管理员取得联系。"), $data, true));
				$this->email->send();
				$this->email->clear();
				
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
					$this->email->clear();
				}
				
				//通知旧团队领队
				if(!is_null($delegate['group']) && $delegate['group'] != $group_id && $group_old['head_delegate'] != $uid)
				{
					$this->email->to($this->delegate_model->get_delegate($group_old['head_delegate'], 'email'));
					$this->email->subject('代表退出了您领队的代表团');
					$this->email->html($this->parser->parse_string(option('email_group_manage_delegate_removed', "{delegate_name}代表已于 {time} 由管理员操作退出您领队的{group_old_name}代表团，如为误操作请立即与管理员取得联系。"), $data, true));
					$this->email->send();
					$this->email->clear();
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
					$this->email->clear();
					
					//通知取消原领队
					if(!is_null($group_new['head_delegate']))
					{
						$this->email->to($this->delegate_model->get_delegate($group_new['head_delegate'], 'email'));
						$this->email->subject('代表团领队已经更换');
						$this->email->html($this->parser->parse_string(option('email_group_manage_delegate_changed', "您领队的{group_new_name}代表团已于 {time} 由管理员操作更换领队为{delegate_name}代表，如为误操作请立即与管理员取得联系。"), $data, true));
						$this->email->send();
						$this->email->clear();
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
					$this->email->clear();
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
	 * AJAX
	 */
	function ajax($action = 'list')
	{
		$json = array();
		
		if($action == 'list')
		{
			$this->load->model('committee_model');
			$this->load->model('seat_model');
			$this->load->helper('date');
			
			$param = $this->_filter_check($this->input->get());
			$input_param = array();
			
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
					$input_param['id'] = $this->seat_model->get_delegates_by_seats($sids);
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
				foreach($ids as $id)
				{
					$delegate = $this->delegate_model->get_delegate($id);

					//操作
					$operation = anchor("delegate/profile/$id", icon('info-circle', false).'信息').' '.anchor("ticket/manage/?delegate=$id", icon('comments', false).'工单');
					
					//姓名
					$hd_text = '';
					if(isset($param['display_hd']) && $param['display_hd'])
					{
						$this->load->model('group_model');
						
						if($this->group_model->get_group_id('head_delegate', $id))
							$hd_text = '<span class="label label-primary">领队</span> ';
					}
					$contact_list = '<p>'.icon('phone').$delegate['phone'].'</p><p>'.icon('envelope-o').$delegate['email'].'</p>';
					$name_line = $hd_text.$delegate['name'].'<a class="contact_list" data-html=true data-placement="right" data-trigger="click" data-original-title=\''
							.$delegate['name']
							.'\' data-toggle="popover" data-content=\''.$contact_list.'\'>'.icon('phone-square', false).'</a>';
					
					//团队
					$group_line = '';
					if(!empty($delegate['group']))
					{
						$this->load->model('group_model');
						$group = $this->group_model->get_group($delegate['group']);
						$group_line = anchor("delegate/manage/?group={$group['id']}", $group['name']);
					}
					
					//申请状态
					$status_text = $this->delegate_model->status_text($delegate['status']);
					switch($this->delegate_model->status_code($delegate['status']))
					{
						case 9:
							$status_class = 'success';
							break;
						case 10:
							$status_class = 'warning';
							break;
						default:
							$status_class = 'primary';
					}
					$status_line = "<span class='label label-{$status_class}'>{$status_text}</span>";
					
					//委员会
					$committee_line = '';
					if($delegate['application_type'] == 'delegate')
					{
						$sid = $this->seat_model->get_seat_id('delegate', $delegate);
						if($sid)
						{
							$seat = $this->seat_model->get_seat($sid);
							$committee_line = $this->committee_model->get_committee($seat['committee'], 'abbr');
						}
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
					);

					$datum[] = $data;

					$json = array('aaData' => $datum);
				}
			}
			else
			{
				$json = array('aaData' => array());
			}
		}
		elseif($action == 'note')
		{
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
				if(in_array($param_status, array('application_imported', 'review_passed', 'review_refused', 'interview_assigned', 'interview_arranged', 'interview_completed', 'moved_to_waiting_list', 'seat_assigned', 'invoice_issued', 'payment_received', 'locked', 'quitted')))
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
		
		//显示领队标志
		if(isset($post['display_hd']) && $post['display_hd'])
		{
			$return['display_hd'] = array(true);
		}
		
		if(!$return_uri)
			return $return;
		
		if(empty($return))
			return '';
		
		foreach($return as $name => $value)
		{
			$return[$name] = join(',', $value);
		}
		return http_build_query($return);
	}
}

/* End of file delegate.php */
/* Location: ./application/controllers/delegate.php */