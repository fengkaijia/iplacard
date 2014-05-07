<?php $this->load->view('header');?>

<div class="page-header">
	<div class="row">
		<div class="col-md-8">
			<h1 style="position: relative;">
				<a href="<?php echo base_url('account/settings/home#avatar');?>" class="thumbnail" style="width: 50px; height: 50px; position: absolute; margin-top: -2px;">
					<?php echo avatar($delegate['id'], 40, 'img');?>
				</a>
				<span style="margin-left: 58px;">个人信息</span>
			</h1>
		</div>
		<?php $this->ui->js('footer', 'nav_menu_top();
		$(window).resize(function($){
			nav_menu_top();
		});');?>
		<div class="col-md-4 menu-tabs">
			<ul class="nav nav-tabs nav-menu">
				<li class="active"><a href="#application" data-toggle="tab">基本信息</a></li>
				<li><a href="#academic" data-toggle="tab">学术信息</a></li>
			</ul>
		</div>
	</div>
</div>

<div class="row">
	<div class="col-md-8">
		<div class="tab-content">
			<div class="tab-pane active" id="application">
				<h3>个人信息</h3>
				<table class="table table-bordered table-striped table-hover">
					<tbody>
						<?php $rules = array(
							'name' => '姓名',
							'email' => '电子邮箱地址',
							'phone' => '电话号码',
							'application_type_text' => '申请类型',
							'status_text' => '申请状态',
						) + option('profile_list_general', array()) + option("profile_list_{$delegate['application_type']}", array());
						foreach($rules as $rule => $text) { ?><tr>
							<td><?php echo $text;?></td>
							<td><?php if(!empty($delegate[$rule])) echo $delegate[$rule];?></td>
						</tr><?php } ?>
					</tbody>
				</table>
			</div>
			
			<div class="tab-pane" id="academic">
				<?php if(!empty($delegate['experience'])) { ?><h3>参会经历</h3>
				<table class="table table-bordered table-striped table-hover">
					<thead>
						<?php $rules = option('profile_list_experience');
						foreach($rules as $rule => $text) { ?><th><?php echo $text;?></th><?php } ?>
					</thead>
					<tbody>
						<?php foreach($delegate['experience'] as $experience) { ?>
						<tr><?php foreach($rules as $rule => $text) { ?>
							<td><?php echo $experience[$rule];?></td><?php } ?>
						</tr><?php } ?>
					</tbody>
				</table><?php } ?>

				<?php if(!empty($delegate['club'])) { ?><h3>社会活动</h3>
				<table class="table table-bordered table-striped table-hover">
					<thead>
						<?php $rules = option('profile_list_club');
						foreach($rules as $rule => $text) { ?><th><?php echo $text;?></th><?php } ?>
					</thead>
					<tbody>
						<?php foreach($delegate['club'] as $experience) { ?>
						<tr><?php foreach($rules as $rule => $text) { ?>
							<td><?php echo $experience[$rule];?></td><?php } ?>
						</tr><?php } ?>
					</tbody>
				</table><?php } ?>

				<?php if(!empty($delegate['test'])) { ?><h3 id="test">学术测试</h3>
				<table class="table table-bordered table-striped table-hover">
					<tbody>
						<?php $questions = option('profile_list_test');
						foreach($questions as $qid => $question) { ?>
						<tr><td><?php echo $question;?></td></tr>
						<tr><td><?php echo nl2br($delegate['test'][$qid]);?></td></tr><?php } ?>
					</tbody>
				</table><?php } ?>
			</div>
		</div>
	</div>
	
	<div class="col-md-4">
		<h3>编辑信息</h3>
		<p>由于会务变动需要，您提交的申请单中可能并没有包含全部的申请及会务信息。随着会议准备工作的进行，我们将会不断添加更多信息编辑请求。</p>
		<?php
		$editable = option('profile_edit_general', array()) + option("profile_edit_{$delegate['application_type']}", array());
		if(!empty($editable)) { ?>
		<p>当前有 <strong><?php echo count($editable);?></strong> 项信息可编辑。</p>
		<p><a class="btn btn-primary" href="<?php echo base_url('apply/edit');?>"><?php echo icon('edit');?>编辑信息</a></p>
		<?php } else { ?><p>当前没有信息需要编辑。</p><?php } ?>
		<p>当有新的附加信息请求时 iPlacard 将会以邮件方式通知您。</p>
	</div>
</div>

<?php $this->load->view('footer');?>