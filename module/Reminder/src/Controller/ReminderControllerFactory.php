<?php

namespace Reminder\Controller;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use Reminder\Controller\ReminderController;
use Application\Core\CollectionMap;

class ReminderControllerFactory implements FactoryInterface {

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null) {
        $config = $container->get('Config');
        $database = $container->get('Database');
        $log_collection = $database->selectCollection('reminder_logs');
        $queue_collection = $database->selectCollection('reminder_queue');
        $locations_collection = $database->selectCollection('locations');
        
        return new ReminderController($queue_collection,$log_collection,$config, $locations_collection);
    }
}