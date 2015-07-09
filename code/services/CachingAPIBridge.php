<?php

/**
 * Add a caching layer to the bridge as e.g. calling API in forms may
 * result in multiple calls to api as form is built and used.
 *
 */
class CheckfrontCachingAPIBridge extends CheckfrontAPIBridge implements CheckfrontAPIInterface {
    const DefaultCacheExpirySeconds = 60;
    /**
     * @var CheckfrontAPICacheInterface
     */
    private $cache;

    public function __construct() {
        parent::__construct();
        $this->cache = CheckfrontModule::api_cache();
    }

    /**
     * Proxy method to parent.api call first checks cache for previous call, if
     * not found then makes call and adds to cache.
     *
     * @param CheckfrontAPIRequest $request
     * @return array
     */
    public function api(CheckfrontAPIRequest $request) {
        $key = $this->key(__METHOD__, $request);

        if (!$result = $this->cache->get($key)) {
            $result = parent::api($request);

            $this->cache->set($result, $key, self::DefaultCacheExpirySeconds);
        }
        return $result;
    }
    /**
     * Proxy method to parent.get call first checks cache for previous call, if
     * not found then makes call and adds to cache.
     *
     * @param CheckfrontAPIRequest $request
     * @return array
     */
    public function get(CheckfrontAPIRequest $request) {
        $key = $this->key(__METHOD__, $request);

        if (!$result = $this->cache->get($key)) {
            $result = parent::get($request);

            $this->cache->set($result, $key, self::DefaultCacheExpirySeconds);
        }
        return $result;
    }
    /**
     * Proxy method to parent.post call first checks cache for previous call, if
     * not found then makes call and adds to cache.
     *
     * @param CheckfrontAPIRequest $request
     * @return array
     */
    public function post(CheckfrontAPIRequest $request) {
        $key = $this->key(__METHOD__, $request);

        if (!$result = $this->cache->get($key)) {
            $result = parent::post($request);

            $this->cache->set($result, $key);
        }
        return $result;
    }

    /**
     * Return a unique cache key for the request object so it can be stored and
     * subsequent requests with same paraamters will be served from cache.
     *
     * @param $method - e.g. 'api', 'get', 'post'
     * @param CheckfrontAPIRequest $request
     *
     * @return string
     */
    private static function key($method, CheckfrontAPIRequest $request) {
        return md5($method . '|' . $request->getEndpoint() . '|' . json_encode($request->getData()));
    }
}