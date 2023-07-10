<?php

namespace Realsoft;

return array(
    'router' => array(
        'routes' => array(
            'realsoft' => array(
                'type' => 'Hostname',
                'options' => array(
                    'route' => 'realsoft.:domain.:ext',
                ),
                'child_routes' => array(
                    'service' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/:action',
                            'defaults' => [
                                'controller' => 'Realsoft\Controller\Index'
                            ],
                        ],
                    ],
                )
            ),
          /*  'location' => array(
                'type' => 'Hostname',
                'options' => array(
                    'route' => 'location.:domain.:ext',
                    'defaults' => array(
                        'controller' => 'Location\Controller\Index',
                        'action' => 'index'
                    ),
                )
            ),*/
        )
    ),
    'controllers' => [
        'factories' => [
            'Realsoft\Controller\Index' => Controller\IndexControllerFactory::class,
        ]
    ],
    'service_manager' => array(
        'factories' => array(
            'RealityService' => Service\Reality\RealityServiceFactory::class,
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
