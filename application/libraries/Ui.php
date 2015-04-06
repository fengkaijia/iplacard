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
	 * @var boolean 是否显示全亮度背景
	 */
	var $show_background = false;
	
	/**
	 * @var array 边栏
	 */
	var $sidebar = array();
	
	/**
	 * @var array 菜单
	 */
	private $menu = array();
	
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
		
		//处理菜单
		$this->panel();
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
		
		foreach($sidebar as $item => $data)
		{
			if(isset($data[2]) && !empty($data[2]) && !$this->CI->admin_model->capable($data[2]))
				unset($sidebar[$item]);
		}
		
		$this->sidebar = $sidebar;
		$this->enable_sidebar();
	}
	
	/**
	 * 编译菜单
	 */
	function panel()
	{
		switch($this->side)
		{
			case 'delegate':
				$this->_delegate_panel();
				break;
			case 'admin':
				$this->_admin_panel();
				break;
			case 'account':
				if(!is_logged_in())
					return array();
				
				if($this->CI->user_model->is_admin(uid()))
					$this->_admin_panel();
				else
					$this->_delegate_panel();
		}
		
		$link = option('ui_menu_additional_link', array());
		if(!empty($link))
		{
			$link_number = 0;
			foreach($link as $name => $url)
			{
				$this->add_menu("link_{$link_number}", $name, $url);
				$link_number++;
			}
		}
	}
	
	/**
	 * 获取菜单信息
	 */
	function get_panel()
	{
		return $this->menu;
	}
	
	/**
	 * 添加母菜单
	 */
	function add_menu($id, $title, $url = '')
	{
		if(isset($this->menu[$id]))
			return false;
		
		return $this->menu[$id] = array(
			'title' => $title,
			'url' => $url,
			'sub' => array()
		);
	}
	
	/**
	 * 移除菜单
	 */
	function remove_menu($id)
	{
		if(!isset($this->menu[$id]))
			return false;
		
		unset($this->menu[$id]);
	}
	
	/**
	 * 添加子菜单
	 */
	function add_sub_menu($id, $parent, $title, $url = '', $divider = false)
	{
		if(!isset($this->menu[$parent]))
			return false;
		
		$this->menu[$parent]['sub'][$id] = array(
			'id' => $id,
			'title' => $title,
			'type' => 'menu',
			'url' => $url
		);
		
		if($divider)
			$this->add_divider($parent);
	}
	
	/**
	 * 移除菜单
	 */
	function remove_sub_menu($id, $parent)
	{
		if(!isset($this->menu[$parent]['sub'][$id]))
			return false;
		
		unset($this->panel[$parent]['sub'][$id]);
	}
	
	/**
	 * 增加分割符号
	 */
	function add_divider($parent)
	{
		$count = count($this->menu[$parent]['sub']);
		
		$this->menu[$parent]['sub'][$count] = array(
			'id' => $count,
			'type' => 'divider'
		);
	}
	
	/**
	 * 显示背景图片
	 */
	function background()
	{
		$this->show_background = true;
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
	 * 增加管理员界面菜单
	 */
	private function _admin_panel()
	{
		$this->CI->load->model('admin_model');
		
		//代表
		if($this->CI->admin_model->capable('administrator') || (!option('interview_enabled', true) && $this->CI->admin_model->capable('reviewer')))
		{
			$this->add_menu('delegate', '代表');
			$this->add_sub_menu('delegate', 'delegate', '代表', 'delegate/manage?type=delegate');
			$this->add_sub_menu('observer', 'delegate', '观察员', 'delegate/manage?type=observer');
			$this->add_sub_menu('volunteer', 'delegate', '志愿者', 'delegate/manage?type=volunteer');
			$this->add_sub_menu('teacher', 'delegate', '指导老师', 'delegate/manage?type=teacher', true);
		}
		
		if($this->CI->admin_model->capable('dais'))
		{
			$this->add_menu('delegate', '代表');
			$committee = $this->CI->admin_model->get_admin(uid(), 'committee');
			if(!empty($committee))
				$this->add_sub_menu('dais_delegate', 'delegate', '委员会代表', 'delegate/manage?committee='.$committee, true);
		}
		
		if($this->CI->admin_model->capable('interviewer'))
		{
			$this->add_menu('delegate', '代表');
			$this->add_sub_menu('interviewer_delegate', 'delegate', '面试代表', 'delegate/manage?interviewer='.uid(), true);
		}
		
		if($this->CI->admin_model->capable('administrator'))
		{
			$this->add_sub_menu('quitted', 'delegate', '退会代表', 'delegate/manage?status=quitted');
		}
		
		//面试
		if($this->CI->admin_model->capable('administrator'))
		{
			$this->add_menu('interview', '面试');
			$this->add_sub_menu('all', 'interview', '全部面试', 'interview/manage?display_interviewer=1');
			$this->add_sub_menu('pending', 'interview', '全部未完成面试', 'interview/manage?status=assigned,arranged&display_interviewer=1', true);
		}
		
		if($this->CI->admin_model->capable('interviewer'))
		{
			$this->add_menu('interview', '面试');
			$this->add_sub_menu('my_all', 'interview', '面试队列', 'interview/manage?interviewer='.uid());
			$this->add_sub_menu('my_pending', 'interview', '未完成面试', 'interview/manage?status=assigned,arranged&interviewer='.uid());
			$this->add_sub_menu('my_finished', 'interview', '已完成面试', 'interview/manage?status=completed,failed,exempted&interviewer='.uid());
		}
		
		//团队
		if($this->CI->admin_model->capable('administrator'))
		{
			$this->add_menu('group', '团队');
			$this->add_sub_menu('manage', 'group', '代表团', 'group/manage');
			$this->add_sub_menu('add', 'group', '添加代表团', 'group/edit');
		}
		
		//文件
		$this->add_menu('document', '文件');
		$this->add_sub_menu('manage', 'document', '文件', 'document/manage');
		if($this->CI->admin_model->capable('dais'))
		{
			$committee = $this->CI->admin_model->get_admin(uid(), 'committee');
			if(!empty($committee))
				$this->add_sub_menu('my', 'document', '委员会文件', 'document/manage?committee='.$committee);
		}
		
		if($this->CI->admin_model->capable('administrator') || $this->CI->admin_model->capable('dais'))
		{
			$this->add_sub_menu('add', 'document', '添加文件', 'document/edit');
		}
		
		//委员会
		$this->add_menu('committee', '委员会');
		$this->add_sub_menu('manage', 'committee', '委员会', 'committee/manage');
		if($this->CI->admin_model->capable('administrator'))
		{
			$this->add_sub_menu('add', 'committee', '添加委员会', 'committee/edit');
		}
		
		//席位
		$committee = $this->CI->admin_model->get_admin(uid(), 'committee');
		if(!empty($committee))
		{
			$this->add_menu('seat', '席位');
			$this->add_sub_menu('committee', 'seat', '委员会席位', 'seat/manage?committee='.$committee, true);
		}
		
		if($this->CI->admin_model->capable('administrator'))
		{
			$this->add_menu('seat', '席位');
			$this->add_sub_menu('manage', 'seat', '全部席位', 'seat/manage');
			$this->add_sub_menu('add', 'seat', '添加席位', 'seat/edit');
		}
		
		//账单
		if($this->CI->admin_model->capable('cashier'))
		{
			$this->add_menu('billing', '账单');
			$this->add_sub_menu('manage', 'billing', '全部账单', 'billing/manage');
			$this->add_sub_menu('manage_unpaid', 'billing', '未支付账单', 'billing/manage?status=unpaid&transaction=0');
			$this->add_sub_menu('manage_pending', 'billing', '待确认账单', 'billing/manage?status=unpaid&transaction=1');
		}
		
		//管理
		$this->add_menu('manage', '管理');
		$this->add_sub_menu('dashboard', 'manage', '控制板', 'admin/dashboard', true);
		
		if($this->CI->admin_model->capable('bureaucrat'))
		{
			$this->add_sub_menu('user_manage', 'manage', '用户', 'user/manage');
			$this->add_sub_menu('user_add', 'manage', '添加用户', 'user/edit', true);
		}
		elseif($this->CI->admin_model->capable('administrator'))
		{
			$this->add_sub_menu('user_manage', 'manage', '用户', 'user/manage', true);
		}
		
		if($this->CI->admin_model->capable('administrator'))
		{
			$this->add_sub_menu('broadcast', 'manage', '群发信息', 'admin/broadcast/email');
			$this->add_sub_menu('export', 'manage', '导出', 'admin/export');
			$this->add_sub_menu('stat', 'manage', '统计', 'admin/stat', true);
			
			$this->add_sub_menu('knowledge_manage', 'manage', '知识库管理', 'knowledgebase/manage');
			$this->add_sub_menu('knowledge_add', 'manage', '添加知识库文章', 'knowledgebase/edit');
		}
	}
	
	/**
	 * 编译代表界面菜单
	 */
	private function _delegate_panel()
	{
		$this->CI->load->model('delegate_model');
		$this->CI->load->model('seat_model');
		$this->CI->load->model('committee_model');
		$this->CI->load->model('document_model');
		$this->CI->load->model('interview_model');
		$this->CI->load->model('invoice_model');
		
		//SUDO模式提示
		if(is_sudo())
		{
			$this->add_menu('sudo', 'SUDO');
			$this->add_sub_menu('quit', 'sudo', '退出 SUDO', 'account/sudo');
		}
		
		//申请
		$this->add_menu('status', '申请', 'apply/status');
		
		//个人资料
		$this->add_menu('profile', '个人资料', 'apply/profile');
		
		$application = $this->CI->delegate_model->get_delegate(uid(), 'application_type');
		$editable = option('profile_edit_general', array()) + option("profile_edit_{$application}", array());
		if(!empty($editable))
			$this->add_sub_menu('edit', 'profile', '编辑资料', 'apply/edit');
		
		//团队
		if($this->CI->delegate_model->get_delegate(uid(), 'group'))
		{
			$this->add_menu('group', '团队', 'apply/group');
		}
		
		//席位
		if($this->CI->seat_model->get_delegate_selectability(uid()))
		{
			$this->add_menu('seat', '席位', 'apply/seat');
		}
		
		//面试
		if($this->CI->interview_model->get_interview_ids('delegate', uid()) != false)
		{
			$this->add_menu('interview', '面试', 'apply/interview');
		}
		
		//文件
		$committee = 0;
		if($application == 'delegate')
		{
			$seat = $this->CI->seat_model->get_delegate_seat(uid());
			if($seat)
				$committee = $this->CI->seat_model->get_seat($seat, 'committee');
		}
		
		if($this->CI->document_model->get_committee_documents($committee))
		{
			$this->add_menu('document', '文件', 'apply/document');
		}
		
		//账单
		if($this->CI->invoice_model->get_delegate_invoices(uid()) != false)
		{
			$this->add_menu('invoice', '账单', 'apply/invoice');
		}
	}
}

/* End of file Ui.php */
/* Location: ./application/libraries/Ui.php */