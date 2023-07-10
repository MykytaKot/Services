<?php

namespace Application\Core;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use MongoDB\Client;

class DatabaseFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('Config');

        if (!isset($config['mongo'])) {
            die("Missing database configuration! [local.php]. Check if old config is not cached!");
        }

		$dbConfig = $config['mongo'];

		$user   = $dbConfig['options']['username'];
		$pwd    = $dbConfig['options']['password'];
		$db     = $dbConfig['options']['db'];
		$server = $dbConfig['server'];

		$driverOptions = [
			'typeMap' => [
				'root'     => 'array',
				'document' => 'array',
				'array'    => 'array'
			]
		];

        $mongo = new Client("mongodb://${user}:${pwd}@${server}/${db}", [], $driverOptions);

        return $mongo->selectDatabase($db);
    }
}
