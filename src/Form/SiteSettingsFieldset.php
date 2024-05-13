<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\Form;

use Common\Form\Element as CommonElement;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;

class SiteSettingsFieldset extends Fieldset
{
    use TraitPropertyValuesDisplay;

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
            ->addElementsPropertyDisplay()
        ;
    }
}
