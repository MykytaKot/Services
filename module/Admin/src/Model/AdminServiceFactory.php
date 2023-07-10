<?php

namespace Admin\Model;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use Application\Core\CollectionMap;
use Admin\Model\AdminService;

class AdminServiceFactory implements FactoryInterface {

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null) {
        $database       = $container->get('Database');
        $AdminCollection = $database->selectCollection(CollectionMap::ADMIN_FUNCIONS);
        return new AdminService($AdminCollection);
    }

}