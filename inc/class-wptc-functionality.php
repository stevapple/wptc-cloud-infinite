<?php

namespace WPTC;

if (!interface_exists(__NAMESPACE__ . '\Functionality', false)) {
    interface Functionality {
        /**
         * Register the settings.
         */
        public static function registerSettings();

        /**
         * Add the settings to admin UI.
         */
        public static function addAdminSettings();

        /**
         * Activate the functionality.
         */
        public static function activate();
    }
}
