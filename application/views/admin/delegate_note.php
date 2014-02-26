<?php
if(!empty($notes))
{
	//管理用户记录（多次出现不显示职位）
	$admins = array();
	
	foreach($notes as $id => $note)
	{ ?><blockquote>
	<p><?php echo nl2br($note['text']);?></p>
	<small><?php
	if(!empty($note['admin']['title']) && !in_array($note['admin']['id'], $admins))
	{
		$name_line = sprintf('%1$s（%2$s）', $note['admin']['name'], $note['admin']['title']);
		$admins[] = $note['admin']['id'];
	}
	else
		$name_line = sprintf('%1$s', $note['admin']['name']);
	
	if(!empty($note['category']) && isset($categories[$note['category']]))
		printf('%4$s / %1$s / %2$s（%3$s）', $name_line, date('n月j日', $note['time']), nicetime($note['time']), icon('tag', false).$categories[$note['category']]);
	else
		printf('%1$s / %2$s（%3$s）', $name_line, date('n月j日', $note['time']), nicetime($note['time']));
	?></small>
</blockquote><?php } }
echo form_button(array(
	'content' => icon('pencil', true).'添加笔记',
	'type' => 'button',
	'class' => 'btn btn-primary',
	'data-toggle' => 'modal',
	'data-target' => '#add_note'
));

echo form_open("delegate/note/add/$uid", array(
	'class' => 'modal fade form-horizontal',
	'id' => 'add_note',
	'tabindex' => '-1',
	'role' => 'dialog',
	'aria-labelledby' => 'add_label',
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
			<h4 class="modal-title" id="delete_label">添加笔记</h4>
		</div>
		<div class="modal-body">
			<p>笔记为您和其他团队成员的工作带来便利，这些笔记内容将不会发送给代表。</p>
			
			<div class="form-group <?php if(form_has_error('note')) echo 'has-error';?>">
				<?php echo form_label('笔记内容', 'note', array('class' => 'col-lg-3 control-label'));?>
				<div class="col-lg-9">
					<?php echo form_textarea(array(
						'name' => 'note',
						'id' => 'note',
						'class' => 'form-control',
						'rows' => 5
					));
					if(form_has_error('note'))
						echo form_error('note');
					else { ?><div class="help-block">保护笔记简洁将有助于提高团队效率。</div><?php } ?>
				</div>
			</div>
			
			<?php if(!empty($categories)) { ?><div class="form-group <?php if(form_has_error('category')) echo 'has-error';?>">
				<?php echo form_label('笔记分类', 'category', array('class' => 'col-lg-3 control-label'));?>
				<div class="col-lg-5">
					<?php
					echo form_dropdown('category', array('' => '不分类') + $categories, set_value('category', ''), 'class="form-control" id="category"');
					if(form_has_error('category'))
						echo form_error('category');
					else { ?><div class="help-block">选定笔记分类将有助于管理。</div><?php } ?>
				</div>
			</div><?php } ?>
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
				'content' => '添加笔记',
				'type' => 'submit',
				'class' => 'btn btn-primary',
				'onclick' => 'loader(this);'
			)); ?>
		</div>
	</div>
</div><?php echo form_close(); ?>