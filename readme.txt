=== Enhanced Media with Cloud Infinite ===
Contributors: stevapple
Tags: tencent-cloud,media-library
Requires at least: 5.5
Tested up to: 6.1.1
Requires PHP: 7.2
Stable tag: 0.1.2
License: Apache-2.0
License URI: http://www.apache.org/licenses/LICENSE-2.0.txt

Enhances WordPress media library with cloud-based intelligent data processing powered by Cloud Infinite.

== Description ==
Cloud Infinite from Tencent Cloud provides you with intelligent processing services for different types of data such as images.
This plugin integrates the power of Cloud Infinite into WordPress, empowering users to enhance media experience effortlessly.

== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/wptc-cloud-infinite` directory.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Enable plugin functionalities through the 'Cloud Infinite' option page in WordPress.

== Frequently Asked Questions ==
1. The plugin enables CI processing for every attachment by default. You are recommended to set the domain allow-list for explicit control.
2. Cloud Infinite requires using COS bucket as media library storage backend. You can check in the bucket dashboard if CI is enabled.
3. If you're using a CDN, please make sure that your CDN doesn't strip queries.