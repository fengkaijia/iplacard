SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

ALTER TABLE `{IP_PREFIX}knowledgebase` DROP INDEX `knowledgebase_content`, ADD FULLTEXT `knowledgebase_content` (`title`, `content`);