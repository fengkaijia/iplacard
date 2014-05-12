<?php
$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/bootstrap.datetimepicker.css' : 'static/css/bootstrap.datetimepicker.min.css').'" rel="stylesheet">');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/bootstrap.datetimepicker.js' : 'static/js/bootstrap.datetimepicker.min.js').'"></script>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/bootstrap.datetimepicker.locale.js' : 'static/js/bootstrap.datetimepicker.locale.min.js').'"></script>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/jquery.numeric.js' : 'static/js/jquery.numeric.min.js').'"></script>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/jquery.countdown.js': 'static/js/jquery.countdown.min.js').'"></script>');
$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/bootstrap.select.css' : 'static/css/bootstrap.select.min.css').'" rel="stylesheet">');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/bootstrap.select.js' : 'static/js/bootstrap.select.min.js').'"></script>');
$this->load->view('header');?>

<div style="padding-top: 20px;"></div>

<div class="row">
	<div class="<?php echo $unpaid ? 'col-md-8' : 'col-md-8 col-md-offset-2';?>">
		<div id="invoice-detail" style="padding-top: 16px;">
			<?php echo $invoice_html;?>
		</div>
	</div>
	
	<?php if($unpaid) { ?><div class="col-md-4">
		<h3>收到汇款</h3>
		
		<div id="pre_receive">
			<p>您将会在 <span id="clock_overdue">00:00:00</span> 秒（<?php echo date('Y年m月d日', $due_time);?>前）内来自<?php echo anchor("delegate/profile/{$delegate['id']}", icon('user', false).$delegate['name']);?>收到汇款。</p>
			<p>收到汇款后请点击确认汇款信息。</p>
			<p><a class="btn btn-primary" href="#" onclick="$('#do_receive').show(); $('#pre_receive').hide();"><?php echo icon('check-circle');?>确认收款</a></p>
		</div>
		
		<div id="do_receive">
			<?php echo form_open("billing/invoice/{$invoice['id']}/transaction"); ?>
				<p>请填写收款信息，这些信息将在提交确认收款后显示在帐单中。提交后您将无法更改这些信息，请仔细核对。</p>
				<?php if(!empty($transaction)) { ?><p>在此之前，代表填写了转帐信息，请在此基础上核对并更新收款信息。</p><?php } ?>
				
				<div class="form-group <?php if(form_has_error('time')) echo 'has-error';?>">
					<?php echo form_label('转帐时间', 'gateway', array('class' => 'control-label'));?>
					<div class="input-group date form_datetime">
						<?php echo form_input(array(
							'name' => 'time',
							'class' => 'form-control',
							'size' => '16',
							'value' => set_value('time', date('Y-m-d H:i', !empty($transaction['time']) ? $transaction['time'] : time()))
						)); ?>
						<span class="input-group-addon"><span class="glyphicon glyphicon-th" ><?php echo icon('calendar', false);?></span></span>
					</div>
					<?php if(form_has_error('time'))
						echo form_error('time');
					else { ?><div class="help-block">转帐操作时间，如果允许，请精确到分钟。</div><?php } ?>
				</div><?php
				$this->ui->js('footer', '$(".form_datetime").datetimepicker({
					language:  "zh-CN",
					format: "yyyy-mm-dd HH:ii",
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
					echo form_dropdown_select('gateway', $gateway, empty($transaction['gateway']) ? array() : $transaction['gateway'], false, array(), array(), array(), 'selectpicker', 'data-width="100%" title="选择交易渠道"');
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
					else { ?><div class="help-block">交易流水号或支付宝交易号，如未知请留空。</div><?php } ?>
				</div>
				
				<div class="form-group <?php if(form_has_error('amount')) echo 'has-error';?>">
					<?php echo form_label('转帐金额', 'amount', array('class' => 'control-label'));
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
						'content' => icon('check-circle').'确定收到汇款',
						'type' => 'submit',
						'class' => 'btn btn-primary',
						'onclick' => 'loader(this);'
					)); ?>
				</div>
			<?php echo form_close(); ?>
		</div>
		<?php
		if(validation_errors())
			$this->ui->js('footer', "$('#pre_receive').hide();");
		else
			$this->ui->js('footer', "$('#do_receive').hide();");
		?>
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