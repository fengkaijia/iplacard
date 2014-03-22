<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 源订阅类库
 * @link http://www.techbytes.co.in/blogs/2006/01/15/consuming-rss-with-php-the-simple-way/
 * @package iPlacard
 * @since 2.0
 */
class Feed
{
	var $feed_uri = 'http://iplacard.com/feed/';
	private $feed_sum;
	
	var $data;
	var $channel_data;
	
	var $cache_life = 3600;
	
	function __construct()
	{
		$this->CI =& get_instance();
		$this->CI->load->driver('cache', array('adapter' => 'memcached', 'backup' => 'file'));
	}

	function parse()
	{
		$this->data = array();
		$this->channel_data = array();

		if(!$all = $this->CI->cache->get(IP_INSTANCE_ID.'_rss_'.$this->feed_sum))
		{
			$raw = @file_get_contents($this->feed_uri);
			
			if(!$raw)
			{
				$this->data = array();
				return false;
			}
			
			$xml = new SimpleXmlElement($raw);

			if($xml->channel)
			{
				$this->channel_data['title'] = $xml->channel->title;
				$this->channel_data['description'] = $xml->channel->description;

				foreach($xml->channel->item as $item)
				{
					$data = array();
					$data['title'] = (string) $item->title;
					$data['description'] = (string) $item->description;
					$data['date'] = (string) $item->pubDate;
					$data['link'] = (string) $item->link;
					$dc = $item->children('http://purl.org/dc/elements/1.1/');
					$data['author'] = (string) $dc->creator;
					$all[] = $data;
				}
			}
			else
			{
				$this->channel_data['title'] = $xml->title;
				$this->channel_data['description'] = $xml->subtitle;

				foreach($xml->entry as $item)
				{
					$data = array();
					$data['id'] = (string) $item->id;
					$data['title'] = (string) $item->title;
					$data['description'] = (string) $item->content;
					$data['date'] = (string) $item->published;
					$data['link'] = (string) $item->link['href'];
					$dc = $item->children('http://purl.org/dc/elements/1.1/');
					$data['author'] = (string) $dc->creator;
					$all[] = $data;
				}
			}
			$this->CI->cache->save(IP_INSTANCE_ID.'_rss_'.$this->feed_sum, $all, $this->cache_life);
		}
		$this->data = $all;
		return true;
	}

	function set_cache_life($period = '')
	{
		if(!empty($period))
			$this->cache_life = intval($period);
	}

	function set_feed_url($url = '')
	{
		if(!empty($url))
		{
			$this->feed_uri = $url;
			$this->feed_sum = md5($url);
		}
	}

	function get_feed($num)
	{
		$this->parse();
		
		$c = 0;
		$return = array();

		foreach($this->data as $item)
		{
			$return[] = $item;
			$c++;

			if($c == $num)
				break;
		}
		return $return;
	}

	function &get_channel_data()
	{
		$flag = false;

		if(!empty($this->channel_data))
			return $this->channel_data;
		else
			return $flag;
	}
}

/* End of file Feed.php */
/* Location: ./application/libraries/Feed.php */