<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * CodeIgniter Email延伸类库
 * @package iPlacard
 * @since 2.0
 */
class IP_Email extends CI_Email
{
	private $CI;
	
	/**
	 * @var string 邮件标题
	 */
	var $subject = '';
	
	function __construct($config = array())
	{
		parent::__construct($config);
		
		$this->CI =& get_instance();
		
		$this->_set_default();
	}
	
	/**
	 * 记录邮件标题
	 */
	public function subject($subject)
	{
		parent::subject($subject);
		
		$this->subject = $subject;
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
		{
			$html = nl2br(str_replace("\t", "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $body));
		}
		
		$message = $this->CI->load->view('email', array('text' => $html, 'title' => $this->subject), true);
		$this->message($message);
		$this->set_alt_message($body);
		
		return $this;
	}
	
	/**
	 * 清空设置
	 */
	public function clear($clear_attachments = false)
	{
		parent::clear($clear_attachments);
		
		$this->subject = '';
		$this->_set_default();
	}
	
	/**
	 * 设置默认发件地址和回复信息
	 */
	private function _set_default()
	{
		$this->from(option('email_from', 'no-reply@iplacard.com'), option('email_from_name', 'iPlacard'));
		$this->reply_to(option('site_contact_email', 'contact@iplacard.com'), option('email_from_name', 'iPlacard'));
	}
}

/* End of file IP_Email.php */
/* Location: ./application/libraries/IP_Email.php */