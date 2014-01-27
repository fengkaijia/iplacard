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
  `application_type` text NOT NULL COMMENT '申请类型',
  `status` text NOT NULL COMMENT '申请状态',
  `group` int(11) DEFAULT NULL COMMENT '所在代表团',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='代表';

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

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `operator` int(11) NOT NULL COMMENT '用户ID',
  `time` int(11) NOT NULL COMMENT '操作时间',
  `operation` text NOT NULL COMMENT '操作名称',
  `value` text COMMENT '信息',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='日志' AUTO_INCREMENT=1;

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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='席位延期请求' AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}session` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '系统记录ID',
  `session_id` varchar(40) NOT NULL DEFAULT '0' COMMENT '显示ID',
  `ip_address` varchar(16) NOT NULL DEFAULT '0' COMMENT 'IP地址',
  `user_agent` varchar(120) NOT NULL COMMENT 'UA',
  `last_activity` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后活动',
  `user_data` text NOT NULL COMMENT 'Session数据',
  PRIMARY KEY (`id`),
  KEY `last_activity_idx` (`last_activity`)
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
  `last_login` int(11) DEFAULT NULL COMMENT '最近登录时间',
  `last_ip` int(11) DEFAULT NULL COMMENT '最近登录IP',
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