<?php echo form_open('help/search', array('method' => 'get'));?>
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

<div class="panel panel-default" style="margin-top: 20px;">
	<div class="panel-heading">使用知识库帮助</div>
	<div class="panel-body">
		<p>通过 iPlacard 知识库自主获取帮助与支持。</p>
		<p style="margin-bottom: 0;">iPlacard 知识库提供完整的操作指南和会议信息。</p>
	</div>
</div>