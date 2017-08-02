<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 搜索知识库视图
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
 * @package iPlacard
 * @since 2.0
 */

$this->load->view('header');?>

<div class="page-header">
	<h1><?php echo "{$keyword}的知识库搜索结果";?></h1>
</div>

<div class="row">
	<div class="col-lg-8">
		<div id="result"><?php foreach($result as $one) { ?>
			<div>
				<h4><strong><?php echo anchor("knowledgebase/article/kb{$one['kb']}", icon('book').$one['title']);?></strong></h4>
				<p><?php echo character_limiter(strip_tags($one['content']), 300);?></p>
			</div>
		<?php } ?></div>
		
		<hr />
		
		<?php $this->load->view('help/knowledgebase/footerbar');?>
	</div>
	
	<div class="col-lg-4">
		<div>
			<ul class="breadcrumb">
				<li><?php echo anchor('knowledgebase', '知识库');?></li>
				<li class="active">搜索结果</li>
			</ul>
		</div>
		
		<?php $this->load->view('help/knowledgebase/sidebar');?>
	</div>
</div>

<?php $this->load->view('footer');?>