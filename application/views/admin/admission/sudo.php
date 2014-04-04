<p>开启 SUDO 模式以此代表视角登录 iPlacard。</p>

<a class="btn btn-primary" href="#" data-toggle="modal" data-target="#refuse_application"><?php echo icon('user-md');?>SUDO 模式</a>

<?php echo form_open("account/sudo/$uid", array(
	'class' => 'modal fade form-horizontal',
	'id' => 'refuse_application',
	'tabindex' => '-1',
	'role' => 'dialog',
	'aria-labelledby' => 'refuse_label',
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
				<h4 class="modal-title" id="refuse_label">启用 SUDO 模式</h4>
			</div>
			<div class="modal-body">
				<p>您将启用 SUDO 模式。启用 SUDO 模式后，您将会切换到<?php echo icon('user', false).$delegate['name'];?>代表的视角，这将允许您完成部分后台无法操作的功能，例如协助代表修改邮箱等个人信息。</p>
				<p>在 SUDO 模式下，您可以点击菜单栏中“退出 SUDO”按钮切换回管理员界面。</p>
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
					'content' => '确认切换',
					'type' => 'submit',
					'class' => 'btn btn-primary',
					'onclick' => 'loader(this);'
				)); ?>
			</div>
		</div>
	</div>
<?php echo form_close(); ?>