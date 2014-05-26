<?php
$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/flags.css' : 'static/css/flags.min.css').'" rel="stylesheet">');
$this->load->view('header');?>

<div class="page-header">
	<div class="row">
		<div class="col-md-8">
			<h1><?php echo icon('users').$group['name'];?>代表团</h1>
		</div>
		<?php $this->ui->js('footer', 'nav_menu_top();
		$(window).resize(function($){
			nav_menu_top();
		});');?>
		<div class="col-md-4 menu-tabs">
			<ul class="nav nav-tabs nav-menu">
				<li class="active"><a href="#member" data-toggle="tab">团队成员</a></li>
				<li><a href="#hd" data-toggle="tab">领队信息</a></li>
			</ul>
		</div>
	</div>
</div>

<div class="row">
	<div class="tab-content">
		<div class="tab-pane active" id="member">
			<div class="col-md-12">
				<p>您是<?php echo icon('users', false).$group['name'];?>代表团成员，当前代表团中有 <strong><?php echo count($delegates);?></strong> 位成员。</p>
				<table class="table table-bordered table-striped table-hover">
					<thead>
						<tr>
							<th>姓名</th>
							<th>类型</th>
							<th>电子邮箱</th>
							<th>手机</th>
							<th>委员会</th>
							<th>席位</th>
							<?php if($head_delegate) { ?><th>申请状态</th><?php } ?>
						</tr>
					</thead>
					<tbody><?php foreach($delegates as $one) { ?>
						<tr>
							<td><?php if($group['head_delegate'] == $one['id'])
								echo '<span class="label label-primary">领队</span> ';
							echo $one['name'];?></td>
							<td><?php echo $one['application_type_text'];?></td>
							<td><?php echo $one['email'];?></td>
							<td><?php echo $one['phone'];?></td>
							<td><?php if(isset($one['seat'])) echo $one['committee']['name']; ?></td>
							<td><?php if(isset($one['seat']) && !empty($one['seat']['iso']))
								echo flag($one['seat']['iso'], true).$one['seat']['name'];
							?></td>
							<?php if($head_delegate) { ?><td><?php echo "<span class='label label-{$one['status_class']}'>{$one['status_text']}</span>";?></td><?php } ?>
						</tr>
					<?php } ?></tbody>
				</table>
			</div>
		</div>
		
		<div class="tab-pane" id="hd">
			<div class="col-md-8">
				<h3>领队信息</h3>
				<?php
				if(!empty($group['head_delegate']))
				{
					$head_profile = $delegates[$group['head_delegate']];
				?><table class="table table-bordered table-striped table-hover">
					<tbody>
						<?php $rules = array(
							'name' => '姓名',
							'application_type_text' => '参会类型',
							'email' => '电子邮箱地址',
							'phone' => '电话号码',
						) + option('profile_list_head_delegate', array());
						foreach($rules as $rule => $text) { ?><tr>
							<td><?php echo $text;?></td>
							<td><?php if(!empty($head_profile[$rule])) echo $head_profile[$rule];?></td>
						</tr><?php } ?>
					</tbody>
				</table><?php } else { ?><p>当前代表团无领队，请联系管理员添加领队。</p><?php } ?>
			</div>
			
			<div class="col-md-4">
				<h3>帮助提示</h3>
				<?php if(!$head_delegate) { ?>
				<p>团队领队将会负责<?php echo icon('users', false).$group['name'];?>代表团的信息沟通和其他事项安排。</p>
				<p>在申请流程中，如果遇到任何问题，您可以通过提供的信息与<?php echo icon('user', false).$head_profile['name'];?>联系解决。</p>
				<?php echo mailto($head_profile['email'], icon('envelope-o')."联系{$head_profile['name']}", array('class' => 'btn btn-primary'));
				} else { ?>
				<p>作为团队领队，您将负责<?php echo icon('users', false).$group['name'];?>代表团的信息沟通和其他事项安排。</p>
				<p>如团队成员的申请遇到问题，我们会优先与您联系。</p>
				<?php } ?>
			</div>
		</div>
	</div>
</div>

<?php $this->load->view('footer');?>