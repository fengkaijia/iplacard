<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 强制登出视图
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
 * @package iPlacard
 * @since 2.0
 */

$this->load->view('header');?>

<?php echo form_open('', array('class' => 'form-auth'));?>
	<h2 class="form-auth-heading">强制登出</h2>
	<?php if(!$no_action) { ?><p>您已经强制登出了正在其他位置登录的帐号，如果出现帐户异常请尽快修改密码。</p><?php } else { ?><p>正在其他位置登录的帐号已经登出无需强制注销，如果出现帐户异常请尽快修改密码。</p><?php } ?>
	<?php echo form_button(array(
		'name' => 'login',
		'content' => '登录',
		'type' => 'button',
		'class' => 'btn btn-primary',
		'onclick' => onclick_redirect('account/login')
	));?>
<?php echo form_close();?>

<?php $this->ui->js('footer', 'form_auth_center();');
$this->load->view('footer'); ?>