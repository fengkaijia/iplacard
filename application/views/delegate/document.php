<?php
foreach($formats as $format_id => $format)
{
	$option[$format_id] = $format['name'];
	$subtext[$format_id] = "{$format['count']} 份文件";
}

$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/mimes.css' : 'static/css/mimes.min.css').'" rel="stylesheet">');
$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/bootstrap.select.css' : 'static/css/bootstrap.select.min.css').'" rel="stylesheet">');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/bootstrap.select.js' : 'static/js/bootstrap.select.min.js').'"></script>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/locales/bootstrap.select.locale.js' : 'static/js/locales/bootstrap.select.locale.min.js').'"></script>');
$this->load->view('header');?>

<div class="page-header">
	<h1><?php echo icon('files-o')?>文件</h1>
</div>

<div class="row">
	<div class="col-md-8">
		<h3>可供下载的文件</h3>
		<p>当前您共有 <?php echo $count;?> 份文件可供下载，您可以点击下方的下载按钮下载文件。当有新的文件发布时，我们将会向您发送邮件通知。</p>
		
		<div id="document-list" class="mimes-16">
			<?php foreach($documents as $document) { ?><div class="well">
				<legend style="margin-bottom: 12px;"><?php echo icon(!$document['downloaded'] ? (isset($document['formats']) && !empty($document['formats']) ? 'folder' : 'folder-o') : 'folder-open-o').$document['title'];?></legend>
				<p><?php if($document['highlight']) { ?><span class="text-primary"><?php echo icon('star', false).'重要文件'; ?></span> <?php } ?><span class="text-muted"><?php echo icon('calendar').sprintf('%1$s（%2$s）', date('n月j日', $document['create_time']), nicetime($document['create_time']));?></span></p>
				<?php if(!empty($document['description'])) { ?><p><?php echo $document['description'];?></p><?php } ?>
				
				<?php if(isset($document['formats']) && !empty($document['formats'])) { ?><table class="table table-bordered table-striped table-hover">
					<thead>
						<tr>
							<th>文件格式</th>
							<th>文件大小</th>
							<th>更新时间</th>
							<th>下载链接</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach($document['formats'] as $format => $file) { ?><tr>
							<td><?php echo sprintf('<span class="document_info" data-original-title="%1$s（%2$s）文件" data-toggle="tooltip">%3$s</span>', strtoupper($document['files'][$file]['filetype']), get_mime_by_extension('.'.$document['files'][$file]['filetype']), mime($document['files'][$file]['filetype']));
							echo "<span class=\"document_info\" data-original-title=\"{$formats[$format]['detail']}\" data-toggle=\"tooltip\">{$formats[$format]['name']}</span>";?></td>
							<td><?php echo byte_format($document['files'][$file]['filesize']);?></td>
							<td><?php echo sprintf('%1$s（%2$s）', date('n月j日 H:i:s', $document['files'][$file]['upload_time']), nicetime($document['files'][$file]['upload_time']));?></td>
							<td><?php
							if(empty($document['files'][$file]['identifier']))
								echo anchor("document/download/{$document['id']}/{$format}", icon('download').'点击下载');
							else
								echo anchor("document/download/{$document['id']}/{$format}", icon('download').'点击下载', array('onclick' => "$('#download_name').html('{$document['title']}'); $('#download_format').html('{$formats[$format]['name']}'); $('#single_download').modal('show');"));?></td>
						</tr><?php } ?>
					</tbody>
				</table><?php } else { ?><p class="text-danger">暂无可用文件。</p><?php } ?>
			</div><?php } ?>
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
						<p>即将开始弹出下载文件<?php echo icon('file-o', false);?><span id="download_name"></span>（<span id="download_format"></span>）。</p>
						
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
	</div>
	
	<div class="col-md-4">
		<?php echo form_open("document/zip/1", array('id' => 'zip')); ?>
			<h3>下载文件合集</h3>
			<p>您可以打包下载指定文件格式的所有文件，文件合集将会以 ZIP 压缩包格式提供，可以使用常用的解压缩软件打开。请在下方选择框中选择需要下载的文件格式。</p>
			<div class="form-group" style="margin-bottom: 0px;">
				<?php
				echo form_label('文件格式', 'format');
				echo form_dropdown_select('format', $option, array(), count($formats) > 10 ? true : false, array(), $subtext, array(), 'selectpicker', 'data-width="100%" title="选择文件格式"'); ?>
			</div>
			<p>完成选择后点击下方按钮开始下载文件合集。</p>
			<?php echo form_button(array(
				'name' => 'submit',
				'content' => '下载文件合集',
				'type' => 'submit',
				'id' => 'submit',
				'class' => 'btn btn-primary',
			)); ?>
		<?php echo form_close();
			
		echo form_open("document/zip/1", array(
			'class' => 'modal fade form-horizontal',
			'id' => 'zip_download',
			'tabindex' => '-1',
			'role' => 'dialog',
			'aria-labelledby' => 'zip_label',
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
						<h4 class="modal-title" id="zip_label">即将开始下载文件合集</h4>
					</div>
					<div class="modal-body">
						<p>即将开始弹出下载文件合集。</p>
						
						<div class="progress progress-striped active" style="height: 12px; width: 80%; margin: 40px auto;">
							<div class="progress-bar" style="width: 100%;"></div>
						</div>
						
						<p style="margin-bottom: 0;">请在即将弹出的下载提示框中保存 ZIP 文件。如果长时间没有弹出下载提示，请点击下方按钮重新开始下载。</p>
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
	</div>
</div>

<?php
$zip_js = "$('#submit').click(function(){
	$('#zip_download').modal('show');
});";
$this->ui->js('footer', $zip_js);

$url = base_url('document/zip');
$select_js = "
$('select[name=format]').change(function(){
	value = $('select[name=format] option:selected').val();
	
	$('#zip').attr('action', '{$url}/' + value);
	$('#zip_download').attr('action', '{$url}/' + value);
});
$('.selectpicker').selectpicker();
";
$this->ui->js('footer', $select_js);

$this->ui->js('footer', "$('.document_info').tooltip();");

$this->load->view('footer');?>