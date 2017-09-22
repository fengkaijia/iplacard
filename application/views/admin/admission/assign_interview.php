<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 分配面试步骤视图
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
 * @package iPlacard
 * @since 2.0
 */

$data = array();

foreach($select as $cid => $committee)
{
	$data[$cid] = $committee;
}

foreach($data as $group => $list)
{
	$groupname = ($group == 0) ? '公共' : $committees[$group]['name'];
	foreach($list as $one)
	{
		$option[$groupname][$one['id']] = $one['name'];

		$subtext_queue[$one['id']] = $one['queue'] > 0 ? $one['queue'] : '空队列';
		$subtext_title[$one['id']] = $one['title'];
	}
}

if($is_rollbacked)
{
	foreach($rollback as $one)
	{
		if(!empty($one['committee']))
			$user_text = "（{$committees[$one['committee']]['abbr']}）";
		else
			$user_text = empty($one['title']) ? '' : "（{$one['title']}）";

		$link = $this->admin_model->capable('bureaucrat') ? anchor("/user/edit/{$one['id']}", $one['name']) : $one['name'];
		$rollback_data[] = icon('user', false).$link.$user_text;
	}
}

if($is_retest_requested)
{
	$retest_last = '';
	$retest_last_id = 0;
	
	foreach($retest as $one)
	{
		if(!empty($one['committee']))
			$user_text = "（{$committees[$one['committee']]['abbr']}）";
		else
			$user_text = empty($one['title']) ? '' : "（{$one['title']}）";

		$link = $this->admin_model->capable('bureaucrat') ? anchor("/user/edit/{$one['id']}", $one['name']) : $one['name'];
		
		$link_text = icon('user', false).$link.$user_text;
		$retest_data[] = $link_text;
		
		//最近一次面试复试情况
		if($one['id'] == $current_interviewer)
			$retest_current = $link_text;
		
		//早前请求复试最近一次面试未通过情况
		if($one['id'] > $retest_last_id)
		{
			$retest_last = $link_text;
			$retest_last_id = $one['id'];
		}
	}
	
	//最后一次复试请求
	if(!isset($retest_current))
		$retest_current = $retest_last;
}
?><script>
	$('.selectpicker').selectpicker();
	
	jQuery(function($){
		$('#do_assign').hide();
		$('#do_exempt').hide();
	});
</script>

<div id="pre_select">
	<?php if($is_retest_requested) { ?>
		<h3 id="admission_operation">复试请求</h3>
		
		<p><?php echo join("、", $retest_data); ?>请求增加复试，请在笔记中了解请求复试原因。如果认为无需复试，您可以关闭复试请求。</p>
		
		<?php echo form_button(array(
			'content' => '关闭复试请求',
			'type' => 'button',
			'class' => 'btn btn-warning',
			'data-toggle' => 'modal',
			'data-target' => '#deny_retest',
		)); ?>
		
		<hr />
		
		<?php echo form_open("delegate/operation/deny_retest/{$delegate['id']}", array(
			'class' => 'modal fade form-horizontal',
			'id' => 'deny_retest',
			'tabindex' => '-1',
			'role' => 'dialog',
			'aria-labelledby' => 'deny_label',
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
						<h4 class="modal-title" id="fail_label">关闭复试请求</h4>
					</div>
					<div class="modal-body">
						<p>您将关闭由<?php echo $retest_current; ?>发起的复试请求。复试请求关闭后，他将需要为此代表分配席位。</p>
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
							'content' => '确认关闭',
							'type' => 'submit',
							'class' => 'btn btn-warning',
							'onclick' => 'loader(this);'
						)); ?>
					</div>
				</div>
			</div>
		<?php echo form_close();
	} ?>
	
	<h3 id="admission_operation">安排面试</h3>
	
	<?php if($is_secondary) { ?><p><span class="label label-warning">注意</span> 这是二次面试分配。</p><?php } ?>
	<p>此代表已经通过审核，将需要为其安排面试。</p>
	<p>点击<strong>分配面试</strong>按钮后，将会出现可选择的面试官列表，您可以分配一位面试官面试此代表。</p>
	<p>如果此代表具有规定的免试资格，可以以免试通过方式完成此代表的面试流程。点击<strong>免试通过</strong>按钮后，将会出现可选择的面试官列表，您需要分配一位面试官为此代表分配席位。</p>
	
	<?php if($is_rollbacked) { ?><p><span class="label label-warning">注意</span> 这位代表的面试安排曾被<?php echo join("、", $rollback_data); ?>回退，请在笔记中了解回退原因。</p><?php } ?>
	
	<?php echo form_button(array(
		'content' => '分配面试',
		'type' => 'button',
		'class' => 'btn btn-primary',
		'onclick' => "$('#do_assign').show(); $('#pre_select').hide();"
	));
	echo ' ';
	echo form_button(array(
		'content' => '免试通过',
		'type' => 'button',
		'class' => 'btn btn-primary',
		'onclick' => "$('#do_exempt').show(); $('#pre_select').hide();"
	)); ?>
</div>

<div id="do_assign">
	<h3 id="admission_operation">分配面试官</h3>

	<?php if($is_secondary) { ?><p><span class="label label-warning">注意</span> 这是二次面试分配。</p><?php }
	if(!empty($choice_committee)) { ?><p>代表志愿选择<?php echo join('、', $choice_committee);?>委员会，系统已经高亮标记了对应委员会的面试官。</p><?php } ?>
	<p>请在此列表中选择面试官，面试官姓名右侧显示了面试官当前面试队列长度。</p>

	<?php
	echo form_open("delegate/operation/assign_interview/{$delegate['id']}");
		echo form_dropdown_select('interviewer', $option, array(), $interviewer_count > 10 ? true : false, isset($primary['interviewer']) ? $primary['interviewer'] : array(), $subtext_queue);

		if($is_rollbacked) { ?><p><span class="label label-warning">注意</span> 这位代表的面试安排曾被<?php echo join("、", $rollback_data); ?>回退，请在笔记中了解回退原因。</p><?php } ?>

		<p>分配完成之后，分配信息将会以邮件形式自动通知代表和面试官。</p>

		<?php echo form_button(array(
			'name' => 'submit',
			'content' => icon('share').'分配面试官',
			'type' => 'submit',
			'class' => 'btn btn-primary',
			'onclick' => 'loader(this);'
		));
		echo form_button(array(
			'content' => '取消',
			'type' => 'button',
			'class' => 'btn btn-link',
			'onclick' => "$('#do_assign').hide(); $('#pre_select').show();"
		)); ?>
		
	<?php echo form_close(); ?>
</div>

<div id="do_exempt">
	<h3 id="admission_operation">免试指派席位</h3>

	<?php if(!empty($choice_committee)) { ?><p>代表志愿选择<?php echo join('、', $choice_committee);?>委员会，系统已经高亮标记了对应委员会的面试官。</p><?php } ?>
	<p>将会以免试通过方式完成此代表的面试流程，请在此列表中选择面试官，选定的面试官将可以直接为此代表分配席位。</p>

	<?php
	echo form_open("delegate/operation/exempt_interview/{$delegate['id']}");
		echo form_dropdown_select('interviewer', $option, array(uid()), $interviewer_count > 10 ? true : false, isset($primary['interviewer']) ? $primary['interviewer'] : array(), $subtext_title);

		if($is_rollbacked) { ?><p><span class="label label-warning">注意</span> 这位代表的面试安排曾被<?php echo join("、", $rollback_data); ?>回退，请在笔记中了解回退原因。</p><?php } ?>

		<p>指派之后，iPlacard 将会以邮件形式自动通知代表和面试官。</p>

		<?php echo form_button(array(
			'name' => 'submit',
			'content' => icon('reply-all').'确认免试通过',
			'type' => 'submit',
			'class' => 'btn btn-primary',
			'onclick' => 'loader(this);'
		));
		echo form_button(array(
			'content' => '取消',
			'type' => 'button',
			'class' => 'btn btn-link',
			'onclick' => "$('#do_exempt').hide(); $('#pre_select').show();"
		)); ?>
		
	<?php echo form_close(); ?>
</div>

<hr />