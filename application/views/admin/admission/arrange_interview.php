<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 安排面试时间步骤视图
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
 * @package iPlacard
 * @since 2.0
 */

if($is_retest_requested)
{
	foreach($retest as $one)
	{
		if(!empty($one['committee']))
			$user_text = "（{$committees[$one['committee']]['abbr']}）";
		else
			$user_text = empty($one['title']) ? '' : "（{$one['title']}）";

		$link = $this->admin_model->capable('bureaucrat') ? anchor("/user/edit/{$one['id']}", $one['name']) : $one['name'];
		
		$link_text = icon('user', false).$link.$user_text;
		$retest_data[] = $link_text;
		
		if($one['id'] == $last_interviewer)
			$retest_last = $link_text;
	}
}
?><script>
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

<?php if($is_secondary) { ?><p><span class="label label-warning">注意</span> 这是二次面试。</p><?php }

if($is_retest_requested) { ?><p><span class="label label-warning">注意</span> <?php if(count($retest_data) == 1)
	{
		printf('面试官%s曾经请求增加复试，请查看面试评价和笔记了解复试需求。', join("、", $retest_data));
	}
	else
	{
		printf('面试官%1$s曾经请求增加复试，最近一次复试由%2$s发起，请查看面试评价和笔记了解复试需求。', join("、", $retest_data), $retest_last);
	} ?>
</p><?php } ?>
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