<?php

namespace Location\Model;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use Application\Core\CollectionMap;
use Location\Model\LocationService;

class LocationServiceFactory implements FactoryInterface {

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null) {
        $database       = $container->get('Database');
        $locCollection = $database->selectCollection(CollectionMap::LOCATION_COLLECTION);
        $streetCollection = $database->selectCollection(CollectionMap::STREET_COLLECTION);
        return new LocationService($locCollection,$streetCollection);
    }

}
