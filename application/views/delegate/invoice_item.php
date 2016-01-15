<?php
$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/bootstrap.datetimepicker.css' : 'static/css/bootstrap.datetimepicker.min.css').'" rel="stylesheet">');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/bootstrap.datetimepicker.js' : 'static/js/bootstrap.datetimepicker.min.js').'"></script>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/bootstrap.datetimepicker.locale.js' : 'static/js/bootstrap.datetimepicker.locale.min.js').'"></script>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/jquery.numeric.js' : 'static/js/jquery.numeric.min.js').'"></script>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/jquery.countdown.js' : 'static/js/jquery.countdown.min.js').'"></script>');
$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/bootstrap.select.css' : 'static/css/bootstrap.select.min.css').'" rel="stylesheet">');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/bootstrap.select.js' : 'static/js/bootstrap.select.min.js').'"></script>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/locales/bootstrap.select.locale.js' : 'static/js/locales/bootstrap.select.locale.min.js').'"></script>');
$this->load->view('header');?>

<div style="padding-top: 10.5px;"></div>

<div class="row">
	<div class="<?php echo $unpaid ? 'col-md-8' : 'col-md-8 col-md-offset-2';?>">
		<div id="invoice-detail" style="padding-top: 16px;">
			<?php echo $invoice_html;?>
		</div>
		
		<?php if(!is_null($previous) || !is_null($next)) { ?><ul class="pager">
			<?php if(!is_null($previous)) { ?><li class="previous"><?php echo anchor("apply/invoice/{$previous}", '&larr; 前一份账单');?></li><?php } ?>
			<?php if(!is_null($next)) { ?><li class="next"><?php echo anchor("apply/invoice/{$next}", '&rarr; 后一份账单');?></li><?php } ?>
		</ul><?php } ?>
	</div>
	
	<?php if($unpaid) { ?><div class="col-md-4">
		<h3>支付</h3>
		
		<div id="pre_pay">
			<p>您将需要 <span id="clock_overdue">00:00:00</span> 秒（<?php echo date('Y年m月d日', $due_time);?>前）内完成支付。如果您未能在账单到期时间前完成汇款，请与我们联系以延长账单支付时间。</p>
			<p><a class="btn btn-primary" href="#" data-toggle="modal" data-target="#payment_offline"><?php echo icon('info-circle');?>查看汇款详情</a></p>
			<?php if(empty($transaction)) { ?><p>通过线下支付的汇款将需要经过人工验证。如果已经完成支付，请点击下方按钮填写相关信息以便我们确认。</p>
			<p><a class="btn btn-primary" href="#" onclick="$('#do_pay').show(); $('#pre_pay').hide();"><?php echo icon('check-circle');?>已经完成支付</a></p><?php
			} else { ?><p>通过线下支付的汇款将需要经过人工验证。您已经填写过转账信息，如果需要更新转账信息请点击下方按钮。</p>
			<p><a class="btn btn-primary" href="#" onclick="$('#do_pay').show(); $('#pre_pay').hide();"><?php echo icon('edit');?>更新转账信息</a></p><?php } ?>

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
		</div>
		
		<div id="do_pay">
			<?php echo form_open("apply/invoice/{$invoice['id']}/transaction"); ?>
				<p>完成线下支付后，请在此处填写汇款信息，这些信息将大大加快我们的汇款速度。通常，这些信息可以在您的交易凭条中找到。</p>
				
				<div class="form-group <?php if(form_has_error('time')) echo 'has-error';?>">
					<?php echo form_label('转账时间', 'gateway', array('class' => 'control-label'));?>
					<div class="input-group date form_datetime">
						<?php $value = set_value('time', $transaction['time']);
						echo form_input(array(
							'name' => 'time',
							'class' => 'form-control',
							'size' => '16',
							'value' => !empty($value) ? set_value('time', date('Y-m-d H:i', $transaction['time'])) : ''
						)); ?>
						<span class="input-group-addon"><span class="glyphicon glyphicon-th" ><?php echo icon('calendar', false);?></span></span>
					</div>
					<?php if(form_has_error('time'))
						echo form_error('time');
					else { ?><div class="help-block">转账操作时间，如果允许，请精确到分钟。</div><?php } ?>
				</div><?php
				$this->ui->js('footer', '$(".form_datetime").datetimepicker({
					language:  "zh-CN",
					format: "yyyy-mm-dd hh:ii",
					weekStart: 1,
					todayBtn: 1,
					autoclose: 1,
					todayHighlight: 1,
					startView: 2,
					forceParse: 0,
					showMeridian: 1,
					pickerPosition: "bottom-left"
				});');
				?>
				
				<div class="form-group <?php if(form_has_error('gateway')) echo 'has-error';?>">
					<?php echo form_label('交易渠道', 'gateway', array('class' => 'control-label'));
					echo form_dropdown_select('gateway', $gateway, empty($transaction['gateway']) ? array() : $transaction['gateway'], false, array(), array(), array(), array(), 'selectpicker', 'data-width="100%" title="选择交易渠道"');
					if(form_has_error('gateway'))
						echo form_error('gateway');
					?>
				</div>
				<?php $this->ui->js('footer', "$('.selectpicker').selectpicker();");
				if(empty($transaction['gateway']))
					$this->ui->js('footer', "$('.selectpicker').selectpicker('val', null);");
				?>
				
				<div class="form-group <?php if(form_has_error('transaction')) echo 'has-error';?>">
					<?php echo form_label('交易流水号', 'transaction', array('class' => 'control-label'));
					echo form_input(array(
						'name' => 'transaction',
						'id' => 'transaction',
						'class' => 'form-control',
						'value' => set_value('transaction', $transaction['transaction'])
					));
					if(form_has_error('transaction'))
						echo form_error('transaction');
					else { ?><div class="help-block">交易流水号、汇款帐号或支付宝交易号，如未知请留空。</div><?php } ?>
				</div>
				
				<div class="form-group <?php if(form_has_error('amount')) echo 'has-error';?>">
					<?php echo form_label('转账金额', 'amount', array('class' => 'control-label'));
					echo form_input(array(
						'name' => 'amount',
						'id' => 'amount',
						'class' => 'form-control',
						'value' => set_value('amount', $transaction['amount'])
					));
					if(form_has_error('amount'))
						echo form_error('amount');
					else { ?><div class="help-block">仅需填写数字并请精确到分位。</div><?php } ?>
				</div>
				
				<div class="form-group">
					<?php echo form_button(array(
						'name' => 'submit',
						'content' => '提交信息',
						'type' => 'submit',
						'class' => 'btn btn-primary',
						'onclick' => 'loader(this);'
					)); ?>
				</div>
				
				<p>收到您的汇款后，我们将尽快进行确认账单。您将会收到确认邮件或短信，同时您可以登录 iPlacard 查看汇款状态。</p>
			<?php echo form_close(); ?>
		</div>
		<?php
		if(validation_errors())
			$this->ui->js('footer', "$('#pre_pay').hide();");
		else
			$this->ui->js('footer', "$('#do_pay').hide();");
		?>
	</div><?php } ?>
</div>

<?php
//倒计时
$overdue_js = <<<EOT
$('#clock_overdue').countdown({$due_time} * 1000, function(event) {
	$(this).html(event.strftime('%-D 天 %H:%M:%S'));
});
EOT;
$this->ui->js('footer', $overdue_js);

$this->load->view('footer');?>