<?php $this->load->view('header');?>

<div class="page-header">
	<h1><?php echo icon('comments')?>面试信息</h1>
</div>

<div class="row">
	<div class="col-md-8">
		<?php if(!empty($interviews)) {
		foreach($interviews as $interview) { ?><h3><?php echo $interview['id'] == $current_interview ? '当前面试信息' : '早前面试信息';?></h3>
		<table class="table table-bordered table-striped table-hover">
			<tbody>
				<tr>
					<td>状态</td>
					<td class="text-<?php echo $interview['status_class'];?>"><?php echo $interview['status_text'];?></td>
				</tr>
				<tr>
					<td>面试官</td>
					<td><?php echo icon('user').$interview['interviewer']['name'];
					if(!empty($interview['interviewer']['committee']))
						echo "（{$interview['interviewer']['committee']['name']}）";?></td>
				</tr>
				<tr>
					<td>分配时间</td>
					<td><?php echo sprintf('%1$s（%2$s）', date('n月j日 H:i:s', $interview['assign_time']), nicetime($interview['assign_time']));?></td>
				</tr>
				<?php if(!empty($interview['schedule_time'])) { ?><tr>
					<td>安排时间</td>
					<td><?php echo sprintf('%1$s（%2$s）', date('n月j日 H:i:s', $interview['schedule_time']), nicetime($interview['schedule_time']));?></td>
				</tr><?php }
				if(!empty($interview['finish_time'])) { ?><tr>
					<td><?php echo $interview['status'] == 'cancelled' ? '取消时间' : '完成时间';?></td>
					<td><?php echo sprintf('%1$s（%2$s）', date('n月j日 H:i:s', $interview['finish_time']), nicetime($interview['finish_time']));?></td>
				</tr><?php }
				if(!empty($interview['feedback']['remark'])) { ?><tr>
					<td style="min-width: 100px;">面试评价</td>
					<td><?php echo $interview['feedback']['remark'];?></td>
				</tr><?php } ?>
			</tbody>
		</table>
		<?php if(end($interviews) != $interview) { ?><hr /><?php }
		} } ?>
	</div>
	
	<div class="col-md-4">
		<?php $current = $interviews[$current_interview];
		if($current['status'] == 'arranged') { ?><div id="countdown_bar">
			<h3>面试安排</h3>
			<div id="countdown_area">
				<p><?php echo icon('calendar');?>当前面试将于<?php echo sprintf('%1$s（%2$s）', date('n月j日 H:i:s', $current['schedule_time']), nicetime($current['schedule_time']));?>开始。</p>
				
				<p><small>在面试开始前一小时，iPlacard 将会发送短信通知。请做好面试准备，面试官将按照约定的方式与您取得联系。如果因故无法于约定的时间参加面试，请提前联系面试官更改时间。如果您对面试有任何意见，您可以随时于<?php echo safe_mailto(option('site_contact_email', 'contact@iplacard.com'), '联系组委邮箱');?>。</small></p>
			</div>
			
			<hr />
		</div><?php } ?>
		
		<?php $interviewer = $interviews[$current_interview]['interviewer'];?>
		<div id="interviewer_bar">
			<h3>面试官信息</h3>
			<div id="interviewer_area">
				<div style="position: relative; margin-bottom: 10px;">
					<a class="thumbnail" style="width: 90px; height: 90px; position: absolute; margin-top: 2px;">
						<?php echo avatar($interviewer['id'], 80, 'img');?>
					</a>
					<p style="margin-bottom: 4px; margin-left: 100px;"><strong><?php echo $interviewer['name'];?></strong></p>
					<p style="margin-bottom: 4px; margin-left: 100px;"><?php
					$title = '';
					if(isset($interviewer['title']) && !empty($interviewer['title']))
						$title = $interviewer['title'];

					$committee = '';
					if(isset($interviewer['committee']))
						$committee = $interviewer['committee']['name'];

					if(!empty($title) && !empty($committee))
						echo "{$title}，{$committee}";
					elseif(!empty($title) && empty($committee))
						echo $title;
					elseif(empty($title) && !empty($committee))
						echo $committee;
					?></p>
					<p style="margin-bottom: 4px; margin-left: 100px;"><?php echo icon('envelope-o').mailto($interviewer['email']);?></p>
					<p style="margin-bottom: 4px; margin-left: 100px;"><?php echo icon('phone').$interviewer['phone'];?></p>
				</div>
				
				<p><small>iPlacard 仅会负责您当前面试官的信息，请自行保留早前面试官信息。通常情况下，如有任何问题请与当前面试官取得联系，早前的面试官可能无法进行相关操作。</small></p>
			</div>
		</div>
	</div>
</div>

<?php $this->load->view('footer');?>