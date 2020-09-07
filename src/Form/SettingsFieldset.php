<?php

namespace AdvancedResourceTemplate\Form;

use Laminas\Form\Element;
use Laminas\Form\Fieldset;

class SettingsFieldset extends Fieldset
{
    protected $label = 'Advanced Resource Template'; // @translate

    public function init()
    {
        $this
            ->add([
                'name' => 'advancedresourcetemplate_autofillers',
                'type' => Element\Textarea::class,
                'options' => [
                    'label' => 'Autofillers', // @translate
                    'info' => 'List of autofillers to use for this template.', // @translate
                    'documentation' => 'https://gitlab.com/Daniel-KM/Omeka-S-module-AdvancedResourceTemplate',
                ],
                'attributes' => [
                    'id' => 'advancedresourcetemplate_autofillers',
                    'rows' => 8,
                ],
            ]);
    }
}
