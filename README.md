# wptc-cloud-infinite

[Cloud Infinite](https://www.tencentcloud.com/products/ci) from Tencent Cloud provides you with intelligent processing services for different types of data such as images.
This plugin integrates the power of Cloud Infinite into WordPress, empowering users to enhance media experience effortlessly.

## Installation

1. Upload the plugin files to the `/wp-content/plugins/wptc-cloud-infinite` directory.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Enable plugin functionalities through the 'Cloud Infinite' option page in WordPress.

## Development

To develop this plugin, you need to have [PHP](https://www.php.net) and [Composer](https://getcomposer.org) installed locally.

### Setup 

1. Clone and checkout the repository:
   ```bash
   $ git clone https://github.com/stevapple/wptc-cloud-infinite.git
   $ cd wptc-cloud-infinite
   ```
2. Generate vendor files:
   ```bash
   $ composer install
   ```

### Testing

1. Install WordPress testing environment:
   ```bash
   $ ./bin/install-wp-tests.sh <db-name> <db-user> <db-pass>
   ```
2. Run [PHPUnit](https://phpunit.de):
   ```bash
   $ ./vendor/bin/phpunit
   ```
