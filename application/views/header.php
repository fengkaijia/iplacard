<!DOCTYPE html>
<html lang="zh_CN">
<head profile="http://gmpg.org/xfn/11">
	<title><?php echo (empty($this->ui->title)) ? 'iPlacard' : $this->ui->title;?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge, chrome=1" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />

	<link href="<?php echo static_url(is_dev() ? 'static/css/bootstrap.css' : 'static/css/bootstrap.min.css');?>" rel="stylesheet">
	<link href="<?php echo static_url(is_dev() ? 'static/css/font-awesome.css' : 'static/css/font-awesome.min.css');?>" rel="stylesheet">
	<link href="<?php echo static_url(is_dev() ? 'static/css/iplacard.css' : 'static/css/iplacard.min.css');?>" rel="stylesheet">
	
	<script src="<?php echo static_url(is_dev() ? 'static/js/jquery.js' : 'static/js/jquery.min.js');?>"></script>
	<script src="<?php echo static_url(is_dev() ? 'static/js/bootstrap.js' : 'static/js/bootstrap.min.js');?>"></script>
	<script src="<?php echo static_url(is_dev() ? 'static/js/iplacard.js' : 'static/js/iplacard.min.js');?>"></script>
	
	<?php if(!empty($this->ui->js['header'])) { ?><script language="javascript"><?php echo $this->ui->js['header'];?></script><?php } ?>

	<!--[if lt IE 9]>
		<script src="<?php echo static_url(is_dev() ? 'static/js/html5shiv.js' : 'static/js/html5shiv.min.js');?>"></script>
		<script src="<?php echo static_url(is_dev() ? 'static/js/respond.js' : 'static/js/respond.min.js');?>"></script>
	<![endif]-->

	<link href="<?php echo static_url('static/img/favicon.ico');?>" rel="shortcut icon" />

	<?php if(!option('robots_allow', false)) { ?><meta name="robots" content="noallow" /><?php } ?>
	<meta name="description" content="iPlacard is the Next-Gen Model United Nations Conference Management Service Provider backed by IMUNC." />
	<meta name="generator" content="iPlacard Engine <?php echo IP_VERSION;?>" />
	<meta name="author" content="Kaijia Feng" />
	<?php if(!empty($this->ui->html['header'])) echo $this->ui->html['header'];?>
</head>

<body>
	<div id="wrap">
		<?php if($this->ui->show_menu == true) { ?><div class="navbar navbar-inverse navbar-fixed-top">
			<div class="container">
				<div class="navbar-header">
					<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					</button>
					<a class="navbar-brand" href="<?php echo base_url('');?>">iPlacard</a>
				</div>
				<div class="navbar-collapse collapse">
					<ul class="nav navbar-nav">
						<?php foreach($this->ui->panel() as $name => $column)
						{
							if(count($column) == 1) { ?><li <?php if(!empty($this->ui->now_page) && $this->ui->now_page == $name) { ?>class="active"<?php } ?>><?php echo anchor($column[0][1], $column[0][0]);?></li><?php }
							else { ?><li class="dropdown<?php //TODO: 高亮当前?>">
							<a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php echo $column[0][0];?> <!--<b class="caret"></b>--></a>
							<ul class="dropdown-menu">
								<?php foreach($column as $item) { ?><li><?php
									echo anchor($item[1], $item[0]);
									if($this->ui->is_backend() && $item[2] == true)
									{ ?></li><li class="divider"><?php } ?></li><?php } ?>
							</ul><?php }
						} ?>
					</ul>
					<ul class="nav navbar-nav navbar-right">
						<?php if(!$this->session->userdata('logged_in') && option('login_link')) { $login_link = option('login_link'); ?><li><?php echo anchor($login_link['link'], $login_link['text']);?></li><?php } ?>
						<li class="dropdown">
							<?php if($this->session->userdata('logged_in')) { ?>
							<a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php echo $this->user_model->get_user($this->session->userdata('uid'), 'name');?> <b class="caret"></b></a>
							<ul class="dropdown-menu">
								<li><a href="<?php echo base_url('account/detail');?>">帐户管理</a></li>
								<li><a href="<?php echo base_url('account/password');?>">修改密码</a></li>
								<?php if($this->session->userdata('logged_in') && $this->session->userdata('type') == 'delegate' && option('tos')) { ?><li><a href="<?php echo base_url('apply/tos');?>">服务条款</a></li><?php } ?>
								<li><a href="<?php echo base_url('help/knowledgebase');?>">知识库</a></li>
								<li class="divider"></li>
								<li><a href="<?php echo base_url('account/logout');?>">登出</a></li>
							</ul><?php } else { ?>
							<a href="#" class="dropdown-toggle" data-toggle="dropdown">登录 <b class="caret"></b></a>
							<ul class="dropdown-menu">
								<li><a href="<?php echo base_url('account/login');?>">登录</a></li>
								<li><a href="<?php echo base_url('account/recover');?>">帐号恢复</a></li>
								<li><a href="<?php echo base_url('help/knowledgebase');?>">知识库</a></li>
								<li class="divider"></li>
								<li><?php echo safe_mailto(option('site_contact_email', 'contact@iplacard.com'), '联系管理员');?></li>
								<li><a href="<?php echo base_url('about/iplacard');?>">关于</a></li>
							</ul><?php } ?>
						</li>
					</ul>
				</div><!--/.nav-collapse -->
			</div>
		</div><?php } ?>

		<div id="content" class="container" style="padding-top: <?php echo $this->ui->show_menu ? 72 : 36;?>px;">
			<?php if($this->ui->show_alert && !empty($this->ui->alert)) { ?><section id="global-alert">
				<?php foreach($this->ui->alert as $alert) { ?>
				<div class="alert alert-dismissable alert-<?php echo $alert['type'];?>">
					<button type="button" class="close" data-dismiss="alert">×</button>
					<span class="alert-notice"><strong><?php switch($alert['type'])
					{
						case 'danger':
						case 'error':
							echo '错误';
							break;
						case 'warning':
							echo '注意';
							break;
						case 'info':
						case 'success':
							echo '信息';
							break;
					}
					?></strong> <?php echo $alert['message'];?></span>
				</div><?php } ?>
			</section><?php $this->session->unset_userdata('alert');
			} ?>