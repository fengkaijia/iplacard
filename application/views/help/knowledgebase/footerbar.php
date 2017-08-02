<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 知识库子视图
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
 * @package iPlacard
 * @since 2.0
 */
?><div class="row">
	<div class="col-lg-6">
		<div class="panel panel-default">
			<div class="panel-heading">置顶知识库文章</div>
			<div class="list-group">
				<?php foreach($highlight as $kb => $one) {
					echo anchor("knowledgebase/article/kb{$kb}", character_limiter($one['title'], 30), array('class' => (isset($article['kb']) && $kb == $article['kb']) ? 'list-group-item active' : 'list-group-item'));
				} ?>
			</div>
		</div>
	</div>
	
	<div class="col-lg-6">
		<div class="panel panel-default">
			<div class="panel-heading">热门知识库文章</div>
			<div class="list-group">
				<?php foreach($popular as $kb => $one) {
					echo anchor("knowledgebase/article/kb{$kb}", '<span class="badge">'.$one['count'].'</span>'.character_limiter($one['title'], 30), array('class' => (isset($article['kb']) && $kb == $article['kb']) ? 'list-group-item active' : 'list-group-item'));
				} ?>
			</div>
		</div>
	</div>
</div>