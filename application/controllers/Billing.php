<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 账单控制器
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
 * @package iPlacard
 * @since 2.0
 */
class Billing extends CI_Controller
{
	var $currency_sign = '';
	var $currency_text = '';
	var $gateway = array();
	
	function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->library('ui', array('side' => 'admin'));
		$this->load->model('admin_model');
		$this->load->model('delegate_model');
		$this->load->model('invoice_model');
		$this->load->library('invoice');
		
		//检查登录情况
		if(!is_logged_in())
		{
			login_redirect();
			return;
		}
		
		//检查权限
		if(!$this->user_model->is_admin(uid()) || (!$this->admin_model->capable('cashier')))
		{
			redirect('');
			return;
		}
		
		$this->currency_sign = option('invoice_currency_sign', '￥');
		$this->currency_text = option('invoice_currency_text', 'RMB');
		
		$gateway = option('invoice_payment_gateway', array('汇款', '网银转账', '支付宝', '其他'));
		$this->gateway = array_combine($gateway, $gateway);
		
		$this->ui->now('billing');
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
		$title = '全部账单';
		
		if(isset($param['status']))
		{
			$text_status = array();
			foreach($param['status'] as $one)
			{
				$text_status[] = $this->invoice_model->status_text($one);
			}
			$title = sprintf("%s账单列表", join('、', $text_status));
			
			if(count($param['status']) == 1 && in_array('unpaid', $param['status']) && isset($param['transaction']) && in_array('0', $param['transaction']))
				$part = 'unpaid';
			elseif(count($param['status']) == 1 && in_array('unpaid', $param['status']) && isset($param['transaction']) && in_array('1', $param['transaction']))
				$part = 'pending';
			else
				$part = '';
		}
		else
		{
			$part = 'all';
		}
		
		if(isset($param['group']))
		{
			$this->load->model('group_model');
			
			$text_group = array();
			foreach($param['group'] as $one)
			{
				$text_group[] = $this->group_model->get_group($one, 'name');
			}
			$title = sprintf("%s代表团账单列表", join('、', $text_group));
		}
		
		if(isset($param['delegate']))
		{
			$text_delegate = array();
			foreach($param['delegate'] as $one)
			{
				$text_delegate[] = $this->delegate_model->get_delegate($one, 'name');
			}
			$title = sprintf("%s代表账单列表", join('、', $text_delegate));
		}
		
		//标签地址
		$params = $param;
		
		$params['status'] = array('unpaid');
		$params['transaction'] = array(0);
		$param_tab['unpaid'] = $this->_filter_build($params);
		
		$params['status'] = array('unpaid');
		$params['transaction'] = array(1);
		$param_tab['pending'] = $this->_filter_build($params);
		
		unset($params['status']);
		unset($params['transaction']);
		$param_tab['all'] = $this->_filter_build($params);
		
		$vars = array(
			'param_uri' => $this->_filter_build($param),
			'param_tab' => $param_tab,
			'part' => $part,
			'title' => $title,
		);
		
		$this->ui->title($title, '账单列表');
		$this->load->view('admin/invoice_manage', $vars);
	}
	
	/**
	 * 查看账单信息
	 */
	function invoice($id, $action = 'view')
	{
		$this->load->library('form_validation');
		$this->load->helper('form');
		
		if(!$this->invoice->load($id))
		{
			$this->ui->alert('账单信息不存在。', 'danger', true);
			back_redirect();
			return;
		}
		
		//代表信息
		$delegate = $this->delegate_model->get_delegate($this->invoice->get('delegate'));
		$vars['delegate'] = $delegate;
		
		//编辑转账信息
		if($action == 'transaction')
		{
			$this->form_validation->set_error_delimiters('<div class="help-block">', '</div>');

			$this->form_validation->set_rules('time', '转账时间', 'trim|required|strtotime', array('strtotime' => '转账时间格式有误。'));
			$this->form_validation->set_rules('gateway', '交易渠道', 'trim|required');
			$this->form_validation->set_rules('transaction', '交易流水号', 'trim|required');
			$this->form_validation->set_rules('amount', '转账金额', 'trim|required|numeric');

			if($this->form_validation->run() == true)
			{
				//删除帐户情况
				if($delegate['status'] == 'deleted')
				{
					$this->ui->alert('此代表帐户已停用删除，无法确认账单。', 'danger', true);
					back_redirect();
					return;
				}
				
				$time = $this->input->post('time');
				$gateway = $this->input->post('gateway');
				$transaction = $this->input->post('transaction');
				$amount = (float) $this->input->post('amount');
				
				$this->invoice->transaction(
					!empty($gateway) ? $gateway : '',
					!empty($transaction) ? $transaction : '',
					!empty($amount) ? $amount : '',
					!empty($time) ? $time : '',
					true
				);
				
				$this->invoice->update(false);
				
				$this->invoice->receive(uid());
				
				$this->system_model->log('invoice_received', array('invoice' => $id, 'transaction' => $this->invoice->get('transaction')));
				
				$this->ui->alert('已经确认收款并更新账单状态。', 'success');
			}
		}
		
		//发送催款邮件
		if($action == 'remind' && $this->input->post('send') && $this->invoice->get('status') == 'unpaid')
		{
			$this->invoice->remind(true);
			
			$this->ui->alert('已经向代表发送账单提醒。', 'success');
		}
		
		$vars['invoice_html'] = $this->invoice->display();
		$vars['due_time'] = $this->invoice->get('due_time');
		$vars['transaction'] = $this->invoice->get('transaction');
		$vars['gateway'] = $this->gateway;
		$vars['unpaid'] = $this->invoice_model->is_unpaid($id);
		
		$this->ui->now('billing');
		$this->ui->title("账单 #{$id}", '查看账单');
		$this->load->view('admin/invoice_item', $vars);
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
			
			$param = $this->_filter_check($this->input->get());
			$input_param = array();
			
			//账单状态
			if(isset($param['status']))
				$input_param['status'] = $param['status'];
			
			//转账记录
			if(isset($param['transaction']))
			{
				$transaction = $param['transaction'][0];
				if($transaction == '0')
					$input_param['transaction IS NULL'] = NULL;
				else
					$input_param['transaction IS NOT NULL'] = NULL;
			}
			
			//代表团
			if(isset($param['group']))
			{
				$gids = $this->delegate_model->get_group_delegates($param['group']);
				if($gids)
					$input_param['delegate'] = $gids;
				else
					$input_param['delegate'] = array(NULL);
			}
			
			//代表
			if(isset($param['delegate']))
				$input_param['delegate'] = $param['delegate'];
			
			$args = array();
			if(!empty($input_param))
			{
				foreach($input_param as $item => $value)
				{
					$args[] = $item;
					$args[] = $value;
				}
			}
			$ids = call_user_func_array(array($this->invoice_model, 'get_invoice_ids'), $args);
			
			if($ids)
			{
				$invoices = $this->invoice_model->get_invoices($ids);
				$delegates = $this->delegate_model->get_delegates(array_unique(array_column($invoices, 'delegate')));
				
				foreach($invoices as $id => $invoice)
				{
					$delegate = $delegates[$invoice['delegate']];

					//操作
					$operation = anchor("billing/invoice/{$invoice['id']}", icon('file-text', false).'账单');
					$operation .= ' '.anchor("delegate/profile/{$invoice['delegate']}", icon('user', false).'代表');
					
					//姓名
					$contact_list = '<p>'.icon('phone').$delegate['phone'].'</p><p>'.icon('envelope-o').$delegate['email'].'</p><p>'.icon('male').'ID '.$delegate['id'].'</p>';
					$name_line = $delegate['name'].'<a style="cursor: pointer;" class="contact_list" data-html="1" data-placement="right" data-trigger="click" data-original-title=\''
							.$delegate['name']
							.'\' data-toggle="popover" data-content=\''.$contact_list.'\'>'.icon('info-circle', false).'</a>';
					
					//状态
					$status_text = $this->invoice_model->status_text($invoice['status']);
					switch($invoice['status'])
					{
						case 'unpaid':
							$status_class = 'warning';
							if($invoice['due_time'] < time())
							{
								$status_class = 'danger';
								$status_text = '已逾期';
							}
							
							//转账记录
							if(!empty($invoice['transaction']))
							{
								$status_class = 'primary';
								$status_text = '待确认';
							}
							break;
						case 'paid':
							$status_class = 'success';
							break;
						case 'cancelled':
						case 'refunded':
							$status_class = 'info';
							break;
					}
					$status_line = "<span class='label label-{$status_class}'>{$status_text}</span>";
					
					$data = array(
						$invoice['id'], //ID
						$name_line, //姓名
						$invoice['title'], //账单标题
						$this->currency_sign.number_format((double) $invoice['amount'], 2).' '.$this->currency_text, //金额
						!empty($invoice['generate_time']) ? sprintf('%1$s（%2$s）', date('n月j日', $invoice['generate_time']), nicetime($invoice['generate_time'])) : 'N/A', //生成时间
						!empty($invoice['due_time']) ? sprintf('%1$s（%2$s）', date('n月j日', $invoice['due_time']), nicetime($invoice['due_time'])) : 'N/A', //到期时间
						$status_line, //状态
						!empty($invoice['transaction']) ? ($invoice['transaction']['confirm'] ? "<span class='label label-success'>已确认</span>" : "<span class='label label-warning'>待确认</span>") : '', //转账确认
						!empty($invoice['transaction']) ? unix_to_human($invoice['transaction']['time']) : '', //转账时间
						!empty($invoice['transaction']) ? $invoice['transaction']['gateway'] : '', //交易渠道
						!empty($invoice['transaction']) ? $invoice['transaction']['transaction'] : '', //流水号
						!empty($invoice['transaction']) ? $this->currency_sign.number_format((double) $invoice['transaction']['amount'], 2).' '.$this->currency_text : '', //交易金额
						$operation, //操作
						$invoice['generate_time'], //生成时间（排序数据）
						$invoice['due_time'] //到期时间（排序数据）
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
		
		//账单状态
		if(isset($post['status']))
		{
			$status = array();
			foreach(explode(',', $post['status']) as $param_status)
			{
				if(in_array($param_status, array('unpaid', 'paid', 'cancelled', 'refunded')))
					$status[] = $param_status;
			}
			if(!empty($status))
				$return['status'] = $status;
		}
		
		//转账记录
		if(isset($post['transaction']) && in_array($post['transaction'], array('0', '1')))
		{
			$return['transaction'] = array($post['transaction']);
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
		
		//代表
		if(isset($post['delegate']))
		{
			$delegate = array();
			foreach(explode(',', $post['delegate']) as $param_delegate)
			{
				if(in_array($param_delegate, $this->delegate_model->get_delegate_ids()))
					$delegate[] = $param_delegate;
			}
			if(!empty($delegate))
				$return['delegate'] = $delegate;
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

/* End of file billing.php */
/* Location: ./application/controllers/billing.php */