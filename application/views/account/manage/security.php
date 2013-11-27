<?php $this->load->view('header');?>

<div class="page-header">
	<h1>帐户安全设置</h1>
</div>

<div class="row">
	<div class="col-md-3">
		<?php $this->load->view('account/manage/sidebar', array('now' => 'security'));?>
	</div>
	
	<div class="col-md-9">
		<?php echo form_open('account/settings/security', array('class' => 'well form-horizontal'));?>
			<?php echo form_fieldset('邮件通知设置'); ?>
				<p>设置接收邮件通知将可以在选定的情况发生时收到邮件通知，这将提高帐户的安全性。</p>
		
				<?php foreach($notice_options as $name => $option) { ?><div class="form-group <?php if(form_has_error("notice_{$name}")) echo 'has-error';?>">
					<?php echo form_label($option['name'], "notice_{$name}", array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-10">
						<div class="checkbox">
							<label>
								<?php echo form_checkbox(array(
									'name' => "notice_{$name}",
									'id' => "notice_{$name}",
									'value' => true,
									'checked' => $option['value'],
								));
								echo $option['description']; ?>
							</label>
						</div>
						<?php echo form_error("notice_{$name}");?>
					</div>
				</div><?php } ?>
			<?php echo form_fieldset_close();?>
		
			<div class="form-group">
				<div class="col-lg-10 col-lg-offset-2">
					<?php echo form_button(array(
						'name' => 'confirm',
						'content' => '修改设置',
						'type' => 'button',
						'class' => 'btn btn-primary',
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
							<h4 class="modal-title" id="submit_label">密码验证</h4>
						</div>
						<div class="modal-body">
							<p>您将更改您的帐户安全设置，请输入您的登录密码并点击确认更改按钮继续操作。</p>

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
								'content' => '确认修改',
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

<?php $this->load->view('footer');?>