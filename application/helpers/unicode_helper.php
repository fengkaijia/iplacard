<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 检查是否为汉字
 * @param string $string 需要检查的文字
 * @return boolean
 */
function is_chinsese_character($string)
{
	if(preg_match("/^[\x7f-\xff]+$/", $string))
		return true;
	return false;
}

/**
 * 使用API获取汉字的拼音
 * @param string $string 汉字
 * @param string $type 返回值类型
 */
function pinyin($string, $type = 'combine')
{
	$CI =& get_instance();
	$CI->load->library('curl');
	
	$data = json_decode($CI->curl->simple_get('http://pinyin.api.iplacard.com/place/?access_token='.IP_INSTANCE_API_ACCESS_KEY.'&word='.urlencode($string)), true);
	
	if(!$data || !$data['result'])
		return false;
	
	if($type == 'combine')
		return $data['data']['combine'];
	return $data['data'];
}

/* End of file unicode_helper.php */
/* Location: ./application/helpers/unicode_helper.php */

