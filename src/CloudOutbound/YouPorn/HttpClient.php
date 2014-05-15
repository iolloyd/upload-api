<?php
/**
 * cloudxxx-api (http://www.cloud.xxx)
 *
 * Copyright (C) 2014 Really Useful Limited.
 * Proprietary code. Usage restrictions apply.
 *
 * @copyright  Copyright (C) 2014 Really Useful Limited
 * @license    Proprietary
 */


namespace CloudOutbound\YouPorn;

use GuzzleHttp;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Subscriber\Cookie as CookieSubscriber;

class HttpClient extends GuzzleHttp\Client
{
    /**
     * @var CookieJarInterface
     */
    protected $cookieJar;

    /**
     * {@inheritDoc}
     */
    public function __construct(array $config = [])
    {
        $this->configureCookieJar($config);
        parent::__construct($config);
    }

    /**
     * Get the session cookie jar
     *
     * @return CookieJarInterface
     */
    public function getCookieJar()
    {
        return $this->cookieJar;
    }

    // XHR Request Methods

    protected function createJsonOptions(array $options = [])
    {
        $options = array_replace_recursive([
            'headers' => [
                'Accept'           => 'application/json,text/javascript',
                'X-Requested-With' => 'XMLHttpRequest',
            ],
        ], $options);

        return $options;
    }

    public function createJsonRequest($method, $url = null, array $options = [])
    {
        return parent::createRequest($method, $url, $this->createJsonOptions($options));
    }

    public function jsonGet($url = null, $options = [])
    {
        //return parent::get($url, $options);
        return parent::get($url, $this->createJsonOptions($options));
    }

    public function jsonHead($url = null, array $options = [])
    {
        return parent::head($url, $this->createJsonOptions($options));
    }

    public function jsonDelete($url = null, array $options = [])
    {
        return parent::delete($url, $this->createJsonOptions($options));
    }

    public function jsonPut($url = null, array $options = [])
    {
        return parent::put($url, $this->createJsonOptions($options));
    }

    public function jsonPatch($url = null, array $options = [])
    {
        return parent::patch($url, $this->createJsonOptions($options));
    }

    public function jsonPost($url = null, array $options = [])
    {
        return parent::post($url, $this->createJsonOptions($options));
    }

    public function jsonOptions($url = null, array $options = [])
    {
        return parent::options($url, $this->createJsonOptions($options));
    }

    // Config

    /**
     * Get the default User-Agent string to use with Guzzle
     *
     * TODO: env/version
     *
     * @return string
     */
    public static function getDefaultUserAgent()
    {
        static $defaultAgent = '';

        if (!$defaultAgent) {
            $defaultAgent = sprintf(
                'Mozilla/5.0 (compatible; cloudxxx; +http://www.cloud.xxx/support) Gecko/20100101 %s/%s (by ReallyUseful)',
                'development',
                '0.1'
            );
        }

        return $defaultAgent;
    }

    /**
     * Get an array of default options to apply to the client
     *
     * @return array
     */
    protected function getDefaultOptions()
    {
        $settings = array_replace(parent::getDefaultOptions(), [
            'timeout'         => 60,
            'connect_timeout' => 10,
            'allow_redirects' => false,
            'expect'          => false,
            'cookies'         => $this->getCookieJar(),

            // TODO: based on log level?
            //'debug' => true,

            'headers' => [
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.8',
                'Cache-Control'   => 'max-age=0',
            ],
        ]);

        return $settings;
    }

    /**
     * Configure the cookie jar and default cookies
     *
     * @param array $config
     * @return void
     */
    protected function configureCookieJar(&$config)
    {
        $value = isset($config['cookies']) ? $config['cookies'] : true;

        if ($value === false) {
            return;
        } elseif ($value === true) {
            $this->cookieJar = new CookieJar();
        } elseif ($value instanceof CookieJarInterface) {
            $this->cookieJar = $value;
        } elseif (is_array($value)) {
            if (!isset($config['base_url'])) {
                throw new \InvalidArgumentException('config.cookies array requires config.base_url to be set');
            } elseif (is_array($config['base_url'])) {
                $baseUrl = GuzzleHttp\Url::fromString(GuzzleHttp\uri_template($config['base_url'][0], $config['base_url'][1]));
            } else {
                $baseUrl = GuzzleHttp\Url::fromString($config['base_url']);
            }

            $this->cookieJar = CookieJar::fromArray($value, ltrim($baseUrl->getHost(), 'w'));
        } else {
            throw new \InvalidArgumentException('config.cookies must be an array, '
                . 'true, or a CookieJarInterface object');
        }

        unset($config['cookies']);
    }
}
