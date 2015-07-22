<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 使用临时目录发送文件
 */
function temp_download($path, $filename = '')
{
	$path = realpath($path);
	
	if(!@is_file($path) || !($filesize = @filesize($path)))
	{
		return false;
	}
	
	$temp_path = temp_path();
	
	if(!file_exists($temp_path))
	{
		mkdir($temp_path, DIR_READ_MODE, true);
	}
	
	if(!file_exists("{$temp_path}/{$filename}"))
	{
		symlink($path, "{$temp_path}/{$filename}");
	}
	
	redirect("{$temp_path}/{$filename}");
	exit;
}

/**
 * 生成临时下载路径
 */
function temp_path()
{
	$uid = uid();
	if(!$uid)
		$uid = 0;
	
	return './temp/'.IP_INSTANCE_ID.'/download/'.sha1($uid.date('Y-m-d').$uid);
}

/**
 * 使用X-Sendfile和代替协议发送文件
 */
function xsendfile_download($path, $filename = '')
{
	if(!@is_file($path) || !($filesize = @filesize($path)))
	{
		return false;
	}
	
	if(empty($filename))
	{
		$filename = explode('/', str_replace(DIRECTORY_SEPARATOR, '/', $path));
		$filename = end($filename);
	}
	
	$x = explode('.', $filename);
	$extension = end($x);
	
	if(count($x) !== 1 && isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/Android\s(1|2\.[01])/', $_SERVER['HTTP_USER_AGENT']))
	{
		$x[count($x) - 1] = strtoupper($extension);
		$filename = implode('.', $x);
	}
	
	header('Content-Disposition: attachment; filename="'.$filename.'"');
	header('Expires: 0');
	header('Content-Transfer-Encoding: binary');
	header('Content-Length: '.$filesize);

	if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== FALSE)
	{
		header('Cache-Control: no-cache, no-store, must-revalidate');
	}

	header('Pragma: no-cache');
	
	switch(option('server_download_method', 'apache'))
	{
		case 'apache':
			$path = realpath($path);
			header("X-Sendfile: $path");
			break;
		case 'nginx':
			$path = substr($path, 1); //X-Accel-Redirect仅支持URI
			header("X-Accel-Redirect: $path");
			break;
	}
	exit;
}

/* End of file IP_download_helper.php */
/* Location: ./application/helpers/IP_download_helper.php */