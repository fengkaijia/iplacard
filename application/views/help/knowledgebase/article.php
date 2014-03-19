<?php $this->load->view('header');?>

<div class="page-header">
	<h1>知识库文章</h1>
</div>

<div class="row">
	<div class="col-lg-8">
		<h2><?php echo $article['title'];?></h2>
		
		<p><?php
		if(is_null($article['update_time']) || $article['update_time'] == $article['create_time'])
		{
			printf('%s', date('Y年m月d日', $article['create_time']));
		}
		else
		{
			printf('%1$s（最后更新%2$s）', date('Y年m月d日', $article['create_time']), date('Y年m月d日', $article['update_time']));
		}
		?></p>
		
		<hr />
		
		<div><?php echo $article['content'];?></div>
		
		<hr />
		
		<?php $this->load->view('help/knowledgebase/footerbar');?>
	</div>
	
	<div class="col-lg-4">
		<div>
			<ul class="breadcrumb">
				<li><?php echo anchor('help/knowledgebase', '知识库');?></li>
				<li class="active"><?php echo $article['title'];?></li>
			</ul>
		</div>
		
		<?php $this->load->view('help/knowledgebase/sidebar');?>
	</div>
</div>

<?php $this->load->view('footer');?>