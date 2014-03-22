<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 支持显示页面内链接 Anchor 辅助函数
 */
function anchor($uri = '', $title = '', $attributes = '', $inline = false)
{
	$title = (string) $title;

	if ( ! $inline)
	{
		if ( ! is_array($uri))
		{
			$site_url = ( ! preg_match('!^\w+://! i', $uri)) ? site_url($uri) : $uri;
		}
		else
		{
			$site_url = site_url($uri);
		}
	}
	else
	{
		$site_url = $uri;
	}

	if ($title == '')
	{
		$title = $site_url;
	}

	if ($attributes != '')
	{
		$attributes = _parse_attributes($attributes);
	}

	return '<a href="'.$site_url.'"'.$attributes.'>'.$title.'</a>';
}

/**
 * 根据权限确定是否显示超链接
 */
function anchor_capable($uri = '', $title = '', $role = '', $attributes = '')
{
	$CI =& get_instance();
	$CI->load->model('admin_model');
	
	if($CI->admin_model->capable($role))
		return anchor($uri, $title, $attributes);
	return "<span {$attributes}>{$title}</span>";
}

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