<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 时间优化函数
 * @link http://php.net/manual/en/function.time.php
 * @param int $date UNIX时间戳
 * @return string 经过优化的时间（如几天前）
 */
function nicetime($date)
{
	if(empty($date))
	{
		return false;
	}

	$periods = array('秒钟', '分钟', '小时', '天', '周', '月', '年');
	$lengths = array("60", "60", "24", "7", "4.35", "12");

	$now = time();

	if($now > $date)
	{
		$difference = $now - $date;
		$tense = '前';
	}
	else
	{
		$difference = $date - $now;
		$tense = '后';
	}

	for($j = 0; $difference >= $lengths[$j] && $j < count($lengths) - 1; $j++)
	{
		$difference /= $lengths[$j];
	}

	$difference = round($difference);
	
	return "$difference$periods[$j]{$tense}";
}

/* End of file IP_date_helper.php */
/* Location: ./application/helpers/IP_date_helper.php */