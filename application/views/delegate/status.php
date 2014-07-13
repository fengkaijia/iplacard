<?php
if($delegate['application_type'] == 'delegate')
	$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/flags.css' : 'static/css/flags.min.css').'" rel="stylesheet">');

if($lock_open)
	$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/jquery.shorten.js': 'static/js/jquery.shorten.min.js').'"></script>');
$this->load->view('header');?>

<?php if($welcome) { ?><div id="welcome" class="jumbotron" style="margin-top: 20px; margin-bottom: 20px;">
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
		<div class="wizard" style="padding-top: 42px; padding-bottom: 62px;">
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

<div class="row">
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
		
		<?php if($lock_open) { ?><div id="ui-news" class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title"><?php echo icon('lock');?>确认锁定</h3>
			</div>
			<div class="panel-body flags-16">
				<p>现可以确认申请完成并锁定您的席位，锁定后将不会发生变动。</p>
				<p style="margin-bottom: 0;"><a class="btn btn-primary" href="#" data-toggle="modal" data-target="#lock">立即锁定申请</a></p>
			</div>
		</div>
			
		<?php echo form_open("apply/status/lock", array(
			'class' => 'modal fade form-horizontal',
			'id' => 'lock',
			'tabindex' => '-1',
			'role' => 'dialog',
			'aria-labelledby' => 'lock_label',
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
						<h4 class="modal-title" id="lock_label">确认锁定申请</h4>
					</div>
					<div class="modal-body flags-16">
						<p><?php echo sprintf('您将确认申请完成并锁定席位 %1$s，%2$s为您的席位。席位锁定后您将无法调整您的席位和增加席位候选，并且您的面试官也将无法再向您追加席位分配。',
							flag($seat['iso'], false).$seat['name'],
							$seat['committee']['name']
						);?></p>
						
						<?php if(!empty($backorders)) { ?><p>同时，您的以下 <?php echo count($backorders);?> 项席位候选也将失效。</p>
						
						<table id="backorder_list" class="table table-striped table-bordered table-hover table-responsive">
							<thead>
								<tr>
									<th>席位名称</th>
									<th>委员会</th>
									<th>候选时间</th>
								</tr>
							</thead>

							<tbody>
								<?php foreach($backorders as $backorder) { ?><tr>
									<td><?php echo flag($backorder['seat']['iso'], true).'<span class="shorten">'.$backorder['seat']['name'].'</span>';?></td>
									<td><?php echo $backorder['seat']['committee']['name'];?></td>
									<td><?php echo sprintf('%1$s（%2$s）', date('n月j日', $backorder['order_time']), nicetime($backorder['order_time']));?></td>
								</tr><?php } ?>
							</tbody>
						</table>
						<?php } ?>

						<p><?php
						if(is_sudo())
							echo '席位锁定后无法重新解锁，您将以 SUDO 授权锁定代表申请，请输入您管理员帐号的密码并点击确认锁定按钮以继续。';
						else
							echo '席位锁定后无法重新解锁，请输入您的登录密码并点击确认锁定按钮以继续。';
						?></p>
						
						<div class="form-group <?php if(form_has_error('password')) echo 'has-error';?>">
							<?php echo form_label(is_sudo() ? '管理员密码' : '登录密码', 'password', array('class' => 'col-lg-3 control-label'));?>
							<div class="col-lg-5">
								<?php echo form_password(array(
									'name' => 'password',
									'id' => 'password',
									'class' => 'form-control',
									'required' => NULL
								));
								echo form_error('password');?>
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
							'content' => '确认锁定',
							'type' => 'submit',
							'class' => 'btn btn-primary',
							'onclick' => 'loader(this);'
						)); ?>
					</div>
				</div>
			</div>
		<?php echo form_close(); } ?>
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
					echo '<p style="margin-bottom: 4px; margin-left: 104px;">'.flag($seat['iso'], true)."{$seat['name']}，{$seat['committee']['name']}</p>";
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

<?php
$read_more = icon('caret-right', false);
$read_less = icon('caret-left', false);
$shorten_js = <<<EOT
$('.shorten').shorten({
	showChars: '20',
	moreText: '{$read_more}',
	lessText: '{$read_less}'
});
EOT;
if($lock_open)
	$this->ui->js('footer', $shorten_js);

$this->load->view('footer');?>
