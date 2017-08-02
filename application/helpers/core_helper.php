<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 核心辅助函数
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
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
 * 检查正在两步验证阶段
 * @return boolean
 */
function is_pending_twostep()
{
	$CI =& get_instance();
	
	//命令行模式
	if(!isset($CI->session))
		return false;
	
	if($CI->session->userdata('uid_twostep'))
		return true;
	return false;
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

/**
 * 检查当前是否处于开发模式
 * @return boolean
 */
function is_dev()
{
	if(IP_ENVIRONMENT == 'development')
		return true;
	return false;
}

/**
 * 获取站点设置
 * $this->system_model->option 简单路径
 * @param string $name 项目
 * @param mixed $default 默认值
 * @return array|false 值，如不存在返回FALSE
 */
function option($name, $default = NULL)
{
	$CI =& get_instance();
	return $CI->system_model->option($name, $default);
}

/**
 * 获取用户设置
 * $this->user_model->user_option 简单路径
 * @param string $name 项目
 * @param mixed $default 默认值，如为空将首先尝试调用系统默认设置
 * @param int $user 用户ID
 * @return array|false 值，如不存在返回FALSE
 */
function user_option($name, $default = NULL, $user = '')
{
	$CI =& get_instance();
	return $CI->user_model->user_option($name, $default, $user);
}

/* End of file core_helper.php */
/* Location: ./application/helpers/core_helper.php */