<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 数字辅助函数
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
 * @package iPlacard
 * @since 2.0
 */

/**
 * 文件大小去格式化
 * @param string $val 形如 1g、2M 的字符串
 * @return int 字节数
 */
function byte_deformat($val)
{
	$val = trim($val);
	$last = strtolower($val[strlen($val)-1]);
	
	$val = intval($val);
	switch($last)
	{
		case 'g':
			$val *= 1024;
		case 'm':
			$val *= 1024;
		case 'k':
			$val *= 1024;
	}

	return $val;
}

/* End of file IP_number_helper.php */
/* Location: ./application/helpers/IP_number_helper.php */