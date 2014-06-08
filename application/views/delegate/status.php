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

<div class="row">
	<div class="col-md-12" align="center">
		<div class="wizard" style="padding-top: 42px; padding-bottom: 20px;">
			<?php foreach($wizard as $step => $one)
			{
				$attr = 'data-html="1" data-placement="bottom" data-trigger="click" data-original-title="'.$one['text'].'" data-toggle="popover" data-content="'.$one['intro'].'"';
				if($one['current'])
					$attr .= ' class="status_intro current"';
				else
					$attr .= ' class="status_intro"';
				
				$now = $step + 1;
				$label = $one['current'] ? 'default' : 'primary';
				$text = "<span class=\"label label-$label\">{$now}</span> {$one['text']}";
				echo "<a $attr>$text</a>";
			}
			$this->ui->js('footer', "$('.status_intro').popover();");
			?>
		</div>
	</div>
</div>

<div class="row" style="padding-top: 42px;">
	<div class="col-md-4">
		<div id="ui-news" class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title"><?php echo icon('info');?>申请状态</h3>
			</div>
			<div class="panel-body flags-16">
				<?php
				foreach($wizard as $step => $one)
				{
					if($one['current'])
						echo $one['intro'];
				} ?>
			</div>
		</div>
	</div>
	
	<div class="col-md-4">
		<?php if($feed_enable) { ?><div id="ui-news" class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title"><?php echo icon('globe');?>会议新闻</h3>
			</div>
			<div class="list-group">
				<?php foreach($feed as $num => $news) { ?><a href="<?php echo $news['link'];?>" target="_blank" class="list-group-item">
					<h4 class="list-group-item-heading"><?php echo $news['title'];?> <small><?php echo nicetime(strtotime($news['date']));?></small></h4>
					<div class="list-group-item-text">
						<?php echo character_limiter(strip_tags(empty($news['content']) ? $news['description'] : $news['content']), 300);?>
					</div>
				</a><?php } ?>
			</div>
		</div><?php } ?>
	</div>
	
	<div class="col-md-4">
		<div id="ui-news" class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title"><?php echo icon('user');?>个人信息</h3>
			</div>
			<div class="panel-body flags-16" style="position: relative;">
				<a class="thumbnail" style="width: 90px; height: 90px; position: absolute; margin-top: 2px;">
					<?php echo avatar($delegate['id'], 80, 'img');?>
				</a>
				<p style="margin-bottom: 4px; margin-left: 104px;"><strong><?php echo $delegate['name'];?></strong></p>
				<p style="margin-bottom: 4px; margin-left: 104px;"><?php echo $delegate['application_type_text'];?></p>
				<?php
				if(!empty($seat))
				{
					echo '<p style="margin-bottom: 4px; margin-left: 104px;">'.flag($seat['iso'], true)."{$seat['name']}，{$seat['committee']['name']}委员会</p>";
				}
				
				if(!empty($group))
				{
					echo "<p style=\"margin-bottom: 4px; margin-left: 104px;\">{$group['name']}代表团</p>";
				} ?>
				<br />
				<p style="margin-bottom: 4px; margin-left: 104px;"><strong>联系方式</strong> <?php echo anchor('account/settings/home', icon('edit', false));?></p>
				<p style="margin-bottom: 4px; margin-left: 104px;"><?php echo icon('envelope-o').$delegate['email'];?></p>
				<p style="margin-bottom: 4px; margin-left: 104px;"><?php echo icon('phone').$delegate['phone'];?></p>
			</div>
		</div>
	</div>
</div>

<?php $this->load->view('footer');?>
