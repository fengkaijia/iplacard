<?php $this->load->view('header');?>

<?php echo form_open('account/recover', array('class' => 'form-auth'));?>
	<h2 class="form-auth-heading">密码重置</h2>
	<?php if($sent) { ?><p>一封密码重置确认邮件已经发送到您的邮箱 <?php echo icon('envelope');?><i><?php echo $email;?></i> 中，请按邮件指示的操作重置您的密码，如果您长时间没有收到重置邮件，请联系管理员。</p>
	<?php echo form_button(array(
		'name' => 'login',
		'content' => '登录',
		'type' => 'button',
		'class' => 'btn btn-primary',
		'onclick' => onclick_redirect('account/login')
	));
	} else { ?>
	<?php echo validation_errors();
	echo form_input(array(
		'name' => 'name',
		'value' => set_value('name'),
		'class' => 'form-control',
		'placeholder' => '姓名',
		'required' => NULL
	));
	echo form_input(array(
		'name' => 'email',
		'value' => set_value('email'),
		'class' => 'form-control',
		'placeholder' => '电子邮箱地址',
		'required' => NULL
	));
	?>
	<p>如果忘记了密码，您可以提供您的姓名和电子邮箱地址，点击确认后，我们将会向您的邮箱发送一封指导完成操作的重置确认邮件。</p>
	<?php echo form_button(array(
		'name' => 'recover',
		'content' => '确认重置',
		'type' => 'submit',
		'class' => 'btn btn-primary',
		'onclick' => 'loader(this);'
	));
	echo form_button(array(
		'content' => '登录',
		'type' => 'button',
		'class' => 'btn btn-link',
		'onclick' => onclick_redirect('account/login')
	)); ?>
<?php }
echo form_close();?>

<?php $this->ui->js('footer', 'form_auth_center();');
$this->load->view('footer'); ?>