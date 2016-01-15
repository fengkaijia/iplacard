# iPlacard

iPlacard 是一个由 IMUNC 支持，使用友好、界面简洁、功能强大的下一代模拟联合国会议管理系统。

## 设计目标

在适配所有常规类型会议的情况下实现全模拟联合国会议流程的自动化管理，最大限度降低人工支持、提高管理效率。

## 运行环境

iPlacard 标准实例可以运行在标准的 LAMP 环境中，最低需求如下：

* PHP 5.5
* MySQL 5.5
* Nginx 1.0 / Apache 2.2
* Linux
* Memcached
* Cron
* CURL

iPlacare 需要以下软件以运行扩展功能：

* FreeRADIUS（扩展认证）
* PDFtk（iDocument）
* Ghostscript（iDocument）
* ImageMagick（图像处理）

## 开发者

* [Kaijia Feng](feng@kaijia.me)