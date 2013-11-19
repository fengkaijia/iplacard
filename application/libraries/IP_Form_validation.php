<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * CodeIgniter Form Validation延伸类库
 * @package iPlacard
 * @since 2.0
 */
class IP_Form_validation extends CI_Form_validation {

	function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * 不符合指定条件
	 */
	public function not($str, $field)
	{
		$param = explode('.', $field, 2);
		if(count($param) == 1)
			return !$this->{$param[0]}($str);
		return !$this->{$param[0]}($str, $param[1]);
	}
	
}

/* End of file IP_Form_validation.php */
/* Location: ./application/libraries/IP_Form_validation.php */