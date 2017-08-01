# iPlacard

iPlacard 是 [Kaijia Feng](http://kaijia.me/) 于 2013 年起为 [IMUNC](http://imunc.com/) 开发的模拟联合国会议管理系统。

iPlacard 的设计目标是适配所有常规类型会议的自动化管理系统，功能包括管理完整的申请流程，包括代表、面试、席位、文件、缴费、委员会、代表团，以及附带的数据导出、API、统计及个人帐户管理功能。同时提供了一个供与会代表访问界面，可显示申请进度、席位、文件、代表团、委员会信息。

iPlacard 同时按照早前会议的需求扩展有与其他系统对接的能力，包括利用 FreeRADIUS 实现会场 WIFI 网络登录（首次于 IMUNC 2013 中使用）；利用 iDocument 系统分发文件（首次于 IMUNC 2015 中使用）；利用 jsConnect 等模式实现 Single Sign-On 等（首次于 IMUNC 2015 中使用）。iPlacard 数据库经扩展亦可作为会期期间利用 NFC 或二维码签到、领餐、打卡等功能的数据后端（首次于 IMUNC 2016 中使用）。

iPlacard 1.0 于 IMUNC 2013 中首次使用，随后进行了重写，并于 IMUNC 2014 开始使用 2.0 版本。

## 安装

使用 iPlacard 需要系统管理员具有一定的网站维护知识，建议使用 phpMyAdmin 等软件方便维护。

### 运行环境

iPlacard 标准实例可以运行在标准的 LAMP 环境中，最低需求如下：

* PHP 7.0
* MySQL 5.7
* Nginx 1.0 / Apache 2.2
* Memcached
* Cron
* CURL
* ImageMagick

### 获取源代码

可通过两种方法获取源代码。

#### 1、使用 Git 和 Composer

```bash
git clone https://dev.kaijia.me/imunc/iplacard.git
cd iplacard
wget https://raw.githubusercontent.com/composer/getcomposer.org/master/web/installer -O - -q | php -- --quiet
php composer.phar install
```

#### 2、下载预编译的文件

访问 <https://dev.kaijia.me/imunc/iplacard/tags> 下载包含所有代码的源文件并解压缩到文件目录。请注意通过此方法下载的代码无法自动升级，如条件允许请使用前种方法。

### 创建配置文件

复制 iPlacard 根目录下的 [`config.example.php`](config.example.php) 文件到 `config.php`。根据提示修改数据库连接等配置信息。

### 导入数据库

将 [`resource/install.sql`](resource/install.sql) 文件导入数据库。导入前需将 `install.sql` 文件中的 `{IP_PREFIX}` 更换为在 `config.php` 中设定的数据库前缀（默认配置将 `{IP_PREFIX}` 替换为 `ip_`）。在 Linux 下可运行：

```bash
sed -e 's/{IP_PREFIX}/ip_/g' resource/install.sql | mysql -u user -p database
```

### 创建管理用户

在数据库中运行 SQL 语句：

```mysql
INSERT INTO `{IP_PREFIX}user` (`name`, `email`, `password`, `phone`, `type`, `pin_password`, `reg_time`) VALUES (
  '姓名', 
  'example@iplacard.com', 
  '$2a$12$85882f010234860c69b20unGCh76I/fvE.eL5QBdhG7Dfk7BSYah6', 
  '13800000000', 
  'admin', 
  'iPlacard', 
  UNIX_TIMESTAMP(CURRENT_TIMESTAMP)
);

INSERT INTO `{IP_PREFIX}admin` (`id`, `role_reviewer`, `role_dais`, `role_interviewer`, `role_cashier`, `role_administrator`, `role_bureaucrat`) VALUES (1, 1, 1, 1, 1, 1, 1);
```

其中，替换姓名、邮箱和手机号三项必要信息。将 `{IP_PREFIX}` 更换为在 `config.php` 中设定的数据库前缀。

### 更改系统设置

iPlacard 总共有两百余项设置项，可设置申请流程、账单、席位模式、界面等等行为。这些设置项在数据库的 `option` 表中可被修改。详细的设置项名称及其控制的功能请参见 [`resource/install.sql`](resource/install.sql)。

### 设置 Cron

Cron 脚本用于处理发送短信、面试提醒、删除代表帐户等任务。增加以下 Cron 任务：

```
* * * * * php /path/to/iplacard/index.php cron minutely >> /path/to/iplacard/data/0/log/minutely.log 2>&1
@hourly php /path/to/iplacard/index.php cron hourly >> /path/to/iplacard/data/0/log/hourly.log 2>&1
@daily php /path/to/iplacard/index.php cron daily >> /path/to/iplacard/data/0/log/daily.log 2>&1
@weekly php /path/to/iplacard/index.php cron weekly >> /path/to/iplacard/data/0/log/weekly.log 2>&1
```

替换其中的 `/path/to/iplacard/` 为 iPlacard 的安装目录。

### 开始使用

访问 iPlacard 网页，使用上述创建管理用户步骤中设置的邮箱登录，登录密码为 `iplacardadmin`，登录后请修改密码。

### 可选操作

#### 1、导入示例数据

[`resource/sample.sql`](resource/sample.sql) 文件包含有示例数据，导入示例数据可快速开始使用 iPlacard。导入前需将 `sample.sql` 文件中的 `{IP_PREFIX}` 更换为在 `config.php` 中设定的数据库前缀。在 Linux 下可运行：

```bash
sed -e 's/{IP_PREFIX}/ip_/g' resource/sample.sql | mysql -u user -p database
```

#### 2、配置 Apache

将 [`resource/htaccess`](resource/htaccess) 复制到 iPlacard 根目录下并重命名为 `.htaccess` 可隐藏网址中的 `index.php/` 部分并防止恶意获取文件。

#### 3、配置 Nginx

参照 [`resource/nginx`](resource/nginx) 修改 Nginx 的配置文件可隐藏网址中的 `index.php/` 部分并防止恶意获取文件。