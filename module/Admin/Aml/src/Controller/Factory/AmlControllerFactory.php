<?php

declare(strict_types=1);

namespace Aml\Controller\Factory;

use Aml\Controller\AmlController;
use Aml\Service\AmlService;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class AmlControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new AmlController(
            $container->get(AmlService::class)
        );
    }
}
