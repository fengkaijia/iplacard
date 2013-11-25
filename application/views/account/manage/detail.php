<?php $this->load->view('header');?>

<div class="page-header">
	<h1>帐户管理</h1>
</div>

<div class="row">
	<div class="col-md-3">
		<?php $this->load->view('account/manage/sidebar', array('now' => 'detail'));?>
	</div>
	
	<div class="col-md-9">
		<?php echo form_open('account/settings/home', array('class' => 'well form-horizontal'), array('change_email' => false, 'change_phone' => false));?>
			<?php echo form_fieldset('个人信息'); ?>
				<p>您的邮箱和手机等信息将用于登录 iPlacard，错误的信息将可以导致无法登录 iPlacard，修改这些信息时请仔细确认信息是否正确。</p>
		
				<div class="form-group">
					<?php echo form_label('姓名', 'name', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-4">
						<?php echo form_input(array(
							'name' => 'name',
							'id' => 'name',
							'value' => $data['name'],
							'disabled' => NULL,
							'class' => 'form-control',
						));?>
						<div class="help-block">如需更改姓名请联系管理员。</div>
					</div>
				</div>
		
				<div class="form-group <?php if(form_has_error('email')) echo 'has-error';?>">
					<?php echo form_label('电子邮箱', 'email', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-4">
						<div class="input-group">
							<?php echo form_input(array(
								'name' => 'email',
								'id' => 'email',
								'value' => set_value('email', $data['email']),
								'disabled' => NULL,
								'class' => 'form-control',
							));?>
							<span class="input-group-addon" onclick="edit_item('email');"><?php echo icon('pencil', false);?></span>
						</div>
						<?php if(form_has_error('email'))
							echo form_error('email');
						else { ?><div class="help-block">请点击右侧图标以更改邮箱。</div><?php } ?>
					</div>
				</div>
				
				<?php if(isset($data['email_pending'])) { ?><div class="form-group has-warning">
					<?php echo form_label('待验证邮箱', 'email_pending', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-4">
						<?php echo form_input(array(
							'name' => 'email_pending',
							'id' => 'email_pending',
							'value' => $data['email_pending'],
							'disabled' => NULL,
							'class' => 'form-control',
						));?>
						<div class="help-block">
							<span id="email_help" data-trigger="hover focus" data-original-title="确认您的新邮箱" data-toggle="popover" data-content="我们已经向此邮箱发送了一封确认邮件，请在 24 小时内按邮件中的提示确认此邮箱的可用性。在此邮箱获得确认之前，您将使用旧邮箱登录 iPlacard。">
								<?php echo icon('question-circle');?>
							</span>等待验证可用性……
							<?php $this->ui->js('footer', "$('#email_help').popover();");?>
						</div>
					</div>
				</div><?php } ?>
		
				<div class="form-group <?php if(form_has_error('phone')) echo 'has-error';?>">
					<?php echo form_label('手机', 'email', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-4">
						<div class="input-group">
							<?php echo form_input(array(
								'name' => 'phone',
								'id' => 'phone',
								'value' => set_value('phone', $data['phone']),
								'disabled' => NULL,
								'class' => 'form-control',
							));?>
							<span class="input-group-addon" onclick="edit_item('phone'); $('#phone_text').text('仅支持中国大陆地区运营商号码。');"><?php echo icon('pencil', false);?></span>
						</div>
						<?php if(form_has_error('phone'))
							echo form_error('phone');
						else { ?><div class="help-block" id="phone_text">请点击右侧图标以更改手机号码。</div><?php } ?>
					</div>
				</div>
			<?php echo form_fieldset_close();?>
		
			<div class="form-group">
				<div class="col-lg-10 col-lg-offset-2">
					<?php echo form_button(array(
						'name' => 'confirm',
						'content' => '提交更改',
						'type' => 'button',
						'class' => 'btn btn-primary disabled',
						'data-toggle' => 'modal',
						'data-target' => '#submit_data',
					));?>
				</div>
			</div>
		
			<div class="modal fade" id="submit_data" tabindex="-1" role="dialog" aria-labelledby="submit_label" aria-hidden="true">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">
							<?php echo form_button(array(
								'content' => '&times;',
								'class' => 'close',
								'type' => 'button',
								'data-dismiss' => 'modal',
								'aria-hidden' => 'true'
							));?>
							<h4 class="modal-title" id="submit_label">确认更改个人信息</h4>
						</div>
						<div class="modal-body">
							<p>您将更改您的个人信息，请输入您的登录密码并点击确认更改按钮继续操作。</p>

							<div class="form-group <?php if(form_has_error('password')) echo 'has-error';?>">
								<?php echo form_label('登录密码', 'password', array('class' => 'col-lg-3 control-label'));?>
								<div class="col-lg-5">
									<?php echo form_password(array(
										'name' => 'password',
										'id' => 'password',
										'class' => 'form-control',
										'required' => NULL
									));
									echo form_error('password');?>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<?php echo form_button(array(
								'content' => '关闭',
								'type' => 'button',
								'class' => 'btn btn-link',
								'data-dismiss' => 'modal'
							));
							echo form_button(array(
								'name' => 'submit',
								'content' => '确认更改',
								'type' => 'submit',
								'class' => 'btn btn-primary'
							)); ?>
						</div>
					</div>
				</div>
			</div>
		<?php echo form_close();?>
	</div>
</div>

<?php
$edit_js = <<<EOT
function edit_item(item)
{
	$('#'+item).removeAttr('disabled');
	$('button[name=confirm]').removeClass('disabled');
	$('input[name=change_'+item+']').val(true);
}
EOT;
$this->ui->js('footer', $edit_js);
$this->load->view('footer');?>