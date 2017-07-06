<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 事件类库
 * @package iPlacard
 * @since 2.0
 */
class Event
{
	private $CI;
	
	protected $id = 0;
	protected $delegate = 0;
	protected $time = 0;
	protected $event = '';
	protected $info = NULL;
	
	protected $title = '';
	protected $text = NULL;
	protected $level = NULL;
	protected $icon = NULL;
	
	function __construct()
	{
		$this->CI =& get_instance();
		$this->CI->load->model('delegate_model');
	}
	
	/**
	 * 载入账单
	 */
	function load($id)
	{
		$event = $this->CI->delegate_model->get_event($id);
		
		if(!$event)
			return false;
		
		//基础信息
		$this->id = $event['id'];
		$this->delegate = $event['delegate'];
		$this->time = $event['time'];
		$this->event = $event['event'];
		$this->info = $event['info'];
		
		//处理内容
		if(method_exists($this, "_event_{$event['event']}"))
		{
			call_user_func_array(array($this, "_event_{$event['event']}"), array());
		}
		else
		{
			$this->title = '未知事件';
			$this->text = NULL;
			$this->level = 'default';
			$this->icon = NULL;
		}
		
		return true;
	}
	
	/**
	 * 重置属性
	 */
	function clear()
	{
		$this->id = 0;
		$this->delegate = 0;
		$this->time = 0;
		$this->event = '';
		$this->info = array();
		
		$this->title = '';
		$this->text = NULL;
		$this->level = NULL;
		$this->icon = NULL;
	}
	
	/**
	 * 获取事件信息
	 */
	function get($item)
	{
		return $this->{$item};
	}
	
	/**
	 * 申请已导入事件
	 */
	function _event_application_imported()
	{
		$this->title = '参会申请导入完成';
		$this->level = 'primary';
		$this->icon = 'envelope';
	}
	
	/**
	 * 审核通过事件
	 */
	function _event_review_passed()
	{
		$this->title = '参会申请通过审核';
		$this->level = 'success';
		$this->icon = 'envelope';
	}
	
	/**
	 * 审核未通过事件
	 */
	function _event_review_refused()
	{
		$this->title = '参会申请未通过审核';
		$this->level = 'danger';
		$this->icon = 'envelope';
		
		if(!empty($this->info['reason']))
		{
			$this->text = "<blockquote><p>{$this->info['reason']}</p></blockquote>参会申请由于以上原因未通过审核。";
		}
	}
	
	/**
	 * 面试已分配事件
	 */
	function _event_interview_assigned()
	{
		$this->title = '分配面试官';
		$this->level = 'primary';
		$this->icon = 'comments';
		
		if(!empty($this->info['interviewer']))
		{
			$interviewer = $this->CI->admin_model->get_admin($this->info['interviewer']);
			if(!$interviewer)
				return;
			
			$interviewer_link = anchor_capable("interview/manage?interviewer={$interviewer['id']}", icon('user', false).$interviewer['name'], 'administrator');
			
			$this->text = "已经分配{$interviewer_link}面试官面试代表。";
		}
	}
	
	/**
	 * 面试已安排事件
	 */
	function _event_interview_arranged()
	{
		$this->title = '面试安排完成';
		$this->level = 'primary';
		$this->icon = 'comments';
		
		if(!empty($this->info['time']))
		{
			$this->text = sprintf('面试已经安排在%1$s（%2$s）进行。', date('Y年n月j日 H:i:s', $this->info['time']), nicetime($this->info['time']));
		}	
	}
	
	/**
	 * 面试已取消事件
	 */
	function _event_interview_cancelled()
	{
		$this->title = '面试安排取消';
		$this->level = 'warning';
		$this->icon = 'comments';
		
		$this->text = '面试官已经取消了面试。';
	}
	
	/**
	 * 免试通过事件
	 */
	function _event_interview_exempted()
	{
		$this->title = '免试通过面试流程';
		$this->level = 'success';
		$this->icon = 'comments';
	}
	
	/**
	 * 面试未通过事件
	 */
	function _event_interview_failed()
	{
		$this->title = '面试未通过';
		$this->level = 'danger';
		$this->icon = 'comments';
		
		if(!empty($this->info['interview']))
		{
			$this->CI->load->model('interview_model');
			
			$interview = $this->CI->interview_model->get_interview($this->info['interview']);
			if(!$interview)
				return;
			
			$interviewer_link = anchor_capable("interview/manage?interviewer={$interview['interviewer']}", icon('user', false).$this->CI->admin_model->get_admin($interview['interviewer'], 'name'), 'administrator');
			
			$this->text = "面试官{$interviewer_link}认定本次面试不通过并给出了 {$interview['score']} 分的评分。";
			
			if(!empty($interview['feedback']['remark']))
			{
				$remark = nl2br($interview['feedback']['remark']);
				$this->text .= "<blockquote><p>{$remark}</p></blockquote>以上为面试官向代表发送的面试评价。";
			}
			
			if(!empty($interview['feedback']['supplement']) && !empty($interview['feedback']['feedback'])) //兼容2.1版之前数据结构
			{
				$supplement = nl2br($interview['feedback'][empty($interview['feedback']['feedback']) ? 'supplement' : 'feedback']);
				$this->text .= "<blockquote><p>{$supplement}</p></blockquote>以上为面试官撰写的内部反馈。";
			}
		}
	}
	
	/**
	 * 面试通过事件
	 */
	function _event_interview_passed()
	{
		$this->title = '面试通过';
		$this->level = 'success';
		$this->icon = 'comments';
		
		if(!empty($this->info['interview']))
		{
			$this->CI->load->model('interview_model');
			
			$interview = $this->CI->interview_model->get_interview($this->info['interview']);
			if(!$interview)
				return;
			
			$interviewer_link = anchor_capable("interview/manage?interviewer={$interview['interviewer']}", icon('user', false).$this->CI->admin_model->get_admin($interview['interviewer'], 'name'), 'administrator');
			
			$this->text = "面试官{$interviewer_link}对本次面试给出了 {$interview['score']} 分的评分。";
			
			if(!empty($interview['feedback']['remark']))
			{
				$remark = nl2br($interview['feedback']['remark']);
				$this->text .= "<blockquote><p>{$remark}</p></blockquote>以上为面试官向代表发送的面试评价。";
			}
			
			if(!empty($interview['feedback']['supplement']) && !empty($interview['feedback']['feedback'])) //兼容2.1版之前数据结构
			{
				$supplement = nl2br($interview['feedback'][empty($interview['feedback']['feedback']) ? 'supplement' : 'feedback']);
				$this->text .= "<blockquote><p>{$supplement}</p></blockquote>以上为面试官撰写的内部反馈。";
			}
		}
	}
	
	/**
	 * 面试回退事件
	 */
	function _event_interview_rollbacked()
	{
		$this->title = '面试回退';
		$this->level = 'info';
		$this->icon = 'comments';
		
		if(!empty($this->info['interview']))
		{
			$this->CI->load->model('interview_model');
			
			$interview = $this->CI->interview_model->get_interview($this->info['interview']);
			if(!$interview)
				return;
			
			$interviewer_link = anchor_capable("interview/manage?interviewer={$interview['interviewer']}", icon('user', false).$this->CI->admin_model->get_admin($interview['interviewer'], 'name'), 'administrator');
			
			$this->text = "面试官{$interviewer_link}回退了本次面试。";
		}
	}
	
	/**
	 * 移入等待队列事件
	 */
	function _event_waitlist_entered()
	{
		$this->title = '移入等待队列';
		$this->level = 'warning';
		$this->icon = 'clock-o';
	}
	
	/**
	 * 移入等待队列事件
	 */
	function _event_waitlist_accepted()
	{
		$this->title = '移出等待队列';
		$this->level = 'primary';
		$this->icon = 'clock-o';
	}
	
	/**
	 * 席位已分配事件
	 */
	function _event_seat_assigned()
	{
		$this->title = '分配席位';
		$this->level = 'primary';
		$this->icon = 'list-alt';
		
		if(!empty($this->info['seat']))
		{
			$this->CI->load->model('seat_model');
			
			$seat = $this->CI->seat_model->get_seat($this->info['seat']);
			if(!$seat)
				return;
			
			$seat_text = '<span class="flags-16">'.flag($seat['iso'], true).$seat['name'].'</span>';
			
			$this->text = "面试官向代表分配了 {$seat_text} 为其席位。";
		}
	}
	
	/**
	 * 席位选择已分配事件
	 */
	function _event_seat_added()
	{
		$this->title = '分配席位选择';
		$this->level = 'primary';
		$this->icon = 'list-alt';
		
		$this->CI->load->model('seat_model');
		if(!empty($this->info['selectability']))
		{
			$sids = $this->CI->seat_model->get_seats_by_selectabilities($this->info['selectability']);
		}
		
		if($sids)
		{
			$this->CI->load->model('committee_model');
			
			$seat_text = '<ul class="list-unstyled flags-16" style="margin-top: 10.5px;">';
			
			foreach($sids as $sid)
			{
				$seat = $this->CI->seat_model->get_seat($sid);
				$committee = $this->CI->committee_model->get_committee($seat['committee']);
				$flag = flag($seat['iso'], true);
				
				$seat_text .= "<li>{$flag}{$seat['name']}（{$committee['abbr']}）</li>";
			}
			
			$seat_text .= '</ul>';
			
			if($this->info['new'])
			{
				$this->text = "面试官向代表分配了以下席位。{$seat_text}";
			}
			else
			{
				$this->text = "面试官向代表追加分配了以下席位。{$seat_text}";
			}
		}
	}
	
	/**
	 * 席位已取消事件
	 */
	function _event_seat_cancelled()
	{
		$this->title = '取消席位选择';
		$this->level = 'primary';
		$this->icon = 'list-alt';
		
		if(!empty($this->info['seat']))
		{
			$this->CI->load->model('seat_model');
			
			$seat = $this->CI->seat_model->get_seat($this->info['seat']);
			if(!$seat)
				return;
			
			$seat_text = '<span class="flags-16">'.flag($seat['iso'], true).$seat['name'].'</span>';
			
			$this->text = "代表已经取消了选定的席位 {$seat_text}。";
		}
	}
	
	/**
	 * 席位已释放事件
	 */
	function _event_seat_released()
	{
		$this->title = '席位被释放';
		$this->level = 'warning';
		$this->icon = 'list-alt';
		
		if(!empty($this->info['seat']))
		{
			$this->CI->load->model('seat_model');
			
			$seat = $this->CI->seat_model->get_seat($this->info['seat']);
			if(!$seat)
				return;
			
			$seat_text = '<span class="flags-16">'.flag($seat['iso'], true).$seat['name'].'</span>';
			
			$this->text = "代表选定的席位 {$seat_text} 已被系统自动释放。";
		}
	}
	
	/**
	 * 席位已选择事件
	 */
	function _event_seat_selected()
	{
		$this->title = '选择席位';
		$this->level = 'success';
		$this->icon = 'list-alt';
		
		if(!empty($this->info['seat']))
		{
			$this->CI->load->model('seat_model');
			
			$seat = $this->CI->seat_model->get_seat($this->info['seat']);
			if(!$seat)
				return;
			
			$seat_text = '<span class="flags-16">'.flag($seat['iso'], true).$seat['name'].'</span>';
			
			$this->text = "代表从已经分配的席位中选定 {$seat_text} 为其席位。";
		}
	}
	
	/**
	 * 席位已选择事件
	 */
	function _event_seat_locked()
	{
		$this->title = '锁定席位';
		$this->level = 'success';
		$this->icon = 'lock';
		
		if(!empty($this->info['seat']))
		{
			$this->CI->load->model('seat_model');
			
			$seat = $this->CI->seat_model->get_seat($this->info['seat']);
			if(!$seat)
				return;
			
			$seat_text = '<span class="flags-16">'.flag($seat['iso'], true).$seat['name'].'</span>';
			
			$this->text = "代表锁定 {$seat_text} 为其席位。";
		}
	}
	
	/**
	 * 账单生成事件
	 */
	function _event_invoice_generated()
	{
		$this->title = '生成账单';
		$this->level = 'primary';
		$this->icon = 'file-text';
		
		if(!empty($this->info['invoice']))
		{
			$this->CI->load->model('invoice_model');
			
			$invoice = $this->CI->invoice_model->get_invoice($this->info['invoice']);
			if(!$invoice)
				return;
			
			$this->text = "代表账单 #{$invoice['id']} 已经生成。";
		}
	}
	
	/**
	 * 账单支付完成事件
	 */
	function _event_invoice_received()
	{
		$this->title = '账单支付完成';
		$this->level = 'success';
		$this->icon = 'file-text';
		
		if(!empty($this->info['invoice']))
		{
			$this->CI->load->model('invoice_model');
			
			$invoice = $this->CI->invoice_model->get_invoice($this->info['invoice']);
			if(!$invoice)
				return;
			
			$this->text = "代表账单 #{$invoice['id']} 已经确认完成支付。";
		}
	}
	
	/**
	 * 账单已更新事件
	 */
	function _event_invoice_updated()
	{
		$this->title = '更新账单';
		$this->level = 'primary';
		$this->icon = 'file-text';
		
		if(!empty($this->info['invoice']))
		{
			$this->CI->load->model('invoice_model');
			
			$invoice = $this->CI->invoice_model->get_invoice($this->info['invoice']);
			if(!$invoice)
				return;
			
			$this->text = "代表账单 #{$invoice['id']} 已经更新。";
		}
	}
	
	/**
	 * 账单已更新事件
	 */
	function _event_invoice_cancelled()
	{
		$this->title = '取消账单';
		$this->level = 'warning';
		$this->icon = 'file-text';
		
		if(!empty($this->info['invoice']))
		{
			$this->CI->load->model('invoice_model');
			
			$invoice = $this->CI->invoice_model->get_invoice($this->info['invoice']);
			if(!$invoice)
				return;
			
			$this->text = "代表账单 #{$invoice['id']} 已经被取消。";
		}
	}
	
	/**
	 * 申请完成事件
	 */
	function _event_locked()
	{
		$this->title = '申请完成';
		$this->level = 'success';
		$this->icon = 'star';
		
		$this->text = "代表确认申请完成。";
	}
	
	/**
	 * 退会事件
	 */
	function _event_quitted()
	{
		$this->title = '退会';
		$this->level = 'danger';
		$this->icon = 'recycle';
		
		if(!empty($this->info['reason']))
		{
			$this->text = "<blockquote><p>{$this->info['reason']}</p></blockquote>代表由于以上原因退会。";
		}
	}
	
	/**
	 * 取消退会事件
	 */
	function _event_unquitted()
	{
		$this->title = '退会取消';
		$this->level = 'success';
		$this->icon = 'undo';
		
		$this->text = "管理员取消了代表的退会状态。";
	}
	
	/**
	 * 计划删除事件
	 */
	function _event_deleted()
	{
		$this->title = '计划删除帐户';
		$this->level = 'danger';
		$this->icon = 'trash';
		
		if(!empty($this->info['reason']))
		{
			$this->text = "<blockquote><p>{$this->info['reason']}</p></blockquote>由于以上原因，管理员停用并计划删除了代表帐户。";
		}
	}
	
	/**
	 * 恢复帐户事件
	 */
	function _event_recovered()
	{
		$this->title = '恢复帐户';
		$this->level = 'success';
		$this->icon = 'undo';
		
		$this->text = "管理员恢复了即将被删除的代表帐户。";
	}
	
	/**
	 * 更换申请类型事件
	 */
	function _event_type_changed()
	{
		$this->title = '更换参会申请类型';
		$this->level = 'warning';
		$this->icon = 'exchange';
		
		if(!empty($this->info['reason']) && !empty($this->info['old']) && !empty($this->info['new']))
		{
			$this->CI->load->model('delegate_model');
			
			$old = $this->CI->delegate_model->application_type_text($this->info['old']);
			$new = $this->CI->delegate_model->application_type_text($this->info['new']);
			
			$this->text = "代表的参会申请类型由{$old}变更为了{$new}。<blockquote><p>{$this->info['reason']}</p></blockquote>变更由于以上原因产生。";
		}
	}
}

/* End of file Event.php */
/* Location: ./application/libraries/Event.php */