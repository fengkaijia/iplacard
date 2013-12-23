<?php $this->load->view('header');?>

<div class="page-header">
	<h1>两步验证管理</h1>
</div>

<div class="row">
	<div class="col-md-3">
		<?php $this->load->view('account/manage/sidebar', array('now' => 'twostep'));?>
	</div>
	
	<div class="col-md-9">
		<?php echo form_open('account/settings/twostep/disable', array('class' => 'well form-horizontal'));?>
			<?php echo form_fieldset('两步验证已经启用'); ?>
				<p>两步验证使用您手机中的 Google 身份验证器应用生成验证码，可为您的 iPlacard 帐户增加额外的安全保障。</p>
				<p>您已经启用两步验证保护您的 iPlacard 帐户。您可以在本页面中停用两步验证功能，我们强烈不建议停用此功能。如需停用两步验证，请输入您的登录密码并点击确认。</p>
				
				<div class="form-group <?php if(form_has_error('password')) echo 'has-error';?>">
					<?php echo form_label('密码', 'password', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-4">
						<?php echo form_password(array(
							'name' => 'password',
							'id' => 'password',
							'class' => 'form-control',
						));
						echo form_error('password');?>
					</div>
				</div>
				
				<div class="form-group <?php if(form_has_error('confirm')) echo 'has-error';?>">
					<?php echo form_label('', 'confirm', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-4">
						<?php echo form_checkbox(array(
							'name' => 'confirm',
							'id' => 'confirm',
							'value' => true,
							'checked' => false,
						));?> 我确认停用两步验证功能
						<?php echo form_error('confirm');?>
					</div>
				</div>
			<?php echo form_fieldset_close();?>

			<div class="form-group">
				<div class="col-lg-10 col-lg-offset-2">
					<?php echo form_button(array(
						'name' => 'submit',
						'content' => '停用两步验证',
						'type' => 'submit',
						'class' => 'btn btn-danger',
						'onclick' => 'loader(this);'
					));?>
				</div>
			</div>
		<?php echo form_close();?>
	</div>
</div>

<?php $this->load->view('footer');?>