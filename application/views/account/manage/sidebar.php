<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 设置边栏视图
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
 * @package iPlacard
 * @since 2.0
 */
?><div class="list-group">
	<?php
	echo anchor('account/settings/home', '个人信息', array('class' => $now == 'detail' ? 'list-group-item active' : 'list-group-item'));
	echo anchor('account/settings/security', '帐户安全设置', array('class' => $now == 'security' ? 'list-group-item active' : 'list-group-item'));
	echo ($is_admin) ? anchor('account/settings/admin', '管理设置', array('class' => $now == 'admin' ? 'list-group-item active' : 'list-group-item')) : '';
	echo anchor('account/settings/password', '修改密码', array('class' => $now == 'password' ? 'list-group-item active' : 'list-group-item'));
	echo anchor('account/settings/pin', '设置安全码', array('class' => $now == 'pin' ? 'list-group-item active' : 'list-group-item'));
	echo anchor('account/settings/twostep', '两步验证', array('class' => $now == 'twostep' ? 'list-group-item active' : 'list-group-item'));
	echo anchor('account/activity', '当前帐户活动', array('class' => $now == 'activity' ? 'list-group-item active' : 'list-group-item'));
	echo anchor('knowledgebase', '知识库', array('class' => 'list-group-item'));
	?>
</div>