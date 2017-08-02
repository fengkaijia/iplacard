<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 跨实例查询客户端类库
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
 * @package iPlacard
 * @since 2.2
 */
class Ciq
{
	private $CI;
	
	private $api;
	private $access_token;
	
	private $request;
	private $post;
	
	/**
	 * @var array 代表信息
	 */
	protected $data = array();
	
	function __construct()
	{
		$this->CI =& get_instance();
		$this->CI->load->driver('cache', array('adapter' => 'memcached', 'backup' => 'file'));
	}
	
	/**
	 * 获取数据
	 */
	function parse()
	{
		$url = $this->api.$this->request;
		$post = json_encode($this->post);
		
		if(!$data = $this->CI->cache->get(IP_CACHE_PREFIX.'_'.IP_INSTANCE_ID.'_ciq_'.md5($url).'_'.md5($post)))
		{
			$this->CI->load->library('curl');

			//向API发送请求
			$raw = $this->CI->curl->simple_post($url, array(
					'access_token' => $this->access_token,
					'data' => $post
			));
			
			$data = array();
			if(!empty($raw))
			{
				//获取结果
				$return = json_decode($raw, true);
				
				if($return['result'])
				{
					$data = $return['data'];
				}
			}
			
			$this->CI->cache->save(IP_CACHE_PREFIX.'_'.IP_INSTANCE_ID.'_ciq_'.md5($url).'_'.md5($post), $data, option('ciq_cache_life', 3600 * 24));
		}
		
		if(empty($data))
			return false;
		
		$this->data = $data;
		return true;
	}
	
	/**
	 * 获取数据
	 */
	function get($key = '')
	{
		if(empty($this->data))
			return false;
		
		if(!empty($key))
		{
			if(!isset($this->data[$key]))
				return false;
			
			return $this->data[$key];
		}
		
		return $this->data;
	}
	
	/**
	 * 设置实例API
	 */
	function set_api($url, $access_token)
	{
		$this->api = "{$url}/api/";
		$this->access_token = $access_token;
	}
	
	/**
	 * 设置实例请求
	 */
	function set_request($module, $action)
	{
		$this->request = "{$module}/{$action}";
	}
	
	/**
	 * 设置输入数据
	 */
	function set_post($data)
	{
		$this->post = $data;
	}
	
	/**
	 * 重置属性
	 */
	function clear($all = true)
	{
		if($all)
		{
			$this->api = '';
			$this->access_token = '';
		}
		
		$this->request = '';
		$this->post = '';
		
		$this->data = array();
	}
}

/* End of file Ciq.php */
/* Location: ./application/libraries/Ciq.php */