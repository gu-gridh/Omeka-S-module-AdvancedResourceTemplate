<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\Form;

use Laminas\Form\Element;
use Laminas\Form\Fieldset;

class SettingsFieldset extends Fieldset
{
    protected $label = 'Advanced Resource Template'; // @translate

    public function init(): void
    {
        $this
            ->add([
                'name' => 'advancedresourcetemplate_closed_property_list',
                'type' => Element\Radio::class,
                'options' => [
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
                    'label' => 'Autofillers', // @translate
                    'info' => 'List of autofillers to use for this template.', // @translate
                    'documentation' => 'https://gitlab.com/Daniel-KM/Omeka-S-module-AdvancedResourceTemplate#autofilling',
                ],
                'attributes' => [
                    'id' => 'advancedresourcetemplate_autofillers',
                    'rows' => 8,
                ],
            ]);
    }
}
