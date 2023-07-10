<?php

declare(strict_types=1);

namespace Aml\Service\Factory;

use Aml\Service\EmailService;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class EmailServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new EmailService(
            $container->get('config')
        );
    }
}
