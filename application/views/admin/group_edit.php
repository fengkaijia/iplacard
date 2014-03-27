<?php $this->load->view('header');?>

<div class="page-header">
	<h1><?php echo $action == 'add' ? '添加代表团' : icon('group').$group['name'];?></h1>
</div>

<div class="row">
	<div class="col-md-3">
		<div class="panel panel-default">
			<div class="panel-heading">帮助</div>
			<div class="panel-body"><?php if($action == 'edit') { ?>
				<p>您可以通过此页面修改代表团的名称和支付方式。</p>
				<p style="margin-bottom: 0;">根据设定的团队支付方式，代表帐单生成模式将有所不同。团队领队可在对应的代表资料页面中设置。</p>
				<?php } else { ?>
				<p>您可以通过此页面添加代表团。</p>
				<p style="margin-bottom: 0;">根据设定的团队支付方式，代表帐单生成模式将有所不同。团队领队可在对应的代表资料页面中设置。</p>
			<?php } ?></div>
		</div>
	</div>
	
	<div class="col-md-9">
		<?php echo form_open($action == 'add' ? 'group/edit' : "group/edit/{$group['id']}", array('class' => 'well form-horizontal'), array('change_name' => false, 'change_email' => false, 'change_phone' => false));?>
			<?php echo form_fieldset('团队信息'); ?>
				<div class="form-group <?php if(form_has_error('name')) echo 'has-error';?>">
					<?php echo form_label('团队名称', 'name', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-4">
						<?php echo form_input(array(
							'name' => 'name',
							'id' => 'name',
							'class' => 'form-control',
							'value' => set_value('name', $action == 'add' ? '' : $group['name']),
							'required' => NULL,
						));
						if(form_has_error('name'))
							echo form_error('name');
						else { ?><div class="help-block">建议使用全称。</div><?php } ?>
					</div>
				</div>
		
				<div class="form-group <?php if(form_has_error('group_payment')) echo 'has-error';?>">
					<?php echo form_label('团队支付方式', 'group_payment', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-4">
						<?php
						echo form_dropdown('group_payment', array(true => '团队支付', false => '代表个人支付'), set_value('group_payment', $action == 'add' ? true : $group['group_payment']), 'class="form-control" id="committee"');
						if(form_has_error('group_payment'))
							echo form_error('group_payment');
						else { ?><div class="help-block">选定是否由团队支付此团队所有代表的帐单。</div><?php } ?>
					</div>
				</div>
			<?php echo form_fieldset_close();?>
		
			<div class="form-group">
				<div class="col-lg-10 col-lg-offset-2">
					<?php echo form_button(array(
						'name' => 'submit',
						'content' => $action == 'add' ? '添加团队' : '修改团队',
						'type' => 'submit',
						'class' => 'btn btn-primary',
						'onclick' => 'loader(this);'
					));
					echo ' ';
					if($action == 'edit')
					{
						echo ' ';
						echo form_button(array(
							'name' => 'delete',
							'content' => '删除团队',
							'type' => 'button',
							'class' => 'btn btn-danger',
							'data-toggle' => 'modal',
							'data-target' => '#delete_group',
						));
					} ?>
				</div>
			</div>
		<?php echo form_close();
		
		if($action == 'edit')
		{
			echo form_open("group/delete/{$group['id']}", array(
				'class' => 'modal fade form-horizontal',
				'id' => 'delete_group',
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
						<h4 class="modal-title" id="delete_label">删除代表团</h4>
					</div>
					<div class="modal-body">
						<p>您将删除<?php echo icon('group', false).$group['name'];?>代表团。该代表团中的所有代表将会转换为个人代表。请输入您的登录密码并点击确认更改按钮继续操作。</p>

						<div class="form-group <?php if(form_has_error('admin_password')) echo 'has-error';?>">
							<?php echo form_label('登录密码', 'admin_password', array('class' => 'col-lg-3 control-label'));?>
							<div class="col-lg-5">
								<?php echo form_password(array(
									'name' => 'admin_password',
									'id' => 'admin_password',
									'class' => 'form-control',
									'required' => NULL
								));
								echo form_error('admin_password');?>
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
							'content' => '确认删除',
							'type' => 'submit',
							'class' => 'btn btn-danger',
							'onclick' => 'loader(this);'
						)); ?>
					</div>
				</div>
			</div>
		<?php echo form_close(); } ?>
	</div>
</div>

<?php $this->load->view('footer');?>