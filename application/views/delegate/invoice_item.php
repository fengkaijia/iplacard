<?php
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/jquery.numeric.js' : 'static/js/jquery.numeric.min.js').'"></script>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/jquery.countdown.js': 'static/js/jquery.countdown.min.js').'"></script>');
$this->load->view('header');?>

<div style="padding-top: 20px;"></div>

<div class="row">
	<div class="<?php echo $unpaid ? 'col-md-8' : 'col-md-8 col-md-offset-2';?>">
		<div id="invoice-detail" style="padding-top: 16px;">
			<?php echo $invoice_html;?>
		</div>
	</div>
	
	<?php if($unpaid) { ?><div class="col-md-4">
		<h3>支付</h3>
		
		<p>您将需要 <span id="clock_overdue">00:00:00</span> 秒（<?php echo date('Y年m月d日', $due_time);?>）内完成支付。</p>
		<p>请尽快完成支付，如果您未能在帐单到期时间前完成汇款，请与我们联系以延长帐单支付时间。</p>
		<p><a class="btn btn-primary" href="#" data-toggle="modal" data-target="#payment_offline"><?php echo icon('info-circle');?>查看汇款详情</a></p>
		<p>收到您的汇款后，我们将尽快进行确认帐单。您将会收到确认短信和邮件，同时您可以登录 iPlacard 查看汇款状态。</p>
		
		<?php echo form_open("", array(
			'class' => 'modal fade form-horizontal',
			'id' => 'payment_offline',
			'tabindex' => '-1',
			'role' => 'dialog',
			'aria-labelledby' => 'offline_label',
			'aria-hidden' => 'true'
		));?><div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<?php echo form_button(array(
							'content' => '&times;',
							'class' => 'close',
							'type' => 'button',
							'data-dismiss' => 'modal',
							'aria-hidden' => 'true'
						));?>
						<h4 class="modal-title" id="offline_label">线下汇款详情</h4>
					</div>

					<div class="modal-body">
						<div style="padding-bottom: 10px;"><?php echo option('invoice_payment_offline_info', '');?></div>

						<div><span class="label label-primary">注意</span> 如有任何问题请<?php echo mailto(option('site_contact_email', 'contact@iplacard.com'), '联系管理员'); ?>。</div>
					</div>

					<div class="modal-footer">
						<?php echo form_button(array(
							'content' => '关闭',
							'type' => 'button',
							'class' => 'btn btn-primary',
							'data-dismiss' => 'modal'
						)); ?>
					</div>
				</div>
			</div>
		<?php echo form_close(); ?>
	</div><?php } ?>
</div>

<?php
//倒计时
$overdue_js = <<<EOT
$('#clock_overdue').countdown({$due_time} * 1000, function(event) {
	$(this).html(event.strftime('%H:%M:%S'));
});
EOT;
$this->ui->js('footer', $overdue_js);

$this->load->view('footer');?>