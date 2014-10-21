<!DOCTYPE html>
<html lang="zh_CN">
<head profile="http://gmpg.org/xfn/11">
	<title><?php echo (empty($this->ui->title)) ? 'iPlacard' : $this->ui->title;?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge, chrome=1" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />

	<link href="<?php echo static_url(is_dev() ? 'static/css/bootstrap.css' : 'static/css/bootstrap.min.css');?>" rel="stylesheet">
	<link href="<?php echo static_url(is_dev() ? 'static/css/webfont.css' : 'static/css/webfont.min.css');?>" rel="stylesheet">
	<link href="<?php echo static_url(is_dev() ? 'static/css/font-awesome.css' : 'static/css/font-awesome.min.css');?>" rel="stylesheet">
	<link href="<?php echo static_url(is_dev() ? 'static/css/iplacard.css' : 'static/css/iplacard.min.css');?>" rel="stylesheet">
	
	<script src="<?php echo static_url(is_dev() ? 'static/js/jquery.js' : 'static/js/jquery.min.js');?>"></script>
	<script src="<?php echo static_url(is_dev() ? 'static/js/bootstrap.js' : 'static/js/bootstrap.min.js');?>"></script>
	<script src="<?php echo static_url(is_dev() ? 'static/js/iplacard.js' : 'static/js/iplacard.min.js');?>"></script>
	
	<?php if(!empty($this->ui->js['header'])) { ?><script language="javascript"><?php echo is_dev() ? $this->ui->js['header'] : preg_replace("/\s+/", ' ', $this->ui->js['header']);?></script><?php } ?>

	<!--[if lt IE 9]>
		<script src="<?php echo static_url(is_dev() ? 'static/js/html5shiv.js' : 'static/js/html5shiv.min.js');?>"></script>
		<script src="<?php echo static_url(is_dev() ? 'static/js/respond.js' : 'static/js/respond.min.js');?>"></script>
	<![endif]-->
	
	<!--[if lt IE 10]><meta name="css3-support" content="no-css3"><![endif]-->
	
	<?php
	$background = option('ui_background_image', false);
	if($background && ($this->ui->show_background || option('ui_background_global_enabled', false))) { ?><style type="text/css">
		#wrap {
			display: block;
		}

		#wrap::after {
			content: '';
			background: #fff url('<?php echo base_url('public/'.IP_INSTANCE_ID.'/img/'.$background);?>') no-repeat center center fixed;
			-webkit-background-size: cover;
			   -moz-background-size: cover;
				 -o-background-size: cover;
					background-size: cover;
			<?php if(!$this->ui->show_background) { ?>opacity: 0.3;<?php } ?>
			top: 0;
			left: 0;
			bottom: 0;
			right: 0;
			position: absolute;
			z-index: -1;
		}
	</style><?php } ?>

	<link href="<?php echo static_url('static/img/favicon.ico');?>" rel="shortcut icon" />

	<?php if(!option('robots_allow', false)) { ?><meta name="robots" content="noallow" /><?php } ?>
	<meta name="description" content="iPlacard is the Next-Gen Model United Nations Conference Management Service Provider backed by IMUNC." />
	<meta name="generator" content="iPlacard Engine <?php echo IP_VERSION;?>" />
	<meta name="author" content="Kaijia Feng" />
	<?php if(!empty($this->ui->html['header'])) echo $this->ui->html['header'];?>
</head>

<body>
	<?php if($this->ui->show_menu) { ?><div class="navbar navbar-inverse navbar-fixed-top">
		<div class="container-fluid">
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
					<?php foreach($this->ui->get_panel() as $name => $menu)
					{
						if(empty($menu['sub']))
						{
							$class = !empty($this->ui->now_page) && $this->ui->now_page == $name ? ' class="active"' : '';
							echo '<li'.$class.'>'.anchor($menu['url'], $menu['title']).'</li>';
						}
						else
						{ ?><li class="dropdown<?php if(!empty($this->ui->now_page) && $this->ui->now_page == $name) echo ' active';?>">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php echo $menu['title'];?></a>
						<ul class="dropdown-menu">
							<?php $divideable = true;
							foreach($menu['sub'] as $item)
							{
								if($item['type'] == 'menu')
								{
									echo '<li>'.anchor($item['url'], $item['title']).'</li>';
									$divideable = true;
								}
								elseif($item['type'] == 'divider' && $divideable && end($menu['sub']) !== $item)
								{
									echo '<li class="divider"></li>';
									$divideable = false;
								}
							} ?>
						</ul><?php }
					} ?>
				</ul>
				<ul class="nav navbar-nav navbar-right">
					<?php if(!$this->session->userdata('logged_in') && option('login_link')) { $login_link = option('login_link'); ?><li><?php echo anchor($login_link['link'], $login_link['text']);?></li><?php } ?>
					<li class="dropdown">
						<?php if($this->session->userdata('logged_in')) { ?>
						<a href="#" class="dropdown-toggle" data-toggle="dropdown" style="position: relative;"><?php
							echo avatar('', 26, 'img', array('class' => 'img-circle', 'style' => 'position: absolute; margin-top: -2px;'));
							?> <span style="margin-left: 32px;"><?php echo $this->session->userdata('name');?></span> <b class="caret"></b></a>
						<ul class="dropdown-menu">
							<li><a href="<?php echo base_url('account/settings/home');?>">个人信息</a></li>
							<li><a href="<?php echo base_url('account/settings/security');?>">帐户设置</a></li>
							<?php if($this->session->userdata('logged_in') && $this->session->userdata('type') == 'delegate' && option('tos')) { ?><li><a href="<?php echo base_url('apply/tos');?>">服务条款</a></li><?php } ?>
							<li><a href="<?php echo base_url('help/knowledgebase');?>">知识库</a></li>
							<li class="divider"></li>
							<li><?php if(is_sudo()) { ?><a href="<?php echo base_url('account/sudo');?>">退出 SUDO</a><?php } else { ?><a href="<?php echo base_url('account/logout');?>">登出</a><?php } ?></li>
						</ul><?php } else { ?>
						<a href="#" class="dropdown-toggle" data-toggle="dropdown">登录 <b class="caret"></b></a>
						<ul class="dropdown-menu">
							<li><a href="<?php echo base_url('account/login');?>">登录</a></li>
							<li><a href="<?php echo base_url('account/recover');?>">找回密码</a></li>
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
	
	<?php if($this->ui->show_sidebar) { ?><div class="col-sm-3 col-md-2 sidebar">
		<ul class="nav nav-sidebar">
			<?php foreach($this->ui->sidebar() as $item)
			{
				echo isset($item[4]) && $item[4] ? '<li class="active">' : '<li>';
				echo anchor(!empty($item[1]) ? $item[1] : '#', $item[0], isset($item[3]) && $item[3] ? "onclick=\"$('.sidebar li').removeClass('active'); $(this).parent().addClass('active');\"" : '', isset($item[3]) ? $item[3] : false);
				echo isset($item[5]) && $item[5] ? '</li></ul><ul class="nav nav-sidebar">' : '</li>';
			} ?>
		</ul>
	</div><?php } ?>

	<div id="wrap">
		<div id="content" class="<?php echo $this->ui->show_sidebar ? 'container col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main' : 'container';
		echo $this->ui->show_menu ? ' container-menu' : ' container-nomenu';?>">
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