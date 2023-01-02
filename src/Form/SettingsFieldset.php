<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\Form;

use AdvancedResourceTemplate\Form\Element as AdvancedResourceTemplateElement;
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
                'type' => AdvancedResourceTemplateElement\OptionalMultiCheckbox::class,
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
                    'info' => 'Disable the checking of the template settings. For example if a value is longer than the specified length, it will be saved anyway.
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
