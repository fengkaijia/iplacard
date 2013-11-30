/**
 * Chinese translation of DataTables
 * @author Chi Cheng
 */
$.extend( true, $.fn.dataTable.defaults, {
	"oLanguage": {
		"sProcessing": "载入中...",
		"sLengthMenu": "显示 _MENU_ 项结果",
		"sZeroRecords": "没有匹配结果",
		"sInfo": "显示第 _START_ 至 _END_ 项数据，共 _TOTAL_ 项",
		"sInfoEmpty": "无可用数据",
		"sInfoFiltered": "（筛选自 _MAX_ 项数据）",
		"sInfoPostFix": "",
		"sSearch": "搜索：",
		"sUrl": "",
		"sEmptyTable": "数据表为空",
		"sLoadingRecords": "载入中...",
		"sInfoThousands": ",",
		"oPaginate": {
			"sFirst": "首页",
			"sPrevious": "上页",
			"sNext": "下页",
			"sLast": "末页"
		},
		"oAria": {
			"sSortAscending": "：升序排列此列",
			"sSortDescending": "：降序排列此列"
		}
	}
} );