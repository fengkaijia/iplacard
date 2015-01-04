<?php $this->load->view('header');?>

<div class="page-header">
	<h1><?php echo $action == 'add' ? '添加委员会' : icon('sitemap').$committee['name'];?></h1>
</div>

<div class="row">
	<div class="col-md-3">
		<div class="panel panel-default">
			<div class="panel-heading">帮助</div>
			<div class="panel-body"><?php if($action == 'edit') { ?>
				<p style="margin-bottom: 0;">您可以通过此页面修改委员会信息。</p>
				<?php } else { ?>
				<p style="margin-bottom: 0;">您可以通过此页面添加委员会。</p>
			<?php } ?></div>
		</div>
	</div>
	
	<div class="col-md-9">
		<?php echo form_open($action == 'add' ? 'committee/edit' : "committee/edit/{$committee['id']}", array('class' => 'well form-horizontal'));?>
			<?php echo form_fieldset('委员会信息'); ?>
				<div class="form-group <?php if(form_has_error('name')) echo 'has-error';?>">
					<?php echo form_label('委员会名称', 'name', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-4">
						<?php echo form_input(array(
							'name' => 'name',
							'id' => 'name',
							'class' => 'form-control',
							'value' => set_value('name', $action == 'add' ? '' : $committee['name']),
							'required' => NULL,
						));
						if(form_has_error('name'))
							echo form_error('name');
						else { ?><div class="help-block">建议使用委员会的全称。</div><?php } ?>
					</div>
				</div>
				
				<div class="form-group <?php if(form_has_error('abbr')) echo 'has-error';?>">
					<?php echo form_label('委员会缩写', 'abbr', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-4">
						<?php echo form_input(array(
							'name' => 'abbr',
							'id' => 'abbr',
							'class' => 'form-control',
							'value' => set_value('abbr', $action == 'add' ? '' : $committee['abbr']),
							'required' => NULL,
						));
						if(form_has_error('abbr'))
							echo form_error('abbr');
						else { ?><div class="help-block">英文简写请尽量使用大写字母。</div><?php } ?>
					</div>
				</div>
		
				<div class="form-group <?php if(form_has_error('description')) echo 'has-error';?>">
					<?php echo form_label('委员会介绍', 'description', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-6">
						<?php echo form_textarea(array(
							'name' => 'description',
							'id' => 'description',
							'class' => 'form-control',
							'rows' => 4,
							'value' => set_value('description', $action == 'add' ? '' : $committee['description']),
							'required' => NULL,
						));
						if(form_has_error('description'))
							echo form_error('description');
						else { ?><div class="help-block">委员会详细介绍将会公开显示，可为空。</div><?php } ?>
					</div>
				</div>
			<?php echo form_fieldset_close();?>
			
			<br />
			
			<?php echo form_fieldset('委员会设置'); ?>
				<p>设置委员会类型数据，这些数据将会影响席位设置功能和与 iPlacard 对接的程序。</p>
				
				<div class="form-group <?php if(form_has_error('type')) echo 'has-error';?>">
					<?php echo form_label('委员会类型', 'type', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-4">
						<?php
						echo form_dropdown('type', empty($types) ? array('' => '委员会类型为空') : $types, set_value('type', $action == 'add' ? '' : $committee['type']), 'class="form-control" id="committee"');
						if(form_has_error('type'))
							echo form_error('type');
						else { ?><div class="help-block">委员会类型对应接口程序生效。</div><?php } ?>
					</div>
				</div>
				
				<div class="form-group <?php if(form_has_error('seat_width')) echo 'has-error';?>">
					<?php echo form_label('席位宽度', 'seat_width', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-4">
						<?php
						echo form_dropdown('seat_width', empty($widths) ? array('' => '不设置席位宽度') : $widths, set_value('seat_width', $action == 'add' ? '' : $committee['seat_width']), 'class="form-control" id="committee"');
						if(form_has_error('seat_width'))
							echo form_error('seat_width');
						else { ?><div class="help-block">席位宽度对应席位设置功能生效。</div><?php } ?>
					</div>
				</div>
			<?php echo form_fieldset_close();?>
		
			<div class="form-group">
				<div class="col-lg-10 col-lg-offset-2">
					<?php echo form_button(array(
						'name' => 'submit',
						'content' => $action == 'add' ? '添加委员会' : '编辑委员会',
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
							'content' => '删除委员会',
							'type' => 'button',
							'class' => 'btn btn-danger',
							'data-toggle' => 'modal',
							'data-target' => '#delete_committee',
						));
					} ?>
				</div>
			</div>
		<?php echo form_close();
		
		if($action == 'edit')
		{
			echo form_open("committee/delete/{$committee['id']}", array(
				'class' => 'modal fade form-horizontal',
				'id' => 'delete_committee',
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
						<h4 class="modal-title" id="delete_label">删除委员会</h4>
					</div>
					<div class="modal-body">
						<p>您将删除<?php echo icon('sitemap', false).$committee['name'];?>，同时将会重置所有该委员会的主席的指派信息。请输入您的登录密码并点击确认删除按钮继续操作。</p>

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