# wptc-cloud-infinite

[Cloud Infinite](https://www.tencentcloud.com/products/ci) from Tencent Cloud provides you with intelligent processing services for different types of data such as images.
This plugin integrates the power of Cloud Infinite into WordPress, empowering users to enhance media experience effortlessly.

## Installation

1. Upload the plugin files to the `/wp-content/plugins/wptc-cloud-infinite` directory.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Enable plugin functionalities through the 'Cloud Infinite' option page in WordPress.

## FAQ

1. The plugin enables CI processing for every attachment by default. You are recommended to set the domain allow-list for explicit control.
2. Cloud Infinite requires using COS bucket as media library storage backend. You can check in the bucket dashboard if CI is enabled.
3. If you're using a CDN, please make sure that your CDN doesn't strip queries.

## Development

To develop this plugin, you need to have [PHP](https://www.php.net) and [Composer](https://getcomposer.org) installed locally.

### Set up 

1. Clone and checkout the repository:
   ```bash
   $ git clone https://github.com/stevapple/wptc-cloud-infinite.git
   ```
2. Generate vendor files:
   ```bash
   $ composer install
   ```

### Testing

1. Install WordPress testing environment:
   ```bash
   $ ./bin/install-wp-tests.sh
   ```
2. Run [PHPUnit](https://phpunit.de):
   ```bash
   $ ./vendor/bin/phpunit
   ```
