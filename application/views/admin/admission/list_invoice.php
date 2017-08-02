<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 显示账单功能视图
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
 * @package iPlacard
 * @since 2.0
 */

if(count($invoice_unpaid) > 0) { ?><p>代表当前共有 <?php echo $invoice_count;?> 份账单，其中包含 <?php echo count($invoice_unpaid);?> 份未支付账单。</p><?php }
else { ?><p>代表共有 <?php echo $invoice_count;?> 份账单。</p><?php } ?>

<?php if(count($invoice_unpaid) == 1) { ?><div class="btn-group" style="margin-bottom: 10.5px;">
	<a class="btn btn-primary" href="<?php echo base_url("billing/invoice/{$invoice_unpaid[0]}");?>"><?php echo icon('file-text');?>查看账单 #<?php echo $invoice_unpaid[0];?></a>
	<button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
	<ul class="dropdown-menu">
		<li><a href="<?php echo base_url("billing/manage?delegate=$uid");?>">查看代表全部账单</a></li>
	</ul>
</div><?php } else { ?>
<p><a class="btn btn-primary" href="<?php echo base_url("billing/manage?delegate=$uid");?>"><?php echo icon('file-text');?>查看代表账单</a></p>
<?php } ?>
