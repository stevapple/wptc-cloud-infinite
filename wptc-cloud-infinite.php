<?php

/*
Plugin Name: Enhanced Media with Cloud Infinite
Plugin URI: https://github.com/stevapple/wptc-cloud-infinite
Description: Enhances WordPress media library with cloud-based intelligent data processing powered by Cloud Infinite.
Version: 0.1.2
Requires at least: 5.5
Requires PHP: 7.2
Author: YR Chen
Author URI: https://github.com/stevapple
License: Apache-2.0
License URI: http://www.apache.org/licenses/LICENSE-2.0
Text Domain: wptc-cloud-infinite
Domain Path: /i18n
*/

require_once(plugin_dir_path(__FILE__) . '/vendor/autoload.php');

use WPTC\CloudInfinite;

// Register settings.
function wptc_ci_register_settings() {
    // General settings registration.
    CloudInfinite\General::registerSettings();
    // Functionality-scope settings registration.
    CloudInfinite\ImageResize::registerSettings();
    CloudInfinite\ImageQuality::registerSettings();
}

add_action('admin_init', 'wptc_ci_register_settings');
add_action('rest_api_init', 'wptc_ci_register_settings');


// Register admin settings.
function wptc_ci_add_admin_settings() {
    // General settings registration.
    CloudInfinite\General::addAdminSettings();
    // Functionality-scope settings registration.
    CloudInfinite\ImageResize::addAdminSettings();
    CloudInfinite\ImageQuality::addAdminSettings();
}

add_action('admin_menu', 'wptc_ci_add_admin_settings');


// Custom translation loader.
add_action('init', function () {
    load_plugin_textdomain('wptc-cloud-infinite', false, dirname(plugin_basename(__FILE__)) . '/i18n');
});


// Enable plugin features.
CloudInfinite\General::activate();
CloudInfinite\ImageResize::activate();
CloudInfinite\ImageQuality::activate();
