<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 核心辅助函数
 * @package iPlacard
 * @since 2.0
 */

/**
 * 用户ID
 * @param boolean $sudoer 如为TRUE显示当前使用sudo模式的管理员ID，如sudo模式未开启则参数不生效
 * @return int|false 用户ID，如未登录返回FALSE
 */
function uid($sudoer = false)
{
	$CI =& get_instance();
	
	//命令行模式
	if(!isset($CI->session))
		return false;
	
	//获取ID
	if($sudoer && $CI->session->userdata('sudoer'))
		$uid = $CI->session->userdata('sudoer');
	else
		$uid = $CI->session->userdata('uid');
	
	//如未登录
	if(!$uid)
		return false;
	
	return $uid;
}

/**
 * 检查是否已经登录
 * @return boolean
 */
function is_logged_in()
{
	if(!uid())
		return false;
	return true;
}

/**
 * 检查是否处于sudo状态
 * @return boolean
 */
function is_sudo()
{
	$CI =& get_instance();
	
	//命令行模式
	if(!isset($CI->session))
		return false;
	
	if($CI->session->userdata('sudo'))
		return true;
	
	return false;
}

/* End of file core_helper.php */
/* Location: ./application/helpers/core_helper.php */