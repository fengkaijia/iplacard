<h3 id="admission_operation">分配席位</h3>

<?php if($interview['status'] == 'completed' && !$score_level) { ?><p>代表于 <em><?php echo date('Y-m-d H:i', $interview['finish_time']);?></em> 通过面试，面试得分为 <strong><?php echo round($interview['score'], 2);?></strong>，满分为 <?php echo $score_total;?> 分。现在您可以为代表分配席位选择。</p><?php }
elseif($interview['status'] == 'completed' && $score_level) { ?><p>代表于 <em><?php echo date('Y-m-d H:i', $interview['finish_time']);?></em> 通过面试，面试得分为 <strong><?php echo round($interview['score'], 2);?></strong>，此成绩大约位于前 <?php echo $score_level;?>%，满分为 <?php echo $score_total;?> 分。现在您可以为代表分配席位选择。</p><?php }
elseif($interview['status'] == 'exempted') { ?><p>代表于 <em><?php echo date('Y-m-d H:i', $interview['assign_time']);?></em> 免试通过面试。现在您可以直接为代表分配席位选择。<?php } ?>

<p><a class="btn btn-primary" href="<?php echo base_url("seat/assign/$uid");?>"><?php echo icon('th-list');?>分配席位</a></p>

<hr />