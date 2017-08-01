<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 使用API查询IP地址信息
 */
function ip_lookup($ip = '')
{
	$CI =& get_instance();
	$CI->load->driver('cache', array('adapter' => 'memcached', 'backup' => 'file'));
	
	if(empty($ip))
		$ip = $CI->input->ip_address();
	
	if(!$data = $CI->cache->get("0_ip_lookup_{$ip}"))
	{
		$CI->load->library('curl');
		$data = json_decode($CI->curl->simple_get('https://ip.api.iplacard.com/?access_token='.IP_INSTANCE_API_ACCESS_KEY.'&ip='.$ip), true);

		if(!$data || !$data['result'])
			return false;
		
		$CI->cache->save("0_ip_lookup_{$ip}", $data, 60 * 60 * 24);
	}
	
	return $data['data'];
}

/**
 * 隐藏部分IP
 */
function hide_ip($ip = '')
{
	if(empty($ip))
	{
		$CI =& get_instance();
		$ip = $CI->input->ip_address();
	}
	
	$ip_part = explode('.', $ip);
	
	//是IPv4
	if(!empty($ip_part[3]))
		return "$ip_part[0].$ip_part[1].$ip_part[2].*";
	else
		return $ip;
}

/* End of file ip_helper.php */
/* Location: ./application/helpers/ip_helper.php */