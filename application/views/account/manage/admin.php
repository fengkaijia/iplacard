<?php $this->load->view('header');?>

<div class="page-header">
	<h1>管理设置</h1>
</div>

<div class="row">
	<div class="col-md-3">
		<?php $this->load->view('account/manage/sidebar', array('now' => 'admin'));?>
	</div>
	
	<div class="col-md-9">
		<?php echo form_open('account/settings/admin', array('class' => 'well form-horizontal'));?>
			<?php echo form_fieldset('批处理'); ?>
				<p>当有大量相同任务需要处理时，批处理工具将有效提高生产力。修改这些设置将不会影响其他管理员。</p>
		
				<div class="form-group <?php if(form_has_error('admin_batch_admission')) echo 'has-error';?>">
					<?php echo form_label('批量审核', 'admin_batch_admission', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-10">
						<div class="checkbox">
							<label>
								<?php echo form_checkbox(array(
									'name' => 'admin_batch_admission',
									'id' => 'admin_batch_admission',
									'value' => true,
									'checked' => $admin_options['batch_admission']['value'],
								)); ?> 开启批量审核模式
							</label>
						</div>
						<?php if(form_has_error('admin_batch_admission'))
							echo form_error('admin_batch_admission');
						else { ?><div class="help-block">在批量审核模式下，每当审核通过一位代表（或完成一项面试官分配，如果有必要）后，系统将会自动跳转到下一位需要审核（或分配面试官，如果有必要）的代表。</div><?php } ?>
					</div>
				</div>
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
							<p>您将更改您的个人管理设置，请输入您的登录密码并点击确认更改按钮继续操作。</p>

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
								'class' => 'btn btn-primary',
								'onclick' => 'loader(this);'
							)); ?>
						</div>
					</div>
				</div>
			</div>
		<?php echo form_close();?>
	</div>
</div>

<?php $this->load->view('footer');?>