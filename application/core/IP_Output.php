<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * CodeIgniter Output核心延伸
 * @package iPlacard
 * @since 2.0
 */
class IP_Output extends CI_Output
{
	/**
	 * @var string 输出类型
	 */
	protected $mime_type = 'html';
	
	function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * 设置输出类型
	 */
	function set_content_type($mime_type)
	{
		parent::set_content_type($mime_type);
		
		$this->mime_type = $mime_type;
	}
	
	/**
	 * 获取输出类型
	 */
	function get_content_type()
	{
		return $this->mime_type;
	}
}

/* End of file IP_Output.php */
/* Location: ./application/core/IP_Output.php */