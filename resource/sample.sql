SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

INSERT INTO `{IP_PREFIX}api_token` (`access_token`, `ip_range`, `note`, `permission`, `last_activity`) VALUES
('sample_access_token', '127.0.0.0-127.0.0.254', '示例', '[\"sample_permission\"]', NULL);

INSERT INTO `{IP_PREFIX}app` (`name`, `type`, `token`, `secret`, `info`) VALUES
('示例SSO登录应用', 'sso_vanilla', 'sample_access_token', 'sample_secret_token', NULL);

INSERT INTO `{IP_PREFIX}app_role` (`type`, `key`, `app`, `role`) VALUES
('user', 1, 1, 'administrator');

INSERT INTO `{IP_PREFIX}document_format` (`name`, `detail`) VALUES
('标准版', '标准格式文件');

INSERT INTO `{IP_PREFIX}geolocation` (`name`) VALUES
('北京'), ('天津'), ('河北'), ('山西'), ('内蒙古'), ('辽宁'), ('吉林'),
('黑龙江'), ('上海'), ('江苏'), ('浙江'), ('安徽'), ('福建'), ('江西'),
('山东'), ('河南'), ('湖北'), ('湖南'), ('广东'), ('广西'), ('海南'),
('重庆'), ('四川'), ('贵州'), ('云南'), ('西藏'), ('陕西'), ('甘肃'),
('青海'), ('宁夏'), ('新疆'), ('香港'), ('澳门'), ('台湾'), ('海外');

INSERT INTO `{IP_PREFIX}note_category` (`name`) VALUES
('示例笔记分类');

UPDATE `{IP_PREFIX}option` SET `value` = '\"示例模联会议\"' WHERE `name` = 'organization';
UPDATE `{IP_PREFIX}option` SET `value` = '\"iPlacard 会议管理系统\"' WHERE `name` = 'site_name';
UPDATE `{IP_PREFIX}option` SET `value` = '\"contact@iplacard.com\"' WHERE `name` = 'site_contact_email';
UPDATE `{IP_PREFIX}option` SET `value` = '\"这是一条 iPlacard 示例公告，现在你可以在 option 表中删除它。\"' WHERE `name` = 'site_announcement';
UPDATE `{IP_PREFIX}option` SET `value` = '\"no-reply@iplacard.com\"' WHERE `name` = 'email_from';
UPDATE `{IP_PREFIX}option` SET `value` = '\"iPlacard\"' WHERE `name` = 'email_from_name';
UPDATE `{IP_PREFIX}option` SET `value` = '\"iPlacard.com\"' WHERE `name` = 'auth_imap_domain';
UPDATE `{IP_PREFIX}option` SET `value` = '\"imap.iplacard.com\"' WHERE `name` = 'auth_imap_server';
UPDATE `{IP_PREFIX}option` SET `value` = '[{\"api\": \"https://example.iplacard.com\", \"name\": \"去年的 iPlacard\", \"access_token\": \"77f50563ab91c15b893e5b6403db9889\"}]' WHERE `name` = '~ciq_instance';
UPDATE `{IP_PREFIX}option` SET `value` = '{\"skill\": {\"name\": \"学术\", \"weight\": 0.6}, \"passion\": {\"name\": \"热情\", \"weight\": 0.4}}' WHERE `name` = 'interview_score_standard';
UPDATE `{IP_PREFIX}option` SET `value` = '800' WHERE `name` = 'invoice_amount_delegate';
UPDATE `{IP_PREFIX}option` SET `value` = '300' WHERE `name` = 'invoice_amount_observer';
UPDATE `{IP_PREFIX}option` SET `value` = '[\"参会费\", \"住宿费\"]' WHERE `name` = 'invoice_item_fee';
UPDATE `{IP_PREFIX}option` SET `value` = '[\"席位费\", \"参会费\", \"住宿费\"]' WHERE `name` = 'invoice_item_fee_delegate';
UPDATE `{IP_PREFIX}option` SET `value` = '[\"支付宝\", \"银行汇款\"]' WHERE `name` = 'invoice_payment_gateway';
UPDATE `{IP_PREFIX}option` SET `value` = '\"iPlacard 不含席位代表参会会费\"' WHERE `name` = 'invoice_title_fee';
UPDATE `{IP_PREFIX}option` SET `value` = '\"iPlacard 含席位代表参会会费\"', `name` = 'invoice_title_fee_delegate' WHERE `name` = '~invoice_title_fee_delegate';
UPDATE `{IP_PREFIX}option` SET `value` = '{\"link\": \"http://iplacard.com\", \"text\": \"示例自定义链接\"}' WHERE `name` = 'link_login';
UPDATE `{IP_PREFIX}option` SET `value` = '{\"roommate\": \"示例自定义室友信息资料列\"}' WHERE `name` = 'profile_block';
UPDATE `{IP_PREFIX}option` SET `value` = '{\"gender\": \"性别\", \"school\": \"学校\", \"grade\": \"年级\", \"idcard\": \"身份证\", \"qq\": \"QQ\", \"wechat\": \"微信\"}' WHERE `name` = 'profile_list_general';
UPDATE `{IP_PREFIX}option` SET `value` = '{\"choice\": \"委员会意向\"}' WHERE `name` = 'profile_list_delegate';
UPDATE `{IP_PREFIX}option` SET `value` = '{\"rank\": \"级别\", \"conference\": \"会议名称\", \"role\": \"职位\", \"year\": \"年份\", \"award\": \"获奖\", \"remark\": \"备注\"}' WHERE `name` = 'profile_list_experience';
UPDATE `{IP_PREFIX}option` SET `value` = '{\"club\": \"社团名称\", \"rank\": \"级别\", \"role\": \"职位\", \"remark\": \"备注\"}' WHERE `name` = 'profile_list_club';
UPDATE `{IP_PREFIX}option` SET `value` = '[\"你为什么参加模联？\", \"你想象中十年前的模联是怎么样的？\"]' WHERE `name` = 'profile_list_test';
UPDATE `{IP_PREFIX}option` SET `value` = '[\"qq\"]' WHERE `name` = 'profile_list_manage';
UPDATE `{IP_PREFIX}option` SET `value` = '{\"qq\": \"QQ\"}' WHERE `name` = 'profile_list_head_delegate';
UPDATE `{IP_PREFIX}option` SET `value` = '{\"qq\": \"QQ\"}' WHERE `name` = 'profile_list_related';
UPDATE `{IP_PREFIX}option` SET `value` = '\"idcard\"' WHERE `name` = 'signup_unique_identifier';
UPDATE `{IP_PREFIX}option` SET `value` = '\"会议模联\"' WHERE `name` = 'sms_identity';
UPDATE `{IP_PREFIX}option` SET `value` = 'true' WHERE `name` = 'signup_test';
UPDATE `{IP_PREFIX}option` SET `value` = '1' WHERE `name` = 'signup_test_count';
INSERT INTO `{IP_PREFIX}option` (`name`, `value`) VALUES ('profile_list_roommate', '{\"roommate_name\": \"室友姓名\", \"roommate_email\": \"室友邮箱\", \"roommate_phone\": \"室友手机\"}');