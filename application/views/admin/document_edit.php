<?php
$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/mimes.css' : 'static/css/mimes.min.css').'" rel="stylesheet">');
$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/bootstrap.select.css' : 'static/css/bootstrap.select.min.css').'" rel="stylesheet">');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/bootstrap.select.js' : 'static/js/bootstrap.select.min.js').'"></script>');
$this->load->view('header');?>

<div class="page-header mime-48">
	<h1><?php echo $action == 'add' ? '添加文件' : mime($document['filetype']).$document['title'];?></h1>
</div>

<div class="row">
	<div class="col-md-3">
		<div class="panel panel-default">
			<div class="panel-heading">帮助</div>
			<div class="panel-body"><?php if($action == 'edit') { ?>
				<p>您可以通过此页面添加文件。</p>
				<p style="margin-bottom: 0;">添加文件后将会向受影响的用户发送邮件通知。</p>
				<?php } else { ?>
				<p>您可以通过此页面编辑文件属性或上传文件的一个新版本。</p>
				<p style="margin-bottom: 0;">上传新版本的文件后将会向受影响的用户发送邮件通知。</p>
			<?php } ?></div>
		</div>
	</div>
	
	<div class="col-md-9">
		<?php echo form_open_multipart($action == 'add' ? 'document/edit' : "document/edit/{$document['id']}", array('class' => 'well form-horizontal'), array('new_upload' => false));?>
			<?php echo form_fieldset('文件信息'); ?>
				<div class="form-group <?php if(form_has_error('title')) echo 'has-error';?>">
					<?php echo form_label('文件名称', 'title', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-6">
						<?php echo form_input(array(
							'name' => 'title',
							'id' => 'title',
							'class' => 'form-control',
							'value' => set_value('title', $action == 'add' ? '' : $document['title']),
							'required' => NULL,
						));
						if(form_has_error('title'))
							echo form_error('title');
						else { ?><div class="help-block">用于显示的文件标题。</div><?php } ?>
					</div>
				</div>
		
				<div class="form-group <?php if(form_has_error('description')) echo 'has-error';?>">
					<?php echo form_label('文件介绍', 'description', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-6">
						<?php echo form_textarea(array(
							'name' => 'description',
							'id' => 'description',
							'class' => 'form-control',
							'rows' => 4,
							'value' => set_value('description', $action == 'add' ? '' : $document['description']),
						));
						if(form_has_error('description'))
							echo form_error('description');
						else { ?><div class="help-block">文件详细信息，可为空。</div><?php } ?>
					</div>
				</div>
		
				<div class="form-group <?php if(form_has_error('highlight')) echo 'has-error';?>">
					<?php echo form_label('标记重要', 'highlight', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-10">
						<div class="checkbox">
							<label>
								<?php echo form_checkbox(array(
									'name' => 'highlight',
									'id' => 'highlight',
									'value' => true,
									'checked' => set_value('highlight', $action == 'add' ? '' : $document['highlight']),
								)); ?> 标记此文件为重要文件
							</label>
						</div>
						<?php if(form_has_error('highlight'))
							echo form_error('highlight');
						else { ?><div class="help-block">重要的文件将会高亮显示并带有“<?php echo icon('star', false);?>”。</div><?php } ?>
					</div>
				</div>
			<?php echo form_fieldset_close();?>
		
			<br />
		
			<?php echo form_fieldset('分发设置'); ?>
				<p>设置分发范围将可以限定部分代表下载文件。当分发类型设置为全局分发时，所有代表（即使尚未分配席位）都可以下载此文件；当设置为限定委员会分发时，只有选定的一个或多个委员会的代表可以下载此文件。</p>
			
				<div id="access_type" class="form-group <?php if(form_has_error('access_type')) echo 'has-error';?>">
					<?php echo form_label('分发类型', 'access_type', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-4">
						<?php
						$array = array(
							'global' => '全局分发',
							'committee' => '限定委员会分发',
						);
						echo form_dropdown('access_type', $array, set_value('access_type', $action == 'add' ? 'global' : $document['access_type']), 'class="form-control"');
						if(form_has_error('access_type'))
							echo form_error('access_type');
						else { ?><div class="help-block">分发范围将决定代表可否下载文件。</div><?php }
						$access_select_js = "$('#access_select').hide();
						$('#access_type').change(function() {
							if($('select[name=access_type]').val() === 'committee') {
								$('#access_select').show();
							} else {
								$('#access_select').hide();
							}
						});";
						$this->ui->js('footer', $access_select_js);
						
						//委员会分发默认显示范围栏
						if(set_value('access_type', $action == 'add' ? 'global' : $document['access_type']) == 'committee')
							$this->ui->js('footer', "$('#access_select').show();");
						?>
					</div>
				</div>
			
				<div id="access_select" class="form-group <?php if(form_has_error('access_select')) echo 'has-error';?>">
					<?php echo form_label('分发范围', 'access_select', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-10">
						<?php
						$now_committee = set_value('access_select', $action == 'add'? '' : $document['access_select']);
						echo form_dropdown_multiselect('access_select[]', $committees, $now_committee, false, array(), array(), array(), 'selectpicker', 'title="选择分发委员会" data-selected-text-format="count > 1"');
						if(form_has_error('access_select'))
							echo form_error('access_select');
						else { ?><div class="help-block">委员会分发范围，选定委员会的代表将可以下载此文件。</div><?php } ?>
					</div>
				</div>
			<?php echo form_fieldset_close();?>
			
			<br />
		
			<?php echo form_fieldset('上传新版本'); ?>
				<div class="form-group <?php if(form_has_error('version')) echo 'has-error';?>">
					<?php echo form_label('版本号', 'version', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-4">
						<?php echo form_input(array(
							'name' => 'version',
							'id' => 'version',
							'class' => 'form-control',
							'value' => set_value('version')
						));
						if(form_has_error('version'))
							echo form_error('version');
						else { ?><div class="help-block">设置版本号将有助于文件管理。</div><?php } ?>
					</div>
				</div>
				
				<div class="form-group <?php if(form_has_error('file') || form_has_error('new_upload')) echo 'has-error';?>">
					<?php echo form_label('上传文件', 'file', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-4">
						<?php echo form_upload(array(
							'name' => 'file',
							'id' => 'file',
							'class' => 'form-control',
							'onchange' => "$('input[name=new_upload]').val(true);"
						));
						if(form_has_error('file') || form_has_error('new_upload'))
							echo form_error('file').form_error('new_upload');
						else { ?><div class="help-block">上传的文件大小应不超过 <?php echo $file_max_size;?>。</div><?php } ?>
					</div>
				</div>
			
				<div class="form-group <?php if(form_has_error('drm')) echo 'has-error';?>">
					<?php echo form_label('版权保护', 'drm', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-10">
						<div class="checkbox">
							<label>
								<?php echo form_checkbox(array(
									'name' => 'drm',
									'id' => 'drm',
									'value' => true,
									'checked' => set_value('drm', $action == 'add' ? '' : $document['drm']),
								)); ?> 启用版权标识
							</label>
						</div>
						<?php if(form_has_error('drm'))
							echo form_error('drm');
						else { ?><div class="help-block">选中后将会在文件下载中添加版权保护标识，这些标识是不可见的。此功能仅支持部分格式的文件。</div><?php } ?>
					</div>
				</div>
			<?php echo form_fieldset_close();?>
			
			<div class="form-group">
				<div class="col-lg-10 col-lg-offset-2">
					<?php echo form_button(array(
						'name' => 'submit',
						'content' => $action == 'add' ? '添加文件' : '编辑文件',
						'type' => 'submit',
						'class' => 'btn btn-primary',
						'onclick' => 'loader(this);'
					));
					echo ' ';
					if($action == 'edit')
					{
						echo ' ';
						echo form_button(array(
							'name' => 'delete',
							'content' => '删除文件',
							'type' => 'button',
							'class' => 'btn btn-danger',
							'data-toggle' => 'modal',
							'data-target' => '#delete_document',
						));
					} ?>
				</div>
			</div>
		<?php echo form_close();
		
		if($action == 'edit')
		{
			echo form_open("document/delete/{$document['id']}", array(
				'class' => 'modal fade form-horizontal',
				'id' => 'delete_document',
				'tabindex' => '-1',
				'role' => 'dialog',
				'aria-labelledby' => 'delete_label',
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
						<h4 class="modal-title" id="delete_label">删除文件</h4>
					</div>
					<div class="modal-body">
						<p>您将删除<?php echo icon('document', false).$document['title'];?>。该文件的所有版本将被同时删除。请输入您的登录密码并点击确认更改按钮继续操作。</p>

						<div class="form-group <?php if(form_has_error('admin_password')) echo 'has-error';?>">
							<?php echo form_label('登录密码', 'admin_password', array('class' => 'col-lg-3 control-label'));?>
							<div class="col-lg-5">
								<?php echo form_password(array(
									'name' => 'admin_password',
									'id' => 'admin_password',
									'class' => 'form-control',
									'required' => NULL
								));
								echo form_error('admin_password');?>
							</div>
						</div>
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
							'content' => '确认删除',
							'type' => 'submit',
							'class' => 'btn btn-danger',
							'onclick' => 'loader(this);'
						)); ?>
					</div>
				</div>
			</div>
		<?php echo form_close(); } ?>
	</div>
</div>

<?php
$this->ui->js('footer', "$('.selectpicker').selectpicker({
	iconBase: 'fa',
	tickIcon: 'fa-check'
});");
$this->load->view('footer');?>