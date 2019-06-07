SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

ALTER TABLE `{IP_PREFIX}knowledgebase` DROP INDEX `knowledgebase_content`, ADD FULLTEXT `knowledgebase_content` (`title`, `content`);

INSERT INTO `{IP_PREFIX}option` (`name`, `value`) VALUES
  ('signup_test', 'false'), -- 在简易报名表中增加学术测试
  ('signup_test_dynamic', 'false'), -- 从学术测试题库中动态抽取题目
  ('signup_test_committee', '[]'), -- 学术测试题对应的委员会
  ('signup_test_exclusive', '[]'), -- 学术测试题间的相互互斥关系
  ('signup_test_count', '3'), -- 单意向委员会可显示最多学术测试题数量
;