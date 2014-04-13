<?php $this->load->view('header');?>

<div class="page-header">
	<h1>帐户管理</h1>
</div>

<div class="row">
	<div class="col-md-3">
		<?php $this->load->view('account/manage/sidebar', array('now' => 'pin'));?>
	</div>
	
	<div class="col-md-9">
		<?php echo form_open('account/settings/pin', array('class' => 'well form-horizontal'));?>
			<?php echo form_fieldset('设置安全码', is_sudo() ? 'disabled' : ''); ?>
				<?php if(is_sudo()) { ?><p class="text-danger"><?php echo icon('info-circle');?>由于系统原因，您无法在 SUDO 模式下修改代表的安全码。</p><?php } ?>
				
				<p>安全码是与第三方硬件（例如会场中使用的 WIFI 等）服务对接时使用的密码。由于硬件服务可能不支持加密传输，安全码将会以明文方式储存在 iPlacard 中，请勿使用您的常用密码作为安全码。</p>
				
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
				
				<div class="form-group <?php if(form_has_error('pin')) echo 'has-error';?>">
					<?php echo form_label('新安全码', 'pin', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-4">
						<?php echo form_password(array(
							'name' => 'pin',
							'id' => 'pin',
							'class' => 'form-control',
						));
						echo form_error('pin');?>
					</div>
				</div>
				
				<div class="form-group <?php if(form_has_error('pin_repeat')) echo 'has-error';?>">
					<?php echo form_label('重复安全码', 'pin_repeat', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-4">
						<?php echo form_password(array(
							'name' => 'pin_repeat',
							'id' => 'pin_repeat',
							'class' => 'form-control',
						));
						echo form_error('pin_repeat');?>
					</div>
				</div>
			<?php echo form_fieldset_close();?>

			<div class="form-group">
				<div class="col-lg-10 col-lg-offset-2">
					<?php echo form_button(array(
						'name' => 'submit',
						'content' => '修改安全码',
						'type' => 'submit',
						'class' => is_sudo() ? 'btn btn-primary disabled' : 'btn btn-primary',
						'onclick' => 'loader(this);'
					));?>
				</div>
			</div>
		<?php echo form_close();?>
	</div>
</div>

<?php $this->load->view('footer');?>