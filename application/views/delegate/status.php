<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 申请状态视图
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
 * @package iPlacard
 * @since 2.0
 */

if($delegate['application_type'] == 'delegate')
	$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/flags.css' : 'static/css/flags.min.css').'" rel="stylesheet">');

if($lock_open)
	$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/jquery.shorten.js': 'static/js/jquery.shorten.min.js').'"></script>');
$this->load->view('header');?>

<?php if($welcome) { ?><div id="welcome" class="jumbotron" style="margin-top: 20px; margin-bottom: 20px;">
	<h1 style="font-size: 56px;">欢迎使用 iPlacard</h1>
	<p>全新的下一代模拟联合国会议管理系统已经为您准备就绪，立即开始使用。</p>
	<div class="form-inline">
		<div class="form-group">
			<a class="btn btn-primary btn-lg" onclick="dismiss_welcome();">开始使用</a>
		</div>
		<div class="checkbox" style="padding-left: 10px;">
			<label>
				<input id="remember_dismiss" type="checkbox" checked> 不再显示此欢迎提示
			</label>
		</div>
	</div>
	<p></p>
	<?php
	$welcome_dismiss_url = base_url('apply/ajax/dismiss_welcome');
	$welcome_js = "function dismiss_welcome()
	{
		if($('#remember_dismiss').is(':checked')) {
			$.ajax({
				url: '{$welcome_dismiss_url}',
				async: true,
				dataType : 'json'
			});
		}

		$('#welcome').hide();
	}";
	$this->ui->js('footer', $welcome_js);
	?>
</div><?php } ?>

<div class="row">
	<div class="col-md-12" align="center">
		<div class="wizard" style="padding-top: 42px; padding-bottom: 62px;">
			<?php foreach($wizard as $step => $one)
			{
				$attr = 'data-html="1" data-placement="bottom" data-trigger="click" data-original-title="'.$one['text'].'" data-toggle="popover" data-content="'.$one['intro'].'"';
				
				$class = 'status_intro';
				if($one['current'])
					$class .= ' current"';
				if($wizard[0] == $one)
					$class .= ' first';
				if(end($wizard) == $one)
					$class .= ' last';
				
				$attr .= " class=\"{$class}\"";
				
				$now = $step + 1;
				$label = $one['current'] ? 'default' : 'primary';
				$text = "<span class=\"label label-$label\">{$now}</span> {$one['text']}";
				echo "<a $attr>$text</a>";
			}
			$this->ui->js('footer', "$('.status_intro').popover();");
			?>
		</div>
	</div>
</div>

<div class="row">
	<div class="col-md-4">
		<?php if(!empty($announcement)) { ?><div id="ui-announcement" class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title"><?php echo icon('bullhorn');?>公告</h3>
			</div>
			<div class="panel-body">
				<?php echo $announcement;?>
			</div>
		</div><?php } ?>
		
		<div id="ui-status" class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title"><?php echo icon('info');?>申请状态</h3>
			</div>
			<div class="panel-body">
				<?php echo $status_intro;?>
			</div>
		</div>
		
		<?php if(!empty($messages)) { ?><div id="ui-message" class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title"><?php echo icon('inbox');?>消息</h3>
			</div>
			<ul class="list-group"><?php foreach($messages as $message) { ?>
				<li class="list-group-item">
					<a class="close" data-dismiss="alert" onclick="archive_message(<?php echo $message['id'];?>);">
						<span aria-hidden="true" class="text-muted">&times;</span>
					</a>
					<?php if($message['status'] == 'unread') { ?><span class="label label-primary">新消息</span> <?php echo $message['text']; ?> <small class="text-muted"><?php echo nicetime($message['time']); ?></small><?php
					} else { ?><span class="text-muted"><?php echo $message['text']; ?></span> <small class="text-muted"><?php echo nicetime($message['time']); ?></small><?php } ?>
				</li><?php } ?>
			</ul>
		</div><?php } ?>
		
		<?php if($lock_open) { ?><div id="ui-lock" class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title"><?php echo anchor('apply/seat', icon('lock').'确认锁定');?></h3>
			</div>
			<div class="panel-body flags-16">
				<p>现可以确认申请完成并锁定您的席位，锁定后将不会发生变动。</p>
				<p style="margin-bottom: 0;"><a class="btn btn-primary" href="#" data-toggle="modal" data-target="#lock">立即锁定申请</a></p>
			</div>
		</div>
			
		<?php $this->load->view('delegate/panel/lock'); } ?>
	</div>
	
	<div class="col-md-4">
		<?php if(!empty($documents)) { ?><div id="ui-document" class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title"><?php echo anchor('apply/document', icon('file').'新文件');?></h3>
			</div>
			<div class="list-group">
				<?php foreach($documents as $document) { ?><a href="<?php echo base_url("apply/document/#document-{$document['id']}");?>" class="list-group-item">
					<h4 class="list-group-item-heading"><?php echo $document['title'];?> <small><?php echo nicetime($document['create_time']);?></small></h4>
					<div class="list-group-item-text">
						<?php if($document['highlight']) { ?><span class="label label-primary">重要</span> <?php }
						echo character_limiter($document['description'], 200);?>
					</div>
				</a><?php } ?>
			</div>
		</div><?php } ?>
		
		<?php if($feed_enable) { ?><div id="ui-news" class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title"><?php echo icon('globe');?>会议新闻</h3>
			</div>
			<div class="list-group">
				<?php foreach($feed as $num => $news) { ?><a href="<?php echo $news['link'];?>" target="_blank" class="list-group-item">
					<h4 class="list-group-item-heading"><?php echo $news['title'];?> <small><?php echo nicetime(strtotime($news['date']));?></small></h4>
					<div class="list-group-item-text">
						<?php echo character_limiter(strip_tags(empty($news['content']) ? $news['description'] : $news['content']), 300);?>
					</div>
				</a><?php } ?>
			</div>
		</div><?php } ?>
		
		<?php if(!empty($articles)) { ?><div id="ui-kb" class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title"><?php echo anchor('knowledgebase', icon('book').'知识库文章');?></h3>
			</div>
			<div class="list-group">
				<?php foreach($articles as $article) {
					echo anchor("knowledgebase/article/kb{$article['kb']}", '<span class="badge">'.$article['count'].'</span>'.character_limiter($article['title'], 30), array('class' => 'list-group-item'));
				} ?>
			</div>
		</div><?php } ?>
	</div>
	
	<div class="col-md-4">
		<div id="ui-profile" class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title"><?php echo anchor('apply/profile', icon('user').'个人信息');?></h3>
			</div>
			<div class="panel-body flags-16" style="position: relative;">
				<a class="thumbnail" style="width: 90px; height: 90px; position: absolute; margin-top: 2px;">
					<?php echo avatar($delegate['id'], 80, 'img');?>
				</a>
				<p style="margin-bottom: 4px; margin-left: 104px;"><strong><?php echo $delegate['name'];?></strong></p>
				<p style="margin-bottom: 4px; margin-left: 104px;"><?php echo $delegate['application_type_text'];?></p>
				<?php
				if(!empty($seat))
				{
					echo '<p style="margin-bottom: 4px; margin-left: 104px;">'.flag($seat['iso'], true)."{$seat['name']}，{$seat['committee']['name']}</p>";
				}
				
				if(!empty($group))
				{
					echo "<p style=\"margin-bottom: 4px; margin-left: 104px;\">{$group['name']}代表团</p>";
				} ?>
				<br />
				<p style="margin-bottom: 4px; margin-left: 104px;"><strong>联系方式</strong> <?php echo anchor('account/settings/home', icon('edit', false));?></p>
				<p style="margin-bottom: 4px; margin-left: 104px;"><?php echo icon('envelope-o').$delegate['email'];?></p>
				<p style="margin-bottom: 4px; margin-left: 104px;"><?php echo icon('phone').$delegate['phone'];?></p>
			</div>
		</div>
		
		<?php if(!empty($invoices)) { ?><div id="ui-invoice" class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title"><?php echo anchor('apply/invoice', icon('file-text').'账单');?></h3>
			</div>
			<div class="list-group">
				<?php foreach($invoices as $invoice) { ?><a href="<?php echo base_url("apply/invoice/{$invoice['id']}");?>" class="list-group-item<?php
				if($invoice['due_time'] < time())
					echo ' list-group-item-danger';
				elseif((time() - $invoice['generate_time']) / ($invoice['due_time'] - $invoice['generate_time']) > 0.75)
					echo ' list-group-item-warning';
				?>">
					<h4 class="list-group-item-heading"><?php echo $invoice['title'];?> <small>#<?php echo $invoice['id'];?></small></h4>
					<div class="list-group-item-text">
						<?php echo $currency['sign'].number_format((double) $invoice['amount'], 2).' '.$currency['text'];?> / <?php echo date('Y年m月d日到期', $invoice['due_time']);?>
					</div>
				</a><?php } ?>
			</div>
		</div><?php } ?>
	</div>
</div>

<?php
$read_more = icon('caret-right', false);
$read_less = icon('caret-left', false);
$shorten_js = <<<EOT
$('.shorten').shorten({
	showChars: '20',
	moreText: '{$read_more}',
	lessText: '{$read_less}'
});
EOT;
if($lock_open)
	$this->ui->js('footer', $shorten_js);

$archive_url = base_url('apply/ajax/archive_message');
$message_count = count($messages);
$message_js = <<<EOT
var message_count = {$message_count};
function archive_message(id) {
	$.ajax({
		url: "{$archive_url}?id=" + id,
	});
	message_count--;
	if(message_count == 0) {
		$('#ui-message').hide();
	}
}
EOT;
if(!empty($messages))
	$this->ui->js('footer', $message_js);

$this->load->view('footer');?>
