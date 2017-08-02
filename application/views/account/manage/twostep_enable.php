<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 启用两步验证视图
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
 * @package iPlacard
 * @since 2.0
 */

$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/jquery.numeric.js' : 'static/js/jquery.numeric.min.js').'"></script>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/jquery.qrcode.js' : 'static/js/jquery.qrcode.min.js').'"></script>');
$this->load->view('header');?>

<div class="page-header">
	<h1>两步验证管理</h1>
</div>

<div class="row">
	<div class="col-md-3">
		<?php $this->load->view('account/manage/sidebar', array('now' => 'twostep'));?>
	</div>
	
	<div class="col-md-9">
		<?php echo form_open('account/settings/twostep/enable', array('class' => 'well form-horizontal'), array('secret' => $secret));?>
			<?php echo form_fieldset('设置两步验证'); ?>
				<p>您将要设置启用两步验证功能，请按照以下步骤执行操作。如果您尚未安装 Google 身份验证器应用，请访问由 Google 提供的<?php echo anchor('https://support.google.com/accounts/answer/1066447?hl=zh-Hans', '帮助页面');?>了解如何安装该应用，同时，您可以选择其他替代应用，例如由 Red Hat 提供的 <?php echo anchor('https://fedorahosted.org/freeotp/', 'FreeOTP Authenticator');?>。</p>
				
				<div>
					<div style="padding-left: 1.5px; padding-top: 3px; float: left; width: 100px;">
						<div id="qrcode"></div>
					</div>
					<div style="margin-left: 100px;">
						<ol>
							<li>打开 Google 身份验证器。</li>
							<li>在 Google 身份验证器中触摸 <span class="label label-primary">菜单</span> 。</li>
							<li>选择 <span class="label label-primary">设置帐户</span> 。</li>
							<li>在手动添加帐户项中选择 <span class="label label-primary">扫描条形码</span> 。</li>
							<li>使用二维码应用扫描左侧的条形码。</li>
						</ol>
					</div>
				</div>
				
				<p>扫描条形码后，请输入由身份验证器应用生成的六位数验证码。</p>
				
				<div class="form-group <?php if(form_has_error('code')) echo 'has-error';?>">
					<?php echo form_label('验证码', 'code', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-4">
						<?php echo form_input(array(
							'name' => 'code',
							'id' => 'code',
							'class' => 'form-control',
						));
						echo form_error('code');?>
					</div>
				</div>
			<?php echo form_fieldset_close();?>

			<div class="form-group">
				<div class="col-lg-10 col-lg-offset-2">
					<?php echo form_button(array(
						'name' => 'submit',
						'content' => '启用两步验证',
						'type' => 'submit',
						'class' => 'btn btn-success',
						'onclick' => 'loader(this);'
					));
					echo form_button(array(
						'content' => '取消',
						'type' => 'button',
						'class' => 'btn btn-link',
						'onclick' => onclick_redirect('account/settings/twostep')
					)); ?>
					
				</div>
			</div>
		<?php echo form_close();?>
	</div>
</div>

<?php
$qrcode_js = <<<EOT
$(document).ready(function() {
	$('#qrcode').qrcode( {
		size: 100,
		text: "{$qr}"
	} );
} );
EOT;
$this->ui->js('footer', $qrcode_js);
$this->ui->js('footer', '$("input[name=\'code\']").numeric()');

$this->load->view('footer');?>