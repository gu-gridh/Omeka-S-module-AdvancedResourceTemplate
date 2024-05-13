<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\Form;

use Common\Form\Element as CommonElement;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;

class SettingsFieldset extends Fieldset
{
    protected $label = 'Advanced Resource Template'; // @translate

    protected $elementGroups = [
        'resources' => 'Resources', // @translate
    ];

    public function init(): void
    {
        $this
            ->setAttribute('id', 'advanded-resource-template')
            ->setOption('element_groups', $this->elementGroups)
            ->add([
                'name' => 'advancedresourcetemplate_resource_form_elements',
                'type' => CommonElement\OptionalMultiCheckbox::class,
                'options' => [
                    'element_group' => 'resources',
                    'label' => 'Elements of resource form to display', // @translate
                    'value_options' => [
                        'metadata_collapse' => 'Collapse Metadata description by default', // @translate
                        'metadata_description' => 'Button Metadata description', // @translate
                        'language' => 'Button Language', // @translate
                        'visibility' => 'Button Visibility', // @translate
                        'value_annotation' => 'Button Value annotation', // @translate
                        'more_actions' => 'Button More actions', // @translate
                    ],
                    'use_hidden_element' => true,
                ],
                'attributes' => [
                    'id' => 'advancedresourcetemplate_resource_form_elements',
                ],
            ])
            ->add([
                'name' => 'advancedresourcetemplate_skip_checks',
                'type' => Element\Checkbox::class,
                'options' => [
                    'element_group' => 'resources',
                    'label' => 'Skip checking advanced template settings to allow to save an invalid record', // @translate
                    'info' => 'For example if a value is longer than the specified length, it will be saved anyway.
This option should be used only during a migration process or to simplify a complex batch edition or import.
It does not skip core checks, in particular required properties.', // @translate
                ],
                'attributes' => [
                    'id' => 'advancedresourcetemplate_skip_checks',
                ],
            ])
            ->add([
                'name' => 'advancedresourcetemplate_closed_property_list',
                'type' => Element\Radio::class,
                'options' => [
                    'element_group' => 'resources',
                    'label' => 'Append properties to resource form', // @translate
                    'info' => 'When no template is selected in resource form, the property selector may be available or not to force to select a template.
Warning: you may have to set each resource template as open/close to addition according to this setting.', // @translate
                    'value_options' => [
                        '0' => 'Allow', // @translate
                        '1' => 'Forbid', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'advancedresourcetemplate_closed_property_list',
                ],
            ])
            ->add([
                'name' => 'advancedresourcetemplate_skip_private_values',
                'type' => Element\Checkbox::class,
                'options' => [
                    'element_group' => 'resources',
                    'label' => 'Skip private values on sites, even when user is connected', // @translate
                ],
                'attributes' => [
                    'id' => 'advancedresourcetemplate_skip_private_values',
                ],
            ])

            ->add([
                'name' => 'advancedresourcetemplate_properties_display',
                'type' => CommonElement\OptionalMultiCheckbox::class,
                'options' => [
                    'element_group' => 'resources',
                    'label' => 'Display of property values', // @translate
                    'value_options' => [
                        'search_value' => 'Value as search', // @ŧranslate
                        'advanced_search_value' => 'Value as advanced search (module or fallback)', // @ŧranslate
                        'search_icon_prepend' => 'Prepend an icon for search link', // @translate
                        'advanced_search_icon_prepend' => 'Prepend an icon for advanced search link (module or fallback)', // @translate
                        'resource_icon_prepend' => 'Prepend an icon for linked resource', // @translate
                        'uri_icon_prepend' => 'Prepend an icon for external uri', // @translate
                        'search_icon_append' => 'Append an icon for search link', // @translate
                        'advanced_search_icon_append' => 'Append an icon for advanced search link (module or fallback)', // @translate
                        'resource_icon_append' => 'Append an icon for linked resource', // @translate
                        'uri_icon_append' => 'Append an icon for external uri', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'advancedresourcetemplate_properties_display',
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

            ->add([
                'name' => 'advancedresourcetemplate_autofillers',
                'type' => Element\Textarea::class,
                'options' => [
                    'element_group' => 'resources',
                    'label' => 'Autofillers', // @translate
                    'info' => 'The autofillers should be set in selected templates params.', // @translate
                    'documentation' => 'https://gitlab.com/Daniel-KM/Omeka-S-module-AdvancedResourceTemplate#autofilling',
                ],
                'attributes' => [
                    'id' => 'advancedresourcetemplate_autofillers',
                    'rows' => 8,
                ],
            ]);
    }
}
