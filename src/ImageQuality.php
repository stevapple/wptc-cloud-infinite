<?php

namespace WPTC\CloudInfinite;

use WPTC\CloudInfinite\Enums\ImageQuality\ControlMode;
use WPTC\CloudInfinite\Options\ImageQuality as Options;

abstract class ImageQuality implements \WPTC\Functionality {
    /**
     * @inheritDoc
     */
    public static function registerSettings() {
        // Enable CI quality control.
        register_setting('wptc-cloud-infinite', Options::ENABLED, array(
            'type'         => 'boolean',
            'description'  => __("Enable cloud-based image quality control by Cloud Infinite.", 'wptc-cloud-infinite'),
            'show_in_rest' => true,
            'default'      => false,
        ));

        // CI quality control mode.
        register_setting('wptc-cloud-infinite', Options::CONTROL_MODE, array(
            'type'              => 'string',
            'description'       => __("Image quality control mode for Cloud Infinite.", 'wptc-cloud-infinite'),
            'sanitize_callback' => array(__CLASS__, 'sanitizeMode'),
            'show_in_rest'      => true,
            'default'           => 'maximum',
        ));

        // CI quality control value (from 0 to 100).
        register_setting('wptc-cloud-infinite', Options::CONTROL_VALUE, array(
            'type'              => 'integer',
            'description'       => __("Expected image quality score for Cloud Infinite (0-100).", 'wptc-cloud-infinite'),
            'sanitize_callback' => array(__CLASS__, 'sanitizeValue'),
            'show_in_rest'      => true,
            'default'           => 80,
        ));

        // Ignore errors for CI quality control.
        register_setting('wptc-cloud-infinite', Options::IGNORE_ERROR, array(
            'type'         => 'boolean',
            'description'  => __("Returns the original image when CI pipeline fails.", 'wptc-cloud-infinite'),
            'show_in_rest' => true,
            'default'      => false,
        ));
    }


    /**
     * @inheritDoc
     */
    public static function addAdminSettings() {
        // Add CI section for Image Quality.
        add_settings_section(
            'image-quality',
            __('Image Quality (Beta)', 'wptc-cloud-infinite'),
            function () {
                echo '<p>' . esc_html__("You can apply image quality control rule to attachments to speed up page load.", 'wptc-cloud-infinite') . '</p>';
            },
            'wptc-cloud-infinite'
        );
        // Add Image Quality settings to CI options page.
        add_settings_field(
            Options::ENABLED,
            __('General', 'wptc-cloud-infinite'),
            function () {
                ?>
                <fieldset>
                    <p>
                        <label for="<?php echo Options::ENABLED ?>">
                            <input type="checkbox"
                                   name="<?php echo Options::ENABLED; ?>"
                                   id="<?php echo Options::ENABLED; ?>"
                                   value="1"
                                <?php checked(get_option(Options::ENABLED, 0), 1); ?>
                            > <?php esc_html_e("Enable cloud-based image quality control.", 'wptc-cloud-infinite'); ?>
                        </label>
                    </p>
                    <p>
                        <label for="<?php echo Options::IGNORE_ERROR ?>">
                            <input type="checkbox"
                                   name="<?php echo Options::IGNORE_ERROR; ?>"
                                   id="<?php echo Options::IGNORE_ERROR; ?>"
                                   value="1"
                                <?php checked(get_option(Options::IGNORE_ERROR, 0), 1); ?>
                            > <?php esc_html_e("Return the original image when processing fails.", 'wptc-cloud-infinite'); ?>
                        </label>
                    </p>
                </fieldset>
                <?php
            },
            'wptc-cloud-infinite',
            'image-quality'
        );
        add_settings_field(
            Options::CONTROL_MODE,
            __('Image Quality Settings', 'wptc-cloud-infinite'),
            function () {
                $selected_option = get_option(Options::CONTROL_MODE, ControlMode::MAXIMUM);
                ?>
                <label for="<?php echo Options::CONTROL_MODE; ?>">
                    <?php esc_html_e('Set the ', 'wptc-cloud-infinite'); ?>
                    <select name="<?php echo Options::CONTROL_MODE; ?>"
                            id="<?php echo Options::CONTROL_MODE; ?>"
                    >
                        <?php foreach (ControlMode::getAll() as $option) { ?>
                            <option value="<?php echo esc_attr($option); ?>"
                                <?php selected($selected_option, $option); ?>
                            >
                                <?php echo esc_html(ControlMode::localize($option)); ?>
                            </option>
                        <?php } ?>
                    </select>
                    <?php esc_html_e(' image quality', 'wptc-cloud-infinite'); ?>
                </label>
                <label for="<?php echo Options::CONTROL_VALUE; ?>">
                    <?php esc_html_e(' to ', 'wptc-cloud-infinite'); ?>
                    <input type="number"
                           name="<?php echo Options::CONTROL_VALUE; ?>"
                           id="<?php echo Options::CONTROL_VALUE; ?>"
                           min="0" max="100" step="1"
                           class="small-text"
                           value="<?php echo esc_attr(get_option(Options::CONTROL_VALUE, '80')); ?>"
                    />
                </label>
                <p class="description"><?php esc_html_e('Image quality ranges from 0 to 100.', 'wptc-cloud-infinite'); ?></p>
                <?php
            },
            'wptc-cloud-infinite',
            'image-quality'
        );
    }

    /**
     * @inheritDoc
     */
    public static function activate() {
        if (get_option(Options::ENABLED) === "1") {
            add_filter('wp_get_attachment_url', array(__CLASS__, 'addQualityControl'), 10, 2);
        }
    }

    /**
     * @param string $url URL for the given attachment.
     * @param int    $attachment_id Attachment post ID.
     *
     * @return string Modified URL with CI-powered quality control.
     */
    public static function addQualityControl($url, $attachment_id) {
        // Skip if CI is not enabled for the domain.
        if (Utilities::checkURLForCI($url) === false) {
            return false;
        }
        // Filter supported image types.
        $mime_type = get_post_mime_type($attachment_id);
        if (self::supportsQualityControl($mime_type)) {
            // Add imageMogr2 query inc.
            if (str_contains('?imageMogr2', $url)) {
                $url .= '|';
            } else {
                $url .= '?imageMogr2';
            }

            // Add quality control pipeline.
            $quality = get_option(Options::CONTROL_VALUE, '80');
            $mode = self::sanitizeMode(get_option(Options::CONTROL_MODE));
            $url .= self::getQualityOperation($quality, $mode);

            // Ignore error based on user option.
            if (get_option(Options::IGNORE_ERROR) === "1") {
                $url .= "/ignore-error/1";
            }
        }
        return $url;
    }

    /**
     * @param string $mime_type The MIME type string of attachment file.
     *
     * @return bool If the MIME type supports quality control.
     */
    private static function supportsQualityControl($mime_type) {
        $supported_types = array(
            'image/jpeg',
            'image/webp',
            'image/tpg',
            'image/heic',
            'image/avif',
        );
        return in_array($mime_type, $supported_types);
    }

    /**
     * @param string $mode The input image quality mode.
     *
     * @return string The sanitized quality value.
     */
    public static function sanitizeMode($mode) {
        switch ($mode) {
            case ControlMode::MAXIMUM:
            case ControlMode::MINIMUM:
            case ControlMode::ABSOLUTE:
            case ControlMode::RELATIVE:
                return $mode;
            default:
                return ControlMode::MAXIMUM;
        }
    }

    /**
     * @param string $quality Image quality value string.
     * @param string $mode Image quality mode.
     *
     * @return string The quality operation.
     */
    public static function getQualityOperation($quality, $mode) {
        switch ($mode) {
            case ControlMode::MAXIMUM:
                return "/quality/$quality";
            case ControlMode::MINIMUM:
                return "/lquality/$quality";
            case ControlMode::ABSOLUTE:
                return "/quality/$quality!";
            case ControlMode::RELATIVE:
                return "/rquality/$quality";
        }
        return '';
    }

    /**
     * @param integer $quality The input image quality value.
     *
     * @return integer The sanitized quality value.
     */
    public static function sanitizeValue($quality) {
        if ($quality <= 0) return 0;
        if ($quality >= 100) return 100;
        return $quality;
    }
}