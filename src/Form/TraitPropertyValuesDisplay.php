<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\Form;

use Common\Form\Element as CommonElement;

trait TraitPropertyValuesDisplay
{
    protected function addElementsPropertyDisplay()
    {
        $this
            ->add([
                'name' => 'advancedresourcetemplate_properties_display',
                'type' => CommonElement\OptionalMultiCheckbox::class,
                'options' => [
                    'element_group' => 'resources',
                    'label' => 'Display of property values', // @translate
                    'value_options' => [
                        [
                            'value' => 'search_value',
                            'label' => 'Value as search', // @translate
                            'label_attributes' => [
                                'class' => 'art_pd_value',
                            ],
                        ],
                        [
                            'value' => 'advanced_search_value',
                            'label' => 'Value as advanced search (module or fallback)', // @Translate
                            'label_attributes' => [
                                'class' => 'art_pd_value',
                            ],
                        ],
                        [
                            'value' => 'search_icon_prepend',
                            'label' => 'Prepend an icon for search link', // @translate
                            'label_attributes' => [
                                'class' => 'art_pd_prepend group-br',
                            ],
                        ],
                        [
                            'value' => 'advanced_search_icon_prepend',
                            'label' => 'Prepend an icon for advanced search link (module or fallback)', // @translate
                            'label_attributes' => [
                                'class' => 'art_pd_prepend',
                            ],
                        ],
                        [
                            'value' => 'resource_icon_prepend',
                            'label' => 'Prepend an icon for linked resource', // @translate
                            'label_attributes' => [
                                'class' => 'art_pd_prepend',
                            ],
                        ],
                        [
                            'value' => 'uri_icon_prepend',
                            'label' => 'Prepend an icon for external uri', // @translate
                            'label_attributes' => [
                                'class' => 'art_pd_prepend',
                            ],
                        ],
                        [
                            'value' => 'search_icon_append',
                            'label' => 'Append an icon for search link', // @translate
                            'label_attributes' => [
                                'class' => 'art_pd_append group-br',
                            ],
                        ],
                        [
                            'value' => 'advanced_search_icon_append',
                            'label' => 'Append an icon for advanced search link (module or fallback)', // @translate
                            'label_attributes' => [
                                'class' => 'art_pd_append',
                            ],
                        ],
                        [
                            'value' => 'resource_icon_append',
                            'label' => 'Append an icon for linked resource', // @translate
                            'label_attributes' => [
                                'class' => 'art_pd_append',
                            ],
                        ],
                        [
                            'value' => 'uri_icon_append',
                            'label' => 'Append an icon for external uri', // @translate
                            'label_attributes' => [
                                'class' => 'art_pd_append',
                            ],
                        ],
                    ],
                ],
                'attributes' => [
                    'id' => 'advancedresourcetemplate_properties_display',
                    'class' => 'groups-br',
                ],
            ])
            ->add([
                'name' => 'advancedresourcetemplate_properties_as_search_whitelist',
                'type' => CommonElement\OptionalPropertySelect::class,
                'options' => [
                    'element_group' => 'resources',
                    'label' => 'Properties to display as search link (whitelist)', // @translate
                    'term_as_value' => true,
                    'prepend_value_options' => [
                        'all' => 'All properties', // @translate',
                    ],
                ],
                'attributes' => [
                    'id' => 'advancedresourcetemplate_properties_as_search_whitelist',
                    'multiple' => true,
                    'class' => 'chosen-select',
                    'data-placeholder' => 'Select properties…', // @translate
                ],
            ])
            ->add([
                'name' => 'advancedresourcetemplate_properties_as_search_blacklist',
                'type' => CommonElement\OptionalPropertySelect::class,
                'options' => [
                    'element_group' => 'resources',
                    'label' => 'Properties to display as search link (blacklist)', // @translate
                    'term_as_value' => true,
                ],
                'attributes' => [
                    'id' => 'advancedresourcetemplate_properties_as_search_blacklist',
                    'multiple' => true,
                    'class' => 'chosen-select',
                    'data-placeholder' => 'Select properties…', // @translate
                ],
            ])
        ;
        return $this;
    }
}
