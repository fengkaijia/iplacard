<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 启用帐号功能视图
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
 * @package iPlacard
 * @since 2.0
 */
?><p>此代表帐户已于<?php echo nicetime($disable_time, true);?>因以下原因停用：</p>

<blockquote><p><?php echo $disable_reason;?></p></blockquote>

<p>您可以重新启用此代表帐户。</p>

<p><a class="btn btn-success" href="#" data-toggle="modal" data-target="#enable"><?php echo icon('check-circle-o');?>启用代表帐户</a></p>

<?php echo form_open("delegate/operation/enable_account/$uid", array(
	'class' => 'modal fade form-horizontal',
	'id' => 'enable',
	'tabindex' => '-1',
	'role' => 'dialog',
	'aria-labelledby' => 'enable_label',
	'aria-hidden' => 'true'
));?><div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<?php echo form_button(array(
					'content' => '&times;',
					'class' => 'close',
					'type' => 'button',
					'data-dismiss' => 'modal',
					'aria-hidden' => 'true'
				));?>
				<h4 class="modal-title" id="enable_label">确认重新启用代表帐户</h4>
			</div>
			<div class="modal-body">
				<p>重新启用代表帐户后代表将可以登录到 iPlacard 系统，代表帐户停用阶段内发生的后台操作也将被保留。一封邮件将会发送给代表通知帐户被重新启用。</p>
			</div>
			<div class="modal-footer">
				<?php echo form_button(array(
					'content' => '取消',
					'type' => 'button',
					'class' => 'btn btn-link',
					'data-dismiss' => 'modal'
				));
				echo form_button(array(
					'name' => 'submit',
					'content' => '确认启用帐户',
					'type' => 'submit',
					'class' => 'btn btn-success',
					'onclick' => 'loader(this);'
				)); ?>
			</div>
		</div>
	</div>
<?php echo form_close(); ?>