<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 面试管理控制器
 * @package iPlacard
 * @since 2.0
 */
class Interview extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->library('form_validation');
		$this->load->library('ui', array('side' => 'admin'));
		$this->load->model('admin_model');
		$this->load->model('delegate_model');
		$this->load->model('interview_model');
		$this->load->helper('form');
		
		//检查登录情况
		if(!is_logged_in())
		{
			login_redirect();
			return;
		}
		
		$this->ui->now('interview');
	}
	
	/**
	 * 管理页面
	 */
	function manage()
	{
		//查询过滤
		$post = $this->input->get();
		$param = $this->_filter_check($post);
		$param_tab = array();
		
		//显示标题
		$title = '全部面试列表';
		
		if(isset($param['status']))
		{
			$text_status = array();
			foreach($param['status'] as $one)
			{
				$text_status[] = $this->interview_model->status_text($one);
			}
			$title = sprintf("%s列表", join('、', $text_status));
			
			if(count($param['status']) == 2 && in_array('assigned', $param['status']) && in_array('arranged', $param['status']))
				$part = 'pending';
			elseif(count($param['status']) == 3 && in_array('completed', $param['status']) && in_array('failed', $param['status']) && in_array('exempted', $param['status']))
				$part = 'finished';
			else
				$part = '';
		}
		else
		{
			$part = 'all';
		}
		
		if(isset($param['committee']))
		{
			$this->load->model('committee_model');
			
			$text_committee = array();
			foreach($param['committee'] as $one)
			{
				$text_committee[] = $this->committee_model->get_committee($one, 'name');
			}
			$title = sprintf("%s代表面试列表", join('、', $text_committee));
		}
		
		if(isset($param['group']))
		{
			$this->load->model('group_model');
			
			$text_group = array();
			foreach($param['group'] as $one)
			{
				$text_group[] = $this->group_model->get_group($one, 'name');
			}
			$title = sprintf("%s代表团面试列表", join('、', $text_group));
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
			$title = sprintf("%s的面试队列", join('、', $text_interviewer));
		}
		
		//显示面试官栏
		$show_interviewer = false;
		if(isset($post['display_interviewer']) && $post['display_interviewer'])
			$show_interviewer = true;
		
		//标签地址
		$params = $param;
		
		$params['status'] = array('assigned', 'arranged');
		$param_tab['pending'] = $this->_filter_build($params);
		
		$params['status'] = array('completed', 'failed', 'exempted');
		$param_tab['finished'] = $this->_filter_build($params);
		
		unset($params['status']);
		$param_tab['all'] = $this->_filter_build($params);
		
		$vars = array(
			'param_uri' => $this->_filter_build($param),
			'param_tab' => $param_tab,
			'part' => $part,
			'title' => $title,
			'show_interviewer' => $show_interviewer
		);
		
		$this->ui->title($title, '面试队列');
		$this->load->view('admin/interview_manage', $vars);
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
			$this->load->helper('date');
			
			$param = $this->_filter_check($this->input->get());
			$input_param = array();
			
			//面试状态
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
			{
				$this->load->model('committee_model');
				
				$sids = $this->seat_model->get_seat_ids('committee', $param['committee'], 'status', array('assigned', 'approved', 'locked'));
				if($sids)
				{
					$dids = $this->seat_model->get_delegates_by_seats($sids);
					if($dids)
						$input_param['delegate'] = $dids;
					else
						$input_param['delegate'] = array(NULL);
				}
				else
				{
					$input_param['delegate'] = array(NULL);
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
			$ids = call_user_func_array(array($this->interview_model, 'get_interview_ids'), $args);
			
			if($ids)
			{
				$interviews = $this->interview_model->get_interviews($ids);
				$admins = $this->admin_model->get_admins(array_unique(array_column($interviews, 'interviewer')));
				$delegates = $this->delegate_model->get_delegates(array_unique(array_column($interviews, 'delegate')));
				
				foreach($interviews as $id => $interview)
				{
					$admin = $admins[$interview['interviewer']];
					$delegate = $delegates[$interview['delegate']];

					//操作
					$operation = anchor("delegate/profile/{$interview['delegate']}", icon('user', false).'代表信息');
					if(($interview['status'] == 'completed' || $interview['status'] == 'exempted') && $interview['id'] == $this->interview_model->get_current_interview_id($interview['delegate']) && $interview['interviewer'] == uid() && !$this->seat_model->get_delegate_selectability($interview['delegate']))
					{
						$operation .= ' '.anchor("delegate/profile/{$interview['delegate']}#seat", icon('th-list', false).'分配席位');
					}
					
					//姓名
					$contact_list = '<p>'.icon('phone').$delegate['phone'].'</p><p>'.icon('envelope-o').$delegate['email'].'</p><p>'.icon('male').'ID '.$delegate['id'].'</p>';
					$name_line = $delegate['name'].'<a style="cursor: pointer;" class="contact_list" data-html="1" data-placement="right" data-trigger="click" data-original-title=\''
							.$delegate['name']
							.'\' data-toggle="popover" data-content=\''.$contact_list.'\'>'.icon('info-circle', false).'</a>';
					
					//面试状态
					$status_text = $this->interview_model->status_text($interview['status']);
					switch($interview['status'])
					{
						case 'assigned':
						case 'cancelled':
							$status_class = 'info';
							break;
						case 'arranged':
							$status_class = 'primary';
							break;
						case 'completed':
						case 'exempted':
							$status_class = 'success';
							break;
						case 'failed':
							$status_class = 'danger';
							break;
						default:
							$status_class = 'primary';
					}
					$status_line = "<span class='label label-{$status_class}'>{$status_text}</span>";
					
					//面试评分
					$score_line = '';
					if($interview['status'] == 'completed' || $interview['status'] == 'failed')
					{
						$score_round = round($interview['score'], 2);
						$score_line = "<span class='label label-primary'>总分</span> <strong>{$score_round}</strong>";
						if(!empty($interview['feedback']['score']))
						{
							$score_standard = option('interview_score_standard', array('score' => array('name' => '总分')));
							
							if(count($score_standard) > 2)
								$score_line .= ' <span class="shorten">';
							
							foreach($score_standard as $sid => $one)
							{
								if(!isset($interview['feedback']['score'][$sid]) || is_null($interview['feedback']['score'][$sid]))
									continue;
								
								$score_line .= " <span class='label label-default'>{$one['name']}</span> {$interview['feedback']['score'][$sid]}";
							}
							
							if(count($score_standard) > 2)
								$score_line .= '</span>';
						}
					}
					elseif($interview['status'] == 'exempted')
					{
						$score_line = "<span class='label label-success'>免试通过</span>";
					}
					
					//特殊时间属性
					$special_line = '';
					if($interview['status'] == 'exempted')
						$special_line = "<span class='label label-success'>免试</span> ";
					elseif($interview['status'] == 'cancelled' && empty($interview['schedule_time']))
						$special_line = "<span class='label label-warning'>回退</span> ";
					elseif($interview['status'] == 'cancelled' && !empty($interview['schedule_time']))
						$special_line = "<span class='label label-info'>改期</span> ";
					elseif($this->interview_model->is_secondary($interview['id']))
					{
						if($interview['status'] == 'completed')
							$special_line = "<span class='label label-primary'>二次</span> ";
						else
							$special_line = "<span class='label label-danger'>二次</span> ";
					}
						
					
					$data = array(
						$interview['id'], //ID
						$name_line, //姓名
						$admin['name'], //面试官
						$status_line, //面试状态
						!empty($interview['assign_time']) ? sprintf('%1$s（%2$s）', date('n月j日', $interview['assign_time']), nicetime($interview['assign_time'])) : 'N/A', //分配时间
						!empty($interview['schedule_time']) ? sprintf('%1$s（%2$s）', date('n月j日 H:i', $interview['schedule_time']), nicetime($interview['schedule_time'])) : 'N/A', //安排时间
						!empty($interview['finish_time']) ? $special_line.sprintf('%1$s（%2$s）', date('n月j日', $interview['finish_time']), nicetime($interview['finish_time'])) : $special_line.'N/A', //完成时间
						$score_line, //面试评分
						$operation, //操作
						$interview['assign_time'], //分配时间（排序数据）
						$interview['schedule_time'], //安排时间（排序数据）
						$interview['finish_time'] //完成时间（排序数据）
					);
					
					$datum[] = $data;
				}
				
				$json = array('aaData' => $datum);
			}
			else
			{
				$json = array('aaData' => array());
			}
			
			echo json_encode($json);
		}
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
				if(in_array($param_status, array('assigned', 'arranged', 'completed', 'exempted', 'cancelled', 'failed')))
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

/* End of file interview.php */
/* Location: ./application/controllers/interview.php */