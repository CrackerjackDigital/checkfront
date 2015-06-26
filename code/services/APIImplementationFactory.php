<?php
/**
 * Factory class for API Implementation/Facade
 */

class CheckfrontAPIImplementationFactory implements \SilverStripe\Framework\Injector\Factory {

    /**
     * Returns a new, configured CheckfrontAPIImplementation instance.
     *
     * @param string $service The class name of the service.
     * @param array $params The constructor parameters.
     * @return object The created service instances.
     */
    public function create($service, array $params = array())
    {
        return $service::create($params);

    }
}