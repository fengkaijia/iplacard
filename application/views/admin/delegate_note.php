<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 笔记视图
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
 * @package iPlacard
 * @since 2.0
 */
?><link href="<?php echo static_url(is_dev() ? 'static/css/jquery.atwho.css' : 'static/css/jquery.atwho.min.css');?>" rel="stylesheet">
<script src="<?php echo static_url(is_dev() ? 'static/js/jquery.cookie.js' : 'static/js/jquery.cookie.min.js');?>"></script>
<script src="<?php echo static_url(is_dev() ? 'static/js/jquery.sayt.js' : 'static/js/jquery.sayt.min.js');?>"></script>
<script src="<?php echo static_url(is_dev() ? 'static/js/jquery.caret.js' : 'static/js/jquery.caret.min.js');?>"></script>
<script src="<?php echo static_url(is_dev() ? 'static/js/jquery.atwho.js' : 'static/js/jquery.atwho.min.js');?>"></script>
<script>
	$('#add_note_<?php echo $uid;?> #note').atwho({
		at: "@",
		data: "<?php echo base_url('user/ajax/mention');?>",
		displayTpl: "<li>${name} <small>${title}</small></li>",
		insertTpl: "@${name}(${id})",
		callbacks: {
			beforeInsert: function(value, $li) {
				$('#add_note_<?php echo $uid;?>').append('<input type="hidden" name="mention[]" value="' + value + '" />');
				return value;
			}
		}
	});
	
	$('.mention_tab').popover();
	$('.noter_tab').popover();
	
	$('#add_note_<?php echo $uid;?>').sayt({'days': 15});
</script>

<?php
if(!empty($notes))
{
	//管理用户记录（多次出现不显示职位）
	$admins = array();
	
	foreach($notes as $id => $note)
	{ ?><blockquote>
	<p><?php echo $note['text_rich'];?></p>
	<small><?php
	$noter_tab = '<p>'.icon('phone').$note['admin']['phone'].'</p><p>'.icon('envelope-o').$note['admin']['email'].'</p>';
	$noter_info = '<span class="noter_tab" data-html="1" data-placement="top" data-trigger="hover focus" data-original-title=\''
			.$note['admin']['name']
			.'\' data-toggle="popover" data-content=\'<div class="user-info">'.$noter_tab.'</div>\'>'.$note['admin']['name'].'</span>';
	
	if(!empty($note['admin']['title']) && !in_array($note['admin']['id'], $admins))
	{
		$name_line = sprintf('%1$s（%2$s）', $noter_info, $note['admin']['title']);
		$admins[] = $note['admin']['id'];
	}
	else
		$name_line = $noter_info;
	
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
	'data-target' => "#add_note_{$uid}"
));

echo form_open_multipart("delegate/note/add/$uid", array(
	'class' => 'modal fade form-horizontal',
	'id' => "add_note_{$uid}",
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
				'onclick' => "$('#add_note_{$uid}').sayt({'erase': true}); loader(this);"
			)); ?>
		</div>
	</div>
</div><?php echo form_close(); ?>