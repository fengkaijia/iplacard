<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * CodeIgniter Config核心延伸
 * @package iPlacard
 * @since 2.0
 */
class IP_Config extends CI_Config
{
	function __construct()
	{
		parent::__construct();
		
		// Set the static_url automatically if none was provided
		if ($this->config['static_url'] == '')
		{
			$this->set_item('static_url', $this->config['base_url']);
		}
	}
	
	/**
	 * 设置输出类型
	 */
	function static_url($uri = '')
	{
		return $this->slash_item('static_url').ltrim($this->_uri_string($uri), '/');
	}
}

/* End of file IP_Config.php */
/* Location: ./application/core/IP_Config.php */