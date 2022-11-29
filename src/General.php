<?php

namespace WPTC\CloudInfinite;

use WPTC\CloudInfinite\Options\General as Options;
use WPTC\CloudInfinite\Options\Media as MediaOptions;

abstract class General implements \WPTC\Functionality {
    /**
     * @inheritDoc
     */
    public static function registerSettings() {
        // Disable media size threshold.
        register_setting('media', MediaOptions::DISABLE_SIZE_THRESHOLD, array(
            'type'         => 'boolean',
            'description'  => __("Avoid large images being minified before saving to media library.", 'wptc-cloud-infinite'),
            'show_in_rest' => true,
            'default'      => true,
        ));

        // Disable intermediate image creation.
        register_setting('media', MediaOptions::DISABLE_INTERMEDIATE_IMAGE_CREATION, array(
            'type'         => 'boolean',
            'description'  => __("Don't create additional image sizes when saving to media library.", 'wptc-cloud-infinite'),
            'show_in_rest' => true,
            'default'      => true,
        ));

        // Cloud Infinite domain list.
        register_setting('wptc-cloud-infinite', Options::DOMAIN_LIST, array(
            'type'              => 'array',
            'description'       => __("A list of resource domains that has CI enabled.", 'wptc-cloud-infinite'),
            'sanitize_callback' => array(__NAMESPACE__ . '\Utilities', 'sanitizeDomainList'),
            'show_in_rest'      => array(
                'schema' => array('type' => 'array', 'items' => array('type' => 'string'))
            ),
            'default'           => array(),
        ));
    }

    /**
     * @inheritDoc
     */
    public static function addAdminSettings() {
        // Add settings to media options page.
        add_settings_field(
            'wptc-cloud-infinite-settings',
            __('Automatic Image Crops', 'wptc-cloud-infinite'),
            function () {
                ?>
                <fieldset>
                    <p>
                        <label for="<?php echo MediaOptions::DISABLE_SIZE_THRESHOLD ?>">
                            <input type="checkbox"
                                   name="<?php echo MediaOptions::DISABLE_SIZE_THRESHOLD; ?>"
                                   id="<?php echo MediaOptions::DISABLE_SIZE_THRESHOLD; ?>"
                                   value="1"
                                <?php checked(get_option(MediaOptions::DISABLE_SIZE_THRESHOLD, 1), 1); ?>
                            > <?php esc_html_e("Avoid large images being minified before saving to media library.", 'wptc-cloud-infinite'); ?>
                        </label>
                    </p>
                    <p>
                        <label for="<?php echo MediaOptions::DISABLE_INTERMEDIATE_IMAGE_CREATION; ?>">
                            <input type="checkbox"
                                   name="<?php echo MediaOptions::DISABLE_INTERMEDIATE_IMAGE_CREATION; ?>"
                                   id="<?php echo MediaOptions::DISABLE_INTERMEDIATE_IMAGE_CREATION; ?>"
                                   value="1"
                                <?php checked(get_option(MediaOptions::DISABLE_INTERMEDIATE_IMAGE_CREATION, 1), 1); ?>
                            > <?php esc_html_e("Don't create additional image sizes when saving to media library.", 'wptc-cloud-infinite'); ?>
                        </label>
                    </p>
                </fieldset>
                <?php
            },
            'media',
            'uploads'
        );
        // Add CI options page.
        add_options_page(
            __('Cloud Infinite Settings', 'wptc-cloud-infinite'),
            __('Cloud Infinite', 'wptc-cloud-infinite'),
            'manage_options',
            'wptc-cloud-infinite',
            function () {
                // Check user capabilities.
                if (!current_user_can('manage_options')) {
                    wp_die(__('Sorry, you are not allowed to manage options for this site.'));
                }
                ?>
                <div class="wrap">
                    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
                    <!--suppress HtmlUnknownTarget -->
                    <form action="options.php" method="post">
                        <?php
                        // Standalone CI settings fields.
                        settings_fields('wptc-cloud-infinite');
                        // CI settings sections.
                        do_settings_sections('wptc-cloud-infinite');
                        // Save settings button.
                        submit_button();
                        ?>
                    </form>
                </div>
                <?php
            }
        );
        // Add shortcut to options page.
        add_filter('plugin_action_links_wptc-cloud-infinite/wptc-cloud-infinite.php', function ($links) {
            $settings_url = esc_url(add_query_arg('page', 'wptc-cloud-infinite', get_admin_url(null, 'options-general.php')));
            $settings_link = "<a href='$settings_url'>" . __('Settings') . '</a>';
            $links[] = $settings_link;
            return $links;
        });
        // Add CI general section.
        add_settings_section(
            'general',
            null,
            '__return_zero',
            'wptc-cloud-infinite'
        );
        // Add CI domain list field.
        add_settings_field(
            Options::DOMAIN_LIST,
            __('Domains that can use Cloud Infinite', 'wptc-cloud-infinite'),
            function () {
                ?>
                <p>
                    <label for="<?php echo Options::DOMAIN_LIST ?>">
                        <?php esc_html_e('Attachments using the following domains are able to benefit from Cloud Infinite functionalities.', 'wptc-cloud-infinite'); ?>
                        <br/>
                        <?php esc_html_e('If left empty, Cloud Infinite rules will be applied to all attachments by default.', 'wptc-cloud-infinite'); ?>
                    </label>
                </p>
                <p>
                    <textarea class="large-text code"
                              name="<?php echo Options::DOMAIN_LIST ?>"
                              id="<?php echo Options::DOMAIN_LIST ?>"
                              rows="5"
                    ><?php echo esc_html(implode("\n", get_option(Options::DOMAIN_LIST, array()))); ?></textarea>
                </p>
                <?php
            },
            'wptc-cloud-infinite',
            'general'
        );
    }

    /**
     * @inheritDoc
     */
    public static function activate() {
        // Disable media size threshold.
        if (get_option(MediaOptions::DISABLE_SIZE_THRESHOLD, '1') === "1") {
            add_filter('big_image_size_threshold', '__return_false');
        }
        // Disable intermediate image creation.
        if (get_option(MediaOptions::DISABLE_INTERMEDIATE_IMAGE_CREATION, '1') === "1") {
            add_filter('intermediate_image_sizes_advanced', '__return_empty_array', 100);
        }
    }
}