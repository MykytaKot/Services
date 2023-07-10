<?php

declare(strict_types=1);

namespace Aml\Mapper\Factory;

use Aml\Mapper\AmlMapper;
use Application\Core\CollectionMap;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class AmlMapperFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new AmlMapper(
            $container->get('Database')->selectCollection(CollectionMap::AML_LISTS),
            $container->get('Database')->selectCollection(CollectionMap::AML_NAMES),
            $container->get('config')
        );
    }
}
