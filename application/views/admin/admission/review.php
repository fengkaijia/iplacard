<h3 id="admission_operation">审核申请材料</h3>

<p>审核是否通过此代表的申请，将会通知代表材料审核结果。</p>

<p>此代表的申请类型为<strong><?php echo $delegate['application_type_text'];?></strong>，根据审核政策你可以选择免试通过此代表。</p>

<a class="btn btn-success" href="<?php echo base_url("delegate/operation/pass_application/$uid");?>"><?php echo icon('check');?>通过申请</a> 
<a class="btn btn-danger" href="#" data-toggle="modal" data-target="#refuse_application"><?php echo icon('times');?>拒绝申请</a>

<?php echo form_open("delegate/operation/refuse_application/$uid", array(
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
				<h4 class="modal-title" id="refuse_label">拒绝申请</h4>
			</div>
			<div class="modal-body">
				<p>将会拒绝<?php echo icon('user', false).$delegate['name'];?>的参会申请，你可以为拒绝此参会申请提供详细的原因。</p>

				<div class="form-group <?php if(form_has_error('reason')) echo 'has-error';?>">
					<?php echo form_label('拒绝原因', 'reason', array('class' => 'col-lg-3 control-label'));?>
					<div class="col-lg-9">
						<?php echo form_textarea(array(
							'name' => 'reason',
							'id' => 'reason',
							'class' => 'form-control',
							'rows' => 4,
							'value' => set_value('reason'),
							'placeholder' => '拒绝申请原因'
						));
						if(form_has_error('reason'))
							echo form_error('reason');
						else { ?><div class="help-block">拒绝申请原因将会通过电子邮件自动发送给代表。</div><?php } ?>
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
					'content' => '确定拒绝申请',
					'type' => 'submit',
					'class' => 'btn btn-danger',
					'onclick' => 'loader(this);'
				)); ?>
			</div>
		</div>
	</div>
<?php echo form_close(); ?>

<hr />