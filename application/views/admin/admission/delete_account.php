<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 删除帐号功能视图
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
 * @package iPlacard
 * @since 2.0
 */
?><p>如果此代表申请为恶意报名等其他情况，您可以操作操作删除此代表的信息。</p>

<p><a class="btn btn-danger" href="#" data-toggle="modal" data-target="#delete"><?php echo icon('trash');?>删除代表帐户</a></p>

<?php echo form_open("delegate/operation/delete_account/$uid", array(
	'class' => 'modal fade form-horizontal',
	'id' => 'delete',
	'tabindex' => '-1',
	'role' => 'dialog',
	'aria-labelledby' => 'delete_label',
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
				<h4 class="modal-title" id="delete_label">确认永久删除代表帐户</h4>
			</div>
			<div class="modal-body">
				<p><span class="label label-danger">注意</span> 这项操作不可逆，请确认无误再执行造作。</p>
				<p>将会操作永久删除<?php echo icon('user', false).$delegate['name'];?>代表的用户帐户，他的席位将被立即释放，其他与代表有关的任何信息将被从 iPlacard 中移除。一封邮件将会发送给代表通知帐户被删除。</p>
				
				<div class="form-group <?php if(form_has_error('reason')) echo 'has-error';?>">
					<?php echo form_label('删除原因', 'reason', array('class' => 'col-lg-3 control-label'));?>
					<div class="col-lg-9">
						<?php echo form_textarea(array(
							'name' => 'reason',
							'id' => 'reason',
							'class' => 'form-control',
							'rows' => 2,
							'value' => set_value('reason'),
							'placeholder' => '删除原因',
							'required' => NULL
						));
						if(form_has_error('reason'))
							echo form_error('reason');
						else { ?><div class="help-block">帐户删除原因，将不会显示给代表。</div><?php } ?>
					</div>
				</div>
				
				<p>请输入您的登录密码确认删除操作。</p>
				
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
					'content' => '确认永久删除',
					'type' => 'submit',
					'class' => 'btn btn-danger',
					'onclick' => 'loader(this);'
				)); ?>
			</div>
		</div>
	</div>
<?php echo form_close(); ?>