<?php
$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/mimes.css' : 'static/css/mimes.min.css').'" rel="stylesheet">');
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
				<legend style="margin-bottom: 12px;"><?php echo icon(!$document['downloaded'] ? 'folder-o' : 'folder-open-o').$document['title'];?></legend>
				<p><?php if($document['highlight']) { ?><span class="text-primary"><?php echo icon('star', false).'重要文件'; ?></span> <?php } ?><span class="text-muted"><?php echo icon('calendar').sprintf('%1$s（%2$s）', date('n月j日', $document['create_time']), nicetime($document['create_time']));?></span></p>
				<?php if(!empty($document['description'])) { ?><p><?php echo $document['description'];?></p><?php } ?>
				
				<table class="table table-bordered table-striped table-hover">
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
							<td><?php echo anchor("document/download/{$document['id']}/{$format}", icon('download').'点击下载');?></td>
						</tr><?php } ?>
					</tbody>
				</table>
			</div><?php } ?>
		</div>
	</div>
	
	<div class="col-md-4">
		<?php echo form_open("document/zip"); ?>
			<h3>下载文件合集</h3>
			<p>您可以打包下载所有文件，文件合集将会以 ZIP 压缩包格式提供，可以使用常用的解压缩软件打开。</p>
			<p>请点击下方按钮开始下载文件合集。</p>
			<?php echo form_button(array(
				'name' => 'submit',
				'content' => '下载文件合集',
				'type' => 'submit',
				'id' => 'submit',
				'class' => 'btn btn-primary',
			)); ?>
		<?php echo form_close();
			
		echo form_open("document/zip", array(
			'class' => 'modal fade form-horizontal',
			'id' => 'download_zip',
			'tabindex' => '-1',
			'role' => 'dialog',
			'aria-labelledby' => 'download_label',
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
						<h4 class="modal-title" id="download_label">即将开始下载</h4>
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
$width_js = <<<EOT
$(document).ready(function() {
	var lowest = Infinity;
	
	$('.document_item').each(function() {
		if($(this).width() < lowest) {
			lowest = $(this).width();
		}
	});
	
	$('.document_item').width(lowest);
});
EOT;
$this->ui->js('footer', $width_js);

$zip_js = "$('#submit').click(function(){
	$('#download_zip').modal('show');
});";
$this->ui->js('footer', $zip_js);

$this->ui->js('footer', "$('.document_info').tooltip();");

$this->load->view('footer');?>