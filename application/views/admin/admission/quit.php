<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 退会功能视图
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
 * @package iPlacard
 * @since 2.0
 */
?><p>如果代表申请退会，您可以操作退会并释放代表席位。</p>

<p><a class="btn btn-warning" href="#" data-toggle="modal" data-target="#quit"><?php echo icon('recycle');?>代表退会</a></p>

<?php echo form_open("delegate/operation/quit/$uid", array(
	'class' => 'modal fade form-horizontal',
	'id' => 'quit',
	'tabindex' => '-1',
	'role' => 'dialog',
	'aria-labelledby' => 'quit_label',
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
				<h4 class="modal-title" id="quit_label">确认代表退会</h4>
			</div>
			<div class="modal-body">
				<p><span class="label label-warning">注意</span> 这项操作不可逆，请确认无误再执行造作。</p>
				<p>将会操作<?php echo icon('user', false).$delegate['name'];?>代表退会，他的席位将被立即释放，但同时他的面试记录等信息将会被保留。退会后 <?php echo option('delegate_quit_lock', 7);?> 天内代表将仍可登录 iPlacard 查看申请状态，之后他将会被限制登录。</p>
				
				<div class="form-group <?php if(form_has_error('reason')) echo 'has-error';?>">
					<?php echo form_label('退会原因', 'reason', array('class' => 'col-lg-3 control-label'));?>
					<div class="col-lg-9">
						<?php echo form_textarea(array(
							'name' => 'reason',
							'id' => 'reason',
							'class' => 'form-control',
							'rows' => 2,
							'value' => set_value('reason'),
							'placeholder' => '退会原因'
						));
						if(form_has_error('reason'))
							echo form_error('reason');
						else { ?><div class="help-block">代表退会原因，将不会显示给代表。</div><?php } ?>
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
					'content' => '确认退会',
					'type' => 'submit',
					'class' => 'btn btn-warning',
					'onclick' => 'loader(this);'
				)); ?>
			</div>
		</div>
	</div>
<?php echo form_close(); ?>