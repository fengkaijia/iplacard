<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 时间辅助函数
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
 * @package iPlacard
 * @since 2.0
 */

/**
 * 时间优化函数
 * @link https://www.php.net/manual/en/function.time.php#89415
 * @author Yasmary Mora <yasmary@gmail.com>
 * @param int $date UNIX时间戳
 * @param boolean $space 是否在数字和文字前增加空格
 * @return string 经过优化的时间（如几天前）
 */
function nicetime($date, $space = false)
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
	
	if($space)
		return " $difference $periods[$j]{$tense}";
	return "$difference$periods[$j]{$tense}";
}

/**
 * 检查输入是否为有效的UNIX时间戳
 * @link https://gist.github.com/sepehr/6351385
 * @param string $timestamp 待验证时间戳
 * @return boolean 验证结果
 */
function is_timestamp($timestamp)
{
	$check = (is_int($timestamp) || is_float($timestamp)) ? $timestamp : (string) (int) $timestamp;
	return ($check === $timestamp) && ((int) $timestamp <=  PHP_INT_MAX) && ((int) $timestamp >= ~PHP_INT_MAX);
}

/* End of file IP_date_helper.php */
/* Location: ./application/helpers/IP_date_helper.php */