<?php

namespace Location\Controller;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use Location\Controller\ImportController;

class ImportControllerFactory implements FactoryInterface {

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null) {
        $locationService       = $container->get('LocationService');

        return new ImportController($locationService);
    }

}
