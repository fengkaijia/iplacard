<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * CodeIgniter Upload延伸类库
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
 * @package iPlacard
 * @since 2.2
 * @link https://garrettstjohn.com/article/codeigniter-file-upload-setting-disallowed-file-types/
 */
class IP_Upload extends CI_Upload {
	
	/**
	 * @var array 禁用文件类型
	 */
	var $disallowed_types = array();
	
	/**
	 * 初始化
	 * @param array $config
	 * @param bool $reset
	 * @return CI_Upload
	 */
	public function initialize(array $config = array(), $reset = TRUE)
	{
		if(isset($config['disallowed_types']))
			$this->set_disallowed_types($config['disallowed_types']);
		
		parent::initialize($config, $reset);
	}
	
	/**
	 * 设置禁止上传的文件类型
	 * @param mixed $types
	 * @return CI_Upload
	 */
	public function set_disallowed_types($types)
	{
		$this->disallowed_types = explode('|', $types);
	}
	
	/**
	 * 验证文件类型是否可上传
	 * @param bool $ignore_mime
	 * @return bool
	 */
	public function is_allowed_filetype($ignore_mime = FALSE)
	{
		//未设定白名单
		if(empty($this->allowed_types) || !is_array($this->allowed_types))
		{
			//未设定黑白名单允许全部
			if(empty($this->disallowed_types) || !is_array($this->disallowed_types))
				return TRUE;

			//未设定黑名单
			return !$this->is_disallowed_filetype();
		}

		//已设定白名单
		return parent::is_allowed_filetype($ignore_mime);
	}
	
	/**
	 * 验证文件类型是否不可上传
	 * @return bool
	 */
	public function is_disallowed_filetype()
	{
		//无后缀
		if(empty($this->disallowed_types) || !is_array($this->disallowed_types))
			return FALSE;
		
		foreach($this->disallowed_types as $val)
		{
			if($this->file_ext_tolower == '.'.strtolower($val))
				return TRUE;
		}

		return FALSE;
	}
}

/* End of file IP_Upload.php */
/* Location: ./application/libraries/IP_Upload.php */