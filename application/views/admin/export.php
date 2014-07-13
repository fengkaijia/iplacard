<?php
$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/bootstrap.select.css' : 'static/css/bootstrap.select.min.css').'" rel="stylesheet">');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/bootstrap.select.js' : 'static/js/bootstrap.select.min.js').'"></script>');
$this->load->view('header');?>

<div class="page-header">
	<h1>导出</h1>
</div>

<div class="row">
	<div class="col-md-3">
		<div class="panel panel-default">
			<div class="panel-heading">帮助</div>
			<div class="panel-body">
				<p>您可以通过此工具导出 iPlacard 数据。iPlacard 数据导出工具支持导出代表的申请资料、所属团队、席位信息、面试记录、账单、笔记和附加信息，同时工具还支持导出管理员信息。</p>
				<p>导出工具支持通过申请类型、委员会、申请状态等属性筛选代表，当代表满足全部筛选条件时信息将会导出。</p>
				<p style="margin-bottom: 0;">导出工具支持将导出数据保存为 Microsoft Excel 97-2003/2007、HTML Calc、PDF 或 CSV 文件格式。当选定保存为 CSV 格式时仅能保存单一申请类型的代表信息。</p>
			</div>
		</div>
	</div>
	
	<div class="col-md-9">
		<?php echo form_open_multipart('admin/export/download', array('class' => 'well form-horizontal'));?>
			<?php echo form_fieldset('数据源'); ?>
				<div class="form-group <?php if(form_has_error('client')) echo 'has-error';?>">
					<?php echo form_label('导出对象筛选', 'client', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-9">
						<div class="row" style="margin-bottom: 8px;">
							<div class="col-md-6">
								<?php
								echo form_label('按代表类型筛选', 'type', array('class' => 'control-label'));
								$now_type = set_value('type', array_keys($select['type']));
								echo form_dropdown_multiselect('type[]', $select['type'], $now_type, false, array(), array(), array(), 'selectpicker', 'title="无" data-selected-text-format="count > 1" data-width="100%"');
								
								echo form_label('按代表申请状态筛选', 'status', array('class' => 'control-label'));
								$now_status = set_value('status', array_keys($select['status']));
								echo form_dropdown_multiselect('status[]', $select['status'], $now_status, false, array(), array(), array(), 'selectpicker', 'title="无" data-selected-text-format="count > 1" data-width="100%"');
								?>
							</div>
							<div class="col-md-6">
								<?php
								echo form_label('按代表委员会筛选', 'committee', array('class' => 'control-label'));
								$now_committee = set_value('committee', array_keys($select['committee']));
								echo form_dropdown_multiselect('committee[]', $select['committee'], $now_committee, false, array(), array(), array(), 'selectpicker', 'title="无" data-selected-text-format="count > 1" data-width="100%"');
								
								echo form_label('按管理用户权限筛选', 'role', array('class' => 'control-label'));
								$now_role = set_value('role', array_keys($select['role']));
								echo form_dropdown_multiselect('role[]', $select['role'], $now_role, false, array(), array(), array(), 'selectpicker', 'title="无" data-selected-text-format="count > 1" data-width="100%"');
								?>
							</div>
						</div>
						
						<?php
						echo form_button(array(
							'content' => '全选',
							'type' => 'button',
							'class' => 'btn btn-primary',
							'onclick' => "$('.selectpicker').selectpicker('selectAll');",
						));
						echo ' ';
						echo form_button(array(
							'content' => '清空',
							'type' => 'button',
							'class' => 'btn btn-primary',
							'onclick' => "$('.selectpicker').selectpicker('deselectAll');",
						));
						
						if(form_has_error('client'))
							echo form_error('client');
						else { ?><div class="help-block">根据代表类型、申请状态和委员会筛选数据导出对象，将会导出满足以上全部条件的代表信息。根据管理用户权限筛选数据导出对象，将会导出满足以上全部条件的管理员信息。</div><?php } ?>
					</div>
				</div>
		
				<div class="form-group <?php if(form_has_error('source')) echo 'has-error';?>">
					<?php echo form_label('导出内容', 'source', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-5">
						<?php
						echo form_dropdown_multiselect('source[]', $source, set_value('source'), false, array(), array(), array(), 'selectpicker', 'title="基本信息" data-selected-text-format="count > 1" data-width="100%"');
							
						if(form_has_error('source'))
							echo form_error('source');
						else { ?><div class="help-block">选择导出数据类型，留空将仅到处基础数据。</div><?php } ?>
					</div>
				</div>
		
				<div class="form-group <?php if(form_has_error('format')) echo 'has-error';?>">
					<?php echo form_label('导出文件格式', 'format', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-5">
						<?php
						echo form_dropdown('format', $format, set_value('format'), 'class="form-control"');
						
						if(form_has_error('format'))
							echo form_error('format');
						else { ?><div class="help-block">选择导出数据的文件保存格式。</div><?php } ?>
					</div>
				</div>
			<?php echo form_fieldset_close();?>
		
			<?php echo form_fieldset('数据保护'); ?>
				<div class="form-group <?php if(form_has_error('reason')) echo 'has-error';?>">
					<?php echo form_label('导出原因', 'reason', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-9">
						<?php echo form_textarea(array(
							'name' => 'reason',
							'id' => 'reason',
							'class' => 'form-control',
							'rows' => 4,
							'value' => set_value('reason'),
						));
						if(form_has_error('reason'))
							echo form_error('reason');
						else { ?><div class="help-block">填写详细的导出目的将有助于源信息的管理。</div><?php } ?>
					</div>
				</div>
			<?php echo form_fieldset_close();?>
		
			<div class="form-group">
				<div class="col-lg-10 col-lg-offset-2">
					<?php echo form_button(array(
						'name' => 'submit',
						'content' => '开始导出',
						'type' => 'submit',
						'class' => 'btn btn-primary',
						'data-toggle' => 'modal',
						'data-target' => '#export_data'
					)); ?>
				</div>
			</div>
		
			<div class="modal fade form-horizontal" id="export_data" tabindex="-1" role="dialog" aria-labelledby="export_label" aria-hidden="true">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">
							<?php echo form_button(array(
								'content' => '&times;',
								'class' => 'close',
								'type' => 'button',
								'data-dismiss' => 'modal',
								'aria-hidden' => 'true'
							));?>
							<h4 class="modal-title" id="delete_label">即将开始下载</h4>
						</div>
						<div class="modal-body">
							<p>即将开始弹出下载导出文件。这可能会花费数秒钟。</p>

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
			</div>
		<?php echo form_close(); ?>
	</div>
</div>

<?php
$this->ui->js('footer', "$('.selectpicker').selectpicker({
	iconBase: 'fa',
	tickIcon: 'fa-check'
});");

$this->load->view('footer');?>