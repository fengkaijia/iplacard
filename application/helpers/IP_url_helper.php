<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 跳转到请求登录页面并且保留请求链接
 */
function login_redirect()
{
	$CI =& get_instance();
	$CI->session->set_flashdata('redirect', $CI->input->server('REQUEST_URI'));
	redirect("account/login");
}

/**
 * 输出onclick时跳转代码
 */
function onclick_redirect($uri)
{
	$url = base_url($uri);
	return "location.href='{$url}'";
}

/**
 * 返回上一页跳转
 */
function back_redirect()
{
	$CI =& get_instance();
	redirect($CI->input->server('HTTP_REFERER'));
}

/**
 * 静态文件CDN地址
 */
function static_url($uri)
{
	return base_url(IP_STATIC_CDN.$uri);
}

/* End of file IP_url_helper.php */
/* Location: ./application/helpers/IP_url_helper.php */