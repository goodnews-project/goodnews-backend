<h1><picture>
  <img alt="Goodnews" src="logo.png" height="34">
</picture></h1>

[good.news](https://good.news) 是一个基于 **ActivityPub** 协议开发的自由、开源的社交媒体平台。通过 [good.news](https://good.news)，你可以自由地发布各种内容，包括文字、图片、链接和视频等。作为 **ActivityPub** 生态系统的一部分，[good.news](https://good.news) 支持与其他实现该协议的平台/服务器上的用户和内容互动。

## 主要特点和技术亮点：
1. **高效的后端架构**：后端使用 PHP、Swoole 和 Hyperf 开发，部署便捷、易于定制，并且性能卓越。
2. **开发活跃**：[good.news](https://good.news) 目前处于积极开发的阶段，能够及时响应并解决问题，同时也在积极开发新的功能。
3. **SEO 友好**：前端采用了 **Nuxt.js**，并针对搜索引擎进行了优化，使内容更容易被搜索引擎收录。
4. **兼容 Mastodon API**：[good.news](https://good.news) 兼容绝大多数常用的 **Mastodon API**，用户可以使用支持 **Mastodon** 协议的应用程序访问。此外，[good.news](https://good.news) 也提供了开源 **Flutter** 客户端。

## 部署

### 需要安装

- **Mysql** 8.0+
- **Redis** 6.0+
- **PHP** 8.2+
  - 扩展安装 Libvips(`sudo apt-get install --no-install-recommends libvips42`)
- **Hyperf** 3.0+
- **Nsq** 3.0+

### 安装步骤

### 源码安装
1. `git clone https://github.com/good-news/goodnews.git`
2. `cd goodnews`
3. `composer install`
4. `cp .env.example .env`
5. 迁移数据表: `php bin/hyperf migrate`
6. 生成密钥对，passport 认证需要：`php bin/hyperf.php passport:keys`
7. 运行服务: `php bin/hyperf server:watch`

### Docker
1. `git clone https://github.com/good-news/goodnews.git`
2. `cd goodnews`
3. `docker compose up -d`