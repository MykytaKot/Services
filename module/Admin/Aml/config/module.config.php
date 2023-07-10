<?php

declare(strict_types=1);

namespace Aml;

use Laminas\Router\Http\Hostname;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;

return [
    'router' => [
        'routes' => [
            'aml' => [
                'type' => Hostname::class,
                'options' => [
                    'route' => ':4th.[:3rd.]:2nd.:1st',
                    'constraints' => [
                        '4th' => 'aml',
                        '3rd' => '.*?',
                    ],
                ],
                'child_routes' => [
                    'lists' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/lists',
                            'defaults' => [
                                'controller' => Controller\AmlController::class,
                                'action' => 'lists',
                            ],
                        ],
                    ],
                    'search' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/search',
                            'defaults' => [
                                'controller' => Controller\AmlController::class,
                                'action' => 'search',
                            ],
                        ],
                    ],
                    'update' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/update[/id[/:id]]',
                            'defaults' => [
                                'controller' => Controller\AmlController::class,
                                'action' => 'update',
                            ],
                        ],
                    ],
                    // TODO DELETE ME
                    'find' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/find',
                            'defaults' => [
                                'controller' => Controller\AmlOldController::class,
                                'action' => 'find',
                            ],
                        ],
                    ],
                    // TODO DELETE ME
                    'save' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/save',
                            'defaults' => [
                                'controller' => Controller\AmlOldController::class,
                                'action' => 'save',
                            ],
                        ],
                    ],
                ],
            ],
        ]
    ],
    'controllers' => [
        'factories' => [
            Controller\AmlController::class => Controller\Factory\AmlControllerFactory::class,
            // TODO DELETE ME
            Controller\AmlOldController::class => Controller\Factory\AmlOldControllerFactory::class,
        ],
    ],
    'service_manager' => [
        'factories' => [
            Mapper\AmlMapper::class => Mapper\Factory\AmlMapperFactory::class,

            Service\AmlService::class => Service\Factory\AmlServiceFactory::class,
            Service\EmailService::class => Service\Factory\EmailServiceFactory::class,
            Service\SlugifyService::class => InvokableFactory::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            'aml' => __DIR__ . '/../view',
        ],
    ],
];
