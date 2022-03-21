<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\Form;

use AdvancedResourceTemplate\Form\Element as AdvancedResourceTemplateElement;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;
use Laminas\I18n\Translator\TranslatorAwareTrait;
use Omeka\Form\Element as OmekaElement;

class ResourceTemplateDataFieldset extends Fieldset
{
    use TranslatorAwareTrait;

    /**
     * @var array
     */
    protected $autofillers = [];

    public function init(): void
    {
        $this
            ->add([
                'name' => 'require_resource_class',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Require a class', // @translate
                ],
                'attributes' => [
                    'id' => 'require_resource_class',
                ],
            ])
            ->add([
                'name' => 'closed_property_list',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Limit to specified properties', // @translate
                ],
                'attributes' => [
                    'id' => 'closed_property_list',
                ],
            ])
            ->add([
                'name' => 'value_languages',
                'type' => OmekaElement\ArrayTextarea::class,
                'options' => [
                    'label' => 'Languages for values', // @translate
                    'as_key_value' => true,
                ],
                'attributes' => [
                    'id' => 'value_languages',
                ],
            ])
            ->add([
                'name' => 'default_language',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Default language', // @translate
                ],
                'attributes' => [
                    'id' => 'default_language',
                ],
            ])
            ->add([
                'name' => 'no_language',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'No language', // @translate
                ],
                'attributes' => [
                    'id' => 'no_language',
                ],
            ])
            ->add([
                'name' => 'autocomplete',
                'type' => Element\Radio::class,
                'options' => [
                    'label' => 'Autocomplete with existing values', // @translate
                    'value_options' => [
                        'no' => 'No', // @translate
                        'sw' => 'Starts with', // @translate
                        'in' => 'Contains', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'autocomplete',
                    'value' => 'no',
                ],
            ])
            ->add([
                'name' => 'autofillers',
                'type' => AdvancedResourceTemplateElement\OptionalSelect::class,
                'options' => [
                    'label' => 'Autofillers', // @translate
                    'value_options' => $this->autofillers,
                    'use_hidden_element' => true,
                ],
                'attributes' => [
                    'id' => 'autofillers',
                    'multiple' => true,
                    'class' => 'chosen-select',
                    'data-placeholder' => count($this->autofillers)
                        ? $this->getTranslator()->translate('Select autofillers…') // @translate
                        : $this->getTranslator()->translate('No configured autofiller.'), // @translate
                ],
            ]);
    }

    public function setAutofillers(array $autofillers)
    {
        $this->autofillers = $autofillers;
        return $this;
    }
}
