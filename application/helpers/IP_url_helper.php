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

/* End of file IP_url_helper.php */
/* Location: ./application/helpers/IP_url_helper.php */