<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 文件辅助函数
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
 * @package iPlacard
 * @since 2.0
 */

/**
 * 获取 php.ini 设定的最大文件上传大小
 * @param int $compare 与之比较的字节参数
 * @return int 字节数
 */
function ini_max_upload_size($compare = '')
{
	$CI =& get_instance();
	$CI->load->helper('number');
	
	$max_upload = byte_deformat(ini_get('upload_max_filesize'));
	$max_post = byte_deformat(ini_get('post_max_size'));
	$memory_limit = byte_deformat(ini_get('memory_limit'));
	$int_max = min($max_upload, $max_post, $memory_limit);
	
	if(!empty($compare) && $compare < $int_max)
		return $compare;
	
	return $int_max;
}

/* End of file IP_file_helper.php */
/* Location: ./application/helpers/IP_file_helper.php */