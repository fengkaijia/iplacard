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
?><link href="<?php echo static_url(is_dev() ? 'static/css/bootstrap.select.css' : 'static/css/bootstrap.select.min.css');?>" rel="stylesheet">
<script src="<?php echo static_url(is_dev() ? 'static/js/bootstrap.select.js' : 'static/js/bootstrap.select.min.js');?>"></script>
<script src="<?php echo static_url(is_dev() ? 'static/js/locales/bootstrap.select.locale.js' : 'static/js/locales/bootstrap.select.locale.min.js');?>"></script>
<script>
	$('.selectpicker').selectpicker();
</script>

<h3 id="admission_operation">移出等待队列</h3>

<p>将此代表从等待队列中移出，并选定面试官为其分配席位。代表的面试状态将更新为免试通过。</p>

<?php
echo form_open("delegate/operation/accept_waitlist/{$delegate['id']}");
	echo form_dropdown_select('interviewer', $option, array(uid()), $interviewer_count > 10 ? true : false, array(), $subtext_title);?>

	<p>指派之后，iPlacard 将会以邮件形式自动通知代表和面试官等待队列变化。</p>

	<?php echo form_button(array(
		'name' => 'submit',
		'content' => icon('sign-out').'确认移出等待队列',
		'type' => 'submit',
		'class' => 'btn btn-warning',
		'onclick' => 'loader(this);'
	)); ?>
	
<?php echo form_close(); ?>

<hr />