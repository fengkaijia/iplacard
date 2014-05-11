<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 帐单控制器
 * @package iPlacard
 * @since 2.0
 */
class Billing extends CI_Controller
{
	var $currency_sign = '';
	var $currency_text = '';
	
	function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->library('form_validation');
		$this->load->library('ui', array('side' => 'admin'));
		$this->load->model('admin_model');
		$this->load->model('delegate_model');
		$this->load->model('invoice_model');
		$this->load->helper('form');
		
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
		$title = '全部帐单';
		
		if(isset($param['status']))
		{
			$text_status = array();
			foreach($param['status'] as $one)
			{
				$text_status[] = $this->invoice_model->status_text($one);
			}
			$title = sprintf("%s帐单列表", join('、', $text_status));
			
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
			$title = sprintf("%s代表团帐单列表", join('、', $text_group));
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
		
		$this->ui->title($title, '帐单列表');
		$this->load->view('admin/invoice_manage', $vars);
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
			
			//帐单状态
			if(isset($param['status']))
				$input_param['status'] = $param['status'];
			
			//转帐记录
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
				foreach($ids as $id)
				{
					$invoice = $this->invoice_model->get_invoice($id);
					$delegate = $this->delegate_model->get_delegate($invoice['delegate']);

					//操作
					$operation = anchor("billing/invoice/{$invoice['id']}", icon('file-text', false).'帐单');
					$operation .= ' '.anchor("delegate/profile/{$invoice['delegate']}", icon('user', false).'代表');
					
					//姓名
					$contact_list = '<p>'.icon('phone').$delegate['phone'].'</p><p>'.icon('envelope-o').$delegate['email'].'</p><p>'.icon('male').'ID '.$delegate['id'].'</p>';
					$name_line = $delegate['name'].'<a href="#" class="contact_list" data-html="1" data-placement="right" data-trigger="click" data-original-title=\''
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
							
							//转帐记录
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
							$status_class = 'muted';
							break;
						case 'refunded':
							$status_class = 'info';
							break;
					}
					$status_line = "<span class='label label-{$status_class}'>{$status_text}</span>";
					
					$data = array(
						$invoice['id'], //ID
						$name_line, //姓名
						$invoice['title'], //帐单标题
						$this->currency_sign.number_format($invoice['amount'], 2).' '.$this->currency_text, //金额
						!empty($invoice['generate_time']) ? sprintf('%1$s（%2$s）', date('n月j日', $invoice['generate_time']), nicetime($invoice['generate_time'])) : 'N/A', //生成时间
						!empty($invoice['due_time']) ? sprintf('%1$s（%2$s）', date('n月j日', $invoice['due_time']), nicetime($invoice['due_time'])) : 'N/A', //到期时间
						$status_line, //状态
						!empty($invoice['transaction']) ? ($invoice['transaction']['confirm'] ? "<span class='label label-success'>已确认</span>" : "<span class='label label-warning'>待确认</span>") : '', //转帐确认
						!empty($invoice['transaction']) ? unix_to_human($invoice['transaction']['time']) : '', //转帐时间
						!empty($invoice['transaction']) ? $invoice['transaction']['gateway'] : '', //交易渠道
						!empty($invoice['transaction']) ? $invoice['transaction']['transaction'] : '', //流水号
						!empty($invoice['transaction']) ? $this->currency_sign.number_format($invoice['transaction']['amount'], 2).' '.$this->currency_text : '', //交易金额
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
			
			echo json_encode($json);
		}
	}
	
	/**
	 * 查询过滤
	 */
	function _filter_check($post, $return_uri = false)
	{
		$return = array();
		
		//帐单状态
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