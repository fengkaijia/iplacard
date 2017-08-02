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
			echo form_dropdown('type', $type, set_value('type'), 'class="form-control" id="type"');
			if(form_has_error('type'))
				echo form_error('type'); ?>
		</div>
	</div>

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
			<?php echo form_button(array(
				'name' => 'submit',
				'content' => '提交报名',
				'type' => 'submit',
				'class' => 'btn btn-primary',
				'onclick' => 'loader(this);'
			)); ?>
		</div>
	</div>

<?php echo form_close();?>

<?php $this->load->view('footer'); ?>