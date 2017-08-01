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

INSERT INTO `{IP_PREFIX}option` (`name`, `value`) VALUES
  ('organization', '\"\"'), -- 组织名称
  ('site_name', '\"\"'), -- 站点名称
  ('site_contact_email', '\"\"'), -- 管理员邮箱
  ('site_announcement', '\"\"'), -- 全局显示的 HTML 系统广播信息
  ('email_from', '\"\"'), -- 通知邮件来源邮箱
  ('email_from_name', '\"\"'), -- 通知邮件来源名称
  ('api_custom_time', 'true'), -- 允许 API 导入代表时自定义报名时间
  ('auth_imap_enabled', 'false'),  -- 启用 IMAP 登录
  ('auth_imap_domain', '\"\"'), -- IMAP 登录域名名称
  ('auth_imap_flags', '\"ssl\"'), -- IMAP 参数
  ('auth_imap_force', 'false'), -- 强制后台管理员使用 IMAP 登录
  ('auth_imap_port', '993'), -- IMAP 端口
  ('auth_imap_server', '\"\"'), -- IMAP 域名
  ('avatar_max_size', '10485760'), -- 个人头像大小上限
  ('avatar_public', 'true'), -- 允许不登录访问个人头像（外部应用）
  ('avatar_resizable', 'true'), -- 允许动态生成个人头像
  ('batch_application_type', '[\"delegate\"]'), -- 批量模式下生效的申请类型
  ('chart_application_increment_source', '\"reg\"'), -- 每周代表增量统计的指标
  ('chart_application_increment_week', '20'), -- 每周代表增量统计显示的时长
  ('~ciq_cache_life', '86400'), -- 跨实例查询数据缓存有效期
  ('~ciq_instance', '[]'), -- 跨实例查询
  ('cron_remind_delegate_interview', 'true'), -- 发送面试提醒
  ('delegate_delete_lock', '1'), -- 删除代表的缓冲期
  ('delegate_quit_lock', '7'), -- 代表退会禁止登录的缓冲期
  ('document_enable_refused', 'false'), -- 允许审核未通过代表下载文件
  ('document_show_empty', 'false'), -- 系统无任何文件时仍显示文件页面
  ('easteregg_enabled', 'false'), -- 启用彩蛋
  ('~email_account_disabled', '\"\"'), -- 停用帐户通知通知邮件
  ('~email_account_email_change_cancelled', '\"\"'), -- 帐户邮箱变更取消通知邮件
  ('~email_account_email_changed', '\"\"'), -- 帐户邮箱变更通知邮件
  ('~email_account_email_setting_changed', '\"\"'), -- 帐户邮件通知设置变更通知邮件
  ('~email_account_email_verificaiton_cancelled', '\"\"'), -- 帐户邮箱验证取消通知邮件
  ('~email_account_email_verification_lost', '\"\"'), -- 帐户邮箱绑定取消通知邮件
  ('~email_account_email_verified', '\"\"'), -- 帐户邮箱验证通过通知邮件
  ('~email_account_login_notice', '\"\"'), -- 帐户登录通知邮件
  ('~email_account_login_twostep_disabled_via_panel', '\"\"'), -- 通过设置界面停用登录两步验证通知邮件
  ('~email_account_login_twostep_disabled_via_sms', '\"\"'), -- 通过短信验证停用登录两步验证通知邮件
  ('~email_account_login_twostep_enabled', '\"\"'), -- 启用登录两步验证通知邮件
  ('~email_account_password_changed', '\"\"'), -- 密码修改通知邮件
  ('~email_account_password_reset', '\"\"'), -- 密码重置通知邮件
  ('~email_account_phone_changed', '\"\"'), -- 手机号修改通知邮件
  ('~email_account_pin_changed', '\"\"'), -- PIN 码修改通知邮件
  ('~email_account_reenabled', '\"\"'), -- 帐户重新起用通知邮件
  ('~email_account_request_email_confirm', '\"\"'), -- 验证新邮箱通知邮件
  ('~email_account_request_password_reset', '\"\"'), -- 请求重置密码通知邮件
  ('~email_admin_account_created', '\"\"'), -- 新建管理员帐户通知邮件
  ('~email_admin_account_deleted', '\"\"'), -- 删除管理员帐户通知邮件
  ('~email_admin_account_delete_notice', '\"\"'), -- 管理员帐户删除操作通知邮件
  ('~email_admin_committee_deleted', '\"\"'), -- 删除委员会通知邮件
  ('~email_admin_delegate_deleted', '\"\"'), -- 删除代表帐户通知邮件
  ('~email_admin_delegate_disabled', '\"\"'), -- 停用代表帐户通知邮件
  ('~email_admin_password_changed', '\"\"'), -- 行政员更改管理员密码通知邮件
  ('~email_application_locked', '\"\"'), -- 删除委员会通知邮件
  ('~email_cron_delegate_interview_reminder', '\"\"'), -- 定时代表面试提醒通知邮件
  ('~email_cron_interview_reminder', '\"\"'), -- 定时面试官面试提醒通知邮件
  ('~email_delegate_account_created', '\"\"'), -- 申请导入含登录信息通知邮件
  ('~email_delegate_application_passed_delegate', '\"\"'), -- 代表参会申请审核通过通知邮件
  ('~email_delegate_application_passed_observer', '\"\"'), -- 观察员参会申请审核通过通知邮件
  ('~email_delegate_application_passed_volunteer', '\"\"'), -- 志愿者参会申请审核通过通知邮件
  ('~email_delegate_application_passed_teacher', '\"\"'), -- 指导老师参会申请审核通过通知邮件
  ('~email_delegate_application_refused', '\"\"'), -- 参会申请审核未通过通知邮件
  ('~email_delegate_application_refused_no_reason', '\"\"'), -- 参会申请审核未通过（无理由）通知邮件
  ('~email_delegate_deleted', '\"\"'), -- 删除代表帐号通知邮件
  ('~email_delegate_interview_arranged', '\"\"'), -- 安排面试通知邮件
  ('~email_delegate_interview_assigned', '\"\"'), -- 分配面试通知邮件
  ('~email_delegate_interview_cancelled', '\"\"'), -- 取消面试通知邮件
  ('~email_delegate_interview_exempted', '\"\"'), -- 免试通知邮件
  ('~email_delegate_interview_failed', '\"\"'), -- 面试失败通知邮件
  ('~email_delegate_interview_failed_remark', '\"\"'), -- 面试失败（含反馈）通知邮件
  ('~email_delegate_interview_failed_2nd', '\"\"'), -- 二次面试失败通知邮件
  ('~email_delegate_interview_failed_2nd_remark', '\"\"'), -- 二次面试失败（含反馈）通知邮件
  ('~email_delegate_interview_passed', '\"\"'), -- 面试通过通知邮件
  ('~email_delegate_interview_passed_remark', '\"\"'), -- 面试通过（含反馈）通知邮件
  ('~email_delegate_interview_rollbacked', '\"\"'), -- 退回面试通知邮件
  ('~email_delegate_quitted', '\"\"'), -- 退会通知邮件
  ('~email_delegate_recovered', '\"\"'), -- 恢复代表帐户通知邮件
  ('~email_delegate_retest_denied', '\"\"'), -- 拒绝复试请求代表通知邮件
  ('~email_delegate_seat_added', '\"\"'), -- 分配席位选择通知邮件
  ('~email_delegate_seat_appended', '\"\"'), -- 追加席位选择通知邮件
  ('~email_delegate_seat_assigned', '\"\"'), -- 分配席位通知邮件
  ('~email_delegate_seat_reassigned', '\"\"'), -- 再次分配席位通知邮件
  ('~email_delegate_type_changed', '\"\"'), -- 变更申请类型通知邮件
  ('~email_delegate_unquitted', '\"\"'), -- 取消退会通知邮件
  ('~email_delegate_waitlist_accepted', '\"\"'), -- 等待队列通过通知邮件
  ('~email_document_added', '\"\"'), -- 新的文件上传通知邮件
  ('~email_document_updated', '\"\"'), -- 文件更新通知邮件
  ('~email_group_delegate_changed', '\"\"'), -- 更换代表团通知邮件
  ('~email_group_delegate_dissolved', '\"\"'), -- 代表团解散通知邮件
  ('~email_group_delegate_joined', '\"\"'), -- 加入代表团通知邮件
  ('~email_group_delegate_removed', '\"\"'), -- 离开代表团通知邮件
  ('~email_group_manage_delegate_changed', '\"\"'), -- 更换领队通知邮件
  ('~email_group_manage_delegate_granted', '\"\"'), -- 成为领队通知邮件
  ('~email_group_manage_delegate_joined', '\"\"'), -- 成员加入代表团领队通知邮件
  ('~email_group_manage_delegate_removed', '\"\"'), -- 成员离开代表团领队通知邮件
  ('~email_group_manage_group_deleted', '\"\"'), -- 代表团被删除领队通知邮件
  ('~email_interviewer_interview_assigned', '\"\"'), -- 分配面试面试官通知邮件
  ('~email_interviewer_interview_exempted', '\"\"'), -- 免试分配席位分配员通知邮件
  ('~email_interviewer_retest_denied', '\"\"'), -- 拒绝复试请求面试官通知邮件
  ('~email_interviewer_waitlist_accepted', '\"\"'), -- 等待队列分配席位分配员通知邮件
  ('~email_invoice_cancelled', '\"\"'), -- 账单取消通知邮件
  ('~email_invoice_generated', '\"\"'), -- 账单生成通知邮件
  ('~email_invoice_received', '\"\"'), -- 收到账单通知邮件
  ('~email_invoice_reminder', '\"\"'), -- 账单缴费提醒通知邮件
  ('~email_invoice_reminder_overdue', '\"\"'), -- 账单逾期提醒通知邮件
  ('~email_invoice_updated', '\"\"'), -- 账单更新通知邮件
  ('~email_note_user_mentioned', '\"\"'), -- 管理员在笔记中被提及通知邮件
  ('~email_seat_changed', '\"\"'), -- 席位变更通知邮件
  ('~email_seat_overdue_released', '\"\"'), -- 席位未缴费被释放通知邮件
  ('~email_seat_released', '\"\"'), -- 席位释放通知邮件
  ('~email_seat_selected', '\"\"'), -- 席位选择通知邮件
  ('feed_url', '\"\"'), -- 首页显示的 RSS 订阅
  ('file_max_size', '10485760'), -- 最大上传文件大小
  ('interview_enabled', 'true'), -- 启用面试功能
  ('interview_delegate_enabled', 'true'), -- 启用代表面试功能
  ('interview_observer_enabled', 'false'), -- 启用观察员面试功能
  ('interview_volunteer_enabled', 'false'), -- 启用志愿者面试功能
  ('interview_teacher_enabled', 'false'), -- 启用指导老师面试功能
  ('interview_feedback_required', 'true'), -- 强制要求填写面试反馈
  ('interview_feedback_supplement_enabled', 'true'), -- 启用面试内部反馈字段
  ('interview_interviewer_committe', 'true'), -- 代表面试信息界面显示面试官委员会
  ('interview_save_enabled', 'false'), -- 自动保存面试评价
  ('interview_score_standard', '{}'), -- 面试评价体系
  ('interview_score_total', '5'), -- 面试满分分数
  ('invoice_amount_delegate', '0'), -- 代表账单金额（0 为不收取会费无收费流程）
  ('invoice_amount_observer', '0'), -- 观察员账单金额（0 为不收取会费无收费流程）
  ('invoice_amount_volunteer', '0'), -- 志愿者账单金额（0 为不收取会费无收费流程）
  ('invoice_amount_teacher', '0'), -- 指导老师账单金额（0 为不收取会费无收费流程）
  ('invoice_currency_sign', '\"￥\"'), -- 账单货币单位
  ('invoice_currency_text', '\"RMB\"'), -- 账单货币文字
  ('invoice_due_fee', '7'), -- 账单缴纳天数期限
  ('invoice_item_fee', '[]'), -- 账单小项细节
  ('~invoice_item_fee_delegate', '[]'), -- 代表账单小项细节
  ('~invoice_item_fee_observer', '[]'), -- 观察员账单小项细节
  ('~invoice_item_fee_volunteer', '[]'), -- 志愿者账单小项细节
  ('~invoice_item_fee_teacher', '[]'), -- 指导老师账单小项细节
  ('invoice_payment_gateway', '[]'), -- 可登记的账单支付渠道
  ('invoice_payment_offline_info', '\"\"'), -- HTML 账单支付方法
  ('invoice_title_fee', '\"\"'), -- 账单标题
  ('~invoice_title_fee_delegate', '\"\"'), -- 代表账单标题
  ('~invoice_title_fee_observer', '\"\"'), -- 观察员账单标题
  ('~invoice_title_fee_volunteer', '\"\"'), -- 志愿者账单标题
  ('~invoice_title_fee_teacher', '\"\"'), -- 指导老师账单标题
  ('link_rss', '\"\"'), -- 显示在底部的 RSS 链接
  ('link_facebook', '\"\"'), -- 显示在底部的 Facebook 链接
  ('link_github', '\"\"'), -- 显示在底部的 GitHub 链接
  ('link_google', '\"\"'), -- 显示在底部的 Google Plus 链接
  ('link_instagram', '\"\"'), -- 显示在底部的 Instagram 链接
  ('link_linkedin', '\"\"'), -- 显示在底部的 LinkedIn 链接
  ('link_qq', '\"\"'), -- 显示在底部的 QQ 链接
  ('link_renren', '\"\"'), -- 显示在底部的人人链接
  ('link_tencent-weibo', '\"\"'), -- 显示在底部的腾讯微博链接
  ('link_twitter', '\"\"'), -- 显示在底部的 Twitter 链接
  ('link_wechat', '\"\"'), -- 显示在底部的微信链接
  ('link_weibo', '\"\"'), -- 显示在底部的微博链接
  ('link_whatsapp', '\"\"'), -- 显示在底部的 WhatsApp 链接
  ('link_youtube', '\"\"'), -- 显示在底部的 Youtube 链接
  ('link_login', '{}'), -- 显示在登录页面的自定义链接
  ('notice_check_eu_cookie_law_notice', 'false'), -- 启用 Cookies 采集提示
  ('notice_check_internet_explorer_postback', 'false'), -- 启用 Internet Explorer 不兼容检查提示
  ('notice_check_status_invoice_issued', 'false'), -- 启用支付账单提示
  ('notice_check_status_seat_assigned', 'false'), -- 启用选择席位提示
  ('pin_check_enabled', 'false'), -- 启用检查用户的 PIN 是否已经更改
  ('pin_default_password', '\"iPlacard\"'), -- 默认 PIN 密码
  ('profile_addition_general', '{}'), -- 可登记的附加信息
  ('profile_addition_delegate', '{}'), -- 代表可登记的附加信息
  ('profile_addition_observer', '{}'), -- 观察员可登记的附加信息
  ('profile_addition_volunteer', '{}'), -- 志愿者可登记的附加信息
  ('profile_addition_teacher', '{}'), -- 指导老师可登记的附加信息
  ('profile_block', '{}'), -- 个人信息页面（在学术、社团、学测外）额外显示的资料列
  ('profile_list_general', '{}'), -- 个人信息页面默认显示的资料
  ('profile_list_delegate', '{}'), -- 个人信息页面代表申请附加资料
  ('profile_list_observer', '{}'), -- 个人信息页面观察员申请附加资料
  ('profile_list_volunteer', '{}'), -- 个人信息页面志愿者申请附加资料
  ('profile_list_teacher', '{}'), -- 个人信息页面指导老师申请附加资料
  ('profile_list_experience', '{}'), -- 个人信息页面参会经历资料
  ('profile_list_club', '{}'),  -- 个人信息页面社团经历资料
  ('profile_list_test', '[]'), -- 个人信息页面学测题
  ('profile_list_manage', '[]'), -- 可被搜索的资料
  ('profile_list_head_delegate', '{}'), -- 团队页面中额外显示的领队资料
  ('profile_list_related', '{}'), -- 席位页面中额外显示的多代资料
  ('profile_show_test_all', 'false'), -- 显示全部学测题（默认隐藏未答学测题）
  ('profile_special_committee_choice', 'null'), -- 首选委员会特殊资料项
  ('robots_allow', 'true'), -- 允许爬虫
  ('seat_enabled', 'true'), -- 启用席位功能
  ('seat_global_admin', '[1]'), -- 可全局分配席位的管理员 ID 列表
  ('seat_lock_open', 'true'), -- 允许锁定席位完成申请
  ('seat_mode', '\"select\"'), -- 席位分配模式（select 或 assign）
  ('seat_revert_original', 'false'), -- 曾选席位不得再选
  ('seat_select_open', 'true'), -- 开放席位选择
  ('server_download_method', '\"php\"'), -- 系统文件下载方式
  ('signup_enabled', 'true'), -- 启用系统附带的简易报名表（不推荐）
  ('signup_type', '[\"delegate\", \"observer\", \"volunteer\", \"teacher\"]'), -- 可通过系统附带的简易报名表报名的申请类型
  ('signup_unique_identifier', '\"email\"'), -- 系统附带的简易报名表中唯一字段的属性
  ('sms_enabled', 'false'), -- 启用短信
  ('sms_identity', '\"\"'), -- 短信发送方名称
  ('sso_name', '\"name\"'), -- 在 Single Sign-On 连接中作为用户名字传送的字段
  ('twostep_time_range', '60'), -- 两步验证码有效期
  ('ui_background_global_enabled', 'true'), -- 启用背景
  ('ui_background_image', '\"https://static.iplacard.com/img/background.png\"'), -- 默认背景
  ('ui_menu_additional_link', '[]') -- 后台额外的菜单栏
;