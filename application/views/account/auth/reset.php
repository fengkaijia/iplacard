<?php $this->load->view('header');?>

<?php echo form_open("account/reset/$uri", array('class' => 'form-auth'));?>
	<h2 class="form-auth-heading">重置密码</h2>
	<?php echo validation_errors();?>
	<p>您将重新设置帐户 <?php echo icon('user');?><i><?php echo $email;?></i> 的登录密码。</p>
	<?php echo form_password(array(
		'name' => 'password',
		'class' => 'form-control',
		'placeholder' => '新密码',
		'required' => NULL
	));
	echo form_password(array(
		'name' => 'password_repeat',
		'class' => 'form-control',
		'placeholder' => '重复新密码',
		'required' => NULL
	));
	?>
	<p>确认重置密码后，您将可以使用新的密码登录。</p>
	<?php echo form_button(array(
		'name' => 'reset',
		'content' => '确认重置',
		'type' => 'submit',
		'class' => 'btn btn-primary'
	));?>
<?php echo form_close();?>

<?php $this->ui->js('footer', 'form_auth_center();');
$this->load->view('footer'); ?>