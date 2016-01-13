<?php $this->load->view('header');?>

<div class="page-header">
	<h1>帐户活动管理</h1>
</div>

<div class="row">
	<div class="col-md-3">
		<?php $this->load->view('account/manage/sidebar', array('now' => 'activity'));?>
	</div>
	
	<div class="col-md-9">
		<?php echo form_open('account/activity', array('class' => 'well form-horizontal'), array('halt' => true));?>
			<?php echo form_fieldset('当前会话活动'); ?>
				<?php if(!empty($active)) { ?><p>页面列出了目前所有正在进行的活动信息，这些活动可能是您在其他计算机中登录的。</p>
				<table class="table table-bordered table-hover">
					<thead>
						<tr>
							<th>访问类型</th>
							<th>位置</th>
							<th>登录时间</th>
						</tr>
					</thead>
					<tbody><?php foreach($active as $one) { ?>
						<tr>
							<td><?php
							$device_name = $one['value']['type'] == 'desktop' ? '桌面设备' : '移动设备';
							
							if(!empty($one['value']['platform']))
							{
								$platform = false;
								if(substr($one['value']['platform'], 0, 7) == 'Windows')
									$platform = 'windows';
								elseif(in_array($one['value']['platform'], array('Linux', 'Debian', 'GNU/Linux'))) //Go, go, go!
									$platform = 'linux';
								elseif(in_array($one['value']['platform'], array('iOS', 'Mac OS X', 'Power PC Mac', 'Macintosh')))
									$platform = 'apple';
								elseif($one['value']['platform'] == 'Android')
									$platform = 'android';
								
								if(!$platform)
									$platform_icon = '';
								else
									$platform_icon = icon($platform, false);
							}
							
							if(!empty($one['value']['browser']))
							{
								$browser = false;
								if($one['value']['browser'] == 'Internet Explorer')
									$browser = 'internet-explorer';
								elseif($one['value']['browser'] == 'Chrome')
									$browser = 'chrome';
								elseif($one['value']['browser'] == 'Firefox')
									$browser = 'firefox';
								elseif($one['value']['browser'] == 'Safari')
									$browser = 'safari';
								elseif($one['value']['browser'] == 'Opera')
									$browser = 'opera';
								elseif($one['value']['browser'] == 'Spartan')
									$browser = 'edge';
								
								if(!$browser)
									$browser_icon = '';
								else
									$browser_icon = icon($browser, false);
							}
							
							if(!empty($one['value']['browser']) && !empty($one['value']['platform']))
								echo icon($one['value']['type'])."{$device_name}（使用 {$platform_icon}{$one['value']['platform']} 操作系统的 {$browser_icon}{$one['value']['browser']} 浏览器）";
							elseif(!empty($one['value']['browser']))
								echo icon($one['value']['type'])."{$device_name}（使用 {$browser_icon}{$one['value']['browser']} 浏览器）";
							elseif(!empty($one['value']['platform']))
								echo icon($one['value']['type'])."{$device_name}（使用 {$platform_icon}{$one['value']['platform']} 操作系统）";
							else
								echo icon($one['value']['type'])."{$device_name}";
							
							if(isset($one['current']) && $one['current'] == true)
								echo ' <span class="label label-primary">本机</span>';
							?></td>
							<td><?php
							if($one['place'])
								echo "{$one['place']}（{$one['ip']}）";
							else
								echo $one['ip'];
							?></td>
							<td><?php echo date('n月j日 H:i', $one['time'])?>（<?php echo nicetime($one['time']);?>）</td>
						</tr>
					<?php } ?></tbody>
				</table><?php } ?>
			<?php echo form_fieldset_close();?>

			<div class="form-group">
				<div class="col-lg-12">
					<p>如果您认为在其他位置登录的会话存在异常活动，可以退出这些会话。</p>
					<?php echo form_button(array(
						'name' => 'submit',
						'content' => '退出其他所有会话',
						'type' => 'submit',
						'class' => 'btn btn-primary',
						'onclick' => 'loader(this);'
					));?>
				</div>
			</div>
		<?php echo form_close();?>
	</div>
</div>

<?php $this->load->view('footer');?>
