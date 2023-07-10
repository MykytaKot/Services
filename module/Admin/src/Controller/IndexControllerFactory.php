<?php

namespace Admin\Controller;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use Admin\Controller\IndexController;

class IndexControllerFactory implements FactoryInterface {

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null) {
        $adminService       = $container->get('AdminService');
        $authService       = $container->get('AuthService');
        $notificationService       = $container->get('NotificationService');
        return new IndexController($adminService, $authService, $notificationService);
    }

}