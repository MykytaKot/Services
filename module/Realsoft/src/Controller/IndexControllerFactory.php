<?php

namespace Realsoft\Controller;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use Realsoft\Controller\IndexController;
use Application\Core\CollectionMap;

class IndexControllerFactory implements FactoryInterface {

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null) {
        $database       = $container->get('Database');
        $realityService       = $container->get('RealityService');
       // $globalSettings = $container->get('Admin\Model\GlobalSettings\GlobalSettings');
         $log_collection = $database->selectCollection(CollectionMap::REALSOFT_LOGS_COLLECTION);
        $queue_collection = $database->selectCollection(CollectionMap::REALSOFT_QUEUE_COLLECTION);
        
        return new IndexController($log_collection,$queue_collection,$realityService);
    }

}
