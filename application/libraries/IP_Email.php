<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * CodeIgniter Email延伸类库
 * @package iPlacard
 * @since 2.0
 */
class IP_Email extends CI_Email
{
	private $CI;
	
	function __construct()
	{
		parent::__construct();
		
		$this->CI =& get_instance();
		
		//设置默认发件地址和回复信息
		$this->from(option('email_from', 'no-reply@iplacard.com'), option('email_from_name', 'iPlacard'));
		$this->reply_to(option('site_contact_email', 'contact@iplacard.com'));
	}
	
	/**
	 * 发信内容中自动包含HTML邮件模板
	 * @param string $body 邮件内容
	 * @param boolean $nl2br 是否将\n转换为<br />
	 */
	public function html($body, $nl2br = true)
	{
		$this->mailtype = 'html';
		
		$html = $body;
		if($nl2br)
			$html = nl2br($body);
		
		$message = $this->CI->load->view('email', array('text' => $html), true);
		$this->message($message);
		$this->set_alt_message($body);
		
		return $this;
	}
}

/* End of file IP_Email.php */
/* Location: ./application/libraries/IP_Email.php */