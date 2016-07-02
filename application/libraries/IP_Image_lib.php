<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * CodeIgniter Image Manipulation Class extended with function
 * for converting images
 * Usage:
 *   $config['source_image'] = './uploads/my_pic.png';
 *   $this->image_lib->initialize($config);
 *   $this->image_lib->convert('jpg', TRUE);
 *
 *
 * @package CodeIgniter
 * @subpackage MY_Image_lib
 * @license GPLv3 <http://www.gnu.org/licenses/gpl-3.0.txt>
 * @link http://www.robertmullaney.com/2010/09/18/codeigniter-image_lib-convert-jpg-gif-png/
 * @version 1.3
 * @author Ripe <http://codeigniter.com/forums/member/119227/>
 * @modified waldmeister <http://codeigniter.com/forums/member/57608/>
 * @modified ebspromo <http://www.robertmullaney.com/>
 */
class IP_Image_lib extends CI_Image_lib
{
	function MY_Image_lib()
	{
		parent::CI_Image_lib();
	}

	/**
	 * Converts images
	 *
	 * @access public
	 * @param string
	 * @param bool
	 * @return bool
	 */
	function convert($type = 'jpg', $delete_orig = FALSE)
	{
		$this->full_dst_path = $this->dest_folder . end($this->explode_name($this->dest_image)) . '.' . $type;

		if (!($src_img = $this->image_create_gd()))
		{
			return FALSE;
		}

		if ($this->image_library == 'gd2' AND function_exists('imagecreatetruecolor'))
		{
			$create = 'imagecreatetruecolor';
		}
		else
		{
			$create = 'imagecreate';
		}
		$copy = 'imagecopy';

		$props = $this->get_image_properties($this->full_src_path, TRUE);
		$dst_img = $create($props['width'], $props['height']);
		$copy($dst_img, $src_img, 0, 0, 0, 0, $props['width'], $props['height']);

		$types = array('gif' => 1, 'jpg' => 2, 'jpeg' => 2, 'png' => 3);

		$this->image_type = $types[$type];

		if ($delete_orig)
		{
			unlink($this->full_src_path);
			$this->full_src_path = $this->full_dst_path;
		}

		if ($this->dynamic_output == TRUE)
		{
			$this->image_display_gd($dst_img);
		}
		else
		{
			if (!$this->image_save_gd($dst_img))
			{
			return FALSE;
			}
		}

		imagedestroy($dst_img);
		imagedestroy($src_img);

		@chmod($this->full_dst_path, DIR_WRITE_MODE);

		return TRUE;
	}
	
	/**
	 * Dynamically outputs an image
	 *
	 * @access	public
	 * @param	resource
	 * @return	void
	 */
	function image_display_gd($resource)
	{
		header("Content-Disposition: filename={$this->source_image};");
		header("Content-Type: {$this->mime_type}");
		header('Content-Transfer-Encoding: binary');
		header('Last-Modified: '.gmdate('D, d M Y H:i:s', time()).' GMT');
		
		switch ($this->image_type)
		{
			case 1		:	imagegif($resource);
				break;
			case 2		:	imagejpeg($resource, NULL, $this->quality);
				break;
			case 3		:	imagepng($resource);
				break;
			default		:	echo 'Unable to display the image';
				break;
		}
	}
}

/* End of file IP_Image_lib.php */
/* Location: ./application/libraries/IP_Image_lib.php */