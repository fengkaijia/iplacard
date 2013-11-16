<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 关于控制器
 * @package iPlacard
 * @since 2.0
 */
class About extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->library('ui', array('side' => 'account'));
		$this->load->helper('form');
	}
	
	/**
	 * 跳转页
	 */
	function index()
	{
		redirect('about/iplacard');
	}
	
	/**
	 * 关于页面
	 */
	function iplacard()
	{
		$this->load->view('about');
	}
}

/* End of file about.php */
/* Location: ./application/controllers/about.php */