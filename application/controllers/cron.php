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
		$this->_cleanup_symlink_download();
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
		//$this->_remind_invoice_overdue();
		$this->_remove_deleted_delegate_data();
	}
	
	function weekly()
	{
		echo "\nProcessing Cron Weekly.\n\n";
		$this->_remind_invoice();
	}
	
	/**
	 * 清理过时文件下载符号链接
	 */
	private function _cleanup_symlink_download()
	{
		$this->load->helper('file');
		
		$list = scandir('./temp/'.IP_INSTANCE_ID.'/download');
		if($list)
		{
			$count = count($list);
			echo "{$count} symlink(s) to be checked.\n";
			
			foreach($list as $directory)
			{
				if(in_array($directory, array('.', '..')))
					continue;
				
				if(filectime('./temp/'.IP_INSTANCE_ID.'/download/'.$directory) < time() - 15 * 60)
				{
					delete_files('./temp/'.IP_INSTANCE_ID.'/download/'.$directory);
					@rmdir('./temp/'.IP_INSTANCE_ID.'/download/'.$directory);
					
					echo "Symlink {$directory} removed.\n";
				}
				else
				{
					echo "Symlink {$directory} is unchanged.\n";
				}
			}
		}
		else
		{
			echo "No Symlink to be deleted.\n";
		}
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
	 * 移除已经删除的用户帐户信息
	 */
	private function _remove_deleted_delegate_data()
	{
		$this->load->model('delegate_model');
		
		$ids = $this->delegate_model->get_delegate_ids('status', 'deleted');
		if($ids)
		{
			$this->load->model('document_model');
			$this->load->model('seat_model');
			$this->load->model('interview_model');
			$this->load->model('invoice_model');
			$this->load->model('note_model');
			$this->load->model('twostep_model');
			
			$count = count($ids);
			echo "{$count} Delegate(s) to be deleted.\n";
			
			foreach($ids as $id)
			{
				echo "Deleting Delegate ID {$id}.\n";
				
				//未到删除时间
				if((user_option('delete_time', time(), $id) + option('delegate_delete_lock', 7) * 24 * 60 * 60) > time())
				{
					echo "Delegate ID {$id} skipped.\n";
					continue;
				}
				
				$delete_schema = array();
				$data = array();
				
				//删除文件
				$base_path = './data/'.IP_INSTANCE_ID.'/';
				
				$delete_path = $base_path.'delete/'.$id.'/';
				if(!file_exists($delete_path))
					mkdir($delete_path, DIR_WRITE_MODE, true);
				
				//代表基本信息
				$data['delegate'] = $this->delegate_model->get_delegate($id);
				$delete_schema['id'][] = 'user';
				$delete_schema['id'][] = 'delegate';
				
				//用户头像
				if(file_exists($base_path.'avatar/'.$id.'/'))
					rename($base_path.'avatar/'.$id.'/', $delete_path.'/avatar/');
				
				//代表资料
				$data['delegate_profile'] = $this->delegate_model->get_delegate_profiles($id, 'value');
				$delete_schema['delegate'][] = 'delegate_profile';
				
				//代表事件
				$item_ids = $this->delegate_model->get_delegate_events($id);
				if($item_ids)
				{
					foreach($item_ids as $item_id)
					{
						$data['delegate_event'][$item_id] = $this->delegate_model->get_event($item_id);
					}
					
					$delete_schema['delegate'][] = 'delegate_event';
				}
				
				//代表文件
				$item_ids = $this->document_model->get_document_ids('user', $id);
				if($item_ids)
				{
					foreach($item_ids as $item_id)
					{
						$data['document'][$item_id] = $this->document_model->get_document($item_id);
					}
					
					$delete_schema['user'][] = 'document';
				}
				
				//代表文件版本
				$item_ids = $this->document_model->get_file_ids('user', $id);
				if($item_ids)
				{
					foreach($item_ids as $item_id)
					{
						$data['document_file'][$item_id] = $this->document_model->get_file($item_id);
						
						unlink("{$base_path}document/{$item_id}.{$data['document_file'][$item_id]['filetype']}");
					}
					
					$delete_schema['user'][] = 'document_file';
				}
				
				//代表下载文件日志
				$item_ids = $this->document_model->get_user_downloads($id);
				if($item_ids)
				{
					foreach($item_ids as $item_id)
					{
						$data['document_download'][$item_id] = $this->document_model->get_download($item_id);
					}
					
					$delete_schema['user'][] = 'document_download';
				}
				
				//面试
				$item_ids = $this->interview_model->get_interview_ids('delegate', $id);
				if($item_ids)
				{
					foreach($item_ids as $item_id)
					{
						$data['interview'][$item_id] = $this->interview_model->get_interview($item_id);
					}
					
					$delete_schema['delegate'][] = 'interview';
				}
				
				//账单
				$item_ids = $this->invoice_model->get_delegate_invoices($id);
				if($item_ids)
				{
					mkdir($delete_path.'invoice/', DIR_WRITE_MODE, true);
					
					foreach($item_ids as $item_id)
					{
						$data['invoice'][$item_id] = $this->invoice_model->get_invoice($item_id);
						
						rename($base_path.'invoice/'.$item_id.'/', $delete_path.'invoice/'.$item_id.'/');
					}
					
					$delete_schema['delegate'][] = 'invoice';
				}
				
				//用户发送的信息
				$item_ids = $this->user_model->get_message_ids('sender', $id);
				if($item_ids)
				{
					foreach($item_ids as $item_id)
					{
						$data['message'][$item_id] = $this->user_model->get_message($item_id);
					}
					
					$delete_schema['sender'][] = 'invoice';
				}
				
				//用户收到的信息
				$item_ids = $this->user_model->get_user_messages($id);
				if($item_ids)
				{
					foreach($item_ids as $item_id)
					{
						$data['message'][$item_id] = $this->user_model->get_message($item_id);
					}
					
					$delete_schema['receiver'][] = 'message';
				}
				
				//笔记
				$item_ids = $this->note_model->get_delegate_notes($id);
				if($item_ids)
				{
					foreach($item_ids as $item_id)
					{
						$data['note'][$item_id] = $this->note_model->get_note($item_id);
						$this->note_model->delete_mention($item_id);
					}
					
					$delete_schema['delegate'][] = 'note';
				}
				
				//席位
				$seat_id = $this->seat_model->get_delegate_seat($id);
				if($seat_id)
				{
					$data['seat'] = $this->seat_model->get_seat($seat_id);
					$this->seat_model->change_seat_status($seat_id, 'available', NULL);
					
					//TODO: 候选席位调整
				}
				
				//席位候选
				$item_ids = $this->seat_model->get_delegate_backorder($id);
				if($item_ids)
				{
					foreach($item_ids as $item_id)
					{
						$data['seat_backorder'][$item_id] = $this->seat_model->get_backorder($item_id);
					}
					
					$delete_schema['delegate'][] = 'seat_backorder';
				}
				
				//席位选择
				$item_ids = $this->seat_model->get_delegate_selectability($id);
				if($item_ids)
				{
					foreach($item_ids as $item_id)
					{
						$data['seat_selectability'][$item_id] = $this->seat_model->get_selectability($item_id);
					}
					
					$delete_schema['delegate'][] = 'seat_selectability';
				}
				
				//已经使用的两步验证码
				$item_ids = $this->twostep_model->get_recode_ids('user', $id);
				if($item_ids)
				{
					foreach($item_ids as $item_id)
					{
						$data['twostep_recode'][$item_id] = $this->twostep_model->get_recode($item_id);
					}
					
					$delete_schema['user'][] = 'twostep_recode';
				}
				
				//授权不再要求两步验证记录
				$item_ids = $this->twostep_model->get_safe_ids('user', $id);
				if($item_ids)
				{
					foreach($item_ids as $item_id)
					{
						$data['twostep_safe'][$item_id] = $this->twostep_model->get_safe($item_id);
					}
					
					$delete_schema['user'][] = 'twostep_safe';
				}
				
				//用户设置
				$item_ids = $this->user_model->get_option_ids('user', $id);
				if($item_ids)
				{
					foreach($item_ids as $item_id)
					{
						$data['twostep_safe'][$item_id] = $this->user_model->get_option($item_id);
					}
					
					$delete_schema['user'][] = 'user_option';
				}
				
				//删除数据
				foreach($delete_schema as $schema => $tables)
				{
					$this->db->where($schema, $id);
					$this->db->delete($tables);
				}
				
				$this->system_model->log('delegate_data_removed', array('delegate' => $id, 'data' => $data));
				
				echo "Delegate ID {$id} deleted.\n";
			}
		}
		else
		{
			echo "No Delegate to be deleted.\n";
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