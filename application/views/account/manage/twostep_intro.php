<?php $this->load->view('header');?>

<div class="page-header">
	<h1>两步验证管理</h1>
</div>

<div class="row">
	<div class="col-md-3">
		<?php $this->load->view('account/manage/sidebar', array('now' => 'twostep'));?>
	</div>
	
	<div class="col-md-9">
		<?php echo form_open('account/settings/twostep/enable', array('class' => 'well form-horizontal'));?>
			<?php echo form_fieldset('两步验证未启用'); ?>
				<p>两步验证使用您手机中的 Google 身份验证器应用生成验证码，可为您的 iPlacard 帐户增加额外的安全保障。</p>
				<p>启用两步验证后，您登录 iPlacard 时需要同时输入您的 iPlacard 帐户密码和由 Google 身份验证器应用生成的六位数验证码。此验证码有效期仅 30 秒，过期后将会重新生成，因此黑客无法知道您的验证码。即使别有用心的人获得了您的密码，也还需要拿到您的手机才能进入您的 iPlacard 帐户，这将可以显著提高您 iPlacard 帐户的安全性。</p>
				<p>您尚未设置两步验证，为了保护您 iPlacard 帐户的安全，我们强烈建议您启用两步验证功能。</p>
			<?php echo form_fieldset_close();?>

			<div class="form-group">
				<div class="col-lg-12">
					<?php echo form_button(array(
						'name' => 'submit',
						'content' => '启用两步验证',
						'type' => 'submit',
						'class' => 'btn btn-success'
					));?>
				</div>
			</div>
		<?php echo form_close();?>
	</div>
</div>

<?php $this->load->view('footer');?>