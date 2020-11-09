<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\Form;

use Laminas\EventManager\Event;
use Laminas\EventManager\EventManagerAwareTrait;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;
use Laminas\Form\Form;
use Omeka\Form\Element\ResourceClassSelect;

class ResourceTemplateForm extends Form
{
    use EventManagerAwareTrait;

    public function init(): void
    {
        $this->add([
            'name' => 'o:label',
            'type' => 'Text',
            'options' => [
                'label' => 'Label', // @translate
            ],
            'attributes' => [
                'id' => 'o-label',
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'o:resource_class',
            'type' => ResourceClassSelect::class,
            'options' => [
                'label' => 'Suggested class', // @translate
                'empty_option' => '',
            ],
            'attributes' => [
                'id' => 'o-resource_class-id',
                'class' => 'chosen-select',
                'data-placeholder' => 'Select a class',
            ],
        ]);

        $this->add([
            'name' => 'o:title_property',
            'type' => 'hidden',
            'attributes' => [
                'id' => 'o-title-property-id',
            ],
        ]);
        $this->add([
            'name' => 'o:description_property',
            'type' => 'hidden',
            'attributes' => [
                'id' => 'o-description-property-id',
            ],
        ]);

        $this->add([
            'type' => Fieldset::class,
            'name' => 'o:data',
            'options' => [
                'label' => 'Other settings', // @translate
            ],
            'attributes' => [
                'class' => 'settings',
            ],
        ]);

        $this->add([
            'type' => Element\Collection::class,
            'name' => 'o:resource_template_property',
            'options' => [
                'label' => 'Properties', // @translate
                'count' => 0,
                'allow_add' => true,
                'allow_remove' => true,
                'should_create_template' => false,
                'use_as_base_fieldset' => true,
                'create_new_objects' => false,
                'target_element' => [
                    'type' => ResourceTemplatePropertyFieldset::class,
                ],
            ],
            'attributes' => [
                'id' => 'properties',
            ],
            'attributes' => [
                'class' => 'settings',
            ],
        ]);

        $event = new Event('form.add_elements', $this);
        $this->getEventManager()->triggerEvent($event);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'o:label',
            'required' => true,
        ]);
        $inputFilter->add([
            'name' => 'o:resource_class',
            'allow_empty' => true,
        ]);

        // Separate events because calling $form->getInputFilters() resets
        // everything.
        $event = new Event('form.add_input_filters', $this, ['inputFilter' => $inputFilter]);
        $this->getEventManager()->triggerEvent($event);
    }
}
