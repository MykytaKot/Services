<?php

namespace Admin;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;
return array(
    'router' => array(
        'routes' => array(
            
            'admin' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/admin[/:action]',
                    'defaults' => [
                        'controller' =>  'Admin\Controller\Index',
                        'action'     => 'home',
                    ],
                ],
            ],
            'functions' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/admin/functions[/:action]',
                    'defaults' => [
                        'controller' =>  'Admin\Controller\Index',
                        'action'     => 'index',
                    ],
                ],
            ],
            'notifications' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/admin/notifications[/:action]',
                    'defaults' => [
                        'controller' =>  'Admin\Controller\Notification',
                        'action'     => 'index',
                    ],
                ],
            ],
            'notifications/createform' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/admin/notifications/create[/:action]',
                    'defaults' => [
                        'controller' =>  'Admin\Controller\Notification',
                        'action'     => 'create',
                    ],
                ],
            ],
            'notifications/add' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/admin/notifications/add[/:action]',
                    'defaults' => [
                        'controller' =>  'Admin\Controller\Notification',
                        'action'     => 'add',
                    ],
                ],
            ],
            'admin/addfunction' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/admin/functions/add[/:action]',
                    'defaults' => [
                        'controller' =>  'Admin\Controller\Index',
                        'action'     => 'add',
                    ],
                ],
            ],
            'admin/deletefunction' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/admin/functions/delete[/:action]',
                    'defaults' => [
                        'controller' =>  'Admin\Controller\Index',
                        'action'     => 'delete',
                    ],
                ],
            ],
            'admin/editfunction' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/admin/functions/edit[/:action]',
                    'defaults' => [
                        'controller' =>  'Admin\Controller\Index',
                        'action'     => 'edit',
                    ],
                ],
            ],
            'funkcie' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/api[/:action]',
                    'defaults' => [
                        'controller' =>  'Admin\Controller\Index',
                        'action'     => 'json',
                    ],
                ],
            ],
            'login' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/login[/:action]',
                    'defaults' => [
                        'controller' =>  'Admin\Controller\Index',
                        'action'     => 'login',
                    ],
                ],
            ],
            'logout' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/logout[/:action]',
                    'defaults' => [
                        'controller' =>  'Admin\Controller\Index',
                        'action'     => 'logout',
                    ],
                ],
            ],
        )
    ),
    'controllers' => [
        'factories' => [
            'Admin\Controller\Index' => Controller\IndexControllerFactory::class,
            'Admin\Controller\Notification' => Controller\NotificationControllerFactory::class,
        ],
    ],
    'service_manager' => array(
        'factories' => array(
            'AdminService' => Model\AdminServiceFactory::class,
            'AuthService' => Model\AuthServiceFactory::class,
            'NotificationService' => Model\NotificationSeviceFactory::class,
        ),
        'shared' => array()
    ),
    
    'view_helpers' => array(
        'factories' => array(
        )
    ),
    'view_manager' => array(
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'template_map' => [
            'admin/layout/layout'           => __DIR__ . '/../view/admin/layout/layout.phtml',
           
            'admin/index/login' => __DIR__ . '/../view/admin/index/login.phtml',
           
        ],
        'template_path_stack' => array(
            __DIR__ . '/../view'
        ),
    )
);
