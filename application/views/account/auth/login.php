<?php
if($this->session->userdata('dismiss_browser_notice') != true)
{
	$this->ui->html('header', '<!--[if lt IE 8]>
		<script language="javascript" type="text/javascript">
			window.location.href="'.base_url('help/browser').'";
		</script>
	<![endif]-->');
}
$this->load->view('header');?>

<?php echo form_open('account/login', array('class' => 'form-auth', 'id' => 'auth_internal'));?>
	<h2 class="form-auth-heading">登录</h2>
	<?php echo validation_errors();
	echo form_input(array(
		'name' => 'email',
		'value' => set_value('email'),
		'class' => 'form-control',
		'placeholder' => '电子邮箱地址 / 手机',
		'required' => NULL,
		'autofocus' => NULL
	));
	echo form_password(array(
		'name' => 'password',
		'class' => 'form-control',
		'placeholder' => '密码',
		'required' => NULL
	));
	?>
	<label for="remember" class="checkbox">
		<?php echo form_checkbox('remember', true);?> 记住登录
	</label>
	<?php echo form_button(array(
		'name' => 'login',
		'content' => '登录',
		'type' => 'submit',
		'class' => 'btn btn-primary',
		'onclick' => 'loader(this);'
	));
	echo form_button(array(
		'content' => '忘记密码？',
		'type' => 'button',
		'class' => 'btn btn-link',
		'onclick' => onclick_redirect('account/recover')
	)); ?>
	
	<?php if($imap) { ?><hr />
	<a style="cursor: pointer;" onclick="$('#auth_imap').show(); $('#auth_internal').hide();">使用 <?php echo $imap_domain;?> 邮箱帐号登录</a><?php } ?>
	
<?php echo form_close();?>
	
<?php if($imap) {
	echo form_open('account/login/imap', array('class' => 'form-auth', 'id' => 'auth_imap'));?>
	<h2 class="form-auth-heading">邮箱帐号登录</h2>
	<?php echo validation_errors(); ?>
	
	<p>需要拥有 <?php echo $imap_domain;?> 邮箱帐号才可使用此方式登录。</p>
	
	<div class="input-group" style="margin-bottom: 14px;">
		<?php echo form_input(array(
			'name' => 'email',
			'value' => set_value('email'),
			'class' => 'form-control',
			'style' => 'margin-bottom: 0px;',
			'placeholder' => '邮箱帐户',
			'required' => NULL,
			'autofocus' => NULL
		)); ?>
		<span class="input-group-addon">@<?php echo $imap_domain;?></span>
	</div>
	
	<?php echo form_password(array(
		'name' => 'password',
		'class' => 'form-control',
		'placeholder' => '密码',
		'required' => NULL
	));
	?>
	<label for="remember" class="checkbox">
		<?php echo form_checkbox('remember', true);?> 记住登录
	</label>
	<?php echo form_button(array(
		'name' => 'login',
		'content' => '登录',
		'type' => 'submit',
		'class' => 'btn btn-primary',
		'onclick' => 'loader(this);'
	)); ?>
	
	<?php if($imap) { ?><hr />
	<a style="cursor: pointer;" onclick="$('#auth_internal').show(); $('#auth_imap').hide();">使用 iPlacard 帐号登录</a><?php } ?>
	
<?php echo form_close(); } ?>

<?php
$imap_hide = ($auth == 'imap') ? 'internal' : 'imap';
$imap_js = <<<EOT
$(document).ready(function() {
	$('#auth_{$imap_hide}').hide();
} );
EOT;
if($imap)
	$this->ui->js('footer', $imap_js);

$this->ui->js('footer', 'form_auth_center();');

$this->load->view('footer'); ?>