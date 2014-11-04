<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 显示Font Awesome图标
 * @param string $icon 图标标识 
 * @param boolean $space 是否增加空间
 * @param boolean $fixed 是否匹配宽度
 * @param boolean $double_quota 是否使用双引号输出
 * @return string 包含图标的HTML代码
 */
function icon($icon, $space = true, $fixed = true, $double_quota = true)
{
	$fixed = $fixed ? ' fa-fw' : '';
	$space = $space ? ' ' : '';
	
	if($double_quota)
		return "<i class=\"fa fa-{$icon}{$fixed}\"></i>{$space}";
	return "<i class='fa fa-{$icon}{$fixed}'></i>{$space}";
}

/**
 * 显示旗帜图标
 * @param string $iso ISO 3166-1代码
 * @param boolean $check 是否检查代码是否存在
 * @param boolean $space 是否增加空间
 * @param boolean $null_output 是否在旗帜不存在时显示未知旗帜
 * @param boolean $double_quota 是否使用双引号输出
 * @return string 包含旗帜的HTML代码
 */
function flag($iso, $check = false, $space = true, $null_output = false, $double_quota = true)
{
	if($check)
	{
		$CI =& get_instance();
		$CI->config->load('iso');
		
		$available = $CI->config->item('iso_3166_1');
		
		if(!array_key_exists($iso, $available))
		{
			if(!$null_output)
				return '';
			
			$iso = '_unknown';
		}
	}
	
	if(empty($iso))
		return "";
	
	$space = $space ? ' ' : '';
	
	if($double_quota)
		return "<span class=\"flag {$iso}\"></span>{$space}";
	return "<span class='flag {$iso}'></span>{$space}";
}

/**
 * 显示文件类型图标
 * @param string $mime 文件类型
 * @param boolean $space 是否增加空间
 * @param boolean $double_quota 是否使用双引号输出
 * @return string 包含文件类型的HTML代码
 */
function mime($mime, $space = true, $double_quota = true)
{
	$space = $space ? ' ' : '';
	
	if($double_quota)
		return "<span class=\"mime {$mime}\"></span>{$space}";
	return "<span class='mime {$mime}'></span>{$space}";
}

/* End of file ui_helper.php */
/* Location: ./application/helpers/ui_helper.php */