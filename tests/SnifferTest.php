<?php

namespace CatLab\SameSiteCookieSniffer\Tests;

use CatLab\SameSiteCookieSniffer\Sniffer;
use PHPUnit\Framework\TestCase;

class SnifferTest extends TestCase
{
    const CHROME = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36';

    // iOS 12 WebKit treats SameSite=None as Strict.
    const IOS_12 = 'Mozilla/5.0 (iPhone; CPU iPhone OS 12_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.0 Mobile/15E148 Safari/604.1';

    /** @var array */
    private $server;

    protected function setUp(): void
    {
        $this->server = $_SERVER;
        unset($_SERVER['HTTP_USER_AGENT']);
        $_SERVER['HTTPS'] = 'on';
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
    }

    public function testMissingUserAgentSendsSameSiteNoneWithoutDeprecation()
    {
        $parameters = (new Sniffer())->getCookieParameters();

        $this->assertSame('None', $parameters['samesite']);
        $this->assertTrue($parameters['secure']);
    }

    public function testEmptyUserAgentSendsSameSiteNone()
    {
        $_SERVER['HTTP_USER_AGENT'] = '';

        $this->assertSame('None', (new Sniffer())->getCookieParameters()['samesite']);
    }

    public function testCompatibleBrowserGetsSameSiteNone()
    {
        $_SERVER['HTTP_USER_AGENT'] = self::CHROME;

        $this->assertSame('None', (new Sniffer())->getCookieParameters()['samesite']);
    }

    public function testIncompatibleBrowserGetsNoSameSite()
    {
        $_SERVER['HTTP_USER_AGENT'] = self::IOS_12;

        $this->assertArrayNotHasKey('samesite', (new Sniffer())->getCookieParameters());
    }

    public function testExplicitAgentStringWinsOverTheRequest()
    {
        $_SERVER['HTTP_USER_AGENT'] = self::CHROME;

        $this->assertArrayNotHasKey('samesite', (new Sniffer(self::IOS_12))->getCookieParameters());
    }

    public function testInsecureConnectionNeverSendsSameSiteNone()
    {
        unset($_SERVER['HTTPS']);
        $_SERVER['HTTP_USER_AGENT'] = self::CHROME;

        $parameters = (new Sniffer())->getCookieParameters();
        $this->assertFalse($parameters['secure']);
        $this->assertArrayNotHasKey('samesite', $parameters);
    }
}
