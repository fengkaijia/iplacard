<link href="<?php echo static_url(is_dev() ? 'static/css/bootstrap.datetimepicker.css' : 'static/css/bootstrap.datetimepicker.min.css');?>" rel="stylesheet">
<script src="<?php echo static_url(is_dev() ? 'static/js/bootstrap.datetimepicker.js' : 'static/js/bootstrap.datetimepicker.min.js');?>"></script>
<script src="<?php echo static_url(is_dev() ? 'static/js/locales/bootstrap.datetimepicker.locale.js': 'static/js/locales/bootstrap.datetimepicker.locale.min.js');?>"></script>
<script>
	$('.form_datetime').datetimepicker({
		language:  'zh-CN',
		format: "yyyy-mm-dd hh:ii",
		weekStart: 1,
		todayBtn: 1,
		autoclose: 1,
		todayHighlight: 1,
		startView: 2,
		forceParse: 0,
		showMeridian: 1,
		pickerPosition: "bottom-left"
	});
</script>

<h3 id="admission_operation">安排面试</h3>

<?php if($is_secondary) { ?><p><span class="label label-warning">注意</span> 这是二次面试。</p><?php } ?>
<p>请联系代表并安排面试时间，安排确定后将可以执行面试操作，在预定的面试时间前将有邮件和短信通知。</p>

<?php echo form_open("delegate/operation/arrange_interview/{$delegate['id']}"); ?>

	<div class="form-group">
		<div class="input-group date form_datetime col-lg-10">
			<?php echo form_input(array(
				'name' => 'time',
				'class' => 'form-control',
				'size' => '16',
			)); ?>
			<span class="input-group-addon"><span class="glyphicon glyphicon-th"><?php echo icon('calendar', false);?></span></span>
		</div>
	</div>

	<?php echo form_button(array(
		'name' => 'submit',
		'content' => icon('calendar').'安排面试',
		'type' => 'submit',
		'class' => 'btn btn-primary',
		'onclick' => 'loader(this);'
	));
	echo ' ';
	echo form_button(array(
		'content' => icon('retweet').'回退面试',
		'type' => 'button',
		'class' => 'btn btn-warning',
		'data-toggle' => 'modal',
		'data-target' => '#rollback_interview',
	)); ?>

<?php echo form_close(); ?>

<?php echo form_open("delegate/operation/rollback_interview/$uid", array(
	'class' => 'modal fade form-horizontal',
	'id' => 'rollback_interview',
	'tabindex' => '-1',
	'role' => 'dialog',
	'aria-labelledby' => 'rollback_label',
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
				<h4 class="modal-title" id="rollback_label">回退面试</h4>
			</div>
			<div class="modal-body">
				<p>将会回退<?php echo icon('user', false).$delegate['name'];?>的面试分配，将由管理员重新为代表分配面试官。在回退之前，您确定已经和代表取得联系并已说明回退原因及其他事宜。</p>
				<p><span class="label label-warning">注意</span> 在回退之前，请务必在笔记中注明回退原因以方便重新分配面试官。</p>
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
					'content' => '确定回退面试',
					'type' => 'submit',
					'class' => 'btn btn-warning',
					'onclick' => 'loader(this);'
				)); ?>
			</div>
		</div>
	</div>
<?php echo form_close(); ?>

<hr />