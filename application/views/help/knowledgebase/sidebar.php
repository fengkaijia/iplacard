<?php echo form_open('knowledgebase/search', array('method' => 'get'));?>
	<div class="input-group">
		<?php echo form_input(array(
			'name' => 'keyword',
			'value' => set_value('keyword', isset($keyword) ? $keyword : ''),
			'class' => 'form-control',
			'placeholder' => '搜索知识库'
		));?>
		<span class="input-group-btn">
			<?php echo form_button(array(
				'content' => icon('search', false),
				'type' => 'submit',
				'class' => 'btn btn-primary',
				'onclick' => 'loader(this);'
			));?>
		</span>
	</div>
<?php echo form_close();?>

<?php if(isset($keyword)) { ?><div class="panel panel-default" style="margin-top: 20px;">
	<div class="panel-heading">使用知识库帮助</div>
	<div class="panel-body">
		<p>通过 iPlacard 知识库获取帮助与支持。</p>
		<p style="margin-bottom: 0;">知识库文章提供会议相关帮助信息。</p>
	</div>
</div><?php }
else
{ ?><div class="panel panel-default" style="margin-top: 20px;">
	<div class="panel-heading">知识库文章属性</div>
	<div class="panel-body">
		<p><?php echo icon('book')."文章编号：KB{$article['kb']}";?></p>
		<p><?php echo icon('floppy-o').sprintf('发布时间：%s', date('Y-m-d H:i', $article['create_time']));?></p>
		<?php if(!is_null($article['update_time']) && $article['update_time'] != $article['create_time']) { ?><p><?php echo icon('pencil').sprintf('最后更新：%s', date('Y-m-d H:i', $article['create_time']));?></p><?php } ?>
		<p style="margin-bottom: 0;"><small><?php if($article['system'])
			echo '此知识库文章为 iPlacard 系统帮助文章，文档内容将为使用 iPlacard 提供帮助与支持。';
		else
			printf('知识库文章提供会议相关帮助信息，使用搜索功能可以查找知识库文章。请%s获得更多帮助与支持。', safe_mailto(option('site_contact_email', 'contact@iplacard.com'), '联系管理员'));
		?></small></p>
	</div>
</div><?php } ?>