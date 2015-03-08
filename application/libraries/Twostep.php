<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH.'/third_party/GoogleAuthenticator.php';

/**
 * 两步验证类库
 * @package iPlacard
 * @since 2.0
 */
class Twostep extends Twostep_model
{
	private $GA;
	
	function __construct()
	{
		parent::__construct();
		
		$this->GA = new GoogleAuthenticator();
	}
	
	/**
	 * 检查两步验证码是否正确
	 * @param string $secret 密钥
	 * @param string $code 输入两步验证代码
	 * @return boolean 输入的两步验证代码是否有效
	 */
	function check_code($secret, $code)
	{
		return $this->GA->checkCode($secret, $code);
	}
	
	/**
	 * 获取指定时间段正确的两步验证代码
	 * @param string $secret 密钥
	 * @param int $time 时间
	 * @return string 正确的两步验证代码
	 */
	function get_code($secret, $time = NULL)
	{
		return $this->GA->getCode($secret, $time);
	}

	/**
	 * 获取二维码
	 * @param string $name Google Authenticator中显示的名称
	 * @param string $secret 密钥
	 */
	function get_qr_url($name, $secret)
	{
		$name = rawurlencode($name);
		return "otpauth://totp/{$name}?secret={$secret}&issuer=iPlacard";
	}
	
	/**
	 * 生成一个两步验证密钥
	 */
	function generate_secret()
	{
		return $this->GA->generateSecret();
	}
}

/* End of file Twostep.php */
/* Location: ./application/libraries/Twostep.php */