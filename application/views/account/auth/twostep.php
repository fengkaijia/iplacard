<?php
$this->ui->html('header', '<script src="'.base_url(is_dev() ? 'static/js/jquery.numeric.js' : 'static/js/jquery.numeric.min.js').'"></script>');
$this->load->view('header');?>

<?php echo form_open("account/twostep", array('class' => 'form-auth'));?>
	<h2 class="form-auth-heading">两步验证</h2>
	<?php echo validation_errors();?>
	<p>两步验证已经启用，请输入 Google 身份验证器中生成的六位数验证码。</p>
	<?php echo form_input(array(
		'name' => 'code',
		'class' => 'form-control',
		'placeholder' => '验证码',
		'required' => NULL
	));
	?>
	<label for="safe" class="checkbox">
		<?php echo form_checkbox('safe', true);?> 30 天内在此设备上不再要求两步验证
	</label>
	<?php echo form_button(array(
		'name' => 'submit',
		'content' => '确认',
		'type' => 'submit',
		'class' => 'btn btn-primary'
	));?>
	<span style="padding-left: 8px; vertical-align: middle;"><?php echo anchor('account/sms', '无法接收验证码？');?></span>
<?php echo form_close();?>

<?php
$this->ui->js('footer', '$("input[name=\'code\']").numeric()');
$this->ui->js('footer', 'form_auth_center();');
$this->load->view('footer'); ?>