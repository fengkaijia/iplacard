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
		$this->ui->title('关于');
		$this->ui->background();
		$this->load->view('about');
	}
	
	/**
	 * 爱梦之书
	 */
	function imunc()
	{
		$this->ui->title('爱梦之书');
		$this->ui->background();
		$this->load->view('book');
	}
}

/* End of file about.php */
/* Location: ./application/controllers/about.php */