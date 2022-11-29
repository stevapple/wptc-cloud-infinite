<?php

namespace WPTC\CloudInfinite;

use WPTC\CloudInfinite\Options\General as Options;

abstract class Utilities {
    /**
     * @param string $url The given file URL to check against.
     *
     * @return bool If the URL is CI-enabled.
     */
    public static function checkURLForCI($url) {
        $domain = wp_parse_url($url, PHP_URL_HOST);
        return self::checkDomainForCI($domain);
    }

    /**
     * @param string $domain The given domain to check against.
     *
     * @return bool If the URL is CI-enabled.
     */
    public static function checkDomainForCI($domain) {
        $domains = get_option(Options::DOMAIN_LIST, array());
        // Enable for all domains by default.
        if (is_array($domains) && count($domains) === 0) {
            return true;
        }
        return in_array($domain, $domains);
    }

    /**
     * @param string|string[] $domains The input domain list.
     *
     * @return string[] The sanitized domain list.
     */
    public static function sanitizeDomainList($domains) {
        // Take comma and line break as separators.
        if (gettype($domains) === 'string') {
            $domains = preg_split('/([,\n])/', $domains, -1, PREG_SPLIT_NO_EMPTY);
        }
        // Return empty array if there's nothing.
        if (empty($domains) || !is_array($domains)) {
            return array();
        }
        // Trim and drop empty entries.
        $domains = array_map('trim', $domains);
        $domains = array_filter($domains, function ($input) {
            return !empty($input);
        });
        // Transform URL to host automatically.
        $domains = array_map(function ($input) {
            return wp_parse_url($input, PHP_URL_HOST) ?? $input;
        }, $domains);
        // Encode IDN domain names to ASCII.
        $domains = array_map('idn_to_ascii', $domains);
        // Ensure that the domains are unique.
        return array_unique($domains);
    }
}
