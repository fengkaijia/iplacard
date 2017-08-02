<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 代表团视图
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
 * @package iPlacard
 * @since 2.0
 */

$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/flags.css' : 'static/css/flags.min.css').'" rel="stylesheet">');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/jquery.shorten.js': 'static/js/jquery.shorten.min.js').'"></script>');
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

<div class="menu-pills"></div>

<div class="row">
	<div class="tab-content">
		<div class="tab-pane active" id="member">
			<div class="col-md-12">
				<p>您是<?php echo icon('users', false).$group['name'];?>代表团成员，当前代表团中有 <strong><?php echo count($delegates);?></strong> 位成员，如果您不应是此代表团成员，请<?php echo safe_mailto(option('site_contact_email', 'contact@iplacard.com'), '联系管理员');?>调整代表团。</p>
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
							<td><span class="shorten"><?php if(isset($one['seat'])) echo empty($one['seat']['iso']) ? $one['seat']['name'] : flag($one['seat']['iso'], true).$one['seat']['name']; ?></span></td>
							<?php if($head_delegate) { ?><td><?php echo "<span class='label label-{$one['status_class']}'>{$one['status_text']}</span>";?></td><?php } ?>
						</tr>
					<?php } ?></tbody>
				</table>
			</div>
		</div>
		
		<div class="tab-pane" id="hd">
			<?php
			if(!empty($group['head_delegate']))
			{
				$head_profile = $delegates[$group['head_delegate']];
			?><div class="col-md-8">
				<h3>领队信息</h3>
				<table class="table table-bordered table-striped table-hover">
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
				</table>
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
			</div><?php } else { ?><p>当前代表团无领队，请联系管理员添加领队。</p><?php } ?>
		</div>
	</div>
</div>

<?php
$read_more = icon('caret-right', false);
$read_less = icon('caret-left', false);
$shorten_js = <<<EOT
$('.shorten').shorten({
	showChars: '25',
	moreText: '{$read_more}',
	lessText: '{$read_less}'
});
EOT;
$this->ui->js('footer', $shorten_js);

$this->load->view('footer');?>