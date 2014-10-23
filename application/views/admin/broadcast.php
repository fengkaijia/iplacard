<?php
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/summernote.js' : 'static/js/summernote.min.js').'"></script>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/locales/summernote.locale.js' : 'static/js/locales/summernote.locale.min.js').'"></script>');
$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/summernote.css' : 'static/css/summernote.min.css').'" rel="stylesheet">');
$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/bootstrap.select.css' : 'static/css/bootstrap.select.min.css').'" rel="stylesheet">');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/bootstrap.select.js' : 'static/js/bootstrap.select.min.js').'"></script>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/locales/bootstrap.select.locale.js' : 'static/js/locales/bootstrap.select.locale.min.js').'"></script>');
$this->load->view('header');?>

<div class="page-header">
	<div class="row">
		<div class="col-md-8">
			<h1><?php echo $title;?></h1>
		</div>
		<?php $this->ui->js('footer', 'nav_menu_top();
		$(window).resize(function($){
			nav_menu_top();
		});');
		?>
		<div class="col-md-4 menu-tabs">
			<ul class="nav nav-tabs nav-menu">
				<li<?php if($action == 'email') echo ' class="active"';?>><?php echo anchor('admin/broadcast/email', '群发邮件');?></li>
				<?php if(option('sms_enabled', false)) { ?><li<?php if($action == 'sms') echo ' class="active"';?>><?php echo anchor('admin/broadcast/sms', '群发短信');?></li><?php } ?>
				<li<?php if($action == 'message') echo ' class="active"';?>><?php echo anchor('admin/broadcast/message', '广播站内消息');?></li>
			</ul>
		</div>
	</div>
</div>

<div class="menu-pills"></div>

<div class="row">
	<div class="col-md-3">
		<div class="panel panel-default">
			<div class="panel-heading">帮助</div>
			<div class="panel-body">
				<p style="margin-bottom: 0;">您可以通过此页面向代表群发邮件、短信和站内广播通知。</p>
			</div>
		</div>
	</div>
	
	<div class="col-md-9">
		<?php echo form_open_multipart("admin/broadcast/{$action}", array('class' => 'well form-horizontal'));?>
			<?php echo form_fieldset('群发信息内容'); ?>
				<?php if($action == 'email') { ?><div class="form-group <?php if(form_has_error('title')) echo 'has-error';?>">
					<?php echo form_label('邮件标题', 'title', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-7">
						<?php echo form_input(array(
							'name' => 'title',
							'id' => 'title',
							'class' => 'form-control',
							'value' => set_value('title'),
							'required' => NULL,
						));
						if(form_has_error('title'))
							echo form_error('title');
						else { ?><div class="help-block">电子邮件标题。</div><?php } ?>
					</div>
				</div><?php } ?>
		
				<div class="form-group <?php if(form_has_error('client')) echo 'has-error';?>">
					<?php echo form_label('群发对象', 'client', array('class' => 'col-lg-2 control-label'));?>
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
						else { ?><div class="help-block">根据代表类型、申请状态和委员会筛选群发对象，将会向满足以上全部条件的代表群发信息。根据管理用户权限筛选群发对象，将会向满足以上条件的管理员群发信息。</div><?php } ?>
					</div>
				</div>
		
				<div class="form-group <?php if(form_has_error('content')) echo 'has-error';?>">
					<?php echo form_label('内容', 'content', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-9">
						<?php echo form_textarea(array(
							'name' => 'content',
							'id' => 'content',
							'class' => $action == 'sms' ? 'form-control' : 'summernote',
							'rows' => 4,
							'value' => set_value('content'),
						));
						if(form_has_error('content'))
							echo form_error('content');
						?>
					</div>
				</div>
			<?php echo form_fieldset_close();?>
		
			<div class="form-group">
				<div class="col-lg-10 col-lg-offset-2">
					<?php echo form_button(array(
						'name' => 'submit',
						'content' => '发送信息',
						'type' => 'submit',
						'class' => 'btn btn-primary',
						'onclick' => 'loader(this);'
					)); ?>
				</div>
			</div>
		<?php echo form_close();?>
	</div>
</div>

<?php
$summernote_js = <<<EOT
$(document).ready(function() {
	$('.summernote').summernote({
		toolbar: [
			['style', ['style']],
			['font', ['bold', 'italic', 'underline', 'clear']],
			['color', ['color']],
			['para', ['ul', 'ol', 'paragraph']],
			['table', ['table']],
			['insert', ['link', 'picture']],
			['view', ['fullscreen', 'codeview']]
		],
		height: 300,
		lang: 'zh-CN'
	});
});
EOT;
if($action != 'sms')
	$this->ui->js('footer', $summernote_js);

$this->ui->js('footer', "$('.selectpicker').selectpicker({
	iconBase: 'fa',
	tickIcon: 'fa-check'
});");

$this->load->view('footer');?>