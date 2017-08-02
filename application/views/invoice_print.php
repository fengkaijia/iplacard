<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 账单打印视图
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
 * @package iPlacard
 * @since 2.0
 */
?><!DOCTYPE html>
<html lang="zh_CN">
<head profile="http://gmpg.org/xfn/11">
	<title>iPlacard</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge, chrome=1" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />

	<link href="<?php echo static_url(is_dev() ? 'static/css/bootstrap.css' : 'static/css/bootstrap.min.css');?>" rel="stylesheet">
	<link href="<?php echo static_url(is_dev() ? 'static/css/webfont.css' : 'static/css/webfont.min.css');?>" rel="stylesheet">
	<link href="<?php echo static_url(is_dev() ? 'static/css/font-awesome.css' : 'static/css/font-awesome.min.css');?>" rel="stylesheet">
	<link href="<?php echo static_url(is_dev() ? 'static/css/iplacard.css' : 'static/css/iplacard.min.css');?>" rel="stylesheet">
	
	<script src="<?php echo static_url(is_dev() ? 'static/js/jquery.js' : 'static/js/jquery.min.js');?>"></script>
	<script src="<?php echo static_url(is_dev() ? 'static/js/bootstrap.js' : 'static/js/bootstrap.min.js');?>"></script>
	<script src="<?php echo static_url(is_dev() ? 'static/js/iplacard.js' : 'static/js/iplacard.min.js');?>"></script>

	<meta name="robots" content="noallow" />
	<meta name="description" content="iPlacard is the Next-Gen Model United Nations Conference Management Service Provider backed by IMUNC." />
	<meta name="generator" content="iPlacard Engine <?php echo IP_VERSION;?>" />
	<meta name="author" content="Kaijia Feng" />
</head>

<body>
	<div id="wrap">
		<div id="content" class="container" style="padding-top: 36px;">
			<div class="page-header" style="border-bottom-width: 0px;">
				<h1><?php echo option('organization', 'iPlacard');?></h1>
			</div>
			
			<?php $this->load->view('invoice');?>
		</div>
	</div>
</body>
</html>