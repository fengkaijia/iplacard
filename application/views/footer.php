		</div><!-- /container -->

		<div id="push"></div>
	</div><!-- /wrap -->
	
	<footer id="footer" class="<?php echo $this->ui->show_sidebar ? 'container col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2' : 'container';?>">
		<hr />
		<p class="pull-right">
			<?php
			if($this->ui->is_backend() || $this->ui->is_frontend())
			{
				foreach(array('rss', 'renren', 'weibo', 'tencent-weibo', 'wechat', 'qq', 'github', 'twitter', 'facebook', 'google-plus') as $social)
				{
					$link = option("link_{$social}", false);
					if($link)
						echo anchor($link, icon($social, false), array('style' => 'color: inherit;', 'target' => '_blank')).' ';
				}
			} ?></p>
		&copy; 2008-<?php echo date('Y');?> <a href="http://imunc.com/">IMUNC</a>. All rights reserved.
	</footer>
	
	<?php if(!empty($this->ui->js['footer'])) { ?><script language="javascript"><?php echo is_dev() ? $this->ui->js['footer'] : preg_replace("/\s+/", ' ', $this->ui->js['footer']);?></script><?php } ?>
	<?php if(!empty($this->ui->html['footer'])) echo $this->ui->html['footer'];?>
</body>
</html>