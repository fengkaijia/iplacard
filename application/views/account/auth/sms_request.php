<?php $this->load->view('header');?>

<?php echo form_open("account/sms/request", array('class' => 'form-auth'), array('request' => true));?>
	<h2 class="form-auth-heading">短信验证</h2>
	<?php echo validation_errors();?>
	<p>我们将向你的手机发送一条包含有六位数验证码的短信。您将在单击发送验证码按钮后 2 分钟内收到短信。收到短信后，请根据页面下一步提示输入您收到的六位数验证码以完成验证。了解说明后请点击发送验证码按钮以继续。</p>
	<?php echo form_button(array(
		'name' => 'submit',
		'content' => '发送验证码',
		'type' => 'submit',
		'class' => 'btn btn-primary'
	));
	echo form_button(array(
		'name' => 'resend',
		'content' => '已有验证码',
		'type' => 'button',
		'class' => 'btn btn-link',
		'onclick' => onclick_redirect('account/sms/validate')
	)); ?>
	
<?php echo form_close();?>

<?php $this->ui->js('footer', 'form_auth_center();');
$this->load->view('footer'); ?>