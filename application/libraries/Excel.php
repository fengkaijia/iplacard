<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once realpath(APPPATH.'../vendor/autoload.php');

/**
 * Excel类库
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
 * @package iPlacard
 * @since 2.0
 */
class Excel extends \PHPExcel
{
	function __construct()
	{
		parent::__construct();
	}
}

/* End of file Excel.php */
/* Location: ./application/libraries/Excel.php */
