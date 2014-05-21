<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 账单类库
 * @package iPlacard
 * @since 2.0
 */
class Invoice extends CI_Model
{
	private $CI;

	private $pdf_api = 'http://pdf.api.iplacard.com/';
	
	protected $id = 0;
	protected $delegate = 0;
	protected $title = '';
	protected $items = array();
	protected $discounts = array();
	protected $amount = 0;
	protected $generate_time = 0;
	protected $due_time = 0;
	protected $receive_time = 0;
	protected $status = 'unpaid';
	protected $trigger = array();
	protected $transaction = array();
	protected $cashier = 0;
	
	/**
	 * @var array 代表信息
	 */
	protected $delegate_info = array();
	
	/**
	 * @var string PDF账单文件存放路径
	 */
	private $path = '';
	
	function __construct()
	{
		parent::__construct();
		
		$this->CI =& get_instance();
		$this->CI->load->model('invoice_model');
		
		//文件路径
		$this->path = './data/'.IP_INSTANCE_ID.'/invoice/';
	}
	
	/**
	 * 设置账单名称
	 */
	function title($title)
	{
		$this->title = $title;
	}
	
	/**
	 * 设置账单对象
	 */
	function to($delegate)
	{
		$this->CI->load->model('delegate_model');
		
		$info = $this->CI->delegate_model->get_delegate($delegate);
		if(!$delegate)
			return false;
		
		$this->delegate = $delegate;
		$this->delegate_info = $info;
	}
	
	/**
	 * 增加项目明细
	 * @param string $title 项目标题
	 * @param float $amount 项目金额
	 * @param array $detail 详细信息
	 */
	function item($title, $amount, $detail = array())
	{
		$data = array(
			'title' => $title,
			'amount' => $amount,
			'detail' => $detail
		);
		
		$this->items[] = $data;
		$this->amount += $amount;
	}
	
	/**
	 * 增加折扣明细
	 * @param string $title 折扣标题
	 * @param float $amount 折扣金额
	 * @param array $detail 折扣信息
	 */
	function discount($title, $amount, $detail = array())
	{
		$data = array(
			'title' => $title,
			'amount' => $amount,
			'detail' => $detail
		);
		
		$this->discounts[] = $data;
		$this->amount -= $amount;
	}
	
	/**
	 * 增加转账记录
	 * @param string $gateway 通道
	 * @param string $transaction_id 交易流水号 
	 * @param float $amount 转账金额
	 * @param bool $confirm 交易是否确认
	 * @param array $extra 附加信息
	 */
	function transaction($gateway, $transaction_id, $amount, $time, $confirm = true, $extra = array())
	{
		$data = array(
			'gateway' => $gateway,
			'amount' => $amount,
			'transaction' => $transaction_id,
			'confirm' => $confirm,
			'time' => $time,
			'extra' => $extra
		);
		
		$this->transaction = $data;
	}
	
	/**
	 * 设置到期时间
	 * @param int $time 时间或时间差
	 */
	function due_time($time)
	{
		if($time < 365 * 24 * 60 * 60)
			$time = time() + $time;
		
		$this->due_time = $time;
	}
	
	/**
	 * 增加触发器
	 * @param string $type 触发类型
	 * @param string $function 触发函数
	 * @param array $args 参数
	 */
	function trigger($type, $function, $args)
	{
		if(!in_array($type, array('overdue', 'receive', 'cancel', 'refund')))
			return false;
		
		$data = array(
			'type' => $type,
			'function' => $function,
			'args' => $args
		);
		
		$this->trigger[] = $data;
	}
	
	/**
	 * 显示账单
	 */
	function display($full = false)
	{
		$this->CI->load->library('ui');
		$this->CI->load->helper('date');
		
		$delegate = $this->delegate_info;
		$delegate['application_type_text'] = $this->CI->delegate_model->application_type_text($delegate['application_type']);
		
		if(!empty($delegate['group']))
		{
			$this->CI->load->model('group_model');
			
			$group = $this->CI->group_model->get_group($delegate['group']);
			
			$vars['group'] = $group;
		}
		
		$pids = $this->CI->delegate_model->get_profile_ids('delegate', $this->delegate);
		if($pids)
		{
			foreach($pids as $pid)
			{
				$one = $this->CI->delegate_model->get_profile($pid);
				$delegate[$one['name']] = $one['value'];
			}
		}
		
		$vars['delegate'] = $delegate;
		
		switch($this->status)
		{
			case 'unpaid':
				$status_class = 'warning';
				if($this->due_time < time())
					$status_class = 'danger';
				break;
			case 'paid':
				$status_class = 'success';
				break;
			case 'cancelled':
				$status_class = 'muted';
				break;
			case 'refunded':
				$status_class = 'info';
				break;
		}
		
		$vars['invoice'] = get_object_vars($this);
		$vars['invoice']['status_text'] = $this->CI->invoice_model->status_text($this->status);
		$vars['invoice']['status_class'] = $status_class;
		
		
		$vars['currency']['sign'] = option('invoice_currency_sign', '￥');
		$vars['currency']['text'] = option('invoice_currency_text', 'RMB');
		
		return $this->load->view($full ? 'invoice_print' : 'invoice', $vars, true);
	}
	
	/**
	 * 生成PDF账单
	 */
	function pdf()
	{
		$this->load->library('curl');
		
		//生成数据
		$data = array(
			'html' => $this->display(true)
		);
		
		//获取结果
		$return = $this->curl->simple_post($this->pdf_api, $data);
		
		if(empty($return))
			return false;
		
		//检查路径
		$path = $this->path.$this->id.'/';
		if(!file_exists($path))
			mkdir($path, DIR_WRITE_MODE, true);
		
		//保存HTML文件
		file_put_contents($path.$this->status.'.html', $data['html']);
		
		//保存PDF文件
		if(!file_put_contents($path.$this->status.'.pdf', $return))
			return false;
		
		return $path.$this->status.'.pdf';
	}
	
	/**
	 * 生成账单
	 */
	function generate()
	{
		if($this->amount < 0)
			$this->amount = 0;
		
		$id = $this->CI->invoice_model->generate_invoice($this->delegate, $this->title, $this->amount, $this->due_time, $this->items, $this->discounts, $this->trigger);
		$this->clear();
		$this->load($id);
		
		//发送邮件
		$this->CI->load->library('email');
		$this->CI->load->library('parser');
		$this->CI->load->helper('date');
		
		$email_data = array(
			'uid' => $this->delegate,
			'id' => $this->id,
			'title' => $this->title,
			'generate_time' => unix_to_human($this->generate_time),
			'due_time' => unix_to_human($this->due_time),
			'url' => base_url("apply/invoice/{$this->id}"),
		);
		
		$pdf_data = $this->pdf();
		
		$this->CI->email->to($this->delegate_info['email']);
		$this->CI->email->subject('新的账单已经生成');
		$this->CI->email->html($this->parser->parse_string(option('email_invoice_generated', "新的账单 #{id} 已经生成，账单的信息如下：\n\n"
				. "\t账单名称：{title}\n\n"
				. "\t账单生成时间：{generate_time}\n\n"
				. "\t账单到期时间：{due_time}\n\n"
				. "请与账单到期前完成支付，请访问 {url} 查看并支付账单。"), $email_data, true));
		if($pdf_data)
			$this->CI->email->attach($pdf_data, 'attachment', "Invoice-{$this->id}.pdf");
		$this->CI->email->send();
		$this->CI->email->clear();

		//短信通知代表
		if($this->amount > 0 && option('sms_enabled', false))
		{
			$this->load->model('sms_model');
			$this->load->library('sms');

			$this->CI->sms->to($this->delegate);
			$this->CI->sms->message("新的账单 #{$this->id} 已经生成，请登录 iPlacard 系统查看详细信息并完成支付。");
			$this->CI->sms->queue();
		}
		
		$this->CI->delegate_model->add_event($this->delegate, 'invoice_generated', array('invoice' => $this->id));
		$this->CI->system_model->log('invoice_generated', array('invoice' => $this->id));
		
		//无需支付情况
		if($this->amount == 0)
			$this->receive(0);
	}
	
	/**
	 * 更新账单
	 */
	function update($notice = false)
	{
		//生成提交数据
		$data = get_object_vars($this);
		
		unset($data['CI']);
		unset($data['pdf_api']);
		unset($data['path']);
		unset($data['delegate_info']);
		
		$this->CI->invoice_model->edit_invoice($data, $this->id);
		
		$this->CI->delegate_model->add_event($this->delegate, 'invoice_updated', array('invoice' => $this->id));
		$this->CI->system_model->log('invoice_updated', array('invoice' => $this->id));
		
		if($notice)
		{
			//发送邮件
			$this->CI->load->library('email');
			$this->CI->load->library('parser');
			$this->CI->load->helper('date');

			$email_data = array(
				'uid' => $this->delegate,
				'id' => $this->id,
				'title' => $this->title,
				'url' => base_url("apply/invoice/{$this->id}"),
			);

			$pdf_data = $this->pdf();

			$this->CI->email->to($this->delegate_info['email']);
			$this->CI->email->subject('账单已经更新');
			$this->CI->email->html($this->parser->parse_string(option('email_invoice_updated', '账单 #{id} 已经更新，请访问 {url} 查看新的账单信息。'), $email_data, true));
			if($pdf_data)
				$this->CI->email->attach($pdf_data, 'attachment', "Invoice-{$this->id}.pdf");
			$this->CI->email->send();
			$this->CI->email->clear();

			//短信通知代表
			if($this->amount > 0 && option('sms_enabled', false))
			{
				$this->load->model('sms_model');
				$this->load->library('sms');

				$this->CI->sms->to($this->delegate);
				$this->CI->sms->message("您的账单 #{$this->id} 已经更新，请登录 iPlacard 系统查看新的账单信息。");
				$this->CI->sms->queue();
			}
		}
	}
	
	/**
	 * 收到账单
	 */
	function receive($cashier = '')
	{
		$id = $this->id;
		
		if($cashier == '')
			$cashier = uid();
		
		$this->clear();
		$this->CI->invoice_model->receive_invoice($id, $cashier);
		$this->load($id);		
		
		$this->do_trigger('receive');
		
		//发送邮件
		$this->CI->load->library('email');
		$this->CI->load->library('parser');
		$this->CI->load->helper('date');

		$email_data = array(
			'uid' => $this->delegate,
			'id' => $this->id,
			'title' => $this->title,
			'generate_time' => unix_to_human($this->generate_time),
			'due_time' => unix_to_human($this->due_time),
			'receive_time' => unix_to_human($this->receive_time),
			'url' => base_url("apply/invoice/{$this->id}"),
		);

		$pdf_data = $this->pdf();

		$this->CI->email->to($this->delegate_info['email']);
		$this->CI->email->subject('账单支付完成');
		$this->CI->email->html($this->parser->parse_string(option('email_invoice_received', '账单 #{id} 已经完成支付，请访问 {url} 查看账单信息。'), $email_data, true));
		if($pdf_data)
			$this->CI->email->attach($pdf_data, 'attachment', "Invoice-{$this->id}.pdf");
		$this->CI->email->send();
		$this->CI->email->clear();

		//短信通知代表
		if($this->amount > 0 && option('sms_enabled', false))
		{
			$this->load->model('sms_model');
			$this->load->library('sms');

			$this->CI->sms->to($this->delegate);
			$this->CI->sms->message("您的账单 #{$this->id} 已经完成支付，请登录 iPlacard 系统查看账单信息。");
			$this->CI->sms->queue();
		}
		
		$this->CI->delegate_model->add_event($this->delegate, 'invoice_received', array('invoice' => $this->id));
		$this->CI->system_model->log('invoice_received', array('invoice' => $this->id));
	}
	
	/**
	 * 取消账单
	 */
	function cancel($cashier = '')
	{
		$id = $this->id;
		
		if($cashier == '')
			$cashier = uid();
		
		$this->clear();
		$this->CI->invoice_model->cancel_invoice($id, $cashier);
		$this->load($id);
		
		//发送邮件
		$this->CI->load->library('email');
		$this->CI->load->library('parser');
		$this->CI->load->helper('date');

		$email_data = array(
			'uid' => $this->delegate,
			'id' => $this->id,
			'title' => $this->title,
			'generate_time' => unix_to_human($this->generate_time),
			'due_time' => unix_to_human($this->due_time),
			'receive_time' => unix_to_human($this->receive_time),
			'url' => base_url("apply/invoice/{$this->id}"),
		);

		$pdf_data = $this->pdf();

		$this->CI->email->to($this->delegate_info['email']);
		$this->CI->email->subject('账单已经取消');
		$this->CI->email->html($this->parser->parse_string(option('email_invoice_cancelled', '账单 #{id} 已经取消，请访问 {url} 查看账单信息。'), $email_data, true));
		if($pdf_data)
			$this->CI->email->attach($pdf_data, 'attachment', "Invoice-{$this->id}.pdf");
		$this->CI->email->send();
		$this->CI->email->clear();

		//短信通知代表
		if($this->amount > 0 && option('sms_enabled', false))
		{
			$this->load->model('sms_model');
			$this->load->library('sms');

			$this->CI->sms->to($this->delegate);
			$this->CI->sms->message("您的账单 #{$this->id} 已经取消，请登录 iPlacard 系统查看账单信息。");
			$this->CI->sms->queue();
		}
		
		$this->CI->delegate_model->add_event($this->delegate, 'invoice_cancelled', array('invoice' => $this->id));
		$this->CI->system_model->log('invoice_cancelled', array('invoice' => $this->id));
	}
	
	/**
	 * 账单提醒
	 */
	function remind($send_pdf = false)
	{
		//发送邮件
		$this->CI->load->library('email');
		$this->CI->load->library('parser');
		$this->CI->load->helper('date');
		
		$overdued = false;
		if($this->due_time < time())
			$overdued = true;
		
		$email_data = array(
			'uid' => $this->delegate,
			'id' => $this->id,
			'title' => $this->title,
			'generate_time' => unix_to_human($this->generate_time),
			'due_time' => unix_to_human($this->due_time),
			'url' => base_url("apply/invoice/{$this->id}"),
		);

		if($send_pdf)
			$pdf_data = $this->pdf();
		
		$this->CI->email->to($this->delegate_info['email']);
		$this->CI->email->subject($overdued ? '账单逾期提醒' : '账单待支付提醒');
		
		if($overdued)
		{
			$this->CI->email->html($this->parser->parse_string(option('email_invoice_overdue_reminder', "您的账单 #{id} 已经逾期，账单的信息如下：\n\n"
				. "\t账单名称：{title}\n\n"
				. "\t账单生成时间：{generate_time}\n\n"
				. "\t账单到期时间：{due_time}\n\n"
				. "如长时间未支付账单，您的申请将会被暂停。请立即访问 {url} 查看并支付账单。"), $email_data, true));
		}
		else
		{
			$this->CI->email->html($this->parser->parse_string(option('email_invoice_reminder', "您的账单 #{id} 正等待支付，账单的信息如下：\n\n"
				. "\t账单名称：{title}\n\n"
				. "\t账单生成时间：{generate_time}\n\n"
				. "\t账单到期时间：{due_time}\n\n"
				. "请与账单到期前完成支付，请访问 {url} 查看并支付账单。"), $email_data, true));
		}
		
		if($send_pdf && $pdf_data)
			$this->CI->email->attach($pdf_data, 'attachment', "Invoice-{$this->id}.pdf");
		
		$this->CI->email->send();
		$this->CI->email->clear();
		
		$this->CI->system_model->log('invoice_reminded', array('invoice' => $this->id));
	}
	
	/**
	 * 获取账单信息
	 */
	function get($item)
	{
		return $this->{$item};
	}
	
	/**
	 * 载入账单
	 */
	function load($id)
	{
		$invoice = $this->CI->invoice_model->get_invoice($id);
		
		if(!$invoice)
			return false;
		
		$this->id = $invoice['id'];
		$this->title = $invoice['title'];
		$this->items = $invoice['items'];
		$this->discounts = $invoice['discounts'];
		$this->amount = $invoice['amount'];
		$this->generate_time = $invoice['generate_time'];
		$this->due_time = $invoice['due_time'];
		$this->receive_time = $invoice['receive_time'];
		$this->status = $invoice['status'];
		$this->trigger = $invoice['trigger'];
		$this->transaction = $invoice['transaction'];
		$this->cashier = $invoice['cashier'];
		
		$this->to($invoice['delegate']);
		
		return true;
	}
	
	/**
	 * 重置属性
	 */
	function clear()
	{
		$this->id = 0;
		$this->delegate = 0;
		$this->title = '';
		$this->items = array();
		$this->discounts = array();
		$this->amount = 0;
		$this->generate_time = 0;
		$this->due_time = 0;
		$this->receive_time = 0;
		$this->status = 'unpaid';
		$this->trigger = array();
		$this->transaction = array();
		$this->cashier = 0;
	}
	
	/**
	 * 触发器
	 */
	function do_trigger($type)
	{
		if(!empty($this->trigger))
		{
			foreach($this->trigger as $trigger)
			{
				if($trigger['type'] == $type)
				{
					call_user_func_array(array($this, "_trigger_{$trigger['function']}"), array($trigger['args']));
				}
			}
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * 更新申请状态触发器
	 */
	private function _trigger_change_status($args)
	{
		$this->CI->load->model('delegate_model');
		
		$uid = $args['delegate'];
		$status = $args['status'];
		
		$this->CI->delegate_model->change_status($uid, $status);
	}
	
	/**
	 * 释放席位触发器
	 */
	private function _trigger_release_seat($args)
	{
		$this->CI->load->model('delegate_model');
		$this->CI->load->model('seat_model');
		
		$seat_id = $this->CI->seat_model->get_delegate_seat($this->delegate);
		if($seat_id)
		{
			$seat = $this->CI->seat_model->get_seat($seat_id);
			
			//释放席位
			$this->CI->seat_model->change_seat_status($seat_id, 'available', NULL);
			$this->CI->seat_model->assign_seat($seat_id, NULL);
			
			$this->CI->delegate_model->add_event($this->delegate, 'seat_released', array('seat' => $seat_id));
			$this->CI->user_model->add_message($this->delegate, '您选定的席位由于账单逾期已经被释放。');
			
			//TODO: 候选席位调整

			//发送邮件
			$this->CI->load->library('email');
			$this->CI->load->library('parser');
			$this->CI->load->helper('date');
			
			$data = array(
				'uid' => $this->delegate,
				'delegate' => $this->delegate_info['name'],
				'seat' => $seat['name'],
				'time' => unix_to_human(time()),
			);

			$this->CI->email->to($this->delegate_info['email']);
			$this->CI->email->subject('选定席位已释放');
			$this->CI->email->html($this->parser->parse_string(option('email_seat_overdue_released', '由于账单逾期，您选定的席位{seat}已于 {time} 释放，您可登录 iPlacard 系统重新选择席位。'), $data, true));
			$this->CI->email->send();
			$this->CI->email->clear();

			//短信通知代表
			if(option('sms_enabled', false))
			{
				$this->load->model('sms_model');
				$this->load->library('sms');

				$this->CI->sms->to($this->delegate);
				$this->CI->sms->message("由于账单逾期，您选定的席位已被释放，请登录 iPlacard 系统重新选择席位。");
				$this->CI->sms->queue();
			}
		}
	}
}

/* End of file Invoice.php */
/* Location: ./application/libraries/Invoice.php */