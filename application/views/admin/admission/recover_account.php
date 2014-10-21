<script src="<?php echo static_url(is_dev() ? 'static/js/jquery.countdown.js' : 'static/js/jquery.countdown.min.js');?>"></script>
<script>
	$('#clock_delete').countdown(<?php echo $delete_time;?> * 1000, function(event) {
		$(this).html(event.strftime('%-D 天 %H:%M:%S'));
	});
</script>

<p>此代表帐户数据将在 <span id="clock_delete"><?php echo nicetime($delete_time);?></span> 秒内删除，在此之前可以恢复代表帐户。</p>

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