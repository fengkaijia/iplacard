<?php $this->load->view('header');?>

<div class="page-header">
	<h1>帐户管理</h1>
</div>

<div class="row">
	<div class="col-md-3">
		<?php $this->load->view('account/manage/sidebar', array('now' => 'detail'));?>
	</div>
	
	<div class="col-md-9">
		<?php echo form_open_multipart('account/settings/home', array('class' => 'well form-horizontal'), array('change_email' => false, 'change_phone' => false, 'change_avatar' => false));?>
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
					</div>
				</div>
				
				<div class="form-group">
					<?php echo form_label('帐户类型', 'type', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-10">
						<span style="padding-top: 11px; display: inline-block;"><strong><?php
							echo icon('user');
							echo $data['type'] == 'admin' ? '管理用户' : '代表';
						?></strong></span>
						<div class="help-block"><?php echo $data['type'] == 'admin' ? '拥有管理权限的组委会成员帐户。' : '参会代表、观察员、志愿者帐户。';?></div>
					</div>
				</div>
		
				<div class="form-group <?php if(form_has_error('email')) echo 'has-error';?>">
					<?php echo form_label('电子邮箱', 'email', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-4">
						<div class="input-group">
							<?php echo form_input(array(
								'name' => 'email',
								'id' => 'email',
								'value' => $email_changed ? $data['email'] : set_value('email', $data['email']),
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
			
			<br />
			
			<?php echo form_fieldset('个人头像', array('id' => 'avatar')); ?>
				<p>您可以设置您的个人头像，它将显示在 iPlacard 界面和与 iPlacard 关联的应用中，如果您未设置个人头像，我们将使用 Gravatar 代替显示头像。</p>
				
				<div class="form-group">
					<?php echo form_label('头像类型', 'avatar_type', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-10">
						<span style="padding-top: 11px; display: inline-block;"><strong><?php
							echo icon('picture-o');
							echo $avatar ? '本地头像' : 'Gravatar';
						?></strong></span>
						<div class="help-block"><?php echo $avatar ? '您已经成功上传头像。' : '您尚未上传头像，当前显示的是您的 Gravatar。';?></div>
					</div>
				</div>
				
				<div class="form-group">
					<?php echo form_label('当前头像', 'avatar_current', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-10">
						<a class="thumbnail" style="width: 170px; height: 170px;">
							<?php echo avatar($data['id'], 160, 'img');?>
						</a>
					</div>
				</div>
				
				<div class="form-group <?php if(form_has_error('avatar_file')) echo 'has-error';?>">
					<?php echo form_label('上传新头像', 'avatar_file', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-4">
						<div class="input-group">
							<?php echo form_upload(array(
								'name' => 'avatar_file',
								'id' => 'avatar_file',
								'class' => 'form-control',
								'onchange' => "$('button[name=avatar_upload]').removeAttr('disabled');"
							));?>
							<span class="input-group-btn">
								<?php echo form_button(array(
									'name' => 'avatar_upload',
									'content' => '上传',
									'type' => 'submit',
									'class' => 'btn btn-primary',
									'disabled' => NULL,
									'onclick' => 'upload_avatar(); loader(this);'
								));?>
							</span>
						</div>
						<?php if(form_has_error('avatar_file'))
							echo form_error('avatar_file');
						else { ?><div class="help-block">上传图片文件大小应不超过 <?php echo $avatar_max_size;?>。</div><?php } ?>
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
							<p><?php
							if(is_sudo())
								echo '您将以 SUDO 授权更改代表的个人信息，请输入您管理员帐号的登录密码并点击确认更改按钮继续操作。';
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
								'content' => '确认更改',
								'type' => 'submit',
								'class' => 'btn btn-primary',
								'onclick' => "loader(this); $('input[name=avatar_file]').val('');" //不上传头像
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

$avatar_url = base_url('account/settings/avatar/upload');
$switch_js = <<<EOT
function upload_avatar()
{
	$('input[name=change_avatar]').val(true);
	$('button[name=confirm]').addClass('disabled');
	$('input[name=password]').removeAttr('required');
	$('form').get(0).setAttribute('action', '{$avatar_url}');
}
EOT;
$this->ui->js('footer', $switch_js);
		
$this->load->view('footer');?>