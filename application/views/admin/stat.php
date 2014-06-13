<?php
$this->ui->html('header', '<script src="'.static_url(is_dev() ? 'static/js/echarts.js' : 'static/js/echarts.min.js').'"></script>');
$this->load->view('header');?>

<div class="page-header">
	<h1>统计分析</h1>
</div>

<div class="row">
	<div class="col-md-6">
		<div id="ui_chart_application_increment" class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">周代表增量统计</h3>
			</div>
			<div class="panel-body">
				<div id="stat_chart_application_increment"></div>
			</div>
		</div>
		
		<div id="ui_chart_interview_<?php echo $stat_interview;?>" class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">面试评分分布统计</h3>
			</div>
			<div class="panel-body">
				<div id="stat_chart_interview_<?php echo $stat_interview;?>"></div>
			</div>
		</div>
	</div>
	
	<div class="col-md-6">
		<div id="ui_chart_application_status" class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">申请状态分布统计</h3>
			</div>
			<div class="panel-body">
				<div id="stat_chart_application_status"></div>
			</div>
		</div>
		
		<div id="ui_chart_seat_status" class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">席位状态统计</h3>
			</div>
			<div class="panel-body">
				<div id="stat_chart_seat_status"></div>
			</div>
		</div>
	</div>
</div>

<?php
$chart_url = base_url('admin/ajax/stat');
$chart_js = <<<EOT
function draw_chart(type) {
	$('#stat_chart_' + type).css({'height': '360px'});
	$('#stat_chart_' + type).css({'margin': '0'});

	var stat = echarts.init(document.getElementById('stat_chart_' + type));
	var chart_option;

	stat.showLoading({
		text: '正在加载数据....',
		effect: 'whirling',
		backgroundColor: 'rgba(0, 0, 0, 0)'
	});

	$.ajax({
		url: '{$chart_url}?chart=' + type,
		dataType: 'json',
		success: function( result ) {
			if(result) {
				$('#ui_chart_' + type + ' .panel-body').css({'padding': '15px 0 15px 0'});

				switch(type) {
					case 'application_increment':
						chart_option = chart_option_application_increment;

						chart_option.xAxis[0].data = result.category;
						chart_option.series[0].data = result.series['delegate'];
						chart_option.series[1].data = result.series['observer'];
						chart_option.series[2].data = result.series['volunteer'];
						chart_option.series[3].data = result.series['teacher'];
		
						$('#stat_chart_application_increment').css({'margin': '-30px -30px 0'});
						break;

					case 'application_status':
						chart_option = chart_option_application_status;

						chart_option.legend.data = result.legend;
						chart_option.series[0].data = result.series;
		
						$('#stat_chart_application_status').css({'margin': '-30px 0 0'});
						break;

					case 'interview_2d':
						chart_option = chart_option_interview_2d;

						chart_option.series[0].data = result.series['failed'];
						chart_option.series[1].data = result.series['passed'];
						
						$('#ui_chart_interview_2d .panel-body').css({'padding': '0'});
						break;

					case 'seat_status':
						chart_option = chart_option_seat_status;

						chart_option.xAxis[0].data = result.category;
						chart_option.series[0].data = result.series['available'];
						chart_option.series[1].data = result.series['assigned'];
						chart_option.series[2].data = result.series['interview'];
		
						$('#stat_chart_seat_status').css({'margin': '-30px -30px 0'});
						break;
				}
		
				stat = echarts.init(document.getElementById('stat_chart_' + type));
				stat.hideLoading();
				stat.setOption(chart_option);
			} else {
				$('#ui_chart_' + type).removeClass('panel-default').addClass('panel-warning');
				$('#stat_chart_' + type).css({'height': 'auto'});
				$('#stat_chart_' + type).html('无可用数据。');
			}
		},
		error: function ( error ) {
			$('#ui_chart_' + type).removeClass('panel-default').addClass('panel-warning');
			$('#stat_chart_' + type).css({'height': 'auto'});
			$('#stat_chart_' + type).html('载入数据失败。');
		}
	});
}

$(document).ready(function() {
	draw_chart('application_increment');
	draw_chart('application_status');
	draw_chart('interview_{$stat_interview}');
	draw_chart('seat_status');
});
EOT;
$this->ui->js('footer', $chart_js);
$this->load->view('footer');?>