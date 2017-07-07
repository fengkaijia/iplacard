SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}admin` (
  `id` smallint(5) UNSIGNED NOT NULL COMMENT '管理员用户ID',
  `title` text COMMENT '职位',
  `committee` smallint(5) UNSIGNED COMMENT '委员会ID',
  `role_reviewer` tinyint(1) NOT NULL DEFAULT '0' COMMENT '申请审核员权限',
  `role_dais` tinyint(1) NOT NULL DEFAULT '0' COMMENT '主席权限',
  `role_interviewer` tinyint(1) NOT NULL DEFAULT '0' COMMENT '面试官权限',
  `role_cashier` tinyint(1) NOT NULL DEFAULT '0' COMMENT '出纳员权限',
  `role_administrator` tinyint(1) NOT NULL DEFAULT '0' COMMENT '站点管理员权限',
  `role_bureaucrat` tinyint(1) NOT NULL DEFAULT '0' COMMENT '行政员权限',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员';

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}api_token` (
  `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '令牌ID',
  `access_token` char(32) NOT NULL COMMENT '访问令牌字串',
  `ip_range` tinytext COMMENT '访问IP范围',
  `note` text COMMENT '注释',
  `permission` json COMMENT '权限',
  `last_activity` int(10) UNSIGNED COMMENT '最后活动',
  PRIMARY KEY (`id`),
  UNIQUE KEY `api_token` (`access_token`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='接口访问令牌';

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}app` (
  `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '应用ID',
  `name` tinytext NOT NULL COMMENT '应用名',
  `type` enum('sso_vanilla') NOT NULL COMMENT '连接类型',
  `token` char(32) NOT NULL COMMENT '访问令牌字串',
  `secret` char(64) COMMENT '密钥字串',
  `info` json COMMENT '连接信息',
  PRIMARY KEY (`id`),
  UNIQUE KEY `app_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='应用';

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}app_role` (
  `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '权限ID',
  `type` enum('user','seat') NOT NULL COMMENT '权限类型',
  `key` smallint(5) UNSIGNED NOT NULL COMMENT '用户或席位',
  `app` tinyint(3) UNSIGNED NOT NULL COMMENT '应用ID',
  `role` tinytext COMMENT '权限',
  PRIMARY KEY (`id`),
  UNIQUE KEY `app_role` (`type`,`key`,`app`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='应用用户权限';

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}committee` (
  `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '委员会ID',
  `name` tinytext NOT NULL COMMENT '委员会名称',
  `abbr` varchar(64) NOT NULL COMMENT '委员会缩写',
  `seat_width` tinyint(3) UNSIGNED COMMENT '一般席位代表容量',
  PRIMARY KEY (`id`),
  UNIQUE KEY `committee_abbr` (`abbr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='委员会';

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}delegate` (
  `id` smallint(5) UNSIGNED NOT NULL COMMENT '用户ID',
  `unique_identifier` varchar(255) NOT NULL COMMENT '唯一身份标识',
  `geolocation` smallint(5) UNSIGNED COMMENT '地理位置',
  `application_type` enum('delegate','observer','volunteer','teacher') COMMENT '申请类型',
  `status` enum('application_imported','review_passed','review_refused','interview_assigned','interview_arranged','interview_completed','waitlist_entered','seat_assigned','seat_selected','invoice_issued','payment_received','locked','quitted','deleted') COMMENT '申请状态',
  `group` smallint(5) UNSIGNED COMMENT '所在代表团',
  PRIMARY KEY (`id`),
  UNIQUE KEY `delegate_identifier` (`unique_identifier`),
  KEY `delegate_status` (`status`),
  KEY `delegate_type` (`application_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='代表';

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}delegate_event` (
  `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '事件ID',
  `delegate` smallint(5) UNSIGNED NOT NULL COMMENT '代表ID',
  `time` int(10) UNSIGNED NOT NULL COMMENT '事件触发时间',
  `event` varchar(64) NOT NULL COMMENT '事件名称',
  `info` json COMMENT '事件信息',
  PRIMARY KEY (`id`),
  KEY `event_delegate` (`delegate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='代表事件日志';

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}delegate_profile` (
  `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '资料ID',
  `delegate` smallint(5) UNSIGNED NOT NULL COMMENT '代表ID',
  `name` varchar(128) NOT NULL COMMENT '资料类型',
  `value` json COMMENT '值',
  `last_modified` int(10) UNSIGNED COMMENT '最后更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `profile_item` (`delegate`,`name`),
  KEY `profile_delegate` (`delegate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='代表资料';

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}document` (
  `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '文件ID',
  `title` tinytext NOT NULL COMMENT '文件标题',
  `description` text COMMENT '文件介绍',
  `user` smallint(5) UNSIGNED NOT NULL COMMENT '上传用户',
  `create_time` int(10) UNSIGNED COMMENT '上传时间',
  `highlight` tinyint(1) COMMENT '是否置顶',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文件';

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}document_access` (
  `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '文件权限ID',
  `document` smallint(5) UNSIGNED NOT NULL COMMENT '文件ID',
  `access` smallint(5) UNSIGNED COMMENT '委员会授权',
  PRIMARY KEY (`id`),
  UNIQUE KEY `access_committee` (`document`,`access`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文件访问权限';

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}document_download` (
  `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '下载记录ID',
  `file` smallint(5) UNSIGNED NOT NULL COMMENT '文件版本ID',
  `user` smallint(5) UNSIGNED NOT NULL COMMENT '下载用户ID',
  `time` int(10) UNSIGNED NOT NULL COMMENT '下载时间',
  `ip` varchar(45) COMMENT '下载请求IP',
  `drm` tinytext COMMENT '版权标识',
  PRIMARY KEY (`id`),
  KEY `download_count` (`file`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文件下载记录';

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}document_file` (
  `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '文件版本ID',
  `document` smallint(5) UNSIGNED NOT NULL COMMENT '文件ID',
  `format` tinyint(3) UNSIGNED NOT NULL COMMENT '文件格式',
  `version` varchar(16) COMMENT '版本号',
  `filetype` varchar(16) NOT NULL COMMENT '文件类型',
  `filesize` int(10) UNSIGNED NOT NULL COMMENT '文件大小',
  `hash` varchar(40) NOT NULL COMMENT '散列值',
  `identifier` tinytext COMMENT '文献标识保护',
  `user` smallint(5) UNSIGNED NOT NULL COMMENT '上传用户',
  `upload_time` int(10) UNSIGNED NOT NULL COMMENT '上传时间',
  PRIMARY KEY (`id`),
  KEY `file_document` (`document`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文件版本';

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}document_format` (
  `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '格式ID',
  `name` tinytext NOT NULL COMMENT '格式名称',
  `detail` text COMMENT '格式信息',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文件格式';

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}geolocation` (
  `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '地理位置ID',
  `parent` smallint(5) UNSIGNED COMMENT '上级位置ID',
  `name` varchar(255) NOT NULL COMMENT '名称',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='地理位置';

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}group` (
  `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '代表团ID',
  `name` varchar(255) NOT NULL COMMENT '代表团名称',
  `head_delegate` smallint(5) UNSIGNED COMMENT '负责代表ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='代表团';

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}interview` (
  `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '面试ID',
  `delegate` smallint(5) UNSIGNED NOT NULL COMMENT '申请者ID',
  `interviewer` smallint(5) UNSIGNED NOT NULL COMMENT '面试官ID',
  `assign_time` int(10) UNSIGNED NOT NULL COMMENT '分配时间',
  `schedule_time` int(10) UNSIGNED COMMENT '安排时间',
  `finish_time` int(10) UNSIGNED COMMENT '完成时间',
  `status` enum('assigned','arranged','completed','exempted','cancelled','failed') NOT NULL COMMENT '状态',
  `score` float COMMENT '总评分',
  `feedback` json COMMENT '反馈',
  PRIMARY KEY (`id`),
  KEY `interview_delegate` (`delegate`),
  KEY `interview_interviewer` (`interviewer`),
  KEY `interview_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='面试';

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}invoice` (
  `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '账单ID',
  `delegate` smallint(5) UNSIGNED NOT NULL COMMENT '代表ID',
  `title` varchar(255) NOT NULL COMMENT '账单名称',
  `items` json COMMENT '账单明细',
  `discounts` json COMMENT '折扣明细',
  `amount` decimal(10,0) NOT NULL COMMENT '账单总额',
  `generate_time` int(10) UNSIGNED NOT NULL COMMENT '账单生成时间',
  `due_time` int(10) UNSIGNED NOT NULL COMMENT '账单到期时间',
  `receive_time` int(10) UNSIGNED COMMENT '账单支付时间',
  `status` enum('unpaid','paid','cancelled','refunded') NOT NULL COMMENT '支付状态',
  `trigger` json COMMENT '操作触发器',
  `transaction` json COMMENT '转账明细',
  `cashier` smallint(5) UNSIGNED COMMENT '确认管理员ID',
  PRIMARY KEY (`id`),
  KEY `invoice_delegate` (`delegate`),
  KEY `invoice_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='账单';

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}knowledgebase` (
  `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '文章ID',
  `kb` mediumint(8) UNSIGNED NOT NULL DEFAULT '0' COMMENT '显示ID',
  `title` varchar(255) NOT NULL COMMENT '文章标题',
  `content` mediumtext NOT NULL COMMENT '文章内容',
  `order` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '知识库排序',
  `create_time` int(10) UNSIGNED NOT NULL COMMENT '创建时间',
  `update_time` int(10) UNSIGNED COMMENT '修改时间',
  `system` tinyint(1) NOT NULL DEFAULT '0' COMMENT '系统文章标识',
  `count` mediumint(8) UNSIGNED NOT NULL DEFAULT '0' COMMENT '访问次数',
  PRIMARY KEY (`id`),
  UNIQUE KEY `knowledgebase_kb` (`kb`),
  KEY `knowledgebase_order` (`order`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='知识库';

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}log` (
  `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `operator` smallint(5) UNSIGNED NOT NULL COMMENT '用户ID',
  `time` int(10) UNSIGNED NOT NULL COMMENT '操作时间',
  `operation` varchar(64) NOT NULL COMMENT '操作名称',
  `value` json COMMENT '信息',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='日志';

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}message` (
  `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '消息ID',
  `sender` smallint(5) UNSIGNED NOT NULL COMMENT '发送用户ID',
  `receiver` smallint(5) UNSIGNED NOT NULL COMMENT '接收用户ID',
  `type` enum('system','user') COMMENT '消息类型',
  `text` text COMMENT '显示信息',
  `time` int(10) UNSIGNED NOT NULL COMMENT '消息发送时间',
  `status` enum('unread','read','archived') NOT NULL COMMENT '消息状态',
  PRIMARY KEY (`id`),
  KEY `message_user` (`receiver`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户消息';

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}note` (
  `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '笔记ID',
  `admin` smallint(5) UNSIGNED NOT NULL COMMENT '注释者ID',
  `delegate` smallint(5) UNSIGNED NOT NULL COMMENT '代表ID',
  `time` int(10) UNSIGNED NOT NULL COMMENT '生成时间',
  `text` text NOT NULL COMMENT '内容',
  `category` tinyint(3) UNSIGNED COMMENT '记录分类',
  PRIMARY KEY (`id`),
  KEY `note_delegate` (`delegate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户注释';

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}note_category` (
  `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '分类ID',
  `name` varchar(128) NOT NULL COMMENT '分类名称',
  `type` varchar(128) COMMENT '分类类型',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='注释笔记分类';

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}note_mention` (
  `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '提及ID',
  `note` smallint(5) UNSIGNED NOT NULL COMMENT '笔记ID',
  `user` smallint(5) UNSIGNED NOT NULL COMMENT '被提及用户ID',
  PRIMARY KEY (`id`),
  KEY `mention_note` (`note`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='笔记中提及';

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}option` (
  `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '设置ID',
  `name` varchar(255) NOT NULL COMMENT '设置名称',
  `value` json COMMENT '值',
  PRIMARY KEY (`id`),
  UNIQUE KEY `option_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统设置';

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}seat` (
  `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '席位ID',
  `committee` smallint(5) UNSIGNED COMMENT '委员会ID',
  `name` text COMMENT '席位名称',
  `primary` int(11) COMMENT '主席位',
  `iso` varchar(16) COMMENT '符合ISO 3166-1标准的国家代码',
  `status` enum('unavailable','available','preserved','assigned','approved','locked') NOT NULL COMMENT '席位状态',
  `delegate` smallint(5) UNSIGNED COMMENT '代表ID',
  `time` int(10) UNSIGNED COMMENT '分配时间',
  `level` tinyint(3) UNSIGNED DEFAULT '1' COMMENT '席位等级',
  PRIMARY KEY (`id`),
  UNIQUE KEY `seat_delegate` (`delegate`),
  KEY `seat_committee` (`committee`),
  KEY `seat_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='席位';

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}seat_selectability` (
  `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '席位许可ID',
  `seat` smallint(5) UNSIGNED NOT NULL COMMENT '席位ID',
  `delegate` smallint(5) UNSIGNED NOT NULL COMMENT '代表ID',
  `admin` smallint(5) UNSIGNED NOT NULL COMMENT '批准管理员ID',
  `recommended` tinyint(1) COMMENT '是否推荐',
  `time` int(10) UNSIGNED NOT NULL COMMENT '批准时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `selectability_access` (`seat`,`delegate`),
  KEY `selectability_seat` (`seat`) USING BTREE,
  KEY `selectability_delegate` (`delegate`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='席位选择许可';

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}session` (
  `id` varchar(128) NOT NULL COMMENT '显示ID',
  `session` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '系统记录ID',
  `ip_address` varchar(45) NOT NULL COMMENT 'IP地址',
  `timestamp` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '最后活动',
  `data` mediumtext NOT NULL COMMENT 'Session数据',
  PRIMARY KEY (`session`),
  KEY `session_id` (`id`) USING BTREE,
  KEY `session_timestamp` (`timestamp`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Session';

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}sms` (
  `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '短信ID',
  `user` smallint(5) UNSIGNED NOT NULL COMMENT '接收用户',
  `phone` varchar(24) COMMENT '接收手机号码',
  `message` text NOT NULL COMMENT '短信内容',
  `status` enum('sending','queue','sent','failed') NOT NULL COMMENT '发送状态',
  `time_in` int(10) UNSIGNED NOT NULL COMMENT '加入队列时间',
  `time_out` int(10) UNSIGNED COMMENT '发送时间',
  `response` json COMMENT 'API接口响应',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='短信通知队列';

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}twostep_recode` (
  `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '记录ID',
  `user` smallint(5) UNSIGNED NOT NULL COMMENT '用户ID',
  `code` varchar(6) NOT NULL COMMENT '使用的验证码',
  `time` int(10) UNSIGNED NOT NULL COMMENT '使用时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='已经使用两步验证码';

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}twostep_safe` (
  `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '代码ID',
  `code` char(32) NOT NULL COMMENT '授权不再两步验证代码',
  `user` smallint(5) UNSIGNED NOT NULL COMMENT '授权用户ID',
  `auth_time` int(10) UNSIGNED NOT NULL COMMENT '授权时间',
  `auth_ip` varchar(45) NOT NULL COMMENT '授权IP',
  `ua` text NOT NULL COMMENT '授权User Agent',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='授权不再要求两步验证记录';

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}user` (
  `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `name` varchar(255) NOT NULL COMMENT '姓名',
  `email` varchar(255) NOT NULL COMMENT 'Email',
  `password` char(60) COMMENT '密码',
  `phone` varchar(24) NOT NULL COMMENT '电话',
  `type` enum('delegate','admin') NOT NULL COMMENT '用户类型',
  `pin_password` varchar(128) COMMENT '安全码',
  `enabled` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否启用登录',
  `last_login` int(10) UNSIGNED COMMENT '最近登录时间',
  `last_ip` varchar(45) COMMENT '最近登录IP',
  `reg_time` int(10) UNSIGNED NOT NULL COMMENT '注册时间',
  PRIMARY KEY (`id`),
  KEY `user_name` (`name`) USING BTREE,
  KEY `user_phone` (`phone`) USING BTREE,
  KEY `user_email` (`email`) USING BTREE,
  KEY `user_type` (`type`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户';

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}user_option` (
  `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '设置ID',
  `user` smallint(5) UNSIGNED NOT NULL COMMENT '用户ID',
  `name` varchar(128) NOT NULL COMMENT '设置名称',
  `value` json COMMENT '值',
  PRIMARY KEY (`id`),
  UNIQUE KEY `option_user` (`user`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户设置';

ALTER TABLE `{IP_PREFIX}knowledgebase` ADD FULLTEXT KEY `knowledgebase_content` (`content`);