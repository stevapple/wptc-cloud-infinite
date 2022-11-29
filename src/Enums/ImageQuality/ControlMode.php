<?php

namespace WPTC\CloudInfinite\Enums\ImageQuality;

abstract class ControlMode {
    // Set the maximum image quality.
    const MAXIMUM = 'maximum';
    // Set the minimum image quality.
    const MINIMUM = 'minimum';
    // Set the exact image quality.
    const ABSOLUTE = 'absolute';
    // Set the image quality relatively.
    const RELATIVE = 'relative';

    // All values.
    static function getAll() {
        return array(
            self::MAXIMUM,
            self::MINIMUM,
            self::ABSOLUTE,
            self::RELATIVE
        );
    }

    /**
     * @param string $option Control mode option to localize.
     *
     * @return string The localized description.
     */
    static function localize($option) {
        switch ($option) {
            case self::MAXIMUM:
                return _x('maximum', 'Cloud Infinite image quality control mode.', 'wptc-cloud-infinite');
            case self::MINIMUM:
                return _x('minimum', 'Cloud Infinite image quality control mode.', 'wptc-cloud-infinite');
            case self::ABSOLUTE:
                return _x('absolute', 'Cloud Infinite image quality control mode.', 'wptc-cloud-infinite');
            case self::RELATIVE:
                return _x('relative', 'Cloud Infinite image quality control mode.', 'wptc-cloud-infinite');
            default:
                trigger_error('Invalid image control option', E_USER_WARNING);
                return '';
        }
    }
}
