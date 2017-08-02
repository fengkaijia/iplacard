<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 知识库编辑视图
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
 * @package iPlacard
 * @since 2.0
 */

$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/summernote.js' : 'static/js/summernote.min.js').'"></script>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/locales/summernote.locale.js' : 'static/js/locales/summernote.locale.min.js').'"></script>');
$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/summernote.css' : 'static/css/summernote.min.css').'" rel="stylesheet">');
$this->load->view('header');?>

<div class="page-header">
	<h1><?php echo $action == 'add' ? '添加知识库文章' : icon('book').$article['title'];?></h1>
</div>

<div class="row">
	<div class="col-md-3">
		<div class="panel panel-default">
			<div class="panel-heading">帮助</div>
			<div class="panel-body"><?php if($action == 'edit') { ?>
				<p style="margin-bottom: 0;">您可以通过此页面编辑知识库文章。</p>
				<?php } else { ?>
				<p style="margin-bottom: 0;">您可以通过此页面添加知识库文章。</p>
			<?php } ?></div>
		</div>
	</div>
	
	<div class="col-md-9">
		<?php echo form_open($action == 'add' ? 'knowledgebase/edit' : "knowledgebase/edit/{$article['id']}", array('class' => 'well form-horizontal'));?>
			<?php echo form_fieldset('文章信息'); ?>
				<div class="form-group <?php if(form_has_error('title')) echo 'has-error';?>">
					<?php echo form_label('标题', 'title', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-6">
						<?php echo form_input(array(
							'name' => 'title',
							'id' => 'title',
							'class' => 'form-control',
							'value' => set_value('title', $action == 'add' ? '' : $article['title']),
							'required' => NULL,
						));
						if(form_has_error('title'))
							echo form_error('title');
						else { ?><div class="help-block">知识库文章标题。</div><?php } ?>
					</div>
				</div>
		
				<div class="form-group <?php if(form_has_error('content')) echo 'has-error';?>">
					<?php echo form_label('内容', 'content', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-9">
						<?php echo form_textarea(array(
							'name' => 'content',
							'id' => 'content',
							'class' => 'summernote',
							'rows' => 4,
							'value' => set_value('content', $action == 'add' ? '' : $article['content']),
						));
						if(form_has_error('content'))
							echo form_error('content');
						?>
					</div>
				</div>
			<?php echo form_fieldset_close();?>
		
			<br />
		
			<?php echo form_fieldset('显示设置'); ?>
				<div class="form-group <?php if(form_has_error('kb')) echo 'has-error';?>">
					<?php echo form_label('知识库编号', 'kb', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-4">
						<?php echo form_input(array(
							'name' => 'kb',
							'id' => 'kb',
							'class' => 'form-control',
							'value' => set_value('kb', $action == 'add' ? $random_kb : $article['kb']),
							$action == 'add' ? 'required' : 'disabled' => NULL,
						));
						if(form_has_error('kb'))
							echo form_error('kb');
						else { ?><div class="help-block"><?php echo $action == 'add' ? '与已有编号不重复的 5 ~ 7 位数字。' : '知识库编号无法修改。';?></div><?php } ?>
					</div>
				</div>
			
				<div class="form-group <?php if(form_has_error('order')) echo 'has-error';?>">
					<?php echo form_label('文章排序', 'order', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-4">
						<?php echo form_input(array(
							'name' => 'order',
							'id' => 'order',
							'class' => 'form-control',
							'value' => set_value('order', $action == 'add' ? '0' : $article['order']),
							'required' => NULL,
						));
						if(form_has_error('order'))
							echo form_error('order');
						else { ?><div class="help-block">数字越小在知识库首页显示越靠前。</div><?php } ?>
					</div>
				</div>
			<?php echo form_fieldset_close();?>
			
			<div class="form-group">
				<div class="col-lg-10 col-lg-offset-2">
					<?php echo form_button(array(
						'name' => 'submit',
						'content' => $action == 'add' ? '添加文章' : '编辑文章',
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
							'content' => '删除文章',
							'type' => 'button',
							'class' => 'btn btn-danger',
							'data-toggle' => 'modal',
							'data-target' => '#delete_article',
						));
					} ?>
				</div>
			</div>
		<?php echo form_close();
		
		if($action == 'edit')
		{
			echo form_open("knowledgebase/delete/{$article['id']}", array(
				'class' => 'modal fade form-horizontal',
				'id' => 'delete_article',
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
						<h4 class="modal-title" id="delete_label">删除知识库文章</h4>
					</div>
					<div class="modal-body">
						<p>您将删除知识库文章 <?php echo icon('book', false)."KB{$article['kb']}：{$article['title']}";?>。请输入您的登录密码并点击确认删除按钮继续操作。</p>

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
$this->ui->js('footer', $summernote_js);

$this->load->view('footer');?>