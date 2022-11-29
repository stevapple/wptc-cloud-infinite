<?php

namespace WPTC\CloudInfinite;

use WPTC\CloudInfinite\Options\ImageResize as Options;

abstract class ImageResize implements \WPTC\Functionality {
    /**
     * @inheritDoc
     */
    public static function registerSettings() {
        // Enable CI image resizing.
        register_setting('wptc-cloud-infinite', Options::ENABLED, array(
            'type'         => 'boolean',
            'description'  => __("Enable cloud-based image resizing powered by Cloud Infinite.", 'wptc-cloud-infinite'),
            'show_in_rest' => true,
            'default'      => false,
        ));

        // Prefer intermediate sizes to CI resizing.
        register_setting('wptc-cloud-infinite', Options::PREFER_INTERMEDIATE, array(
            'type'         => 'boolean',
            'description'  => __("Prefer intermediate image sizes to CI resizing.", 'wptc-cloud-infinite'),
            'show_in_rest' => true,
            'default'      => false,
        ));

        // Ignore errors for CI resizing.
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
        // Add CI section for Image Resize.
        add_settings_section(
            'image-resize',
            __('Image Resize', 'wptc-cloud-infinite'),
            function () {
                echo '<p>' . esc_html__("With CI image resize, you can embed images of exactly any size you want, without saving additional copies.", 'wptc-cloud-infinite') . '</p>';
            },
            'wptc-cloud-infinite'
        );
        // Add Image Resize settings to CI options page.
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
                            > <?php esc_html_e("Enable cloud-based image resizing.", 'wptc-cloud-infinite'); ?>
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
            'image-resize'
        );
        add_settings_field(
            Options::PREFER_INTERMEDIATE,
            __('Intermediate Sizes', 'wptc-cloud-infinite'),
            function () {
                ?>
                <label for="<?php echo Options::PREFER_INTERMEDIATE ?>">
                    <input type="checkbox"
                           name="<?php echo Options::PREFER_INTERMEDIATE; ?>"
                           id="<?php echo Options::PREFER_INTERMEDIATE; ?>"
                           value="1"
                        <?php checked(get_option(Options::PREFER_INTERMEDIATE, 0), 1); ?>
                    > <?php esc_html_e("Always prefer intermediate image sizes.", 'wptc-cloud-infinite'); ?>
                </label>
                <?php
            },
            'wptc-cloud-infinite',
            'image-resize'
        );
    }

    /**
     * @inheritDoc
     */
    public static function activate() {
        if (get_option(Options::ENABLED) === "1") {
            add_filter('image_downsize', array(__CLASS__, 'resizeImage'), 20, 3);
        }
    }

    /**
     * @param bool|array   $downsize Whether to short-circuit the image downsize.
     * @param int          $attachment_id Attachment ID for image.
     * @param string|int[] $size Requested image size. Can be any registered image size name, or an array of width and height values in pixels (in that order).
     *
     * @return array|false Scale an image to fit a particular size using CI.
     */
    public static function resizeImage($downsize, $attachment_id, $size) {
        // Handle short-circuit and non-image scenarios.
        if ($downsize) {
            return $downsize;
        }
        if (!wp_attachment_is_image($attachment_id)) {
            return false;
        }

        // Skip if metadata is broken.
        $attachment_url = wp_get_attachment_url($attachment_id);
        $attachment_meta = wp_get_attachment_metadata($attachment_id);
        if (!$attachment_url || !$attachment_meta) {
            return false;
        }

        // Skip if CI is not enabled for the domain.
        if (Utilities::checkURLForCI($attachment_url) === false) {
            return false;
        }

        // Normalize image size requirement.
        if (is_string($size)) {
            $image_sizes = wp_get_registered_image_subsizes();
            if (isset($image_sizes[$size])) {
                $box = $image_sizes[$size];
            } else {
                return false;
            }
        } else {
            $box = array(
                'width'  => $size[0],
                'height' => $size[1] ?? 0,
                'crop'   => $size[2] ?? false,
            );
        }

        // Judge if there's a matching intermediate size.
        if (get_option(Options::PREFER_INTERMEDIATE) === "1") {
            foreach ($attachment_meta['sizes'] as $intermediate) {
                if (($box['width'] === $intermediate['width'] || $box['width'] === 0)
                    && ($box['height'] === $intermediate['height'] || $box['height'] === 0)) {
                    $attachment_url = str_replace($attachment_meta['file'], $intermediate['file'], $attachment_url);

                    return array($attachment_url, $intermediate['width'], $intermediate['height'], true);
                }
            }
        }

        // Judge if we really need a crop.
        list ($width, $height) = self::getThumbnailSize($box, $attachment_meta);
        if ($width === $attachment_meta['width'] && $height === $attachment_meta['height']) {
            return array($attachment_url, $width, $height, false);
        }

        // Build CI pipeline with crop/thumbnail at front.
        list ($url, $post_operations) = preg_split('/\?imageMogr2/', $attachment_url);
        $url .= '?imageMogr2'
            . ($box['crop'] ? '/crop/' : '/thumbnail/')
            . ($box['width'] ? strval($box['width']) : '')
            . 'x'
            . ($box['height'] ? strval($box['height']) : '');

        // Add gravity option.
        if (is_array($box['crop'])) {
            $gravity = self::getGravityFromCrop($box['crop']);
            if ($gravity) {
                $url .= '/gravity/' . $gravity;
            }
        }

        // Ignore error based on user option.
        if (get_option(Options::IGNORE_ERROR) === "1") {
            $url .= "/ignore-error/1";
        }

        // Add post operations if there's any.
        if ($post_operations) {
            $url .= '|' . $post_operations;
        }

        return array($url, $width, $height, true);
    }

    /**
     * @param array $box The designated thumbnail box size.
     * @param array $original The original image metadata.
     *
     * @return array The ideal thumbnail image size.
     */
    private static function getThumbnailSize($box, $original) {
        // Combine box size and original size.
        $width = $box['width'] > 0 ? min($box['width'], $original['width']) : $original['width'];
        $height = $box['height'] > 0 ? min($box['height'], $original['height']) : $original['height'];
        if ($box['crop']) {
            return array($width, $height);
        } else {
            // Calculate aspect zoom rate.
            $ratio = min($width / $original['width'], $height / $original['height']);

            return array(intval(round($original['width'] * $ratio)), intval(round($original['height'] * $ratio)));
        }
    }

    /**
     * @param string[] $crop The crop position settings of WordPress.
     *
     * @return string|false The corresponding gravity argument of CI.
     */
    private static function getGravityFromCrop($crop) {
        $gravity_map = array(
            'lefttop'      => 'northwest',
            'leftcenter'   => 'west',
            'leftbottom'   => 'southwest',
            'centertop'    => 'north',
            'centercenter' => 'center',
            'centerbottom' => 'south',
            'righttop'     => 'northeast',
            'rightcenter'  => 'east',
            'rightbottom'  => 'southeast',
        );
        $position = implode('', $crop);

        return $gravity_map[$position] ?? false;
    }
}
