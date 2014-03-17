<?php
$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/flags.css' : 'static/css/flags.min.css').'" rel="stylesheet">');
$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/bootstrap.select.css' : 'static/css/bootstrap.select.min.css').'" rel="stylesheet">');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/bootstrap.select.js' : 'static/js/bootstrap.select.min.js').'"></script>');
$this->load->view('header');?>

<div class="page-header">
	<h1><?php echo $action == 'add' ? $preset ? '添加子席位' : '添加席位' : $seat['name'];?></h1>
</div>

<div class="row">
	<div class="col-md-3">
		<div class="panel panel-default">
			<div class="panel-heading">帮助</div>
			<div class="panel-body"><?php if($action == 'edit') { ?>
				<p>您可以通过此页面修改席位信息。</p>
				<p style="margin-bottom: 0;">当席位已经分配后，原则上不建议修改席位信息。</p>
				<?php } else { ?>
				<p>您可以通过此页面添加席位。</p>
				<p style="margin-bottom: 0;">iPlacard 通过主席位和子席位关系处理多代席位问题，例如要添加一个四代席位，可通过选择本次添加的席位为主席位并设置自动添加 3 个相同属性的子席位来实现。</p>
			<?php } ?></div>
		</div>
	</div>
	
	<div class="col-md-9">
		<?php echo form_open($action == 'add' ? (!$preset ? 'seat/edit' : "seat/edit/?primary={$seat['primary']}") : "seat/edit/{$seat['id']}", array('class' => 'well form-horizontal'));?>
			<?php echo form_fieldset('席位基础信息'); ?>
				<div class="form-group <?php if(form_has_error('name')) echo 'has-error';?>">
					<?php echo form_label('席位名称', 'name', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-4">
						<?php echo form_input(array(
							'name' => 'name',
							'id' => 'name',
							'class' => 'form-control',
							'value' => set_value('name', $action == 'add' && !$preset ? '' : $seat['name']),
							'required'=> NULL,
						));
						if(form_has_error('name'))
							echo form_error('name');
						else { ?><div class="help-block">建议使用全称。</div><?php } ?>
					</div>
				</div>
				
				<?php
				//添加子席位时无法更改委员会
				if($preset)
					echo form_hidden('seat_type', $seat['type']);
				?><div class="form-group <?php if(form_has_error('committee')) echo 'has-error';?>">
					<?php echo form_label('委员会', 'committee', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-4">
						<?php
						$now_committee = set_value('committee', $action == 'add' && !$preset ? '' : $seat['committee']);
						if(!empty($committees))
							$array = array('' => '选择委员会') + $committees;
						else
							$array = array('' => '委员会为空');
						echo form_dropdown('committee', $array, $now_committee, $preset ? 'class="form-control" disabled' : 'class="form-control"');
						if(form_has_error('committee'))
							echo form_error('committee');
						else { ?><div class="help-block">席位所在的委员会。</div><?php } ?>
					</div>
				</div>
		
				<div class="form-group <?php if(form_has_error('level')) echo 'has-error';?>">
					<?php echo form_label('席位等级', 'level', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-4">
						<?php
						$array = array();
						$score_total = option('score_total', 5);
						for($i = 1; $i <= $score_total; $i++)
						{
							if($i == 1 && $score_total != 1)
								$array[$i] = "{$i}（最低）";
							elseif($i == $score_total)
								$array[$i] = "{$i}（最高）";
							else
								$array[$i] = $i;
						}
						
						$now_level = set_value('level', $action == 'add' && !$preset ? round($score_total / 2) : $seat['level']);
						
						echo form_dropdown('level', $array, $now_level, 'class="form-control"');
						if(form_has_error('level'))
							echo form_error('level');
						else { ?><div class="help-block">席位的难度等级。</div><?php } ?>
					</div>
				</div>
			<?php echo form_fieldset_close();?>
			
			<br />
			
			<?php echo form_fieldset('国家地区设置'); ?>
				<p>iPlacard 支持显示 <a href="http://www.iso.org/iso/iso-3166-1_decoding_table">ISO 3166-1</a> 国际标准中规定的国家和地区。同时，iPlacard 还支持包括联合国在内的国际组织、可能会使用的不存在或者不被承认的国家、地区。</p>
			
				<div class="form-group <?php if(form_has_error('iso')) echo 'has-error';?>">
					<?php echo form_label('国家', 'iso', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-10">
						<?php
						$now_iso = set_value('iso', $action == 'add' && !$preset ? '' : $seat['iso']);
						
						$html_text = array();
						if(!empty($iso))
						{
							foreach($iso as $code => $name)
							{
								$html_text[$code] = flag($code, false, true, false, false).$name;
							}
						}
						
						echo form_dropdown_select('iso', array('' => '请选择国家') + $iso, $now_iso, true, array(), array(), $html_text, 'selectpicker flags-16');
						if(form_has_error('iso'))
							echo form_error('iso');
						else { ?><div class="help-block">国家地区信息，可为空。</div><?php } ?>
					</div>
				</div>
			<?php echo form_fieldset_close();?>
			
			<br />
			
			<?php
			//如主席位存在子席位则禁用调整设置
			if(($action == 'edit' && $seat['type'] == 'primary') || $preset)
			{
				echo form_hidden('seat_type', $seat['type']);
				echo form_hidden('primary', $seat['primary']);
				
				echo form_fieldset('多代席位设置', array('disabled' => true));
			}
			else
				echo form_fieldset('多代席位设置'); ?>
				<p>iPlacard 支持设置多代席位。多代席位时由有一个主席位和若干个子席位组成。</p>
				
				<div id="seat_select" class="form-group <?php if(form_has_error('seat_type')) echo 'has-error';?>">
					<?php echo form_label('席位类型', 'seat_type', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-4">
						<?php
						$array = array(
							'single' => '单代席位',
							'primary' => '多代席位的主席位',
							'sub' => '多代席位的子席位'
						);
						
						//禁止单代席位或子席位改为主席位
						if($action == 'edit' && ($seat['type'] == 'sub' || $seat['type'] == 'single'))
							unset($array['primary']);
						
						echo form_dropdown('seat_type', $array, set_value('seat_type', $action == 'add' && !$preset ? 'single' : $seat['type']), 'class="form-control"');
						if(form_has_error('seat_type'))
							echo form_error('seat_type');
						elseif($action == 'edit' && $seat['type'] == 'primary') { ?><div class="help-block">当前席位因含有若干个子席位而无法调整席位类型。</div><?php }
						else { ?><div class="help-block">此席位的类型，默认为单代席位。</div><?php }
						if($action == 'add')
						{
							$seat_type_js = "$('#seat_sub').hide();
							$('#seat_primary').hide();
							$('#seat_select').change(function() {
								if($('select[name=seat_type]').val() === 'primary') {
									$('#seat_sub').show();
									$('#seat_primary').hide();
								} else if($('select[name=seat_type]').val() === 'sub') {
									$('#seat_sub').hide();
									$('#seat_primary').show();
								} else {
									$('#seat_sub').hide();
									$('#seat_primary').hide();
								}
							});";
						}
						else
						{
							$seat_type_js = "$('#seat_primary').hide();
							$('#seat_select').change(function() {
								if($('select[name=seat_type]').val() === 'sub') {
									$('#seat_primary').show();
								} else {
									$('#seat_primary').hide();
								}
							});";
						}
						$this->ui->js('footer', $seat_type_js);
						
						//编辑子席位默认显示
						if(($action == 'edit' || $preset) && $seat['type'] == 'sub')
							$this->ui->js('footer', "$('#seat_primary').show();");
						?>
					</div>
				</div>
				
				<?php if($action == 'add') { ?><div id="seat_sub" class="form-inline form-group <?php if(form_has_error('sub_num')) echo 'has-error';?>">
					<?php echo form_label('生成子席位', 'sub_num', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-10">
						自动添加 <?php echo form_input(array(
							'name' => 'sub_num',
							'id' => 'sub_num',
							'class' => 'form-control',
							'style' => 'max-width: 50px;',
							'value' => set_value('sub_num', 1),
							'required'=> NULL,
						)); ?> 个相同属性的子席位
						<?php if(form_has_error('sub_num'))
							echo form_error('sub_num');
						else { ?><div class="help-block">将会自动生成与此席位名称、委员会、等级及国家信息完全相同的子席位。如有信息不同（例如主席位为主代表、子席位为副代表）可在席位生成后编辑子席位修改。您也可以在之后创建子席位。</div><?php } ?>
					</div>
				</div><?php } ?>
			
				<div id="seat_primary" class="form-group <?php if(form_has_error('primary')) echo 'has-error';?>">
					<?php echo form_label('主席位', 'primary', array('class' => 'col-lg-2 control-label'));?>
					<div class="col-lg-10">
						<?php
						$now_seat = set_value('primary', $action == 'add' && !$preset ? '' : $seat['primary']);
						
						$seat_data = array();
						foreach($seats as $cid => $committee)
						{
							$seat_data[$cid] = $committee;
						}
						
						$option = array();
						$option_html = array();
						foreach($seat_data as $group => $list)
						{
							$groupname = $committees[$group];
							foreach($list as $one)
							{
								$option[$groupname][$one['id']] = $one['name'];
								$option_html[$one['id']] = flag($one['iso'], false, true, false, false).$one['name'];
							}
						}
						$option = array('' => '请选择主席位') + $option;
						
						echo form_dropdown_select('primary', $option, $now_seat, true, array(), array(), $option_html, 'selectpicker flags-16');
						if(form_has_error('primary'))
							echo form_error('primary');
						else { ?><div class="help-block">设置此席位的主席位，如为空，此席位将被设置为单代席位。</div><?php } ?>
					</div>
				</div>
			<?php echo form_fieldset_close();?>
		
			<div class="form-group">
				<div class="col-lg-10 col-lg-offset-2">
					<?php echo form_button(array(
						'name' => 'submit',
						'content' => $action == 'add' ? '添加席位' : '修改席位',
						'type' => 'submit',
						'class' => 'btn btn-primary',
						'onclick' => 'loader(this);'
					));
					echo ' ';
					if($action == 'edit' && in_array($seat['status'], array('unavailable', 'available', 'preserved')) && $seat['type'] != 'primary')
					{
						echo ' ';
						echo form_button(array(
							'name' => 'delete',
							'content' => '删除席位',
							'type' => 'button',
							'class' => 'btn btn-danger',
							'data-toggle' => 'modal',
							'data-target' => '#delete_seat',
						));
					} ?>
				</div>
			</div>
		<?php echo form_close();
		
		if($action == 'edit' && in_array($seat['status'], array('unavailable', 'available', 'preserved')) && $seat['type'] != 'primary')
		{
			echo form_open("seat/operation/delete_seat/{$seat['id']}", array(
				'class' => 'modal fade form-horizontal',
				'id' => 'delete_seat',
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
						<h4 class="modal-title" id="delete_label">删除席位</h4>
					</div>
					<div class="modal-body">
						<p>您将删除<?php echo $seat['name'];?>席位，与此席位相关的席位选择许可和席位预约将被同时删除。请输入您的登录密码并点击确认更改按钮继续操作。</p>
						
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
$this->ui->js('footer', "$('.selectpicker').selectpicker();");
$edit_js = <<<EOT
function edit_item(item)
{
	$('#'+item).removeAttr('disabled');
	$('input[name=change_'+item+']').val(true);
}
EOT;
if($action == 'edit')
	$this->ui->js('footer', $edit_js);
$this->load->view('footer');?>