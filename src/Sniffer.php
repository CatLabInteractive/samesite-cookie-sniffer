<?php

namespace CatLab\SameSiteCookieSniffer;

use Skorp\Dissua\SameSite;

/**
 * Class Sniffer
 * @package CatLab\SameSiteCookieSniffer
 */
class Sniffer
{
    /**
     * @return Sniffer
     */
    public static function instance()
    {
        static $in;
        if (!isset($in)) {
            $in = new self();
        }
        return $in;
    }

    /**
     * @var string
     */
    private $agentString;

    /**
     * Sniffer constructor.
     * @param null $agentString
     */
    public function __construct($agentString = null)
    {
        if ($agentString === null && isset($_SERVER['HTTP_USER_AGENT'])) {
            $agentString = $_SERVER['HTTP_USER_AGENT'];
        }
        $this->agentString = $agentString;
    }

    /**
     * @param array $parameters
     */
    public function setSessionCookieParameters($parameters = [])
    {
        $parameters = $this->getCookieParameters($parameters);
        if (isset($parameters['expires'])) {
            unset($parameters['expires']);
        }

        $parameters['lifetime'] = isset($parameters['lifetime']) ? $parameters['lifetime'] : 0;


        // preparing for the end of the cookie world
        session_set_cookie_params($parameters);
    }

    /**
     * @param array $parameters
     * @return array
     */
    public function getCookieParameters($parameters = [])
    {
        $expires = isset($parameters['expires']) ? $parameters['expires'] : 0;
        $httponly = isset($parameters['httponly']) ? $parameters['httponly'] : true;
        $secure = isset($parameters['secure']) ? $parameters['secure'] : true;
        $samesite = isset($parameters['samesite']) ? $parameters['samesite'] : 'None';

        // Is SameSite compatible? Without a User-Agent (CLI, health checks)
        // there is nothing to sniff: send it, which is what the check itself
        // returned for a null agent before PHP 8.1 deprecated passing null
        // to preg_match().
        $shouldSendSameSiteNone = true;
        if (is_string($this->agentString) && $this->agentString !== '') {
            $shouldSendSameSiteNone = SameSite::handle($this->agentString);
        }

        $secure = $secure && $this->isSecureConnection();

        $cookieParameters = [
            'expires' => $expires,
            'httponly' => $httponly,
            'secure' => $secure,
        ];

        if ($shouldSendSameSiteNone && $secure) {
            $cookieParameters['samesite'] = $samesite;
        }

        return array_merge($parameters, $cookieParameters);
    }

    /**
     * @return bool
     */
    protected function isSecureConnection()
    {
        return
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    }
}
