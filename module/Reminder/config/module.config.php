<?php
namespace Reminder;

use Zend\Router\Http\Segment;
use Zend\ServiceManager\Factory\InvokableFactory;

return [


    'router' => array(
        'routes' => array(
            'reminder' => array(
                'type' => 'Hostname',
                'options' => array(
                    'route' => 'reminder.:domain.:ext',
                ),
                'child_routes' => array(
                    'service' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/:action',
                            'defaults' => [
                                'controller' => 'Reminder\Controller\Reminder'
                            ],
                        ],
                    ],
                )
            ),
        )
    ),


    'controllers' => [
        'factories' => [
            //Controller\ReminderController::class => InvokableFactory::class,
            'Reminder\Controller\Reminder' => Controller\ReminderControllerFactory::class,
        ],
    ],

    'view_manager' => [
        'template_path_stack' => [
            'reminder' => __DIR__ . '/../view',
        ],
    ],
];