<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 显示Font Awesome图标
 * @param string $icon 图标标识 
 * @param boolean $space 是否增加空间
 * @param boolean $fixed 是否匹配宽度
 * @return string 包含图标的HTML代码
 */
function icon($icon, $space = true, $fixed = true)
{
	$fixed = $fixed ? ' fa-fw' : '';
	$space = $space ? ' ' : '';
	
	return "<i class=\"fa fa-{$icon}{$fixed}\"></i>{$space}";
}

/* End of file ui_helper.php */
/* Location: ./application/helpers/ui_helper.php */