<?php $this->load->view('header');?>

<div class="page-header">
	<h1>帐户管理</h1>
</div>

<div class="row">
	<div class="col-md-3">
		<?php $this->load->view('account/manage/sidebar', array('now' => 'password'));?>
	</div>
	
	<div class="col-md-9">
		<?php echo form_open('account/settings/password', array('class' => 'well form-horizontal'));?>
			<?php echo form_fieldset('修改密码'); ?>
				<p><?php
				if(is_sudo())
					echo '新密码的长度不得低于 8 位。一个安全的密码应当同时包含数字和大小写字母。您将以 SUDO 授权更改代表的登录密码，请输入您管理员帐号的密码以验证修改，密码修改后您需要手动告知代表新的密码。';
				else
					echo '新密码的长度不得低于 8 位。一个安全的密码应当同时包含数字和大小写字母，请牢记您的新密码，如果忘记密码，您可以通过找回密码功能重置您的密码。';
				?></p>
		
				<div class="form-group <?php if(form_has_error('old_password')) echo 'has-error';?>">
					<?php echo form_label(is_sudo() ? '管理员密码' : '旧密码', 'old_password', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-4">
						<?php echo form_password(array(
							'name' => 'old_password',
							'id' => 'old_password',
							'class' => 'form-control',
						));
						echo form_error('old_password');?>
					</div>
				</div>
		
				<div class="form-group <?php if(form_has_error('password')) echo 'has-error';?>">
					<?php echo form_label('新密码', 'password', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-4">
						<?php echo form_password(array(
							'name' => 'password',
							'id' => 'password',
							'class' => 'form-control',
						));
						echo form_error('password');?>
					</div>
				</div>
		
				<div class="form-group <?php if(form_has_error('password_repeat')) echo 'has-error';?>">
					<?php echo form_label('重复密码', 'password_repeat', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-4">
						<?php echo form_password(array(
							'name' => 'password_repeat',
							'id' => 'password_repeat',
							'class' => 'form-control',
						));
						echo form_error('password_repeat');?>
					</div>
				</div>
			<?php echo form_fieldset_close();?>

			<div class="form-group">
				<div class="col-lg-10 col-lg-offset-2">
					<?php echo form_button(array(
						'name' => 'submit',
						'content' => '修改密码',
						'type' => 'submit',
						'class' => 'btn btn-primary',
						'onclick' => 'loader(this);'
					));?>
				</div>
			</div>
		<?php echo form_close();?>
	</div>
</div>

<?php $this->load->view('footer');?>