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

function form_dropdown_select($name = '', $options = array(), $selected = array(), $highlighted = array(), $subtexts = array(), $search = false, $class = 'selectpicker', $extra = '')
{
	if ( ! is_array($selected))
	{
		$selected = array($selected);
	}

	// If no selected state was submitted we will attempt to set it automatically
	if (count($selected) === 0)
	{
		// If the form name appears in the $_POST array we have a winner!
		if (isset($_POST[$name]))
		{
			$selected = array($_POST[$name]);
		}
	}

	if ($extra != '') $extra = ' '.$extra;

	$multiple = (count($selected) > 1 && strpos($extra, 'multiple') === FALSE) ? ' multiple="multiple"' : '';
	
	$subtext = (!empty($subtexts)) ? ' data-show-subtext="true"' : '';
	
	$search = ($search) ? ' data-live-search="true"' : '';

	$form = '<select name="'.$name.'" class="'.$class.'"'.$extra.$multiple.$subtext.$search.">\n";

	foreach ($options as $key => $val)
	{
		$key = (string) $key;

		if (is_array($val) && ! empty($val))
		{
			$form .= '<optgroup label="'.$key.'">'."\n";

			foreach ($val as $optgroup_key => $optgroup_val)
			{
				$sel = (in_array($optgroup_key, $selected)) ? ' selected="selected"' : '';
				
				$hig = (in_array($optgroup_key, $highlighted)) ? ' class="special"' : '';
				
				$sub = (array_key_exists($optgroup_key, $subtexts)) ? ' data-subtext="'.$subtexts[$optgroup_key].'"' : '';

				$form .= '<option value="'.$optgroup_key.'"'.$sel.$hig.$sub.'>'.(string) $optgroup_val."</option>\n";
			}

			$form .= '</optgroup>'."\n";
		}
		else
		{
			$sel = (in_array($key, $selected)) ? ' selected="selected"' : '';
			
			$hig = (in_array($key, $highlighted)) ? ' class="chzn-highlight"' : '';
			
			$sub = (array_key_exists($optgroup_key, $subtexts)) ? ' data-subtext="'.$subtexts[$optgroup_key].'"' : '';

			$form .= '<option value="'.$key.'"'.$sel.$hig.$sub.'>'.(string) $val."</option>\n";
		}
	}

	$form .= '</select>';

	return $form;
}

/* End of file IP_form_helper.php */
/* Location: ./application/helpers/IP_form_helper.php */