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
							<td><?php if(isset($one['current']) && $one['current'] == true) { ?><span class="label label-primary">本机</span><?php } ?>
							<?php if($one['value']['type'] == 'desktop')
							{
								if(!empty($one['value']['browser']))
									echo "桌面版（使用{$one['value']['browser']}浏览器）";
								else
									echo "桌面版";
							}
							else
								echo "移动设备";
							?></td>
							<td><?php $ip = hide_ip($one['value']['ip']);
							if($one['value']['place'])
								echo "{$one['value']['place']}（{$ip}）";
							else
								echo $ip;
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
						'class' => 'btn btn-primary'
					));?>
				</div>
			</div>
		<?php echo form_close();?>
	</div>
</div>

<?php $this->load->view('footer');?>