<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 简易报名表视图
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
 * @package iPlacard
 * @since 2.3
 */

$this->load->view('header');?>

<?php echo form_open('', array('class' => 'form-auth form-signup form-horizontal'));?>
	<div id="page_general">
		<h2 class="form-auth-heading">在线报名</h2>
	
		<p>填写下方表单在线报名，报名后一封包含登录信息的邮件将会发送到登记的邮箱中。</p>
	
		<hr />
	
		<div class="form-group <?php if(form_has_error('name')) echo 'has-error';?>">
			<?php echo form_label('姓名', 'name', array('class' => 'col-lg-3 control-label'));?>
			<div class="col-lg-9">
				<?php echo form_input(array(
					'name' => 'name',
					'id' => 'name',
					'value' => set_value('name'),
					'class' => 'form-control'
				));
				
				if(form_has_error('name'))
					echo form_error('name'); ?>
			</div>
		</div>
	
		<div class="form-group <?php if(form_has_error('email')) echo 'has-error';?>">
			<?php echo form_label('电子邮箱', 'email', array('class' => 'col-lg-3 control-label'));?>
			<div class="col-lg-9">
				<?php echo form_input(array(
					'name' => 'email',
					'id' => 'email',
					'value' => set_value('email'),
					'class' => 'form-control'
				));
				
				if(form_has_error('email'))
					echo form_error('email'); ?>
			</div>
		</div>
	
		<div class="form-group <?php if(form_has_error('phone')) echo 'has-error';?>">
			<?php echo form_label('手机号', 'phone', array('class' => 'col-lg-3 control-label'));?>
			<div class="col-lg-9">
				<?php echo form_input(array(
					'name' => 'phone',
					'id' => 'phone',
					'value' => set_value('phone'),
					'class' => 'form-control'
				));
				
				if(form_has_error('phone'))
					echo form_error('phone'); ?>
			</div>
		</div>
	
		<div class="form-group <?php if(form_has_error('type')) echo 'has-error';?>">
			<?php echo form_label('申请类型', 'type', array('class' => 'col-lg-3 control-label'));?>
			<div class="col-lg-9">
				<?php
				echo form_dropdown('type', array('' => '选择参会类型') + $type, set_value('type'), 'class="form-control" id="type"');
				if(form_has_error('type'))
					echo form_error('type'); ?>
			</div>
		</div>
	
		<?php if(!empty($committee)) { ?><div id="committee_input" class="form-group <?php if(form_has_error('committee')) echo 'has-error';?>">
			<?php echo form_label('意向委员会', 'committee', array('class' => 'col-lg-3 control-label'));?>
			<div class="col-lg-9">
				<?php
				echo form_dropdown('committee', array('' => '选择委员会') + $committee, set_value('committee'), 'class="form-control" id="committee"');
				if(form_has_error('committee'))
					echo form_error('committee'); ?>
			</div>
		</div><?php } ?>
	
		<?php
		if(!empty($profiles))
		{
			echo "<hr />";
			foreach($profiles as $name => $text) { ?><div class="form-group <?php if(form_has_error("profile_$name")) echo 'has-error';?>">
			<?php echo form_label($text, "profile_$name", array('class' => 'col-lg-3 control-label'));?>
			<div class="col-lg-9">
				<?php
				echo form_input(array(
					'name' => "profile_$name",
					'id' => "profile_$name",
					'value' => set_value("profile_$name"),
					'class' => 'form-control'
				));
				
				if(form_has_error("profile_$name"))
					echo form_error("profile_$name");
				?>
			</div>
		</div><?php } } ?>
	
		<div class="form-group">
			<div class="col-lg-9 col-lg-offset-3">
				<?php
				echo form_button(array(
					'name' => 'submit',
					'content' => '提交报名',
					'type' => 'submit',
					'class' => 'btn btn-primary',
					'id' => 'page_general_submit',
					'onclick' => 'loader(this);'
				));
				
				if(!empty($test_questions))
				{
					echo form_button(array(
						'name' => 'page_button_next',
						'content' => '下一页',
						'class' => 'btn btn-primary',
						'id' => 'page_button_next'
					));
				} ?>
			</div>
		</div>
	</div>

	<?php
	if(!empty($test_questions))
	{ ?><div id="page_test">
		<h2 class="form-auth-heading">学术测试</h2>

		<hr />
			
		<?php
		foreach($test_questions as $id => $text)
		{
			if (in_array($id, $test_needed)) { ?><div id="test_input_<?php echo $id;?>" class="form-group <?php if(form_has_error("test_$id")) echo 'has-error';?> test_question">
			<div class="col-lg-12">
				<?php echo form_label($text, "test_$id");
				echo form_textarea(array(
					'name' => "test_$id",
					'id' => "test_$id",
					'value' => set_value("test_$id"),
					'class' => 'form-control',
					'rows' => 4,
				));
				
				if(form_has_error("test_$id"))
					echo form_error("test_$id");
				?>
			</div>
		</div><?php } } ?>

		<div class="form-group">
			<div class="col-lg-12">
				<?php
				echo form_button(array(
					'name' => 'submit',
					'content' => '提交报名',
					'type' => 'submit',
					'class' => 'btn btn-primary',
					'onclick' => 'loader(this);'
				));
				echo form_button(array(
					'name' => 'page_button_back',
					'content' => '上一页',
					'class' => 'btn btn-link',
					'id' => 'page_button_back'
				));
				?>
			</div>
		</div>
	</div><?php } ?>

<?php echo form_close();?>

<?php
$committee_js = <<<EOT
$(document).ready(function() {
	$('#committee_input').hide();
	if($('#type').children("option:selected").val() == '')
		$('#page_general_submit').addClass('disabled');
} );

$('#type').change(function() {
	switch($(this).children("option:selected").val()) {
		case '':
			$('#committee_input').hide();
			$('#page_general_submit').addClass('disabled');
			break;
		case 'delegate':
			$('#committee_input').show();
			break;
		default:
			$('#committee_input').hide();
			$('#page_general_submit').removeClass('disabled');
	}
} );
EOT;

$question_js = '';
if(isset($test_selected))
{
	foreach($test_selected as $committee => $selected)
	{
		$question_js .= "if($(this).children(\"option:selected\").val() == '{$committee}') {\n";
		foreach($selected as $index)
		{
			$question_js .= "$('#test_input_{$index}').show();\n";
		}
		$question_js .= "}\n";
	}
}

$test_js = <<<EOT
$(document).ready(function() {
	$('#page_test').hide();
	$('#page_button_next').addClass('disabled').hide();
} );

$('#type').change(function() {
	if($(this).children("option:selected").val() == 'delegate') {
		$('#page_button_next').show();
		$('#page_general_submit').hide();
	} else {
		$('#page_button_next').hide();
		$('#page_general_submit').show();
	}
} );

$('#committee').change(function() {
	if($(this).children("option:selected").val() != '') {
		$('#page_button_next').removeClass('disabled');
		
		$('.test_question').hide();
		{$question_js}
	} else {
		$('#page_button_next').addClass('disabled');
	}
} );

$('#page_button_next').click(function() {
	$('#page_test').show();
	$('#page_general').hide();
} );

$('#page_button_back').click(function() {
	$('#page_test').hide();
	$('#page_general').show();
} );
EOT;

$this->ui->js('footer', $committee_js);
if(!empty($test_questions))
	$this->ui->js('footer', $test_js);
$this->load->view('footer'); ?>