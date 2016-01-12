<?php define('IPLACARD', 'Developed by Kaijia Feng');

/**
 * iPlacard
 * 下一代模拟联合国会议管理系统
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright (c) 2013, Kaijia Feng
 * @link http://iplacard.com/
 * @since 2.0
 */

/**
 * iPlacard版本
 */
define('IP_VERSION', '2.2Alpha');

//配置文件
require_once 'config.php';

//维护模式
if(IP_MAINTENANCE)
{
	include_once 'application/views/raw/maintenance.php';
	exit;
}

/**
 * 当前访问请求的域名
 * @todo 支持Alias
 */
define('IP_REQUEST_DOMAIN', (php_sapi_name() != 'cli' && !defined('STDIN')) ? $_SERVER['HTTP_HOST'] : '');

//是否使用访问SSL
if(isset($_SERVER['HTTPS']))
{
	if('on' == strtolower($_SERVER['HTTPS']))
		define('IP_SSL', true);
	if('1' == $_SERVER['HTTPS'])
		define('IP_SSL', true);
}
if(!defined('IP_SSL'))
	define('IP_SSL', false);

//选择模式，开发模式可能不使用多站点支持
if(IP_MULTISITE)
{
	//实例JSON数据文件
	require_once 'instance.php';
	
	foreach($iplacard_instances as $instance_id => $instance)
	{
		//存在站点并且启用
		if($instance['domain'] == IP_REQUEST_DOMAIN && $instance['enabled'])
		{
			//如果站点需要HTTPS但通过HTTP访问
			if($instance['ssl'] == true && !IP_SSL)
			{
				header("Location: https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");
				exit;
			}

			/**
			 * 实例ID
			 */
			define('IP_INSTANCE_ID', $instance_id);

			/**
			 * 实例名字空间
			 */
			define('IP_INSTANCE_NAMESPACE', $instance['namespace']);
			
			/**
			 * 加密密钥
			 */
			define('IP_INSTANCE_KEY', $instance['encryption_key']);
			
			/**
			 * API密钥
			 */
			define('IP_INSTANCE_API_ACCESS_KEY', $instance['api_access_key']);
			define('IP_INSTANCE_API_SECRET_KEY', $instance['api_secret_key']);
			
			/**
			 * SMTP
			 */
			define('IP_INSTANCE_SMTP', $instance['smtp']);
			define('IP_INSTANCE_SMTP_HOST', $instance['smtp_host']);
			define('IP_INSTANCE_SMTP_USER', $instance['smtp_user']);
			define('IP_INSTANCE_SMTP_PASS', $instance['smtp_pass']);
			define('IP_INSTANCE_SMTP_PORT', $instance['smtp_port']);

			/**
			 * 实例主域名
			 */
			define('IP_INSTANCE_DOMAIN', $instance['domain']);
			break;
		}
	}

	//如不存在站点
	if(!defined('IP_INSTANCE_ID'))
	{
		include_once 'application/views/raw/nonsite.php';
		exit;
	}
}
else
{
	//不使用多站点模式情况下使用默认名字空间
	define('IP_INSTANCE_NAMESPACE', IP_DB_PREFIX);
	
	//不使用多站点模式情况下使用默认加密密钥
	define('IP_INSTANCE_KEY', IP_ENCRYPTION_KEY);
	
	//使用默认API密钥
	define('IP_INSTANCE_API_ACCESS_KEY', IP_DEFAULT_API_ACCESS_KEY);
	define('IP_INSTANCE_API_SECRET_KEY', IP_DEFAULT_API_SECRET_KEY);
	
	//使用默认SMTP设置
	define('IP_INSTANCE_SMTP', IP_SMTP);
	define('IP_INSTANCE_SMTP_HOST', IP_SMTP_HOST);
	define('IP_INSTANCE_SMTP_USER', IP_SMTP_USER);
	define('IP_INSTANCE_SMTP_PASS', IP_SMTP_PASS);
	define('IP_INSTANCE_SMTP_PORT', IP_SMTP_PORT);
	
	//不设置ID
	define('IP_INSTANCE_ID', 0);
	
	//默认使用访问请求的域名
	define('IP_INSTANCE_DOMAIN', IP_DOMAIN);
}

/* End of iPlacard Instances initialization */

//---------------------------------------------------------------

/*
 *---------------------------------------------------------------
 * APPLICATION ENVIRONMENT
 *---------------------------------------------------------------
 *
 * You can load different configurations depending on your
 * current environment. Setting the environment also influences
 * things like logging and error reporting.
 *
 * This can be set to anything, but default usage is:
 *
 *     development
 *     testing
 *     production
 *
 * NOTE: If you change these, also change the error_reporting() code below
 *
 */
	define('ENVIRONMENT', IP_ENVIRONMENT);
/*
 *---------------------------------------------------------------
 * ERROR REPORTING
 *---------------------------------------------------------------
 *
 * Different environments will require different levels of error reporting.
 * By default development will show errors but testing and live will hide them.
 */

if (defined('ENVIRONMENT'))
{
	switch (ENVIRONMENT)
	{
		case 'development':
			error_reporting(E_ALL);
		break;

		case 'testing':
		case 'production':
			error_reporting(0);
		break;

		default:
			exit('The application environment is not set correctly.');
	}
}

/*
 *---------------------------------------------------------------
 * SYSTEM FOLDER NAME
 *---------------------------------------------------------------
 *
 * This variable must contain the name of your "system" folder.
 * Include the path if the folder is not in the same  directory
 * as this file.
 *
 */
	$system_path = 'system';

/*
 *---------------------------------------------------------------
 * APPLICATION FOLDER NAME
 *---------------------------------------------------------------
 *
 * If you want this front controller to use a different "application"
 * folder then the default one you can set its name here. The folder
 * can also be renamed or relocated anywhere on your server.  If
 * you do, use a full server path. For more info please see the user guide:
 * http://codeigniter.com/user_guide/general/managing_apps.html
 *
 * NO TRAILING SLASH!
 *
 */
	$application_folder = 'application';

/*
 * --------------------------------------------------------------------
 * DEFAULT CONTROLLER
 * --------------------------------------------------------------------
 *
 * Normally you will set your default controller in the routes.php file.
 * You can, however, force a custom routing by hard-coding a
 * specific controller class/function here.  For most applications, you
 * WILL NOT set your routing here, but it's an option for those
 * special instances where you might want to override the standard
 * routing in a specific front controller that shares a common CI installation.
 *
 * IMPORTANT:  If you set the routing here, NO OTHER controller will be
 * callable. In essence, this preference limits your application to ONE
 * specific controller.  Leave the function name blank if you need
 * to call functions dynamically via the URI.
 *
 * Un-comment the $routing array below to use this feature
 *
 */
	// The directory name, relative to the "controllers" folder.  Leave blank
	// if your controller is not in a sub-folder within the "controllers" folder
	// $routing['directory'] = '';

	// The controller class file name.  Example:  Mycontroller
	// $routing['controller'] = '';

	// The controller function you wish to be called.
	// $routing['function']	= '';


/*
 * -------------------------------------------------------------------
 *  CUSTOM CONFIG VALUES
 * -------------------------------------------------------------------
 *
 * The $assign_to_config array below will be passed dynamically to the
 * config class when initialized. This allows you to set custom config
 * items or override any default config values found in the config.php file.
 * This can be handy as it permits you to share one application between
 * multiple front controller files, with each file containing different
 * config values.
 *
 * Un-comment the $assign_to_config array below to use this feature
 *
 */
	// $assign_to_config['name_of_config_item'] = 'value of config item';



// --------------------------------------------------------------------
// END OF USER CONFIGURABLE SETTINGS.  DO NOT EDIT BELOW THIS LINE
// --------------------------------------------------------------------

/*
 * ---------------------------------------------------------------
 *  Resolve the system path for increased reliability
 * ---------------------------------------------------------------
 */

	// Set the current directory correctly for CLI requests
	if (defined('STDIN'))
	{
		chdir(dirname(__FILE__));
	}

	if (realpath($system_path) !== FALSE)
	{
		$system_path = realpath($system_path).'/';
	}

	// ensure there's a trailing slash
	$system_path = rtrim($system_path, '/').'/';

	// Is the system path correct?
	if ( ! is_dir($system_path))
	{
		exit("Your system folder path does not appear to be set correctly. Please open the following file and correct this: ".pathinfo(__FILE__, PATHINFO_BASENAME));
	}

/*
 * -------------------------------------------------------------------
 *  Now that we know the path, set the main path constants
 * -------------------------------------------------------------------
 */
	// The name of THIS file
	define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));

	// The PHP file extension
	// this global constant is deprecated.
	define('EXT', '.php');

	// Path to the system folder
	define('BASEPATH', str_replace("\\", "/", $system_path));

	// Path to the front controller (this file)
	define('FCPATH', str_replace(SELF, '', __FILE__));

	// Name of the "system folder"
	define('SYSDIR', trim(strrchr(trim(BASEPATH, '/'), '/'), '/'));


	// The path to the "application" folder
	if (is_dir($application_folder))
	{
		define('APPPATH', $application_folder.'/');
	}
	else
	{
		if ( ! is_dir(BASEPATH.$application_folder.'/'))
		{
			exit("Your application folder path does not appear to be set correctly. Please open the following file and correct this: ".SELF);
		}

		define('APPPATH', BASEPATH.$application_folder.'/');
	}

/*
 * --------------------------------------------------------------------
 * LOAD THE BOOTSTRAP FILE
 * --------------------------------------------------------------------
 *
 * And away we go...
 *
 */
require_once BASEPATH.'core/CodeIgniter.php';

/* End of file index.php */
/* Location: ./index.php */