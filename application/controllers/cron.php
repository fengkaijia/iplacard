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
		echo "iPlacard Cron Job started at $time\n";
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
	}
	
	function hourly()
	{
		echo "\nProcessing Cron Hourly.\n\n";
	}
	
	function daily()
	{
		echo "\nProcessing Cron Daily.\n\n";
	}
	
	function weekly()
	{
		echo "\nProcessing Cron Weekly.\n\n";
	}
}

/* End of file cron.php */
/* Location: ./application/controllers/cron.php */