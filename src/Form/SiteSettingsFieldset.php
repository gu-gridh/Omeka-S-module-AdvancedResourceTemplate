<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\Form;

use Common\Form\Element as CommonElement;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;

class SiteSettingsFieldset extends Fieldset
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
                'name' => 'advancedresourcetemplate_properties_display_site',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'element_group' => 'resources',
                    'label' => 'Mode of display of property values', // @translate
                    'value_options' => [
                        '' => 'Default display', // @translate
                        'main' => 'Use main settings', // @translate
                        'site' => 'Use site settings below', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'advancedresourcetemplate_properties_display_site',
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
        ;
    }
}
