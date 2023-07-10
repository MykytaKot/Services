<?php

namespace Location;
use Laminas\Router\Http\Literal;

return array(
    'router' => array(
        'routes' => array(      
            'location' => array(
                'type' => 'Hostname',
                'options' => array(
                    'route' => 'location.:domain.:ext',
                    'defaults' => array(
                        'controller' => 'Location\Controller\Index',
                    ),
                ),
				'may_terminate' => TRUE,
				'child_routes' => [
					'location' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/',
                            'defaults' => [
                                'controller' => 'Location\Controller\Index',
                                
                            ],
                        ],
                    ],
                    'import' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/import',
                            'defaults' => [
                                'controller' => 'Location\Controller\Import',
                                'action' => 'importgps',
                            ],
                        ],
                    ],
				],
            ),
			
        )
    ),
    'controllers' => [
        'factories' => [
            'Location\Controller\Index' => Controller\IndexControllerFactory::class,
			'Location\Controller\Import' => Controller\ImportControllerFactory::class
        ]
    ],
    'service_manager' => array(
        'factories' => array(
            'LocationService' => Model\LocationServiceFactory::class,
        ),
        'shared' => array()
    ),
    'view_helpers' => array(
        'factories' => array(
        )
    ),
    'view_manager' => array(
        'strategies' => array(
            'ViewJsonStrategy',
        ),
        'template_path_stack' => array(
            __DIR__ . '/../view'
        ),
    )
);
