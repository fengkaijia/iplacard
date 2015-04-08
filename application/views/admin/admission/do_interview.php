<?php
$hiddens = array();
foreach($score_standard as $sid => $one)
{
	$hiddens["score_$sid"] = 0;
}
?><script>
	jQuery(function($){
		$('#total_score').hide();
		
		$('#<?php echo $pre ? 'do' : 'pre';?>_interview').hide();
		
		$('#fail').click(function(){
			<?php foreach($score_standard as $sid => $one)
			{
				echo "$('#fail_interview input[name=\"score_$sid\"]').val($('#pass_interview input[name=\"score_$sid\"]').val());";
			} ?>
			$('#fail_interview input[name="feedback"]').val($('#feedback').val());
		});
		
		$('#retest').click(function(){
			<?php foreach($score_standard as $sid => $one)
			{
				echo "$('#retest_interview input[name=\"score_$sid\"]').val($('#pass_interview input[name=\"score_$sid\"]').val());";
			} ?>
			$('#retest_interview input[name="feedback"]').val($('#feedback').val());
		});
	});
	
	function click_score(part, score)
	{
		$('#score-'+part+' button').removeClass('btn-primary');
		$('#score-'+part+' button').addClass('btn-default');
		$('#score-'+part+' button:nth-child('+(score+2)+')').removeClass('btn-default');
		$('#score-'+part+' button:nth-child('+(score+2)+')').addClass('btn-primary');
		$('#pass_interview input[name=score_'+part+']').val(score);
		update_score();
	}
	
	function update_score()
	{
		<?php
		if($score_level)
		{
			echo 'rank = '.json_encode($score_level).';';
			echo 'level = 100;';
		}
		
		echo 'total = ';
		foreach($score_standard as $sid => $one)
		{
			echo "parseInt($('#pass_interview input[name=\"score_$sid\"]').val()) * {$one['weight']} + ";
		}
		echo '0';?>;
		
		$('#total_score').show();
		$('#tscore').html(total.toFixed(2));
		<?php if($score_level) { ?>$.each(rank, function(clevel, score) {
			if(total >= score) {
				level = clevel;
				return false;
			}
		});
		$('#tlevel').html(level);<?php } ?>
	}
</script>

<div id="pre_interview">
	<h3 id="admission_operation">开始面试</h3>
	
	<?php if($is_secondary) { ?><p><span class="label label-warning">注意</span> 这是二次面试分配。</p><?php } ?>
	<p>面试计划于 <em><?php echo date('Y-m-d H:i', $interview['schedule_time']);?></em> 开始。</p>
	<p>在面试开始之前，您可以选择提前开始面试。如果需要或者错过了面试，您可以重新安排面试时间。</p>
	
	<?php echo form_button(array(
		'content' => icon('rocket').'提前开始',
		'type' => 'button',
		'class' => 'btn btn-primary',
		'onclick' => "$('#do_interview').show(); $('#pre_interview').hide();"
	));
	echo ' ';
	echo form_button(array(
		'content' => icon('retweet').'重新安排',
		'type' => 'button',
		'class' => 'btn btn-warning',
		'data-toggle' => 'modal',
		'data-target' => '#cancel_interview',
	)); ?>
</div>

<div id="do_interview">
	<h3 id="admission_operation">面试</h3>
	
	<?php if($is_secondary) { ?><p><span class="label label-warning">注意</span> 这是二次面试。</p><?php } ?>
	<p>在面试时，建议您保持此页面打开并可随时填写面试反馈。面试完成后，您需要为代表的表现评分。完成评价后，您可以认定面试是否通过。如果认为面试通过但需要更换面试官继续面试，您可以选择增加复试。</p>

	<?php echo form_open("delegate/operation/interview/{$delegate['id']}", array(
		'id' => 'pass_interview'
	), array('pass' => true, 'retest' => false) + $hiddens); ?>
		
		<div class="form-group <?php if(form_has_error('feedback')) echo 'has-error';?>">
			<?php echo form_label('面试反馈', 'feedback', array('class' => 'control-label'));
			echo form_textarea(array(
				'name' => 'feedback',
				'id' => 'feedback',
				'class' => 'form-control',
				'rows' => 3,
				'value' => set_value('feedback'),
				'placeholder' => '面试情况反馈'
			));
			if(form_has_error('feedback'))
				echo form_error('feedback');
			else { ?><div class="help-block">面试反馈将不会发送给代表。</div><?php } ?>
		</div>
		
		<div class="form-group <?php if(form_has_error('score')) echo 'has-error';?>">
			<?php echo form_label('面试评分', 'score', array('class' => 'control-label'));
			foreach($score_standard as $sid => $one)
			{ ?><div class="btn-toolbar">
				<div id="score-<?php echo $sid;?>" class="btn-group">
					<a class="btn btn-primary"><?php echo $one['name'];?></a>
					<?php for($i = 0; $i <= $score_total; $i++)
					{ 
						echo form_button(array(
							'content' => $i,
							'type' => 'button',
							'class' => 'btn btn-default',
							'onclick' => "click_score('$sid', $i);",
						));
					} ?>
				</div>
			</div><?php	}
			
			if($score_level) { ?><div id="total_score" class="help-block">当前总评分为 <strong><span id="tscore">0</span></strong>，这个成绩大约位于前 <strong><span id="tlevel">100</span></strong>%，满分为 <?php echo $score_total;?> 分。</div><?php }
			else { ?><div id="total_score" class="help-block">当前总评分为 <strong><span id="tscore">0</span></strong>，满分为 <?php echo $score_total;?> 分。</div><?php } ?>
		</div>
		
		<div class="form-group <?php if(form_has_error('result')) echo 'has-error';?>">
			<?php echo form_label('面试结果', 'result', array('class' => 'control-label'));?>
			<div>
				<?php echo form_button(array(
					'name' => 'submit',
					'content' => '面试通过',
					'type' => 'submit',
					'class' => 'btn btn-primary',
					'onclick' => 'loader(this);'
				));
				echo ' ';
				echo form_button(array(
					'id' => 'retest',
					'content' => '增加复试',
					'type' => 'button',
					'class' => 'btn btn-warning',
					'data-toggle' => 'modal',
					'data-target' => '#retest_interview',
				));
				echo ' ';
				echo form_button(array(
					'id' => 'fail',
					'content' => '面试未过',
					'type' => 'button',
					'class' => 'btn btn-danger',
					'data-toggle' => 'modal',
					'data-target' => '#fail_interview',
				)); ?>
			</div>
		</div>
		
		<p>如果错过了面试，可以<a href="#" data-toggle="modal" data-target="#cancel_interview"><?php echo icon('retweet', false);?>重新安排面试时间</a>。</p>
	<?php echo form_close();
	
	echo form_open("delegate/operation/interview/$uid", array(
		'class' => 'modal fade form-horizontal',
		'id' => 'fail_interview',
		'tabindex' => '-1',
		'role' => 'dialog',
		'aria-labelledby' => 'fail_label',
		'aria-hidden' => 'true'
	), array('pass' => false, 'retest' => false, 'feedback' => '') + $hiddens);?><div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<?php echo form_button(array(
						'content' => '&times;',
						'class' => 'close',
						'type' => 'button',
						'data-dismiss' => 'modal',
						'aria-hidden' => 'true'
					));?>
					<h4 class="modal-title" id="fail_label">认定面试未通过</h4>
				</div>
				<div class="modal-body">
					<?php if($is_secondary) { ?><p>将会认定<?php echo icon('user', false).$delegate['name'];?>的面试未通过。</p>
					<p><span class="label label-danger">注意</span> 这是二次面试，这意味着此代表的申请被认定为失败。之后，代表将会被转到等待队列。</p><?php }
					else { ?><p>将会认定<?php echo icon('user', false).$delegate['name'];?>的面试未通过。之后，代表将有第二次面试机会，为方便分配二面面试官，请在笔记中注明分配倾向。</p>
					<p><span class="label label-danger">注意</span> 请谨慎执行此操作。</p><?php } ?>
					
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
						'content' => '确定提交',
						'type' => 'submit',
						'class' => 'btn btn-danger',
						'onclick' => 'loader(this);'
					)); ?>
				</div>
			</div>
		</div>
	<?php echo form_close();
	
	echo form_open("delegate/operation/interview/$uid", array(
		'class' => 'modal fade form-horizontal',
		'id' => 'retest_interview',
		'tabindex' => '-1',
		'role' => 'dialog',
		'aria-labelledby' => 'retest_label',
		'aria-hidden' => 'true'
	), array('pass' => true, 'retest' => true, 'feedback' => '') + $hiddens);?><div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<?php echo form_button(array(
						'content' => '&times;',
						'class' => 'close',
						'type' => 'button',
						'data-dismiss' => 'modal',
						'aria-hidden' => 'true'
					));?>
					<h4 class="modal-title" id="retest_label">认定面试通过并增加复试</h4>
				</div>
				<div class="modal-body">
					<p>将会认定<?php echo icon('user', false).$delegate['name'];?>的面试通过但需要复试。您可以在笔记中注明推荐的复试面试官人选方便审核人员安排下一轮面试。</p>
					<p><span class="label label-warning">注意</span> 选择提交后本次面试将被标记为通过，但直到代表的终试面试官认定面试通过并不需要复试后，您才可以向代表分配席位。</p>
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
						'content' => '确定提交',
						'type' => 'submit',
						'class' => 'btn btn-warning',
						'onclick' => 'loader(this);'
					)); ?>
				</div>
			</div>
		</div>
	<?php echo form_close(); ?>
</div>

<?php echo form_open("delegate/operation/cancel_interview/$uid", array(
	'class' => 'modal fade form-horizontal',
	'id' => 'cancel_interview',
	'tabindex' => '-1',
	'role' => 'dialog',
	'aria-labelledby' => 'cancel_label',
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
				<h4 class="modal-title" id="cancel_label">取消面试安排</h4>
			</div>
			<div class="modal-body">
				<p>将会取消与<?php echo icon('user', false).$delegate['name'];?>的面试安排，面试状态将会重置为等待安排时间。面试安排取消后您将可以重新安排面试时间。在取消之前，您确定已经和代表取得联系并已说明取消原因及其他事宜。</p>
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
					'content' => '确定取消安排',
					'type' => 'submit',
					'class' => 'btn btn-primary',
					'onclick' => 'loader(this);'
				)); ?>
			</div>
		</div>
	</div>
<?php echo form_close(); ?>

<hr />