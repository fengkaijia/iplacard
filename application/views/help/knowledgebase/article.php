<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 知识库文章视图
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
 * @package iPlacard
 * @since 2.0
 */

$this->load->view('header');?>

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
			printf('%1$s（%2$s最后更新）', date('Y年m月d日', $article['create_time']), nicetime($article['update_time']));
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
				<li><?php echo anchor('knowledgebase', '知识库');?></li>
				<li class="active"><?php echo character_limiter($article['title'], 25);?></li>
			</ul>
		</div>
		
		<?php $this->load->view('help/knowledgebase/sidebar');?>
	</div>
</div>

<?php $this->load->view('footer');?>