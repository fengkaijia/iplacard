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
			<?php echo form_fieldset('帐户安全状态'); ?>
				<p>iPlacard 非常重视帐户安全，我们提供安全码、两步验证、短信验证等多种措施提升您的帐户安全性。</p>
		
				<div class="form-group">
					<?php echo form_label('密码', 'info_password', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-10">
						<span class="text-<?php echo $info['password'] ? 'success' : 'warning';?>" style="padding-top: 11px; display: inline-block;"><strong><?php echo $info['password'] ? sprintf('最近更改于 %s（%s）', unix_to_human($info['password']), nicetime($info['password'])) : '从未更改';?></strong></span>
					</div>
				</div>
				
				<div class="form-group">
					<?php echo form_label('安全码', 'info_pin', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-10">
						<span class="text-<?php echo $info['pin'] ? 'success' : 'danger';?>" style="padding-top: 11px; display: inline-block;"><strong><?php echo $info['pin'] ? sprintf('最近更改于 %s（%s）', unix_to_human($info['pin']), nicetime($info['pin'])) : '从未更改';?></strong></span>
					</div>
				</div>
				
				<div class="form-group">
					<?php echo form_label('两步验证', 'info_twostep', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-10">
						<span class="text-<?php echo $info['twostep'] ? 'success' : 'warning';?>" style="padding-top: 11px; display: inline-block;"><strong><?php echo $info['twostep'] ? '已经启用' : '尚未启用';?></strong></span>
					</div>
				</div>
				
				<div class="form-group">
					<div class="col-lg-10 col-lg-offset-2">
						<?php if(!$info['password']) echo form_button(array(
							'content' => '修改密码',
							'type' => 'button',
							'class' => 'btn btn-primary',
							'onclick' => onclick_redirect('account/settings/password')
						));?>
						<?php if(!$info['pin']) echo form_button(array(
							'content' => '设置安全码',
							'type' => 'button',
							'class' => 'btn btn-primary',
							'onclick' => onclick_redirect('account/settings/pin')
						));?>
						<?php if(!$info['twostep']) echo form_button(array(
							'content' => '启用两步验证',
							'type' => 'button',
							'class' => 'btn btn-primary',
							'onclick' => onclick_redirect('account/settings/twostep')
						));?>
					</div>
				</div>
			<?php echo form_fieldset_close();?>
			
			<br />
			
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
							<p><?php
							if(is_sudo())
								echo '您将以 SUDO 授权更改代表的邮件通知设置，请输入您管理员帐号的登录密码并点击确认更改按钮继续操作。';
							else
								echo '您将更改您的个人信息，请输入您的登录密码并点击确认更改按钮继续操作。';
							?></p>

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