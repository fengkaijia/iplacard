<?php
$this->ui->html('header', "<style>
.about {
    padding: 10px 0;
    margin: 0 auto 20px;
    text-align: center;
}
.about h1 {
    font-size: 72px;
    font-weight: 100;
    font-family: 'Open Sans';
    line-height: 1;
}
.about p {
    font-size: 24px;
    line-height: 1;
}
</style>");
$this->load->view('header');?>

<div class="container">
	<div class="about">
		<h1>iPlacard</h1>
		<br />
		<p>由 <a href="http://imunc.com">IMUNC</a> 支持，用户友好、功能现代的下一代模拟联合国会议管理系统。</p>
		<br />
		<a href="<?php echo base_url();?>" class="btn btn-primary btn-lg">立即使用</a>
	</div>
</div>

<?php
$js = "
if($(window).height() > ($('.about').height() + $('.navbar').height() + $('#footer').height()))
{
	var head = ($(window).height() - $('.about').height() - $('#footer').height()) / 3.5
	;
	$('.about').css({'margin-top':head});
}";
$this->ui->js('footer', $js);
$this->load->view('footer');?>