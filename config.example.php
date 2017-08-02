<?php  if ( ! defined('IPLACARD')) exit('No direct script access allowed');

/** 
 * iPlacard 配置文件
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
 * @since 2.0
 */

/**
 * 设置环境模式（development、testing、production）
 */
define('IP_ENVIRONMENT', 'development');

/**
 * 数据库服务器
 */
define('IP_DB_HOSTNAME', '');

/**
 * 数据库连接帐号
 */
define('IP_DB_USERNAME', '');

/**
 * 数据库连接密码
 */
define('IP_DB_PASSWORD', '');

/**
 * 数据库名称
 */
define('IP_DB_DATABASE', '');

/**
 * 数据库驱动
 */
define('IP_DB_DRIVER', 'mysqli');

/**
 * 默认数据表前缀
 */
define('IP_DB_PREFIX', 'ip');

/**
 * 缓存前缀
 */
define('IP_CACHE_PREFIX', 'ip');

/**
 * 加密密钥
 */
define('IP_ENCRYPTION_KEY', 'iplacard');

/**
 * API Access Key
 */
define('IP_DEFAULT_API_ACCESS_KEY', '');

/**
 * 默认域名
 */
define('IP_DOMAIN', '');

/**
 * 静态文件CDN
 */
define('IP_STATIC_CDN', '');

/**
 * 是否启用SMTP
 */
define('IP_SMTP', false);

/**
 * SMTP服务器
 */
define('IP_SMTP_HOST', '');

/**
 * SMTP用户名
 */
define('IP_SMTP_USER', '');

/**
 * SMTP密码
 */
define('IP_SMTP_PASS', '');

/**
 * SMTP端口
 */
define('IP_SMTP_PORT', '');

/**
 * 反向代理IP列表
 */
define('IP_REVERSE_PROXY', join(',', array(
	''
)));

/**
 * Memcached缓存服务器列表
 */
define('IP_MEMCACHED_SERVER', serialize(array(
	'hostname' => '127.0.0.1',
	'port' => 11211,
	'weight' => 1
)));

/* End of file config(.example).php */
/* Location: ./config(.example).php */
