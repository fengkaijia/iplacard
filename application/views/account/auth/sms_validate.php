<?php
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/jquery.numeric.js' : 'static/js/jquery.numeric.min.js').'"></script>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/jquery.countdown.js': 'static/js/jquery.countdown.min.js').'"></script>');
$this->load->view('header');?>

<?php echo form_open("account/sms/validate", array('class' => 'form-auth'));?>
	<h2 class="form-auth-heading">短信验证</h2>
	<?php echo validation_errors();?>
	<p>我们已经向 <?php echo substr($phone_number, 0, 3).'****'.substr($phone_number, -4, 4);?> 发送验证短信。请在 <span id="clock_expire">00:00</span> 内输入短信中的六位数验证码。</p>
	<?php echo form_input(array(
		'name' => 'code',
		'class' => 'form-control',
		'placeholder' => '验证码',
		'required' => NULL
	));
	?>
	<label for="close" class="checkbox">
		<?php echo form_checkbox('close', true);?> 同时关闭两步验证功能
	</label>
	<?php echo form_button(array(
		'name' => 'submit',
		'content' => '确认',
		'type' => 'submit',
		'class' => 'btn btn-primary'
	));
	echo ' ';
	echo form_button(array(
		'name' => 'resend',
		'content' => '重新发送',
		'type' => 'button',
		'class' => 'btn',
		'onclick' => onclick_redirect('account/sms')
	)); ?>
	
<?php echo form_close();?>

<?php
//时间实例
$this->ui->js('footer', 'countdate = new Date();');

//禁用重新发送
$this->ui->js('footer', '$(\'button[name=resend]\').attr(\'disabled\', \'\');');

//失效倒计时
$expire_js = <<<EOT
$('#clock_expire').countdown(countdate.getTime() + {$expire_time} * 1000, function(event) {
	$(this).html(event.strftime('%M:%S'));
});
EOT;
$this->ui->js('footer', $expire_js);

//重新发送倒计时
$resend_text = '重新发送';
$resend_js = <<<EOT
$('button[name=resend]').countdown(countdate.getTime() + {$resend_time} * 1000)
	.on('update.countdown', function(event) {
		var total_seconds = event.offset.minutes * 60 + event.offset.seconds;
		$(this).html('{$resend_text} ' + total_seconds); 
	})
	.on('finish.countdown', function(event) {
		$(this).html('{$resend_text}'); 
		$(this).removeAttr('disabled');
	});
EOT;
$this->ui->js('footer', $resend_js);

$this->ui->js('footer', '$("input[name=\'code\']").numeric()');
$this->ui->js('footer', 'form_auth_center();');
$this->load->view('footer'); ?>