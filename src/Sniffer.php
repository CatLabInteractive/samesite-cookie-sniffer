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
            $this->agentString = $_SERVER['HTTP_USER_AGENT'];
        }
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

        // Is SameSite compatible?
        $shouldSendSameSiteNone = SameSite::handle($this->agentString);

        $secure = $secure && $this->isSecureConnection();

        $parameters = [
            'expires' => $expires,
            'httponly' => $httponly,
            'secure' => $secure,
        ];

        if ($shouldSendSameSiteNone && $secure) {
            $parameters['samesite'] = $samesite;
        }

        return $parameters;
    }

    /**
     * @return bool
     */
    protected function isSecureConnection()
    {
        return
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || $_SERVER['SERVER_PORT'] == 443;
    }
}
