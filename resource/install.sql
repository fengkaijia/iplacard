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
  `role_interviewer` int(11) NOT NULL DEFAULT '0' COMMENT '面试官权限',
  `role_cashier` int(11) NOT NULL DEFAULT '0' COMMENT '出纳员权限',
  `role_administrator` int(11) NOT NULL DEFAULT '0' COMMENT '站点管理员权限',
  `role_bureaucrat` int(11) NOT NULL DEFAULT '0' COMMENT '行政员权限',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='管理员';

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}session` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '系统记录ID',
  `session_id` varchar(40) NOT NULL DEFAULT '0' COMMENT '显示ID',
  `ip_address` varchar(16) NOT NULL DEFAULT '0' COMMENT 'IP地址',
  `user_agent` varchar(120) NOT NULL COMMENT 'UA',
  `last_activity` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后活动',
  `user_data` text NOT NULL COMMENT 'Session数据',
  PRIMARY KEY (`id`),
  KEY `last_activity_idx` (`last_activity`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `{IP_PREFIX}user` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `name` text NOT NULL COMMENT '姓名',
  `email` text NOT NULL COMMENT 'Email',
  `password` text COMMENT '密码',
  `phone` text NOT NULL COMMENT '电话',
  `type` text NOT NULL COMMENT '用户类型',
  `recover_key` text COMMENT '密码重置密钥',
  `recover_time` text COMMENT '密码重置请求发送时间',
  `pin_password` text COMMENT '安全码',
  `last_login` int(11) DEFAULT NULL COMMENT '最近登录时间',
  `last_ip` int(11) DEFAULT NULL COMMENT '最近登录IP',
  `reg_time` int(11) NOT NULL COMMENT '注册时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='用户' AUTO_INCREMENT=1;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;