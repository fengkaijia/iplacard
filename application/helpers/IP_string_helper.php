<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 从提及字符串中获取被提及用户ID
 */
function extract_mention($text)
{
	preg_match_all('/\(([0-9]+?)\)/', $text, $match);
	
	if(empty($match))
		return false;
	
	$user = intval(end($match[1]));
	
	if(empty($user))
		return false;
	
	return $user;
}

/**
 * 检查字符串是否以指定的文字开始
 * @param string $haystack 待检查字符串
 * @param string $needle 指定文字
 * @return boolean
 */
function start_with($haystack, $needle)
{
	return $needle === '' || strrpos($haystack, $needle, -strlen($haystack)) !== false;
}

/**
 * 检查字符串是否以指定的文字结束
 * @param string $haystack 待检查字符串
 * @param string $needle 指定文字
 * @return boolean
 */
function end_with($haystack, $needle)
{
	return $needle === '' || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
}

/* End of file IP_string_helper.php */
/* Location: ./application/helpers/IP_string_helper.php */