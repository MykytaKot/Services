<?php

namespace Aml\Controller\Factory;

use Aml\Controller\AmlOldController;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

// TODO DELETE ME
class AmlOldControllerFactory implements FactoryInterface {
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null) {
        return new AmlOldController(
            $container->get('Database'),
            $container->get('config'),
        );
    }
}
