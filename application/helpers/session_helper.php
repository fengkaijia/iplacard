<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Session辅助函数
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2019 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
 * @package iPlacard
 * @since 2.3
 */

/**
 * 解析CodeIgniter序列化Session为数组
 * @link https://www.php.net/manual/en/function.session-decode.php#108037
 * @author Frits van Campen <Frits.vanCampen@moxio.com>
 * @param string $session 序列化Session字符串
 * @return array Session数组
 */
function session_unserialize($session)
{
	$return = array();
	$offset = 0;
	while($offset < strlen($session))
	{
		$pos = strpos($session, '|', $offset);
		$num = $pos - $offset;
		$varname = substr($session, $offset, $num);
		$offset += $num + 1;
		$data = unserialize(substr($session, $offset));
		$return[$varname] = $data;
		$offset += strlen(serialize($data));
	}
	return $return;
}

/* End of file session_helper.php */
/* Location: ./application/helpers/session_helper.php */