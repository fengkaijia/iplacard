<p>如果代表要求，您可以操作更换代表的申请类型。</p>

<p><a class="btn btn-warning" href="#" data-toggle="modal" data-target="#change_type"><?php echo icon('exchange');?>更换申请类型</a></p>

<?php echo form_open("delegate/operation/change_type/$uid", array(
	'class' => 'modal fade form-horizontal',
	'id' => 'change_type',
	'tabindex' => '-1',
	'role' => 'dialog',
	'aria-labelledby' => 'change_label',
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
				<h4 class="modal-title" id="change_label">更换申请类型</h4>
			</div>
			<div class="modal-body">
				<p>将会更换申请类型<?php echo icon('user', false).$delegate['name'];?>代表的申请类型，当前代表的申请类型是<strong><?php echo $application_type_text;?></strong>。申请类型更换后将需要重新完成申请流程，代表的申请状态将被重置为<code>申请已导入</code>。如有必要，代表现有的分配资源，例如席位，将在申请类型更换后释放；代表正在进行的面试将被取消。</p>
				
				<div class="form-group <?php if(form_has_error('reason')) echo 'has-error';?>">
					<?php echo form_label('更换原因', 'reason', array('class' => 'col-lg-3 control-label'));?>
					<div class="col-lg-9">
						<?php echo form_textarea(array(
							'name' => 'reason',
							'id' => 'reason',
							'class' => 'form-control',
							'rows' => 2,
							'value' => set_value('reason'),
							'placeholder' => '更换申请类型原因'
						));
						if(form_has_error('reason'))
							echo form_error('reason');
						else { ?><div class="help-block">代表更换申请类型原因，将会显示给代表。</div><?php } ?>
					</div>
				</div>
				
				<div class="form-group <?php if(form_has_error('type')) echo 'has-error';?>">
					<?php echo form_label('更换类型', 'type', array('class' => 'col-lg-3 control-label'));?>
					<div class="col-lg-6">
						<?php
						echo form_dropdown('type', empty($types) ? array('' => '可更换的申请类型为空') : array('' => '请选择申请类型') + $types, set_value('type'), 'class="form-control" id="type"');
						if(form_has_error('type'))
							echo form_error('type');
						else { ?><div class="help-block">更换后的申请类型。</div><?php } ?>
					</div>
				</div>
				
				<div class="form-group <?php if(form_has_error('cancel_invoice')) echo 'has-error';?>">
					<?php echo form_label('关闭账单', 'cancel_invoice', array('class' => 'col-lg-3 control-label'));?>
					<div class="col-lg-9">
						<div class="checkbox">
							<label>
								<?php echo form_checkbox(array(
									'name' => 'cancel_invoice',
									'id' => 'cancel_invoice',
									'value' => true,
									'checked' => false,
								)); ?> 同时关闭账单
							</label>
						</div>
						<?php if(form_has_error('cancel_invoice'))
							echo form_error('cancel_invoice');
						else { ?><div class="help-block">选中此项后将会同时关闭现有未支付的账单。</div><?php } ?>
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
					'content' => '确认更换类型',
					'type' => 'submit',
					'class' => 'btn btn-warning',
					'onclick' => 'loader(this);'
				)); ?>
			</div>
		</div>
	</div>
<?php echo form_close(); ?>