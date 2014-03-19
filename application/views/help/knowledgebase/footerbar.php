<div class="row">
	<div class="col-lg-6">
		<div class="panel panel-default">
			<div class="panel-heading">置顶知识库文章</div>
			<div class="list-group">
				<?php foreach($highlight as $kb => $one) {
					echo anchor("help/article/kb{$kb}", $one['title'], array('class' => (isset($article['kb']) && $kb == $article['kb']) ? 'list-group-item active' : 'list-group-item'));
				}?>
			</div>
		</div>
	</div>
	
	<div class="col-lg-6">
		<div class="panel panel-default">
			<div class="panel-heading">热门知识库文章</div>
			<div class="list-group">
				<?php foreach($popular as $kb => $one) {
					echo anchor("help/article/kb{$kb}", "<span class=\"badge\">{$one['count']}</span>{$one['title']}", array('class' => (isset($article['kb']) && $kb == $article['kb']) ? 'list-group-item active' : 'list-group-item'));
				}?>
			</div>
		</div>
	</div>
</div>