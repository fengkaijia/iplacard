SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}admin` (
  `id` int(11) NOT NULL COMMENT '管理员用户ID',
  `title` text COMMENT '职位',
  `committee` int(11) DEFAULT NULL COMMENT '委员会ID',
  `role_reviewer` int(11) NOT NULL DEFAULT '0' COMMENT '申请审核员权限',
  `role_dais` int(11) NOT NULL DEFAULT '0' COMMENT '主席权限',
  `role_interviewer` int(11) NOT NULL DEFAULT '0' COMMENT '面试官权限',
  `role_cashier` int(11) NOT NULL DEFAULT '0' COMMENT '出纳员权限',
  `role_administrator` int(11) NOT NULL DEFAULT '0' COMMENT '站点管理员权限',
  `role_bureaucrat` int(11) NOT NULL DEFAULT '0' COMMENT '行政员权限',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='管理员';

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}api_token` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '令牌ID',
  `access_token` text NOT NULL COMMENT '访问令牌字串',
  `ip_range` text COMMENT '访问IP范围',
  `note` text COMMENT '注释',
  `permission` text COMMENT '权限',
  `last_activity` int(11) DEFAULT NULL COMMENT '最后活动',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='接口访问令牌' AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}app` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '应用ID',
  `name` text NOT NULL COMMENT '应用名',
  `type` text NOT NULL COMMENT '连接类型',
  `token` text NOT NULL COMMENT '访问令牌字串',
  `secret` text COMMENT '密钥字串',
  `info` text COMMENT '连接信息',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='应用' AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}app_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '权限ID',
  `type` text NOT NULL COMMENT '权限类型',
  `key` int(11) NOT NULL COMMENT '用户或席位',
  `app` int(11) NOT NULL COMMENT '应用ID',
  `role` text COMMENT '权限',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='应用用户权限' AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}committee` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '委员会ID',
  `name` text NOT NULL COMMENT '委员会名称',
  `abbr` text NOT NULL COMMENT '委员会缩写',
  `description` text COMMENT '委员会介绍',
  `type` text COMMENT '委员会席位类型',
  `seat_width` int(11) DEFAULT NULL COMMENT '一般席位代表容量',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='委员会' AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}delegate` (
  `id` int(11) NOT NULL COMMENT '代表用户ID',
  `unique_identifier` text NOT NULL COMMENT '唯一身份标识',
  `geolocation` int(11) DEFAULT NULL COMMENT '地理位置',
  `application_type` text NOT NULL COMMENT '申请类型',
  `status` text NOT NULL COMMENT '申请状态',
  `group` int(11) DEFAULT NULL COMMENT '所在代表团',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='代表';

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}delegate_event` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '事件ID',
  `delegate` int(11) NOT NULL COMMENT '代表ID',
  `time` int(11) NOT NULL COMMENT '事件触发时间',
  `event` text NOT NULL COMMENT '事件名称',
  `info` text COMMENT '事件信息',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='代表事件日志' AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}delegate_profile` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '资料ID',
  `delegate` int(11) NOT NULL COMMENT '代表ID',
  `name` text NOT NULL COMMENT '资料类型',
  `value` text COMMENT '值',
  `last_modified` int(11) DEFAULT NULL COMMENT '最后更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='代表资料' AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}document` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '文件ID',
  `title` text NOT NULL COMMENT '文件标题',
  `description` text COMMENT '文件介绍',
  `user` int(11) NOT NULL COMMENT '上传用户',
  `create_time` int(11) DEFAULT NULL COMMENT '上传时间',
  `highlight` int(11) DEFAULT NULL COMMENT '是否置顶',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='文件' AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}document_access` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '文件权限ID',
  `document` int(11) NOT NULL COMMENT '文件ID',
  `access` int(11) DEFAULT NULL COMMENT '委员会授权',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='文件访问权限' AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}document_download` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '下载记录ID',
  `file` int(11) NOT NULL COMMENT '文件版本ID',
  `user` int(11) NOT NULL COMMENT '下载用户ID',
  `time` int(11) NOT NULL COMMENT '下载时间',
  `ip` text COMMENT '下载请求IP',
  `drm` text COMMENT '版权标识',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='文件下载记录' AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}document_file` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '文件版本ID',
  `document` int(11) NOT NULL COMMENT '文件ID',
  `format` int(11) NOT NULL COMMENT '文件格式',
  `version` text COMMENT '版本号',
  `filetype` text NOT NULL COMMENT '文件类型',
  `filesize` int(11) NOT NULL COMMENT '文件大小',
  `hash` text NOT NULL COMMENT '散列值',
  `identifier` text COMMENT '文献标识保护',
  `user` int(11) NOT NULL COMMENT '上传用户',
  `upload_time` int(11) NOT NULL COMMENT '上传时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='文件版本' AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}document_format` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '格式ID',
  `name` text NOT NULL COMMENT '格式名称',
  `detail` text COMMENT '格式信息',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='文件格式' AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}geolocation` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '地理位置ID',
  `parent` int(11) DEFAULT NULL COMMENT '上级位置ID',
  `name` text NOT NULL COMMENT '名称',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='地理位置' AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}group` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '代表团ID',
  `name` text NOT NULL COMMENT '代表团名称',
  `head_delegate` int(11) DEFAULT NULL COMMENT '负责代表ID',
  `group_payment` int(11) DEFAULT NULL COMMENT '是否团队支付',
  `geolocation` int(11) DEFAULT NULL COMMENT '地理位置',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='代表团' AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}interview` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '面试ID',
  `delegate` int(11) NOT NULL COMMENT '申请者ID',
  `interviewer` int(11) NOT NULL COMMENT '面试官ID',
  `assign_time` int(11) NOT NULL COMMENT '分配时间',
  `schedule_time` int(11) DEFAULT NULL COMMENT '安排时间',
  `finish_time` int(11) DEFAULT NULL COMMENT '完成时间',
  `status` text NOT NULL COMMENT '状态',
  `score` float DEFAULT NULL COMMENT '总评分',
  `feedback` text COMMENT '反馈',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='面试' AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}invoice` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '账单ID',
  `delegate` int(11) NOT NULL COMMENT '代表ID',
  `title` text NOT NULL COMMENT '账单名称',
  `items` text COMMENT '账单明细',
  `discounts` text COMMENT '折扣明细',
  `amount` decimal(10,0) NOT NULL COMMENT '账单总额',
  `generate_time` int(11) NOT NULL COMMENT '账单生成时间',
  `due_time` int(11) NOT NULL COMMENT '账单到期时间',
  `receive_time` int(11) DEFAULT NULL COMMENT '账单支付时间',
  `status` text NOT NULL COMMENT '支付状态',
  `trigger` text COMMENT '操作触发器',
  `transaction` text COMMENT '转账明细',
  `cashier` int(11) DEFAULT NULL COMMENT '确认管理员ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='账单' AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}knowledgebase` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '文章ID',
  `kb` int(11) NOT NULL DEFAULT '0' COMMENT '显示ID',
  `title` text NOT NULL COMMENT '文章标题',
  `content` longtext NOT NULL COMMENT '文章内容',
  `order` int(11) NOT NULL DEFAULT '0' COMMENT '知识库排序',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '修改时间',
  `system` int(11) NOT NULL DEFAULT '0' COMMENT '系统文章标识',
  `count` int(11) NOT NULL DEFAULT '0' COMMENT '访问次数',
  PRIMARY KEY (`id`),
  FULLTEXT KEY `content` (`title`,`content`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='知识库' AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `operator` int(11) NOT NULL COMMENT '用户ID',
  `time` int(11) NOT NULL COMMENT '操作时间',
  `operation` text NOT NULL COMMENT '操作名称',
  `value` text COMMENT '信息',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='日志' AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}message` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '消息ID',
  `sender` int(11) NOT NULL COMMENT '发送用户ID',
  `receiver` int(11) NOT NULL COMMENT '接收用户ID',
  `type` text COMMENT '消息类型',
  `text` text COMMENT '显示信息',
  `time` int(11) NOT NULL COMMENT '消息发送时间',
  `status` text NOT NULL COMMENT '消息状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户消息' AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}note` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '笔记ID',
  `admin` int(11) NOT NULL COMMENT '注释者ID',
  `delegate` int(11) NOT NULL COMMENT '代表ID',
  `time` int(11) NOT NULL COMMENT '生成时间',
  `text` text NOT NULL COMMENT '内容',
  `category` int(11) DEFAULT NULL COMMENT '记录分类',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户注释' AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}note_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '分类ID',
  `name` text NOT NULL COMMENT '分类名称',
  `type` text COMMENT '分类类型',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='注释笔记分类' AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}note_mention` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '提及ID',
  `note` int(11) NOT NULL COMMENT '笔记ID',
  `user` int(11) NOT NULL COMMENT '被提及用户ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='笔记中提及' AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}option` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '设置ID',
  `name` text NOT NULL COMMENT '设置名称',
  `value` text COMMENT '值',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='系统设置' AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}seat` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '席位ID',
  `committee` int(11) DEFAULT NULL COMMENT '委员会ID',
  `name` text COMMENT '席位名称',
  `primary` int(11) DEFAULT NULL COMMENT '主席位',
  `iso` text COMMENT '符合ISO 3166-1标准的国家代码',
  `status` text NOT NULL COMMENT '席位状态',
  `delegate` int(11) DEFAULT NULL COMMENT '代表ID',
  `time` int(11) DEFAULT NULL COMMENT '分配时间',
  `level` int(11) DEFAULT '1' COMMENT '席位等级',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='席位' AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}seat_backorder` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '延期请求ID',
  `seat` int(11) NOT NULL COMMENT '席位ID',
  `delegate` int(11) NOT NULL COMMENT '代表ID',
  `order_time` int(11) NOT NULL COMMENT '提交时间',
  `expire_time` int(11) DEFAULT NULL COMMENT '过期时间',
  `status` text COMMENT '请求状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='席位延期请求' AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}seat_selectability` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '席位许可ID',
  `seat` int(11) NOT NULL COMMENT '席位ID',
  `delegate` int(11) NOT NULL COMMENT '代表ID',
  `admin` int(11) NOT NULL COMMENT '批准管理员ID',
  `primary` int(11) DEFAULT NULL COMMENT '是否为主要席位许可',
  `recommended` int(11) DEFAULT NULL COMMENT '是否推荐',
  `time` int(11) NOT NULL COMMENT '批准时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='席位选择许可' AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}session` (
  `id` varchar(128) NOT NULL COMMENT '显示ID',
  `session` int(11) NOT NULL AUTO_INCREMENT COMMENT '系统记录ID',
  `ip_address` varchar(45) NOT NULL COMMENT 'IP地址',
  `timestamp` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后活动',
  `data` text NOT NULL COMMENT 'Session数据',
  PRIMARY KEY (`session`),
  KEY `ci_sessions_id` (`id`),
  KEY `ci_sessions_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Session' AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}sms` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '短信ID',
  `user` int(11) NOT NULL COMMENT '接收用户',
  `phone` text COMMENT '接收手机号码',
  `message` text NOT NULL COMMENT '短信内容',
  `status` text NOT NULL COMMENT '发送状态',
  `time_in` int(11) NOT NULL COMMENT '加入队列时间',
  `time_out` int(11) DEFAULT NULL COMMENT '发送时间',
  `response` text COMMENT 'API接口响应',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='短信通知队列' AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}twostep_recode` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '记录ID',
  `user` int(11) NOT NULL COMMENT '用户ID',
  `code` text NOT NULL COMMENT '使用的验证码',
  `time` int(11) NOT NULL COMMENT '使用时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='已经使用两步验证码' AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}twostep_safe` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '代码ID',
  `code` text NOT NULL COMMENT '授权不再两步验证代码',
  `user` int(11) NOT NULL COMMENT '授权用户ID',
  `auth_time` int(11) NOT NULL COMMENT '授权时间',
  `auth_ip` text NOT NULL COMMENT '授权IP',
  `ua` text NOT NULL COMMENT '授权User Agent',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='授权不再要求两步验证记录' AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}user` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `name` text NOT NULL COMMENT '姓名',
  `email` text NOT NULL COMMENT 'Email',
  `password` text COMMENT '密码',
  `phone` text NOT NULL COMMENT '电话',
  `type` text NOT NULL COMMENT '用户类型',
  `pin_password` text COMMENT '安全码',
  `enabled` int(11) NOT NULL DEFAULT '1' COMMENT '是否启用登录',
  `last_login` int(11) DEFAULT NULL COMMENT '最近登录时间',
  `last_ip` text COMMENT '最近登录IP',
  `reg_time` int(11) NOT NULL COMMENT '注册时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户' AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}user_option` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '设置ID',
  `user` int(11) NOT NULL COMMENT '用户ID',
  `name` text NOT NULL COMMENT '设置名称',
  `value` text COMMENT '值',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户设置' AUTO_INCREMENT=1;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
