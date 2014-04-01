<?php
if($delegate['application_type'] == 'delegate')
	$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/flags.css' : 'static/css/flags.min.css').'" rel="stylesheet">');
$this->load->view('header');?>

<?php if($welcome) { ?><div id="welcome" class="jumbotron" style="margin-top: 42px;">
	<h1 style="font-size: 56px;">欢迎使用 iPlacard</h1>
	<p>全新的下一代模拟联合国会议管理系统已经为您准备就绪，立即开始使用。</p>
	<div class="form-inline">
		<div class="form-group">
			<a class="btn btn-primary btn-lg" onclick="dismiss_welcome();">开始使用</a>
		</div>
		<div class="checkbox" style="padding-left: 10px;">
			<label>
				<input id="remember_dismiss" type="checkbox" checked> 不再显示此欢迎提示
			</label>
		</div>
	</div>
	<p></p>
	<?php
	$welcome_dismiss_url = base_url('apply/ajax/dismiss_welcome');
	$welcome_js = "function dismiss_welcome()
	{
		if($('#remember_dismiss').is(':checked')) {
			$.ajax({
				url: '{$welcome_dismiss_url}',
				async: true,
				dataType : 'json'
			});
		}

		$('#welcome').hide();
	}";
	$this->ui->js('footer', $welcome_js);
	?>
</div><?php } ?>

<?php $this->load->view('footer');?>
