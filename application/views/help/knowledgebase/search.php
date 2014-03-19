<?php $this->load->view('header');?>

<div class="page-header">
	<h1><?php echo "{$keyword}的知识库搜索结果";?></h1>
</div>

<div class="row">
	<div class="col-lg-8">
		<div id="result"><?php foreach($result as $one) { ?>
			<div>
				<h4><strong><?php echo anchor("help/article/kb{$one['kb']}", icon('book').$one['title']);?></strong></h4>
				<p><?php echo character_limiter($one['content'], 300);?></p>
			</div>
		<?php } ?></div>
		
		<hr />
		
		<?php $this->load->view('help/knowledgebase/footerbar');?>
	</div>
	
	<div class="col-lg-4">
		<div>
			<ul class="breadcrumb">
				<li><?php echo anchor('help/knowledgebase', '知识库');?></li>
				<li class="active">搜索结果</li>
			</ul>
		</div>
		
		<?php $this->load->view('help/knowledgebase/sidebar');?>
	</div>
</div>

<?php $this->load->view('footer');?>