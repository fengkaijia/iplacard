<div class="list-group">
	<?php
	echo anchor('account/settings/home', '帐户信息', array('class' => $now == 'detail' ? 'list-group-item active' : 'list-group-item'));
	echo anchor('account/settings/security', '帐户安全设置', array('class' => $now == 'security' ? 'list-group-item active' : 'list-group-item'));
	echo anchor('account/settings/password', '修改密码', array('class' => $now == 'password' ? 'list-group-item active' : 'list-group-item'));
	echo anchor('account/settings/pin', '设置安全码', array('class' => $now == 'pin' ? 'list-group-item active' : 'list-group-item'));
	echo anchor('account/settings/twostep', '两步验证', array('class' => $now == 'twostep' ? 'list-group-item active' : 'list-group-item'));
	if($this->user_model->is_admin(uid()))
		echo anchor('account/settings/admin', '管理员安全设置', array('class' => $now == 'admin' ? 'list-group-item active' : 'list-group-item'));
	echo anchor('account/activity', '当前帐户活动', array('class' => $now == 'activity' ? 'list-group-item active' : 'list-group-item'));
	echo anchor('help/knowledgebase', '知识库', array('class' => 'list-group-item'));
	?>
</div>