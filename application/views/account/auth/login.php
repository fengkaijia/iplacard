<?php $this->load->view('header');?>

<?php echo form_open('account/login', array('class' => 'form-auth'));?>
	<h2 class="form-auth-heading">登录</h2>
	<?php echo validation_errors();
	echo form_input(array(
		'name' => 'email',
		'value' => set_value('email'),
		'class' => 'form-control',
		'placeholder' => '电子邮箱地址',
		'required' => NULL,
		'autofocus' => NULL
	));
	echo form_password(array(
		'name' => 'password',
		'class' => 'form-control',
		'placeholder' => '密码',
		'required' => NULL
	));
	?>
	<label for="remember" class="checkbox">
		<?php echo form_checkbox('remember', true);?> 记住登录
	</label>
	<?php echo form_button(array(
		'name' => 'login',
		'content' => '登录',
		'type' => 'submit',
		'class' => 'btn btn-primary'
	));
	echo form_button(array(
		'content' => '忘记密码？',
		'type' => 'button',
		'class' => 'btn btn-link',
		'onclick' => onclick_redirect('account/recover')
	)); ?>
	
<?php echo form_close();?>

<?php if($this->session->userdata('dismiss_browser_notice') != true) { ?><!--[if lt IE 8]>
	<script language="javascript" type="text/javascript">
		window.location.href="<?php echo base_url('help/browser');?>";
	</script>
<![endif]--><?php } ?>


<?php $this->ui->js('footer', 'form_auth_center();');
$this->load->view('footer'); ?>