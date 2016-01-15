<p>此代表由于以下原因退会：</p>

<blockquote><p><?php echo $quit_reason;?></p></blockquote>

<p>管理员可以取消代表的退会状态。</p>

<p><a class="btn btn-primary" href="#" data-toggle="modal" data-target="#recover"><?php echo icon('undo');?>取消退会状态</a></p>

<?php echo form_open("delegate/operation/unquit/$uid", array(
	'class' => 'modal fade form-horizontal',
	'id' => 'recover',
	'tabindex' => '-1',
	'role' => 'dialog',
	'aria-labelledby' => 'unquit_label',
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
				<h4 class="modal-title" id="unquit_label">确认取消退会</h4>
			</div>
			<div class="modal-body">
				<p>将会操作取消<?php echo icon('user', false).$delegate['name'];?>代表的退会状态。取消退会后，代表的申请状态将会还原为可恢复的最高状态，例如退会前代表正在面试，由于退会时关闭了现有面试，因此取消后申请状态将会变为等待分配新的面试。一封邮件将会发送给代表通知申请继续。</p>
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
					'content' => '确认取消',
					'type' => 'submit',
					'class' => 'btn btn-primary',
					'onclick' => 'loader(this);'
				)); ?>
			</div>
		</div>
	</div>
<?php echo form_close(); ?>