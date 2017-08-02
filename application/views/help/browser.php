<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 浏览器不兼容提示视图
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
 * @package iPlacard
 * @since 2.0
 */

$this->load->view('header');?>

<div class="page-header">
	<h1>不支持的浏览器</h1>
</div>

<div style="margin-bottom: 30px;">
	<p>您正在使用较旧版本的 Internet Explorer。</p>
	<p>虽然我们尽全力支持 iPlacard 对旧版本 Internet Explorer 的兼容性，我们仍然无法保证您可以正常访问 iPlacard 的全部功能。我们强烈建议更新您的 Internet Explorer 至<a href="http://windows.microsoft.com/zh-cn/internet-explorer/">最新版本</a>。同时您可以尝试最新版本的 <a href="http://firefox.com/download/">Mozilla Firefox</a> 和 <a href="https://www.google.com/intl/zh-CN/chrome/browser/">Google Chrome</a> 浏览器，iPlacard 完整兼容上述浏览器。</p>
	<p>同时，您仍然可以使用当前的浏览器访问 iPlacard。</p>
</div>

<div align="center">
	<a href="<?php echo base_url('help/browser/dismiss');?>" class="btn btn-primary">我希望继续使用当前浏览器访问 iPlacard</a> 
</div>

<?php $this->load->view('footer');?>