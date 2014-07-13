<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH.'/third_party/PHPOffice/PHPExcel.php';

/**
 * Excel类库
 * @package iPlacard
 * @since 2.0
 */
class Excel extends PHPExcel
{
	function __construct()
	{
		parent::__construct();
	}
}

/* End of file Excel.php */
/* Location: ./application/libraries/Excel.php */