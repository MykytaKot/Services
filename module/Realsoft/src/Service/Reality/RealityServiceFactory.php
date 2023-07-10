<?php

namespace Realsoft\Service\Reality;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use Application\Core\CollectionMap;
use Realsoft\Service\Reality\RealityService;

class RealityServiceFactory implements FactoryInterface {

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null) {
        $database       = $container->get('Database');
        $settingsCollection = $database->selectCollection(CollectionMap::REALSOFT_SETTINGS_COLLECTION);
        $queueCollection = $database->selectCollection(CollectionMap::REALSOFT_QUEUE_COLLECTION);
        return new RealityService($settingsCollection,$queueCollection);
    }

}
