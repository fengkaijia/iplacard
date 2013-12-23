<?php
$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/jquery.jcrop.css' : 'static/css/jquery.jcrop.min.css').'" rel="stylesheet">');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/jquery.jcrop.js' : 'static/js/jquery.jcrop.min.js').'"></script>');
$this->ui->html('header', "<style>
#preview-pane .preview-container {
	max-width: 240px !important;
	max-height: 240px !important;
	overflow: hidden;
}
</style>");
$this->load->view('header');?>

<div class="page-header">
	<h1>设置头像</h1>
</div>

<div class="row">
	<div class="col-md-3">
		<?php $this->load->view('account/manage/sidebar', array('now' => 'detail'));?>
	</div>
	
	<div class="col-md-9">
		<?php echo form_open('account/settings/avatar/crop', array('class' => 'well form-horizontal'), array('x' => NULL, 'y' => NULL, 'w' => NULL, 'h' => NULL, 'crop_avatar' => true));?>
			<?php echo form_fieldset('图像调整'); ?>
				<p>请根据需要对上传的图像进行裁剪，裁剪完成后点击提交更改按钮保存图像。</p>
		
				<div class="row">
					<div class="col-md-8">
						<img style="width: 100%;" src="<?php echo base_url($path.$filename)?>" id="target" alt="原始图像" />
					</div>
					
					<div class="col-md-4">
						<div class="thumbnail">
							<div id="preview-pane">
								<div class="preview-container">
									<img style="width: 100%;" src="<?php echo base_url($path.$filename)?>" class="jcrop-preview" alt="效果预览" />
								</div>
							</div>
						</div>
						
						<?php
						echo form_button(array(
							'name' => 'confirm',
							'content' => '提交更改',
							'type' => 'submit',
							'class' => 'btn btn-primary',
							'onclick' => 'loader(this);'
						));
						echo ' ';
						echo form_button(array(
							'name' => 'confirm',
							'content' => '放弃设置',
							'type' => 'button',
							'class' => 'btn btn-default',
							'onclick' => onclick_redirect('account/settings/home')
						));?>
					</div>
				</div>
			<?php echo form_fieldset_close();?>
		<?php echo form_close();?>
	</div>
</div>

<?php
$crop_js = <<<EOT
$(document).ready(function() {
	$('#preview-pane .preview-container').css({'height': $('#preview-pane .preview-container').width()});
	$('#preview-pane .preview-container .jcrop-preview').css({'height': $('#preview-pane .preview-container .jcrop-preview').width()});
} );
	
$(function($){
	var boundx,
		boundy,
		pcnt = $('#preview-pane .preview-container'),
		pimg = $('#preview-pane .preview-container img'),
		xsize = pcnt.width(),
		ysize = pcnt.height();

	$('#target').Jcrop({
		trueSize: [{$width}, {$height}],
		onChange: updatePreview,
		onSelect: updatePreview,
		bgOpacity: 0.5,
		aspectRatio: xsize / ysize
	},function(){
		var bounds = this.getBounds();
		boundx = bounds[0];
		boundy = bounds[1];
	});

	function updatePreview(c) {
		if (parseInt(c.w) > 0) {
			var rx = xsize / c.w;
			var ry = ysize / c.h;
				
			$('input[name=x]').val(c.x);
			$('input[name=y]').val(c.y);
			$('input[name=w]').val(c.w);
			$('input[name=h]').val(c.h);

			pimg.css({
				width: Math.round(rx * boundx) + 'px',
				height: Math.round(ry * boundy) + 'px',
				marginLeft: '-' + Math.round(rx * c.x) + 'px',
				marginTop: '-' + Math.round(ry * c.y) + 'px'
			});
		}
	}
});
EOT;
$this->ui->js('footer', $crop_js);

$this->load->view('footer');?>