<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 帮助控制器
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013, Kaijia Feng
 * @link http://iplacard.com/
 * @package iPlacard
 * @since 2.0
 */
class Help extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->library('ui', array('side' => 'account'));
		$this->load->helper('form');
	}
	
	/**
	 * 显示浏览器升级提示
	 */
	function browser($operation = 'none')
	{
		if($operation == 'dismiss')
		{
			$this->session->set_userdata('dismiss_browser_notice', true);
			redirect('');
			return;
		}
		
		$this->ui->title('浏览器支持');
		$this->ui->disable_menu();
		$this->load->view('help/browser');
	}
}

/* End of file help.php */
/* Location: ./application/controllers/help.php */