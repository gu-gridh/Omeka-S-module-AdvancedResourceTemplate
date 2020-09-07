<?php

namespace AdvancedResourceTemplate;

return [
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
    ],
    'controllers' => [
        'factories' => [
            'AdvancedResourceTemplate\Controller\Admin\Index' => Service\Controller\Admin\IndexControllerFactory::class,
        ],
    ],
    'router' => [
        'routes' => [
            'admin' => [
                'child_routes' => [
                    'values' => [
                        'type' => \Zend\Router\Http\Literal::class,
                        'options' => [
                            'route' => '/values',
                            'defaults' => [
                                '__NAMESPACE__' => 'AdvancedResourceTemplate\Controller\Admin',
                                'controller' => 'Index',
                                'action' => 'values',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => dirname(__DIR__) . '/language',
                'pattern' => '%s.mo',
                'text_domain' => null,
            ],
        ],
    ],
    'js_translate_strings' => [
        'New item', // @translate
        'New item set', // @translate
    ],
    'advancedresourcetemplate' => [
    ],
];
