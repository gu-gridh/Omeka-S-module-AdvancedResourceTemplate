<?php declare(strict_types=1);

namespace AdvancedResourceTemplate;

return [
    'autofillers' => [
        'factories' => [
            Autofiller\GenericAutofiller::class => Service\Autofiller\AutofillerFactory::class,
            Autofiller\GeonamesAutofiller::class => Service\Autofiller\AutofillerFactory::class,
            Autofiller\IdRefAutofiller::class => Service\Autofiller\AutofillerFactory::class,
        ],
        'aliases' => [
            'generic' => Autofiller\GenericAutofiller::class,
            'geonames' => Autofiller\GeonamesAutofiller::class,
            'idref' => Autofiller\IdRefAutofiller::class,
        ],
    ],
    'service_manager' => [
        'factories' => [
            Autofiller\AutofillerPluginManager::class => Service\Autofiller\AutofillerPluginManagerFactory::class,
        ],
        'aliases' => [
            'Autofiller\Manager' => Autofiller\AutofillerPluginManager::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
    ],
    'form_elements' => [
        'invokables' => [
            Form\SettingsFieldset::class => Form\SettingsFieldset::class,
        ],
        'factories' => [
            'Omeka\Form\Element\DataTypeSelect' => Service\Form\Element\DataTypeSelectFactory::class,
            'Omeka\Form\ResourceTemplateForm' => Service\Form\ResourceTemplateFormFactory::class,
            'Omeka\Form\ResourceTemplatePropertyFieldset' => Service\Form\ResourceTemplatePropertyFieldsetFactory::class,
        ],
    ],
    'controllers' => [
        'factories' => [
            'AdvancedResourceTemplate\Controller\Admin\Index' => Service\Controller\Admin\IndexControllerFactory::class,
        ],
    ],
    'controller_plugins' => [
        'invokables' => [
            'fieldNameToProperty' => Mvc\Controller\Plugin\FieldNameToProperty::class,
            'mapper' => Mvc\Controller\Plugin\Mapper::class,
        ],
        'factories' => [
            'mapperHelper' => Service\ControllerPlugin\MapperHelperFactory::class,
        ],
    ],
    'router' => [
        'routes' => [
            'admin' => [
                'child_routes' => [
                    'values' => [
                        'type' => \Laminas\Router\Http\Literal::class,
                        'options' => [
                            'route' => '/values',
                            'defaults' => [
                                '__NAMESPACE__' => 'AdvancedResourceTemplate\Controller\Admin',
                                'controller' => 'Index',
                                'action' => 'values',
                            ],
                        ],
                    ],
                    'autofiller' => [
                        'type' => \Laminas\Router\Http\Literal::class,
                        'options' => [
                            'route' => '/autofiller',
                            'defaults' => [
                                '__NAMESPACE__' => 'AdvancedResourceTemplate\Controller\Admin',
                                'controller' => 'Index',
                                'action' => 'autofiller',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'settings' => [
                                'type' => \Laminas\Router\Http\Literal::class,
                                'options' => [
                                    'route' => '/settings',
                                    'defaults' => [
                                        'action' => 'autofillerSettings',
                                    ],
                                ],
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
        'No results', // @translate
    ],
    'advancedresourcetemplate' => [
        'settings' => [
            // The default autofillers are in /data/mapping/mappings.ini.
            'advancedresourcetemplate_autofillers' => [],
        ],
    ],
];
