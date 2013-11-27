<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 检查是否有错误
 * @return boolean
 */
function form_has_error($field)
{
	if (FALSE === ($OBJ =& _get_validation_object()))
	{
		return false;
	}

	$return = $OBJ->error($field);
	if(empty($return) || $return == '')
		return false;
	return true;
}

/* End of file IP_form_helper.php */
/* Location: ./application/helpers/IP_form_helper.php */