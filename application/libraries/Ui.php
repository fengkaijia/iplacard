<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 用户界面类库
 * @package iPlacard
 * @since 2.0
 */
class UI
{
	private $CI;
	
	/**
	 * @var string 显示前后台界面
	 */
	private $side = 'account';
	
	/**
	 * @var string 页面标题
	 */
	var $title = 'iPlacard';
	
	/**
	 * @var array 页面Javascript
	 */
	var $js = array(
		'header' => '',
		'footer' => ''
	);
	
	/**
	 * @var array 页面HTML
	 */
	var $html = array(
		'header' => '',
		'footer' => ''
	);
	
	/**
	 * @var string 当前页面标识
	 */
	var $now_page;
	
	/**
	 * @var array 消息
	 */
	var $alert = array();
	
	/**
	 * @var array 在上一个页面请求中提交显示的消息
	 */
	var $alert_flashdata = array();
	
	/**
	 * @var boolean 是否使用系统界面显示消息
	 */
	var $show_alert = true;
	
	/**
	 * @var boolean 是否使用系统界面显示菜单
	 */
	var $show_menu = true;
	
	/**
	 * @var boolean 是否使用系统界面显示边栏
	 */
	var $show_sidebar = false;
	
	/**
	 * @var array 边栏
	 */
	var $sidebar = array();
	
	/**
	 * @var array 代表界面菜单
	 */
	var $delegate_panel = array(
		'status' => array(
			array('申请', 'apply/status'),
		),
		'profile' => array(
			array('个人信息', 'apply/profile')
		)
	);
	
	/**
	 * @var array 管理员界面菜单
	 */
	var $admin_panel = array(
		'delegate' => array(
			array('代表', 'delegate/manage?type=delegate', 'administrator', false),
			array('观察员', 'delegate/manage?type=observer', 'administrator', false),
			array('志愿者', 'delegate/manage?type=volunteer', 'administrator', false),
			array('带队老师', 'delegate/manage?type=teacher', 'administrator', true),
			array('退会代表', 'delegate/manage?status=quitted', 'administrator', false),
		),
		'interview' => array(
			array('面试', 'interview/manage?interviewer=u', 'interviewer', false),
			array('面试队列', 'interview/manage', 'administrator', false),
		),
		'committee' => array(
			array('委员会', 'committee/manage', '', false),
			array('添加委员会', 'committee/edit', 'administrator', false),
		),
		'seat' => array(
			array('席位', 'seat/manage?committee=u', '', false),
			array('全部席位列表', 'seat/manage', 'administrator', true),
			array('添加席位', 'seat/edit', 'administrator', false),
		),
		'invoice' => array(
			array('帐单', 'row/invoice/unpaid', 'cashier', false),
		),
		'group' => array(
			array('团队', 'group/manage', 'administrator', false),
			array('添加团队', 'group/edit', 'administrator', false),
		),
		'site' => array(
			array('管理', 'admin/dashboard', 'administrator', true),
			array('用户', 'user/manage', 'bureaucrat', false),
			array('添加用户', 'user/edit', 'bureaucrat', true),
			array('群发信息', 'admin/broadcast', 'administrator', false),
			array('导出', 'admin/export', 'administrator', false),
		),
	);
	
	function __construct($data = '')
	{
		$this->CI =& get_instance();
		
		$this->CI->load->helper('avatar');
		$this->CI->load->helper('ui');
		
		//设置默认标题
		$this->title = option('site_name', 'iPlacard Instance').' - Powered by iPlacard';
		
		//显示前后台界面
		if(!empty($data))
		{
			$this->side = $data['side'];
		}
		
		//处理在上一个页面请求中提交显示的消息
		$alert_flashdata = $this->CI->session->userdata('alert');
		if(!empty($alert_flashdata))
			$this->alert = $alert_flashdata;
	}
	
	/**
	 * 显示界面消息
	 * @param string $message 显示信息
	 * @param string $type 警告类型
	 * @param boolean $flashdata 是否在下一个请求中显示
	 * @return boolean
	 */
	function alert($message, $type = 'warning', $flashdata = false)
	{
		if(!in_array($type, array('warning', 'danger', 'error', 'info', 'success')))
			$type = 'warning';
		
		//Bootstrap 3移除了alert-error属性
		if($type == 'error')
			$type = 'danger';
		
		$alert = array(
			'type' => $type,
			'message' => $message
		);
		
		//如果延后显示
		if($flashdata)
		{
			$this->alert_flashdata[] = $alert;
			$this->CI->session->set_userdata('alert', $this->alert_flashdata);
			return true;
		}
		
		//加入消息
		$this->alert[] = $alert;
		return true;
	}
	
	/**
	 * 启用用消息显示
	 */
	function enable_alert()
	{
		$this->show_alert = true;
	}
	
	/**
	 * 禁用消息显示
	 */
	function disable_alert()
	{
		$this->show_alert = false;
	}
	
	/**
	 * 启用菜单显示
	 */
	function enable_menu()
	{
		$this->show_menu = true;
	}
	
	/**
	 * 禁用菜单显示
	 */
	function disable_menu()
	{
		$this->show_menu = false;
	}
	
	/**
	 * 启用边栏显示
	 */
	function enable_sidebar()
	{
		$this->show_sidebar = true;
	}
	
	/**
	 * 禁用边栏显示
	 */
	function disable_sidebar()
	{
		$this->show_sidebar = false;
	}
	
	/**
	 * 导入边栏
	 */
	function sidebar($sidebar = array())
	{
		if(empty($sidebar))
			return $this->sidebar;
		
		$this->sidebar = $sidebar;
		$this->enable_sidebar();
	}
	
	/**
	 * 编译菜单
	 */
	function panel($uid = '')
	{
		if(empty($uid))
			$uid = uid();
		
		switch($this->side)
		{
			case 'delegate':
				$this->_set_delegate_panel($uid);
				return $this->_delegate_panel($uid);
			case 'admin':
				return $this->_admin_panel($uid);
			case 'account':
				//未登录情况不显示附加菜单
				if(!is_logged_in())
					return array();
				
				if($this->CI->user_model->is_admin($uid))
				{
					$this->CI->load->model('admin_model');
					return $this->_admin_panel($uid);
				}
				return $this->_delegate_panel($uid);	
		}
		return false;
	}
	
	/**
	 * 设置前后端
	 */
	function side($side)
	{
		$this->side = $side;
	}
	
	/**
	 * 设置页面标题
	 */
	function title()
	{
		$title = '';
		
		//循环
		for($i = 0; $i < func_num_args(); $i++)
		{
			$title .= func_get_arg($i).' - ';
		}
		
		$this->title = $title.option('site_name').' - Powered by iPlacard';
	}
	
	/**
	 * 插入HTML代码
	 * @param string $part 显示部分
	 * @param string $code 代码
	 */
	function html($part, $code)
	{
		$this->html[$part] .= $code."\n";
	}
	
	/**
	 * 插入Javascript代码
	 * @param string $part 显示部分
	 * @param string $code 代码
	 */
	function js($part, $code)
	{
		$this->js[$part] .= $code."\n";
	}
	
	/**
	 * 设置现在页面
	 */
	function now($now)
	{
		$this->now_page = $now;
	}
	
	/**
	 * 是否为管理员界面
	 * @return boolean
	 */
	function is_backend()
	{
		if($this->side == 'admin')
			return true;
		return false;
	}
	
	/**
	 * 是否为用户界面
	 * @return boolean
	 */
	function is_frontend()
	{
		if($this->side == 'delegate')
			return true;
		return false;
	}
	
	/**
	 * 是否为帐户设置界面
	 * @return boolean
	 */
	function is_account_page()
	{
		if($this->side == 'account')
			return true;
		return false;
	}
	
	/**
	 * 编译管理员界面菜单
	 */
	private function _admin_panel($id)
	{
		$panel = array();
		foreach($this->admin_panel as $name => $list)
		{
			foreach($list as $val)
			{
				if(empty($val[2]) || $this->CI->admin_model->capable($val[2], $id))
				{
					$panel[$name][] = array($val[0], $val[1], $val[3]);
				}
			}
		}
		return $panel;
	}
	
	/**
	 * 编译代表界面菜单
	 */
	private function _delegate_panel()
	{
		$panel = array();
		foreach($this->delegate_panel as $name => $list)
		{
			foreach($list as $val)
			{
				$panel[$name][] = array($val[0], $val[1]);
			}
		}
		$link = option('additional_menu_link', array());
		if(!empty($link))
		{
			foreach($link as $name => $url)
			{
				$panel[$name][] = array($name, $url);
			}
		}
		return $panel;
	}
	
	/**
	 * 根据特定情况设置代表界面菜单显示内容
	 */
	private function _set_delegate_panel($id)
	{
		$this->CI->load->model('delegate_model');
		$this->CI->load->model('interview_model');
		$this->CI->load->model('invoice_model');
		if($this->CI->delegate_model->get_delegate($id, 'group'))
		{
			$this->delegate_panel['group'] = array(
				array('团队', 'apply/group')
			);
		}
		if($this->CI->delegate_model->get_delegate($id, 'seat'))
		{
			$this->delegate_panel['seat'] = array(
				array('席位', 'seat/placard')
			);
		}
		if($this->CI->interview_model->get_interview_ids('delegate', $id) != false)
		{
			$this->delegate_panel['interview'] = array(
				array('面试', 'apply/interview')
			);
		}
		if($this->CI->invoice_model->get_invoice_ids('delegate', $id) != false)
		{
			$this->delegate_panel['invoice'] = array(
				array('帐单', 'apply/invoice')
			);
		}
	}
}

/* End of file Ui.php */
/* Location: ./application/libraries/Ui.php */