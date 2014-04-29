<?php
$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/bootstrap.datatables.css' : 'static/css/bootstrap.datatables.min.css').'" rel="stylesheet">');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/jquery.datatables.js' : 'static/js/jquery.datatables.min.js').'"></script>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/locales/jquery.datatables.locale.js': 'static/js/locales/jquery.datatables.locale.min.js').'"></script>');
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/bootstrap.datatables.js' : 'static/js/bootstrap.datatables.min.js').'"></script>');
$this->ui->html('header', '<link href="'.static_url(is_dev() ? 'static/css/flags.css' : 'static/css/flags.min.css').'" rel="stylesheet">');
$this->load->view('header');?>

<div class="page-header">
	<h1><?php echo icon('list-alt')?>席位信息</h1>
</div>

<div class="row">
	<div class="col-md-8">
		<h3>席位分配</h3>
		<p>我们已经向您分配了 <?php echo $selectability_count;?> 个席位，其中包括了 <?php echo $selectability_primary_count;?> 个主分配席位和 <?php echo $selectability_count - $selectability_primary_count;?> 个候选分配席位。</p>
		<p>您可以在主分配席位中选择 1 个席位为您的参会席位，同时您在主分配席位和候选分配席位中还可以选择最多 <?php echo option('seat_backorder_max', 2);?> 个候选席位。由于其他原因（例如席位已经被其他代表选中或者我们尚未决定是否要设置此席位等），您的面试官无法开放部分席位为主分配席位，因此他将此类席位开放为候选分配席位，您可以现在选择这类席位为候选席位，当选中此席位的代表因故退会或调整席位时，您将有机会调整您的席位为此席位。</p>
		<p>iPlacard 已经加粗显示了面试官推荐的席位。如果您认为您不适合分配的席位，您可以与您的面试官<?php echo anchor('apply/interview', '联系');?>，他将可以根据情况追加席位分配。</p>
		<table id="selectability_list" class="table table-striped table-bordered table-hover table-responsive flags-16">
			<thead>
				<tr>
					<th>ID</th>
					<th>席位名称</th>
					<th>委员会</th>
					<th>类型</th>
					<th>开放时间</th>
				</tr>
			</thead>

			<tbody>

			</tbody>
		</table>
	</div>
	
	<div class="col-md-4">
		<h3>选择席位</h3>
		<p>提交席位选择后，您选择的席位将被临时保留，其他人无法继续选择其为参会席位。</p>
		<p>点击确认席位选择后，我们将为您生成会费帐单，您将可以在 <?php echo option('seat_payment_timeout', 7);?> 天内完成会费支付，支付完成后您的席位将被锁定。如果未能在 <?php echo option('seat_payment_timeout', 7);?> 天内完成会费支付，您的席位将会自动解锁。</p>
		<?php if(!option('seat_select_open', false)) { ?><a id="seat_confirm_lock" data-original-title="席位选择尚未开放" href="#" class="btn btn-primary" data-toggle="popover" data-placement="right" data-content="现在您尚不能选择席位，席位选择功能将在稍后统一开放。" title="">确认席位选择</a><?php } ?>
	</div>
</div>

<?php
$selectability_url = base_url('apply/ajax/list_selectability');
$selectability_js = <<<EOT
$(document).ready(function() {
	$('#selectability_list').dataTable( {
		"aoColumnDefs": [
			{ "bVisible": false, "aTargets": [ 0 ] }
		],
		"bProcessing": true,
		"bAutoWidth": false,
		"sAjaxSource": '{$selectability_url}',
		"sDom": "<'row'r>t<'col-xs-4'i><'col-xs-8'p>>"
	} );
} );
EOT;
$this->ui->js('footer', $selectability_js);

$this->ui->js('footer', "$('#seat_confirm_lock').popover();");
$this->load->view('footer');?>