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
                            'value' => 'value_search',
                            'label' => 'Value as search', // @translate
                            'label_attributes' => [
                                'class' => 'art_pd_value',
                            ],
                        ],
                        [
                            'value' => 'value_advanced_search',
                            'label' => 'Value as advanced search (module or fallback)', // @translate
                            'label_attributes' => [
                                'class' => 'art_pd_value',
                            ],
                        ],
                        [
                            'value' => 'prepend_icon_search',
                            'label' => 'Prepend an icon for search link', // @translate
                            'label_attributes' => [
                                'class' => 'art_pd_prepend group-br',
                            ],
                        ],
                        [
                            'value' => 'prepend_icon_advanced_search',
                            'label' => 'Prepend an icon for advanced search link (module or fallback)', // @translate
                            'label_attributes' => [
                                'class' => 'art_pd_prepend',
                            ],
                        ],
                        [
                            'value' => 'prepend_icon_resource',
                            'label' => 'Prepend an icon for linked resource', // @translate
                            'label_attributes' => [
                                'class' => 'art_pd_prepend',
                            ],
                        ],
                        [
                            'value' => 'prepend_icon_uri',
                            'label' => 'Prepend an icon for external uri', // @translate
                            'label_attributes' => [
                                'class' => 'art_pd_prepend',
                            ],
                        ],
                        [
                            'value' => 'append_icon_search',
                            'label' => 'Append an icon for search link', // @translate
                            'label_attributes' => [
                                'class' => 'art_pd_append group-br',
                            ],
                        ],
                        [
                            'value' => 'append_icon_advanced_search',
                            'label' => 'Append an icon for advanced search link (module or fallback)', // @translate
                            'label_attributes' => [
                                'class' => 'art_pd_append',
                            ],
                        ],
                        [
                            'value' => 'append_icon_resource',
                            'label' => 'Append an icon for linked resource', // @translate
                            'label_attributes' => [
                                'class' => 'art_pd_append',
                            ],
                        ],
                        [
                            'value' => 'append_icon_uri',
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
