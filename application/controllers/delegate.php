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
			'param_uri' => $this->_filter_build($param),
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
	 * AJAX 操作
	 */
	function operation($action, $uid)
	{
		if(empty($uid))
			return;

		$delegate = $this->delegate_model->get_delegate($uid);
		if(!$delegate)
			return;
		
		switch($action)
		{
			//通过审核
			case 'pass_application':
				if($delegate['status'] != 'application_imported')
					break;
				
				$this->delegate_model->change_status($uid, 'review_passed');
				
				$this->delegate_model->add_event($uid, 'review_passed');
				
				if($delegate['application_type'] == 'delegate')
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
					$this->sms->send();
				}
				
				$this->ui->alert('参会申请已经通过。', 'success', true);
				
				$this->system_model->log('review_passed', array('delegate' => $uid));
				
				//非代表情况
				if($delegate['application_type'] != 'delegate')
				{
					$fee = option("fee_{$delegate['application_type']}", 0);
					
					if($fee > 0)
					{
						//TODO: 生成帐单
					}
					else
					{
						$this->delegate_model->change_status($uid, 'locked');
				
						$this->delegate_model->add_event($uid, 'locked', array('admin' => uid()));
						
						$this->user_model->add_message($uid, '恭喜您！您的参会申请流程已经完成。');
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
					$this->sms->send();
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
				
				//是否为二次面试
				$old_id = $this->interview_model->get_interview_id('status', 'failed', 'delegate', $uid);
				if($old_id)
				{
					$old_interviewer = $this->interview_model->get_interview($old_id, 'interviewer');
					if($old_interviewer == $interviewer['id'])
					{
						$this->ui->alert('指派的面试官是已经面试过此代表。', 'warning', true);
						break;
					}
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
				$this->email->html($this->parser->parse_string(option('email_delegate_interview_assigned', "我们已经于 {time} 为您分配了面试官，面试官{interviewer}将会在近期内与您取得联系，请登录 iPlacard 系统查看申请状态。"), $data, true));
				$this->email->send();
				$this->email->clear();
				
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
					$this->sms->message('我们已经为您分配了面试官，他将会在近期内与您取得联系，请登录 iPlacard 系统查看申请状态。');
					$this->sms->send();
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
				$this->email->clear();
				
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
					$this->sms->send();
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
					$this->sms->message('您的面试官已经安排面试时间，请登录 iPlacard 系统查看申请状态。');
					$this->sms->send();
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
					$this->sms->send();
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
					$this->sms->send();
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
				
				//是否通过
				if($this->input->post('pass'))
					$pass = true;
				else
					$pass = false;
				
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
				
				//反馈
				$feedback = $this->input->post('feedback');
				if(empty($feedback))
					$feedback = NULL;
				
				$feedback_data = array(
					'score' => $score_all,
					'feedback' => $feedback
				);
				
				//执行面试结果
				$this->interview_model->complete_interview($interview_id, $score, $pass, $feedback_data);
				
				//载入邮件通知
				$this->load->library('email');
				$this->load->library('parser');
				$this->load->helper('date');
				
				$data = array(
					'uid' => $uid,
					'delegate' => $delegate['name'],
					'interviewer' => $this->admin_model->get_admin($interview['interviewer'], 'name'),
					'time' => unix_to_human(time())
				);
				
				if(!$pass)
				{
					$this->delegate_model->add_event($uid, 'interview_failed', array('interview' => $interview['id']));
					
					if(!$this->interview_model->is_secondary($delegate['id'], 'delegate'))
					{
						$this->delegate_model->change_status($uid, 'review_passed');

						$this->user_model->add_message($uid, "您将需要进行二次面试，我们将在近期内为您重新分配面试官。");
						
						//邮件通知
						$this->email->to($delegate['email']);
						$this->email->subject('面试未通过');
						$this->email->html($this->parser->parse_string(option('email_delegate_interview_failed', "您的面试官已经于 {time} 认定您未能通过面试，您将需要进行二次面试，我们将在近期内为您重新分配面试官，请登录 iPlacard 系统查看申请状态。"), $data, true));
						$this->email->send();

						//短信通知代表
						if(option('sms_enabled', false))
						{
							$this->load->model('sms_model');
							$this->load->library('sms');

							$this->sms->to($uid);
							$this->sms->message('您将需要进行二次面试，我们将在近期内为您重新分配面试官，请登录 iPlacard 系统查看申请状态。');
							$this->sms->send();
						}
						
						$this->ui->alert("已经通知{$delegate['name']}代表的准备二次面试。", 'success', true);
					}
					else
					{
						$this->delegate_model->change_status($uid, 'moved_to_waiting_list');

						$this->user_model->add_message($uid, "很遗憾，您未能通过二次面试。您的申请已经移入等待队列。");
						
						//邮件通知
						$this->email->to($delegate['email']);
						$this->email->subject('二次面试未通过');
						$this->email->html($this->parser->parse_string(option('email_delegate_interview_failed_2nd', "您的面试官已经于 {time} 认定您未能通过二次面试。您的申请已经移入等待队列，当席位出现空缺时，我们将会为您分配席位，请登录 iPlacard 系统查看申请状态。"), $data, true));
						$this->email->send();

						//短信通知代表
						if(option('sms_enabled', false))
						{
							$this->load->model('sms_model');
							$this->load->library('sms');

							$this->sms->to($uid);
							$this->sms->message('您将需要进行二次面试，我们将在近期内为您重新分配面试官，请登录 iPlacard 系统查看申请状态。');
							$this->sms->send();
						}
						
						$this->ui->alert("已经将{$delegate['name']}代表移动至等待队列。", 'success', true);
					}
				}
				else
				{
					$this->delegate_model->change_status($uid, 'interview_completed');
					
					$this->delegate_model->add_event($uid, 'interview_passed', array('interview' => $interview['id']));
					
					$this->user_model->add_message($uid, "您已通过面试，我们将在近期内为您分配席位选择。");
					
					//邮件通知
					$this->email->to($delegate['email']);
					$this->email->subject('面试通过');
					$this->email->html($this->parser->parse_string(option('email_delegate_interview_passed', "您的面试官已经于 {time} 认定您成功通过面试，我们将在近期内为您分配席位选择，请登录 iPlacard 系统查看申请状态。"), $data, true));
					$this->email->send();

					//短信通知代表
					if(option('sms_enabled', false))
					{
						$this->load->model('sms_model');
						$this->load->library('sms');

						$this->sms->to($uid);
						$this->sms->message('您已成功通过面试，我们将在近期内为您分配席位选择，请登录 iPlacard 系统查看申请状态。');
						$this->sms->send();
					}

					$this->ui->alert("已经通过{$delegate['name']}代表的面试。", 'success', true);
				}
				
				$this->ui->alert("已经录入面试成绩。", 'success', true);
				
				$this->system_model->log('interview_completed', array('interview' => $interview['id'], 'pass' => $pass));
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
					$name_line = $hd_text.$delegate['name'].'<a href="#" class="contact_list" data-html="1" data-placement="right" data-trigger="click" data-original-title=\''
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
	 * 流程操作栏
	 */
	function _sidebar_admission($delegate)
	{
		$vars = array(
			'uid' => $delegate['id'],
			'delegate' => $delegate
		);
		
		switch($delegate['status'])
		{
			//审核申请界面
			case 'application_imported':
				if(!$this->admin_model->capable('reviewer'))
					break;
				
				$delegate['application_type_text'] = $this->delegate_model->application_type_text($delegate['application_type']);

				$vars['delegate'] = $delegate;

				return $this->load->view('admin/admission/review', $vars, true);
				
			//分配面试界面
			case 'review_passed':
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
						if($committee > 0 && !isset($committees['committee']))
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

				return $this->load->view('admin/admission/assign_interview', $vars, true);
				
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
				
				return $this->load->view('admin/admission/do_interview', $vars, true);
				
			//分配席位选择
			case 'interview_completed':
			case 'seat_assigned':
				if(!$this->admin_model->capable('interviewer'))
					break;
				
				$this->load->model('interview_model');
				
				$current_id = $this->interview_model->get_current_interview_id($delegate['id']);
				if(!$current_id)
					break;
				
				$interview = $this->interview_model->get_interview($current_id);
				if(!$interview)
					break;
				
				if(!in_array($interview['status'], array('completed', 'exempted')))
					break;
				
				if($interview['interviewer'] != uid())
					break;
				
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
				
				return $this->load->view('admin/admission/assign_seat', $vars, true);
		}
		
		return '';
	}
	
	/**
	 * 管理操作栏
	 */
	function _sidebar_administration($delegate)
	{
		return '';
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

/* End of file delegate.php */
/* Location: ./application/controllers/delegate.php */