<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 计划任务控制器
 * @package iPlacard
 * @since 2.0
 */
class Cron extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->benchmark->mark('exec_start');
		
		$this->load->helper('date');
		
		if(!$this->input->is_cli_request())
			exit('iPlacard Cron Job must be run from the command line.');
		
		$time = date('Y-m-d H:i:s');
		echo "iPlacard Cron Job started at $time.\n";
	}
	
	function __destruct()
	{
		if($this->input->is_cli_request())
		{
			$this->benchmark->mark('exec_end');
			$time = (int) ($this->benchmark->elapsed_time('exec_start', 'exec_end') * 1000);
			echo "\niPlacard Cron Job halted in {$time}ms.\n";
		}
	}
	
	function minutely()
	{
		echo "\nProcessing Cron Minutely.\n\n";
		$this->_send_sms();
	}
	
	function hourly()
	{
		echo "\nProcessing Cron Hourly.\n\n";
		$this->_remind_interview();
	}
	
	function daily()
	{
		echo "\nProcessing Cron Daily.\n\n";
		$this->_remind_invoice_overdue();
	}
	
	function weekly()
	{
		echo "\nProcessing Cron Weekly.\n\n";
		$this->_remind_invoice();
	}
	
	/**
	 * 发送面试提醒
	 */
	private function _remind_interview()
	{
		$this->load->model('admin_model');
		$this->load->model('delegate_model');
		$this->load->model('interview_model');
		
		//获取所有在通知窗口的面试安排
		$reminders = array();
		
		$ids = $this->interview_model->get_interview_ids('status', 'arranged');
		if($ids)
		{
			foreach($ids as $id)
			{
				$interview = $this->interview_model->get_interview($id);
				
				//面试开始前2小时内通知
				$schedule = intval($interview['schedule_time']);
				if($schedule > (time() + 1 * 60 * 60) && $schedule < (time() + 2 * 60 * 60))
					$reminders[] = $interview;
			}
		}
		
		if(!empty($reminders))
		{
			$this->load->library('email');
			$this->load->library('parser');
			
			$count = count($reminders);
			echo "{$count} interview(s) to be reminded.\n";
			
			foreach($reminders as $reminder)
			{
				$sent = true;
				
				echo "Processing Inteviewer Reminder ID {$reminder['id']}.\n";
				
				$interviewer = $this->user_model->get_user($reminder['interviewer']);
				$delegate = $this->user_model->get_user($reminder['delegate']);
				
				$data = array(
					'interview_id' => $reminder['id'],
					'interviewer_name' => $interviewer['name'],
					'interviewer_email' => $interviewer['email'],
					'delegate_id' => $delegate['id'],
					'delegate_name' => $delegate['name'],
					'delegate_email' => $delegate['email'],
					'time' => unix_to_human($reminder['schedule_time'])
				);

				//通知面试官
				$this->email->to($interviewer['email']);
				$this->email->subject('iPlacard 面试提醒');
				$this->email->html($this->parser->parse_string(option('email_cron_interview_reminder', '您将于 {time} 面试{delegate_name}代表，请预留充足时间并按照约定的方式与代表联系。'), $data, true));
				
				if(!$this->email->send())
				{
					echo "Failed to send Interview Reminder to {$interviewer['email']}.\n";
					$this->system_model->log('notice_failed', array('id' => $interviewer['id'], 'type' => 'email', 'content' => 'cron_interview_reminder'), 0);
					$sent = false;
				}
				else
				{
					echo "Interview Reminder sent to {$interviewer['email']}.\n";
				}
				
				$this->email->clear();
				
				//短信通知
				if(option('sms_enabled', false))
				{
					$this->load->model('sms_model');
					$this->load->library('sms');
					
					$this->sms->to($interviewer['id']);
					$this->sms->message($this->parser->parse_string('将于 {time} 面试{delegate_name}代表，请预留充足时间。', $data, true));
					
					if(!$this->sms->send())
					{
						echo "Failed to send Interview Reminder SMS to {$interviewer['phone']}.\n";
						$this->system_model->log('notice_failed', array('id' => $interviewer['id'], 'type' => 'sms', 'content' => 'cron_interview_reminder'), 0);
						$sent = false;
					}
					else
					{
						echo "Interview Reminder SMS sent to {$interviewer['phone']}.\n";
					}
					
					$this->sms->clean();
				}
				
				//通知代表
				if(option('cron_remind_delegate_interview', true))
				{
					$this->email->to($delegate['email']);
					$this->email->subject('iPlacard 面试提醒');
					$this->email->html($this->parser->parse_string(option('email_cron_delegate_interview_reminder', '您与面试官{interviewer_name}的面试将于 {time} 进行，请预留充足时间并按照约定的方式与面试官联系。'), $data, true));
					
					if(!$this->email->send())
					{
						echo "Failed to send Interview Reminder to {$delegate['email']}.\n";
						$this->system_model->log('notice_failed', array('id' => $delegate['id'], 'type' => 'email', 'content' => 'cron_interview_reminder'));
						$sent = false;
					}
					else
					{
						echo "Interview Reminder sent to {$delegate['email']}.\n";
					}
					
					$this->email->clear();
					
					//短信通知
					if(option('sms_enabled', false))
					{
						$this->load->model('sms_model');
						$this->load->library('sms');

						$this->sms->to($delegate['id']);
						$this->sms->message($this->parser->parse_string('与{interviewer_name}的面试将在 {time} 进行，请预留充足时间。', $data, true));
						if(!$this->sms->send())
						{
							echo "Failed to send Interview Reminder SMS to {$delegate['phone']}.\n";
							$this->system_model->log('notice_failed', array('id' => $delegate['id'], 'type' => 'sms', 'content' => 'cron_interview_reminder'));
							$sent = false;
						}
						else
						{
							echo "Interview Reminder SMS sent to {$delegate['phone']}.\n";
						}
						
						$this->sms->clean();
					}
				}
				
				//记录通知时间
				if($sent)
				{
					$this->system_model->log('cron_interview_reminder_sent', array('inteview' => $reminder['id']), 0);
				}
			}
		}
		else
		{
			echo "No Interview to be reminded.\n";
		}
	}
	
	/**
	 * 提醒账单到期
	 */
	private function _remind_invoice()
	{
		$this->load->model('invoice_model');
		$this->load->library('invoice');
		
		$ids = $this->invoice_model->get_invoice_ids('status', 'unpaid', 'due_time >=', time(), 'transaction IS NULL', NULL);
		if($ids)
		{
			$count = count($ids);
			echo "{$count} Invoice(s) to be reminded.\n";
			
			foreach($ids as $id)
			{
				$this->invoice->load($id);
				$this->invoice->remind();
			}
		}
		else
		{
			echo "No Invoice to be reminded.\n";
		}
	}
	
	/**
	 * 处理账单逾期
	 */
	private function _remind_invoice_overdue()
	{
		$this->load->model('invoice_model');
		$this->load->library('invoice');
		
		$ids = $this->invoice_model->get_invoice_ids('status', 'unpaid', 'due_time <', time(), 'transaction IS NULL', NULL);
		if($ids)
		{
			$count = count($ids);
			echo "{$count} Overdued Invoice(s) to be reminded.\n";
			
			foreach($ids as $id)
			{
				$this->invoice->load($id);
				$this->invoice->do_trigger('overdue');
				$this->invoice->remind();
			}
		}
		else
		{
			echo "No Overdued Invoice to be reminded.\n";
		}
	}
	
	/**
	 * 处理短信队列
	 */
	private function _send_sms()
	{
		$this->load->model('sms_model');
		$this->load->library('sms');
		
		$ids = $this->sms_model->get_sms_ids('status', 'queue');
		if($ids)
		{
			$count = count($ids);
			echo "{$count} SMS(s) to be sent.\n";
			
			foreach($ids as $id)
			{
				echo "Sending SMS ID {$id}.\n";
				
				if(!$this->sms->api_send($id))
				{
					echo "Failed to send SMS ID {$id}.\n";
					$this->system_model->log('sms_failed', array('id' => $id));
				}
				else
				{
					echo "SMS ID {$id} sent.\n";
				}
			}
		}
		else
		{
			echo "No SMS to be sent.\n";
		}
	}
}

/* End of file cron.php */
/* Location: ./application/controllers/cron.php */