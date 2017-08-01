<?php
$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/bootstrap.select.css' : 'static/css/bootstrap.select.min.css').'" rel="stylesheet">');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/bootstrap.select.js' : 'static/js/bootstrap.select.min.js').'"></script>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/locales/bootstrap.select.locale.js' : 'static/js/locales/bootstrap.select.locale.min.js').'"></script>');
$this->load->view('header');?>

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
		<?php $this->ui->js('footer', 'nav_menu_switch();
		nav_menu_top();
		$(window).resize(function($){
			nav_menu_top();
		});');?>
		<div class="col-md-4 menu-tabs">
			<ul class="nav nav-tabs nav-menu">
				<li class="active"><a href="#application" data-toggle="tab">基本信息</a></li>
				<li><a href="#academic" data-toggle="tab">学术信息</a></li>
				<?php if(!empty($addition)) { ?><li id="addition_tab"><a href="#addition" data-toggle="tab">附加信息</a></li><?php } ?>
			</ul>
		</div>
	</div>
</div>

<div class="menu-pills"></div>

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

				<?php
				$extra = option('profile_block', array());
				if(!empty($extra))
				{
					foreach($extra as $block => $title) { ?><h3><?php echo $title;?></h3>
				<table class="table table-bordered table-striped table-hover">
					<tbody>
					<?php
					foreach(option("profile_list_{$block}", array()) as $rule => $text) { ?><tr>
							<td><?php echo $text;?></td>
							<td><?php if(!empty($delegate[$rule])) echo $delegate[$rule];?></td>
						</tr><?php } ?>
					</tbody>
				</table><?php } } ?>
			</div>
			
			<div class="tab-pane" id="academic">
				<?php if(!empty($delegate['experience'])) { ?><h3>参会经历</h3>
				<table class="table table-bordered table-striped table-hover">
					<thead>
						<?php $rules = option('profile_list_experience', array());
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
						<?php $rules = option('profile_list_club', array());
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
						<?php
						$show_empty = option('profile_show_test_all', false);
						$questions = option('profile_list_test', array());
						foreach($questions as $qid => $question)
						{
							if($show_empty || !empty($delegate['test'][$qid])) { ?>
							<tr><td><?php echo $question;?></td></tr>
							<tr><td><?php echo nl2br($delegate['test'][$qid]);?></td></tr><?php }
						} ?>
					</tbody>
				</table><?php } ?>
			</div>
			
			<div class="tab-pane" id="addition">
				<h3>可编辑附加信息</h3>
				<p>您可以修改以下信息，这类信息通常为住宿、餐饮登记，它们将可能在一定时间后，例如在向宾馆提交名单前停止接受修改，请尽快完成信息填写。修改完成后请单击保存。</p>
				<?php
				echo form_open_multipart('apply/profile/edit', array('class' => 'form-horizontal'), array('edit' => true));
					foreach($addition as $name => $item) { ?><div class="form-group <?php if(form_has_error("addition_$name")) echo 'has-error';?>">
						<?php echo form_label($item['title'], "addition_$name", array('class' => 'col-lg-3 control-label'));?>
						<div class="col-lg-9">
							<?php switch($item['type'])
							{
								case 'checkbox':
									echo '<div class="checkbox" style="margin-bottom: 10.5px;"><label>'.form_checkbox(array(
										'name' => "addition_$name",
										'id' => "addition_$name",
										'value' => true,
										((isset($item['enabled']) && !$item['enabled']) || (isset($item['invoice']) && isset($delegate["addition_$name"]))) ? 'disabled' : 'enabled' => NULL,
										'checked' => set_value("addition_$name", isset($delegate["addition_$name"]) ? $delegate["addition_$name"] : $item['default'])
									)).' '.$item['text'].'</label></div>';
									
									if(form_has_error("addition_$name"))
										echo form_error("addition_$name");
									break;
									
								case 'choice':
									$subtexts = array();
									$disabled = array();
									if(isset($item['current']) && !empty($item['current']))
									{
										foreach($item['item'] as $key => $value)
										{
											if(isset($item['current'][$key]))
											{
												$sub_data = array(
													'max' => $item['max'][$key],
													'current' => $item['current'][$key],
													'left' => $item['max'][$key] - $item['current'][$key],
												);

												$subtexts[$key] = $this->parser->parse_string(isset($item['display']) ? $item['display'] : '{current}', $sub_data, true);

												if($item['max'][$key] <= $item['current'][$key])
													$disabled[] = $key;
											}
										}
									}

									echo form_dropdown_select("addition_$name", empty($item['item']) ? array('' => '选项为空') : $item['item'], set_value("addition_$name", isset($delegate["addition_$name"]) ? $delegate["addition_$name"] : $item['default']), count($item['item']) >= 10, array(), $subtexts, array(), $disabled, 'selectpicker', ((isset($item['enabled']) && !$item['enabled']) || (isset($item['invoice']) && isset($delegate["addition_$name"]))) ? 'disabled data-width="100%"' : 'enabled data-width="100%"');
									
									if(form_has_error("addition_$name"))
										echo form_error("addition_$name");
									elseif(!empty($item['text']))
										echo "<div class=\"help-block\">{$item['text']}</div>";
									break;
								
								case 'textarea':
									echo form_textarea(array(
										'name' => "addition_$name",
										'id' => "addition_$name",
										'class' => 'form-control',
										'rows' => 4,
										(isset($item['enabled']) && !$item['enabled']) ? 'disabled' : 'enabled' => NULL,
										'value' => set_value("addition_$name", isset($delegate["addition_$name"]) ? $delegate["addition_$name"] : $item['default'])
									));
									
									if(form_has_error("addition_$name"))
										echo form_error("addition_$name");
									elseif(!empty($item['text']))
										echo "<div class=\"help-block\">{$item['text']}</div>";
									break;
							} ?>
						</div>
					</div><?php } ?>
					
					<div class="form-group">
						<div class="col-lg-9 col-lg-offset-3">
							<?php echo form_button(array(
								'name' => 'submit',
								'content' => '保存信息',
								'type' => 'submit',
								'class' => 'btn btn-primary',
								'onclick' => 'loader(this);'
							));
							if($invoice_notice) { ?><div class="help-block">部分附加信息将用于生成对应的账单，此类信息将无法在首次保存后更改。</div><?php } ?>
						</div>
					</div>
				<?php echo form_close();?>
			</div>
		</div>
	</div>
	
	<div class="col-md-4">
		<h3>编辑信息</h3>
		<p>由于会务变动需要，您提交的申请单中可能并没有包含全部的申请及会务信息。随着会议准备工作的进行，我们将会不断添加更多信息编辑请求。</p>
		<?php if(!empty($addition)) { ?>
		<p>当前有 <strong><?php echo count($addition);?></strong> 项信息可以编辑，请尽快完成填写。</p>
		<p><a class="btn btn-primary" href="#addition" data-toggle="tab" onclick="$('.nav-menu li').removeClass('active'); $('#addition_tab').addClass('active');"><?php echo icon('edit');?>编辑信息</a></p>
		<?php } else { ?><p>当前没有信息需要编辑。</p><?php } ?>
	</div>
</div>

<?php
$this->ui->js('footer', "$('.selectpicker').selectpicker();");

$this->load->view('footer');?>