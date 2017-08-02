<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 停用帐号功能视图
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
 * @package iPlacard
 * @since 2.0
 */
?><p>必要情况下您可以暂时停用此代表的用户帐户。</p>

<p><a class="btn btn-warning" href="#" data-toggle="modal" data-target="#disable"><?php echo icon('ban');?>停用代表帐户</a></p>

<?php echo form_open("delegate/operation/disable_account/$uid", array(
	'class' => 'modal fade form-horizontal',
	'id' => 'disable',
	'tabindex' => '-1',
	'role' => 'dialog',
	'aria-labelledby' => 'disable_label',
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
				<h4 class="modal-title" id="disable_label">确认停用代表帐户</h4>
			</div>
			<div class="modal-body">
				<p><span class="label label-warning">注意</span> 这项操作将会影响代表使用 iPlacard。</p>
				<p>停用帐户后代表将无法登录到 iPlacard，一封邮件将会发送给代表通知帐户被停用。帐户停用将不会影响后台对此代表的操作。</p>
				
				<div class="form-group <?php if(form_has_error('reason')) echo 'has-error';?>">
					<?php echo form_label('停用原因', 'reason', array('class' => 'col-lg-3 control-label'));?>
					<div class="col-lg-9">
						<?php echo form_textarea(array(
							'name' => 'reason',
							'id' => 'reason',
							'class' => 'form-control',
							'rows' => 2,
							'value' => set_value('reason'),
							'placeholder' => '停用原因'
						));
						if(form_has_error('reason'))
							echo form_error('reason');
						else { ?><div class="help-block">帐户停用原因，将会通过邮件告知代表。</div><?php } ?>
					</div>
				</div>
				
				<p>请输入您的登录密码确认停用操作。</p>
				
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
					'content' => '取消',
					'type' => 'button',
					'class' => 'btn btn-link',
					'data-dismiss' => 'modal'
				));
				echo form_button(array(
					'name' => 'submit',
					'content' => '确认停用帐户',
					'type' => 'submit',
					'class' => 'btn btn-warning',
					'onclick' => 'loader(this);'
				)); ?>
			</div>
		</div>
	</div>
<?php echo form_close(); ?>