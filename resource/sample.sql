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