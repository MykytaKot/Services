<?php

declare(strict_types=1);

namespace Aml\Service\Factory;

use Aml\Mapper\AmlMapper;
use Aml\Service\AmlService;
use Aml\Service\EmailService;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class AmlServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new AmlService(
            $container->get(AmlMapper::class),
            $container->get(EmailService::class)
        );
    }
}
