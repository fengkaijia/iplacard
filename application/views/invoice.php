<?php
$item_amount = 0;
$discount_amount = 0;
?><div id="invoice">
	<table class="table table-bordered">
		<tr>
			<td width="50%">
				<h4>支付人</h4>
				<address>
					<strong><?php echo $delegate['name'];?></strong><?php echo $delegate['application_type_text'];?><br />
					<?php if(!empty($delegate['group'])) { echo $group['name'].'代表团'; ?><br /><?php } ?>
					<?php echo icon('envelope-o').$delegate['email'];?><br />
					<?php echo icon('user').$delegate['phone'];?>
				</address>
			</td>
			<td width="50%">
				<h4>收付方</h4>
				<address>
					<strong><?php echo option('organization', 'iPlacard');?></strong><br />
					<?php echo icon('envelope-o').option('site_contact_email', 'contact@iplacard.net');?>
				</address>
			</td>
		</tr>
	</table>

	<h3 style="padding-bottom: 8px;">帐单 #<?php echo $invoice['id'];?></h3>
	
	<h4><?php echo $invoice['title'];?></h4>
	
	<table width="100%">
		<tr>
			<td width="65%">
				<p>生成日期：<?php echo date('Y年m月d日', $invoice['generate_time']);?></p>
				<p>到期时间：<?php echo date('Y年m月d日', $invoice['due_time']);?></p>
			</td>
			<td width="35%">
				<p>支付状态：<strong class="text-<?php echo $invoice['status_class'];?>"><?php echo $invoice['status_text'];?></strong></p>
				<p><?php if($invoice['status'] == 'paid') echo '支付于'.date('Y年m月d日', $invoice['receive_time']);?>&nbsp;</p>
			</td>
		</tr>
	</table>
	
	<br />

	<table class="table table-bordered table-striped table-hover table-middle">
		<thead>
			<th width="75%">帐单项目</th>
			<th width="25%">金额</th>
		</thead>
		<tbody>
			<?php foreach($invoice['items'] as $item)
			{
				$item_amount += $item['amount']; ?><tr>
				<td>
					<?php echo $item['title'];?>
					<?php if(!empty($item['detail']))
					{
						foreach($item['detail'] as $detail) { ?><br /><small>- <?php echo $detail;?></small><?php }
					} ?>
				</td>
				<td><?php echo $currency['sign'].number_format($item['amount'], 2).' '.$currency['text'];?></td>
			</tr><?php }
			if(!empty($invoice['discounts']))
			{
				foreach($invoice['discounts'] as $discount)
				{ 
					$discount_amount += $discount['amount']; ?><tr>
					<td>
						<?php echo $discount['title'];?>
					</td>
					<td><?php echo '-'.$currency['sign'].number_format($discount['amount'], 2).' '.$currency['text'];?></td>
				</tr><?php }
			} ?>
		</tbody>
		<tfoot>
			<tr>
				<td style="text-align: right;">帐单小计</td>
				<td><?php echo $currency['sign'].number_format($item_amount, 2).' '.$currency['text'];?></td>
			</tr>
			<tr>
				<td style="text-align: right;">减免优惠</td>
				<td><?php
				if($discount_amount > 0)
					echo '-'.$currency['sign'].number_format($discount_amount, 2).' '.$currency['text'];
				else
					echo 'N/A';
				?></td>
			</tr>
			<tr>
				<td style="font-weight: 700; text-align: right;">总计</td>
				<td style="font-size: larger; font-weight: 700; text-align: right;"><?php echo $currency['sign'].number_format($invoice['amount'], 2).' '.$currency['text'];?></td>
			</tr>
		</tfoot>
	</table>
	
	<br />
	
	<h4>转帐记录</h4>
	
	<table class="table table-bordered table-striped table-hover table-middle">
		<thead>
			<th width="25%">转帐时间</th>
			<th width="25%">交易渠道</th>
			<th width="25%">交易流水号</th>
			<th width="25%">转帐金额</th>
		</thead>
		<tbody>
			<?php if(!empty($invoice['transaction'])) { ?><tr>
				<td><?php echo !empty($invoice['transaction']['time']) ? unix_to_human($invoice['transaction']['time']) : 'N/A';?></td>
				<td><?php echo !empty($invoice['transaction']['gateway']) ? $invoice['transaction']['gateway'] : 'N/A';?></td>
				<td><?php echo !empty($invoice['transaction']['transaction']) ? $invoice['transaction']['transaction'] : 'N/A'; 
				if(!$invoice['transaction']['confirm'])
					echo '<abbr title="此笔交易尚未被管理员确认。">'.icon('question-circle', false).'</abbr>';
				?></td>
				<td><?php echo !empty($invoice['transaction']['amount']) ? ($currency['sign'].number_format($invoice['transaction']['amount'], 2).' '.$currency['text']) : 'N/A';?></td>
			</tr><?php } else { ?><tr><td style="text-align: center;" colspan="4">暂无记录</td></tr><?php } ?>
		</tbody>
		
		<tfoot>
			<tr>
				<td style="font-weight: 700; text-align: right;" colspan="3">总计</td>
				<td style="font-weight: 700;"><?php echo $currency['sign'].number_format($invoice['transaction']['amount'], 2).' '.$currency['text'];?></td>
			</tr>
		</tfoot>
	</table>
	
	<p><small>
		* 在支付页面显示的收款人信息可能与支付界面、银行帐户显示信息不同。<br />
		* 根据相关会费政策，减免或优惠可能不会体现在帐单中。<br />
		* 显示有<?php echo icon('question-circle', false);?>标记的交易尚未被管理员确认。
	</small></p>
</div>