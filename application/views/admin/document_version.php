<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 文件版本视图
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
 * @package iPlacard
 * @since 2.1
 */

$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/mimes.css' : 'static/css/mimes.min.css').'" rel="stylesheet">');
$this->load->view('header');?>

<div class="page-header">
	<h1><?php echo icon('file-o').$document['title'];?></h1>
</div>

<div class="row">
	<div class="col-md-8">
		<h3>文件版本</h3>
		<p><?php echo $document['title'];?>共有 <?php echo $count;?> 个文件版本。</p>

		<div id="document-list" class="mimes-16">
			<?php
			foreach($formats as $format)
			{
				if(!empty($format['file'])) { ?><div class="well">
				<legend style="margin-bottom: 12px;"><?php echo icon('folder-open-o').$format['name'];?></legend>
				<p><?php echo $format['detail'];?></p>

				<?php if(isset($format['file']) && !empty($format['file'])) { ?><table class="table table-bordered table-striped table-hover" style="margin-bottom: 2px;">
					<thead>
					<tr>
						<th>文件类型</th>
						<th>大小</th>
						<th>版本</th>
						<th>更新时间</th>
						<th>操作</th>
					</tr>
					</thead>
					<tbody>
					<?php foreach($format['file'] as $file_id => $file) { ?><tr>
						<td><?php echo sprintf('<span class="file_info" data-original-title="%2$s" data-toggle="tooltip">%3$s %1$s 文件</span>', strtoupper($file['filetype']), get_mime_by_extension('.'.$file['filetype']), mime($file['filetype']));?></td>
						<td><?php echo byte_format($file['filesize']);?></td>
						<td><?php echo !empty($file['version']) ? $file['version'] : 'N/A';?></td>
						<td><?php echo sprintf('%1$s（%2$s）', date('n月j日 H:i', $file['upload_time']), nicetime($file['upload_time']));?></td>
						<td><?php
							if(empty($file['identifier']))
								echo anchor("document/download/{$document['id']}/{$format['id']}/{$file_id}", icon('download').'下载');
							else
								echo anchor("document/download/{$document['id']}/{$format['id']}/{$file_id}", icon('download').'下载', array('onclick' => "$('#download_format').html('{$format['name']}'); $('#single_download').attr('action', $(this).attr('href')); $('#single_download').modal('show');"));?></td>
						</tr><?php } ?>
					</tbody>
				</table><?php } ?>
			</div><?php } } ?>
		</div>

		<?php echo form_open("document/download", array(
			'class' => 'modal fade form-horizontal',
			'id' => 'single_download',
			'tabindex' => '-1',
			'role' => 'dialog',
			'aria-labelledby' => 'single_label',
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
					<h4 class="modal-title" id="single_label">即将开始下载文件</h4>
				</div>
				<div class="modal-body">
					<p>即将开始弹出下载文件<?php echo icon('file-o', false).$document['title'];?>（<span id="download_format"></span>）。</p>

					<div class="progress progress-striped active" style="height: 12px; width: 80%; margin: 40px auto;">
						<div class="progress-bar" style="width: 100%;"></div>
					</div>

					<p style="margin-bottom: 0;">如果长时间没有弹出下载提示，请点击下方按钮重新开始下载。</p>
				</div>
				<div class="modal-footer">
					<?php echo form_button(array(
						'content' => '关闭',
						'type' => 'button',
						'class' => 'btn btn-link',
						'data-dismiss' => 'modal'
					));
					echo form_button(array(
						'name' => 'submit',
						'content' => '重新下载',
						'type' => 'submit',
						'class' => 'btn btn-primary',
						'onclick' => 'loader(this);'
					)); ?>
				</div>
			</div>
		</div>
		<?php echo form_close(); ?>

		<hr />

		<h3>分发范围</h3>
		<p><?php if($access === true) { ?>此文件全局分发，所有代表均可查看。<?php } else { ?>此文件限定委员会分发，仅绿色标识的委员会代表可查看。<?php } ?></p>

		<?php if($committees) { ?><div class="row">
			<?php
			$divide = ceil(count($committees) / 3) - 1;
			$group = array();

			//分组
			$counter = 0;
			$now = 0;
			foreach($committees as $committee)
			{
				$group[$now][] = $committee;

				if($counter == $divide)
				{
					$counter = 0;
					$now++;
				}
				else
				{
					$counter++;
				}
			}

			for($i = 0; $i < 3; $i++) { ?><div class="col-md-4"><?php
				if(!empty($group[$i]))
				{
					foreach($group[$i] as $committee)
					{
						if($access === true || in_array($committee['id'], $access))
						{
							echo sprintf("<p class='text-success'>%s%s</p>", icon('check'), $committee['name']);
						}
						else
						{
							echo sprintf("<p class='text-muted'>%s%s</p>", icon('times'), $committee['name']);
						}
					}
				}
				?></div><?php
			} ?>
		</div><?php } ?>
	</div>

	<div class="col-md-4">
		<div>
			<h3>文件信息</h3>
			<?php if($document['highlight']) { ?><p><span class="text-primary"><?php echo icon('star', false).'重要文件'; ?></span></p><?php } ?>

			<?php if(!empty($document['description'])) { ?><div class="form-group">
				<?php echo form_label('文件介绍', 'description', array('class' => 'control-label'));?>
				<div><?php echo nl2br($document['description']);?></div>
			</div><?php } ?>

			<div class="form-group">
				<?php echo form_label('发布日期', 'create', array('class' => 'control-label'));?>
				<div>
					<?php echo sprintf('%1$s（%2$s）', date('n月j日 H:i', $document['create_time']), nicetime($document['create_time']));?>
				</div>
			</div>

			<?php if($edit_allow) echo anchor("document/edit/{$document['id']}", '编辑文件信息', array('class' => 'btn btn-primary'));?>
		</div>

		<?php
		if($edit_allow)
		{
			echo form_open_multipart("document/version/{$document['id']}", array(), array('upload' => false));?>
			<hr />

			<h3>上传新版本</h3>
			<p>通过此表单上传<?php echo $document['title'];?>的新版本文件。</p>
			<div class="form-group <?php if(form_has_error('format')) echo 'has-error';?>">
				<?php
				echo form_label('文件格式', 'format', array('class' => 'control-label'));
				echo form_dropdown('format', array_column($formats, 'name', 'id'), set_value('format', 1), 'class="form-control"');
				if(form_has_error('format'))
					echo form_error('format');
				else { ?><div class="help-block">此版本文件对应的文件格式。</div><?php } ?>
			</div>

			<div class="form-group <?php if(form_has_error('version')) echo 'has-error';?>">
				<?php
				echo form_label('版本号', 'version', array('class' => 'control-label'));
				echo form_input(array(
					'name' => 'version',
					'id' => 'version',
					'class' => 'form-control',
					'value' => set_value('version')
				));
				if(form_has_error('version'))
					echo form_error('version');
				else { ?><div class="help-block">设置版本号将有助于文件管理。</div><?php } ?>
			</div>

			<div class="form-group <?php if(form_has_error('file') || form_has_error('upload')) echo 'has-error';?>">
				<?php
				echo form_label('上传文件', 'file', array('class' => 'control-label'));
				echo form_upload(array(
					'name' => 'file',
					'id' => 'file',
					'class' => 'form-control',
					'onchange' => "$('input[name=upload]').val(true);"
				));
				if(form_has_error('file') || form_has_error('upload'))
					echo form_error('file').form_error('upload');
				else { ?><div class="help-block">上传的文件大小应不超过 <?php echo $file_max_size;?>。</div><?php } ?>
			</div>

			<div class="form-group <?php if(form_has_error('identifier')) echo 'has-error';?>">
				<?php
				echo form_label('标识', 'identifier', array('class' => 'control-label'));
				echo form_input(array(
					'name' => 'identifier',
					'id' => 'identifier',
					'class' => 'form-control',
					'value' => set_value('identifier')
				));
				if(form_has_error('identifier'))
					echo form_error('identifier');
				else { ?><div class="help-block">文献标识将会用于与指定编译系统的对接。存在文献标识的文件将可以启用分发标记功能。</div><?php } ?>
			</div>

			<?php echo form_button(array(
				'name' => 'submit',
				'content' => '添加文件',
				'type' => 'submit',
				'class' => 'btn btn-primary',
				'onclick' => 'loader(this);'
			));?>
		<?php echo form_close(); } ?>
	</div>
</div>

<?php
$this->ui->js('footer', "$('.file_info').tooltip();");
$this->load->view('footer');?>