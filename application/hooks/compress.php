<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 压缩HTML代码
 * @link http://jeromejaglale.com/doc/php/codeigniter_compress_html
 */
function compress_output()
{
	$CI =& get_instance();
	
	//开发模式或输出类型部委HTML时不压缩
	if(is_dev() || !in_array($CI->output->get_content_type(), array('text/html', 'text/css', 'application/x-javascript', 'text/plain')))
	{
		$CI->output->_display();
		return;
	}

	$search = array(
		//'/\>[^\S ]+/s', 
		//'/[^\S ]+\</s', 
		'/(\s)+/s',
		'#(?://)?<!\[CDATA\[(.*?)(?://)?\]\]>#s'
	);
	$replace = array(
		//'>',
		//'<',
		'\\1',
		"//&lt;![CDATA[\n".'\1'."\n//]]>"
	);
	$buffer = preg_replace($search, $replace, $CI->output->get_output());

	$CI->output->set_output($buffer);
	$CI->output->_display();
}
 
/* End of file compress.php */
/* Location: ./system/application/hooks/compress.php */