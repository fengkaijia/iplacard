<?php
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

		$link = $this->admin_model->capable('administrator') ? anchor("/user/edit/{$one['id']}", $one['name']) : $one['name'];
		$rollback_data[] = icon('user', false).$link.$user_text;
	}
}
?><link href="<?php echo static_url(is_dev() ? 'static/css/bootstrap.select.css' : 'static/css/bootstrap.select.min.css');?>" rel="stylesheet">
<script src="<?php echo static_url(is_dev() ? 'static/js/bootstrap.select.js' : 'static/js/bootstrap.select.min.js');?>"></script>
<script src="<?php echo static_url(is_dev() ? 'static/js/locales/bootstrap.select.locale.js' : 'static/js/locales/bootstrap.select.locale.min.js');?>"></script>
<script>
	$('.selectpicker').selectpicker();
	
	jQuery(function($){
		$('#do_assign').hide();
		$('#do_exempt').hide();
	});
</script>

<div id="pre_select">
	<h3 id="admission_operation">安排面试</h3>
	
	<?php if($is_secondary) { ?><p><span class="label label-warning">注意</span> 这是二次面试分配。</p><?php } ?>
	<p>此代表已经通过审核，将需要为其安排面试。</p>
	<p>点击<strong>分配面试</strong>按钮后，将会出现可选择的面试官列表，您可以分配一位面试官面试此代表。</p>
	<p>如果此代表具有规定的免试资格，可以以免试通过方式完成此代表的面试流程。点击<strong>免试通过</strong>按钮后，将会出现可选择的面试官列表，您需要分配一位面试官为此代表分配席位。</p>
	
	<?php if($is_rollbacked) { ?><p><span class="label label-warning">注意</span> 这位代表的面试安排曾被<?php echo join("、", $rollback_data); ?>回退，请在记事中了解回退原因。</p><?php } ?>
	
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

		if($is_rollbacked) { ?><p><span class="label label-warning">注意</span> 这位代表的面试安排曾被<?php echo join("、", $rollback_data); ?>回退，请在记事中了解回退原因。</p><?php } ?>

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

		if($is_rollbacked) { ?><p><span class="label label-warning">注意</span> 这位代表的面试安排曾被<?php echo join("、", $rollback_data); ?>回退，请在记事中了解回退原因。</p><?php } ?>

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