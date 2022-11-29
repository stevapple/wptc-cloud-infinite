<?php /** @noinspection HttpUrlsUsage */

/**
 * Class UtilitiesTest
 *
 * @package stevapple/wptc-cloud-infinite
 */

use WPTC\CloudInfinite\Options\General as Options;
use WPTC\CloudInfinite\Utilities;

/**
 * Sample test case.
 */
class UtilitiesTest extends WP_UnitTestCase {
    /**
     * Tests {@see Utilities::checkDomainForCI} and {@see Utilities::checkURLForCI} with empty list.
     */
    public function testAllowEveryDomain() {
        // Cleanup the domain list.
        delete_option(Options::DOMAIN_LIST);
        // Test with random domains.
        $domains = array(
            'wordpress.org',
            'github.com',
            'cloud.tencent.com',
            'www.tencentcloud.com',
            'example.org',
            'one.of.my.own.domain',
        );
        foreach ($domains as $domain) {
            $this->assertTrue(Utilities::checkDomainForCI($domain));
        }
        // Test with random URLs.
        $urls = array(
            'https://wordpress.org',
            'https://github.com/stevapple/wptc-cloud-infinite',
            'https://cloud.tencent.com/document/product/460',
            'https://www.tencentcloud.com/products/ci',
            'http://example.org/',
            'http://one.of.my.own.domain/path/to/some/image.webp',
        );
        foreach ($urls as $url) {
            $this->assertTrue(Utilities::checkURLForCI($url));
        }
    }

    /**
     * Tests {@see Utilities::checkDomainForCI} and {@see Utilities::checkURLForCI} with a custom list.
     */
    public function testDomainAllowList() {
        // Set the domain list.
        update_option(Options::DOMAIN_LIST, array('cloud.tencent.com', 'one.of.my.own.domain'));
        // Test with random domains.
        $allowed_domains = array(
            'cloud.tencent.com',
            'one.of.my.own.domain',
        );
        $disallowed_domains = array(
            'wordpress.org',
            'github.com',
            'www.tencentcloud.com',
            'example.org',
        );
        foreach ($allowed_domains as $domain) {
            $this->assertTrue(Utilities::checkDomainForCI($domain));
        }
        foreach ($disallowed_domains as $domain) {
            $this->assertFalse(Utilities::checkDomainForCI($domain));
        }
        // Test with random URLs.
        $allowed_urls = array(
            'https://cloud.tencent.com/document/product/460',
            'https://cloud.tencent.com/favicon.ico',
            'http://one.of.my.own.domain/path/to/some/image.webp',
            'https://one.of.my.own.domain/path-to-another-image.webp',
        );
        $disallowed_urls = array(
            'https://wordpress.org',
            'https://github.com/stevapple/wptc-cloud-infinite',
            'https://www.tencentcloud.com/products/ci',
            'https://intl.cloud.tencent.com/products/ci',
            'http://example.org/',
            'http://another.of.my.own.domain/path/to/another/image.webp',
            'http://my.own.domain/logo.webp',
        );
        foreach ($allowed_urls as $url) {
            $this->assertTrue(Utilities::checkURLForCI($url));
        }
        foreach ($disallowed_urls as $url) {
            $this->assertFalse(Utilities::checkURLForCI($url));
        }
        // Cleanup the domain list.
        delete_option(Options::DOMAIN_LIST);
    }

    /**
     * Tests {@see Utilities::sanitizeDomainList()}.
     */
    public function testSanitizeDomainList() {
        // Test empty lists.
        $this->assertEquals(array(), Utilities::sanitizeDomainList(array()));
        $this->assertEquals(array(), Utilities::sanitizeDomainList(''));
        $this->assertEquals(array(), Utilities::sanitizeDomainList(" \t"));
        // Test separators.
        $this->assertEquals(array(), Utilities::sanitizeDomainList(array('')));
        $this->assertEqualsCanonicalizing(
            Utilities::sanitizeDomainList('wordpress.org, github.com, cloud.tencent.com'),
            array('wordpress.org', 'github.com', 'cloud.tencent.com')
        );
        $this->assertEqualsCanonicalizing(
            Utilities::sanitizeDomainList("wordpress.org\ngithub.com\ncloud.tencent.com"),
            array('wordpress.org', 'github.com', 'cloud.tencent.com')
        );
        // Test stripping empty domains.
        $this->assertEquals(array(), Utilities::sanitizeDomainList(array('')));
        $this->assertEquals(array(), Utilities::sanitizeDomainList(',,,'));
        $this->assertEquals(array(), Utilities::sanitizeDomainList(" \n \t"));
        // Test redundant inputs.
        $this->assertEquals(
            array('example.org'),
            Utilities::sanitizeDomainList(array_fill(0, 3, 'example.org'))
        );
        // Test URL to domains.
        $urls = array(
            'https://wordpress.org',
            'https://github.com/stevapple/wptc-cloud-infinite',
            'https://cloud.tencent.com/document/product/460',
            'https://www.tencentcloud.com/products/ci',
            'https://cloud.tencent.com/products/ci',
            'http://example.org/',
            'http://one.of.my.own.domain/path/to/some/image.webp',
        );
        $domains = array(
            'wordpress.org',
            'github.com',
            'cloud.tencent.com',
            'www.tencentcloud.com',
            'example.org',
            'one.of.my.own.domain',
        );
        $this->assertEqualsCanonicalizing($domains, Utilities::sanitizeDomainList($urls));
        // Test IDN domains.
        $idn_domains = array('中国移动.中国', '📧.ws', 'hermès.com');
        $punycode_domains = array(
            'xn--fiq02ib9d179b.xn--fiqs8s', // 中国移动.中国
            'xn--du8h.ws',                  // 📧.ws
            'xn--herms-7ra.com',            // hermès.com
        );
        $this->assertEqualsCanonicalizing($punycode_domains, Utilities::sanitizeDomainList($idn_domains));
    }
}
