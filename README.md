<h1><picture>
  <img alt="Goodnews" src="logo.png" height="34">
</picture></h1>

[good.news](https://good.news) is a free and open-source social media platform developed based on the **ActivityPub** protocol. With [good.news](https://good.news), you can freely publish various types of content, including text, images, links, and videos. As part of the **ActivityPub** ecosystem, [good.news](https://good.news) supports interaction with users and content from other platforms/servers that implement this protocol.

## Features
1. **Efficient Backend Architecture**: The backend is developed using PHP, Swoole, and Hyperf, offering convenient deployment, ease of customization, and outstanding performance.
2. **Active Development**: [good.news](https://good.news) is currently in an active development phase, with timely responses to issues and continuous development of new features.
3. **SEO Friendly**: The front end utilizes **Nuxt.js** and is optimized for search engines, making content more accessible for search engine indexing.
4. **Mastodon API Compatibility**: [good.news](https://good.news) is compatible with most commonly used **Mastodon API**, allowing users to access it with applications that support the **Mastodon** protocol. Additionally, [good.news](https://good.news) provides an open-source **Flutter** client.

## Deployment

### Required Installation

- **MySQL** 8.0+
- **Redis** 6.0+
- **PHP** 8.2+
  - Install the Libvips extension (`sudo apt-get install --no-install-recommends libvips42`)
- **Hyperf** 3.0+
- **NSQ** 3.0+

### Installation Steps

### From Source
1. `git clone https://github.com/good-news/goodnews.git`
2. `cd goodnews`
3. `composer install`
4. `cp .env.example .env`
5. Migrate the database tables: `php bin/hyperf migrate`
6. Generate the key pair, which is required for passport authentication: `php bin/hyperf.php passport:keys`
7. Run the service: `php bin/hyperf server:watch`

AP_HOST
ATTACHMENT_PREFIX
### Using Docker
1. `git clone https://github.com/good-news/goodnews.git`
2. `cd goodnews`
3. `docker compose build`
3. `docker compose up -d`
4. docker compose exec -it goodnews bash
php bin/hyperf.php migrate
php bin/hyperf.php passport:keys

