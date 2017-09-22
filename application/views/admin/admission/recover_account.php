<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 恢复帐号功能视图
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
 * @package iPlacard
 * @since 2.0
 */
?><script>
	$('#clock_delete').countdown(<?php echo $delete_time;?> * 1000, function(event) {
		$(this).html(event.strftime('%-D 天 %H:%M:%S'));
	});
</script>

<p>此帐户由于以下原因将在 <span id="clock_delete"><?php echo nicetime($delete_time);?></span> 秒内删除：</p>

<blockquote><p><?php echo $delete_reason;?></p></blockquote>

<p>在此之前可以恢复代表帐户。</p>

<p><a class="btn btn-success" href="#" data-toggle="modal" data-target="#recover"><?php echo icon('undo');?>恢复代表帐户</a></p>

<?php echo form_open("delegate/operation/recover_account/$uid", array(
	'class' => 'modal fade form-horizontal',
	'id' => 'recover',
	'tabindex' => '-1',
	'role' => 'dialog',
	'aria-labelledby' => 'recover_label',
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
				<h4 class="modal-title" id="recover_label">确认恢复代表帐户</h4>
			</div>
			<div class="modal-body">
				<p>将会操作恢复<?php echo icon('user', false).$delegate['name'];?>代表的用户帐户。一封邮件将会发送给代表通知帐户被恢复。</p>
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
					'content' => '确认恢复',
					'type' => 'submit',
					'class' => 'btn btn-success',
					'onclick' => 'loader(this);'
				)); ?>
			</div>
		</div>
	</div>
<?php echo form_close(); ?>