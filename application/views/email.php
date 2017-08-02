<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 邮件模板视图
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2013 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
 * @package iPlacard
 * @since 2.0
 */
?><html>

<head></head>

<body>
<div style="font-family: 'Segoe UI', 'Segoe UI Web Regular', Tahoma, Verdana, Arial, sans-serif; font-size: 14px; color: #454545; line-height: normal; direction: ltr; padding: 0px 2px;">
	<div style="background-color: #007fff; color: #ffffff; padding: 40px 30px 40px;">
		<div style="font-family: 'Segoe UI Web Light', 'Segoe UI Light', 'Segoe UI Web Regular', 'Segoe UI', 'Helvetica Neue UltraLight', Arial, sans-serif; font-size: 40px; line-height: 40px;">iPlacard</div>
		<div style="font-family: 'Segoe UI Web Semibold', 'Segoe UI Web Regular', 'Segoe UI', 'Helvetica Neue Medium', Arial, sans-serif; font-size: 18px; line-height: 25px;color: #ffffff; padding-top: 15px;"><?php echo sprintf("%s通知邮件", option('site_name'));?></div>
	</div>
	
	<div style="padding: 22px 30px;">
		<div style="padding-top: 30px;">
			<div style="font-family: 'Segoe UI Web Semibold', 'Segoe UI Web Regular', 'Segoe UI', 'Helvetica Neue Medium', Arial, sans-serif; color: #007fff; font-size: 18px; line-height: 25px;"><?php echo $title;?></div>
			<div style="font-size: 14px; line-height: 20px; padding-top: 5px;"><?php echo $text;?></div>
		</div>
		<div style="font-family: 'Segoe UI Web Semibold', 'Segoe UI Web Regular', 'Segoe UI', 'Helvetica Neue Medium', Arial, sans-serif; color: #007fff; line-height: 25px; padding-top: 20px; padding-bottom: 20px;">
			<table cellpadding="0" cellspacing="0" border="0">
				<tr>
					<td width="16" bgcolor="#007fff">&nbsp;</td>
					<td bgcolor="#007fff" style="color:#ffffff; font-size:16px; padding:10px 2px;">
						<a href="<?php echo base_url();?>" style="text-decoration:none !important; color:#ffffff !important;">立即访问 iPlacard</a>
					</td>
					<td width="16" bgcolor="#007fff">&nbsp;</td>
				</tr>
			</table>
		</div>
	</div>
	
	<div style="background-color: #bbbbbb; color: #ffffff; font-family: Arial, sans-serif; font-size: 12px; padding: 30px;">
		<div>此邮件为 iPlacard 系统通知邮件，请勿直接回复此邮件。</div>
			
		<div style="padding-top: 10px;">您可随时访问<?php echo anchor('account/activity', '当前会话活动', 'style="color: #ffffff;"');?>页面查看您的登录记录或者<?php echo anchor('account/settings/password', '修改密码', 'style="color: #ffffff;"');?>。如果忘记了登录密码，请使用<?php echo anchor('account/recover', '帐户恢复功能', 'style="color: #ffffff;"');?>重置您的密码。建议将此发件地址添加到您的白名单或者联系人列表中。</div>
		
		<div style="padding-top: 10px;"><?php echo option('organization', 'iPlacard'); ?> 尊重您的隐私，并且非常注重保护您的信息安全。此邮件中可能会包含密码等敏感信息，请妥善保存。</div>
	</div>
</div>
</body>
</html>