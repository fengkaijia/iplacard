<?php
$this->ui->html('header', "<style>
.book {
    padding: 10px 0;
    margin: 0 auto 20px;
    text-align: center;
	font-style: italic;
}
.book h1 {
    font-size: 44px;
    font-weight: 100;
    line-height: 1.5;
}

.book h1 em {
    font-size: 52px;
}

.book .from {
    font-size: 36px;
    line-height: 1.5;
	text-align: right;
}
</style>");
$this->load->view('header');?>

<div class="container">
	<div class="book">
		<h1>这是<em>理想者</em>的欢乐颂，这是<em>完美主义</em>的赞美诗。</h1>
		<br />
		<p class="from">来自爱梦之书，2.0</p>
	</div>
</div>

<?php
$js = "
if($(window).height() > ($('.book').height() + $('.navbar').height() + $('#footer').height()))
{
	var head = ($(window).height() - $('.book').height() - $('#footer').height()) / 3.5;
	
	$('.book').css({'margin-top': head});
}";
$this->ui->js('footer', $js);
$this->load->view('footer');?>