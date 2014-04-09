<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * CodeIgniter Security核心延伸
 * @package iPlacard
 * @since 2.0
 */
class IP_Security extends CI_Security
{
	function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * CSRF白名单
	 */
	function csrf_verify()
	{
		// Check if URI has been whitelisted from CSRF checks
		if ($exclude_uris = config_item('csrf_exclude_uris'))
		{
			$uri = load_class('URI', 'core');
			if (in_array($uri->uri_string(), $exclude_uris))
			{
				return $this;
			}
		}
		
		parent::csrf_verify();
	}
}

/* End of file IP_Security.php */
/* Location: ./application/core/IP_Security.php */