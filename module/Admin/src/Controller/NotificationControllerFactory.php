<?php

namespace Admin\Controller;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use Admin\Controller\NotificationController;

class NotificationControllerFactory implements FactoryInterface {

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null) {
        
        $authService       = $container->get('AuthService');
        $notificationService       = $container->get('NotificationService');
        return new NotificationController($authService, $notificationService);
    }

}