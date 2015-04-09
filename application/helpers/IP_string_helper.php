<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 从提及字符串中获取被提及用户ID
 */
function extract_mention($text)
{
	preg_match_all('/\(([0-9]+?)\)/', $text, $match);
	
	if(empty($match))
		return false;
	
	$count = count($match[1]);
	$user = intval($match[1][$count - 1]);
	
	if(empty($user))
		return false;
	
	return $user;
}

/* End of file IP_string_helper.php */
/* Location: ./application/helpers/IP_string_helper.php */