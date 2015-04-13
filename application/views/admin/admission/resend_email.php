<p>如果代表没有收到登录信息，您可以操作重新发送欢迎邮件。</p>

<div class="btn-group" style="margin-bottom: 10.5px;">
	<a href="#" class="btn btn-warning" data-toggle="modal" data-target="#resend_email" onclick="$('#reset').attr('checked', false);"><?php echo icon('paper-plane');?>重发欢迎邮件</a>
	<a href="#" class="btn btn-warning dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></a>
	<ul class="dropdown-menu">
		<li><a href="#" data-toggle="modal" data-target="#resend_email" onclick="$('#reset').attr('checked', true);">重发邮件并重置密码</a></li>
	</ul>
</div>

<?php echo form_open("delegate/operation/resend_email/$uid", array(
	'class' => 'modal fade form-horizontal',
	'id' => 'resend_email',
	'tabindex' => '-1',
	'role' => 'dialog',
	'aria-labelledby' => 'resend_label',
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
				<h4 class="modal-title" id="resend_label">重新发送欢迎邮件</h4>
			</div>
			<div class="modal-body">
				<p>将会操作重发<?php echo icon('user', false).$delegate['name'];?>代表的欢迎邮件。在新发送的报名邮件中，如果没有设置重置密码，密码一栏将显示为<code>（您原先使用的密码）</code>；如果设置重置密码，密码一栏将显示为新的密码。</p>
				
				<div class="checkbox">
					<label>
						<?php echo form_checkbox(array(
							'name' => 'reset',
							'id' => 'reset',
							'value' => true,
							'checked' => false,
						)); ?> 重置代表登录密码
						<div class="help-block">将会重置代表 iPlacard 帐户的登录密码，并且将会在邮件中包含新的密码。</div>
					</label>
				</div>
			</div>
			<div class="modal-footer">
				<?php echo form_button(array(
					'content' => '取消',
					'type' => 'button',
					'class' => 'btn btn-link',
					'data-dismiss' => 'modal'
				));
				echo form_button(array(
					'name' => 'submit',
					'content' => '确认重发邮件',
					'type' => 'submit',
					'class' => 'btn btn-warning',
					'onclick' => 'loader(this);'
				)); ?>
			</div>
		</div>
	</div>
<?php echo form_close(); ?>