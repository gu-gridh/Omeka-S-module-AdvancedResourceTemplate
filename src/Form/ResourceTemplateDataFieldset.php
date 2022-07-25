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
            // Force specified classes.
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
                'name' => 'closed_class_list',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Limit to specified classes', // @translate
                    'checked_value' => 'yes',
                ],
                'attributes' => [
                    'id' => 'closed_class_list',
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
                'name' => 'quick_new_resource',
                'type' => Element\Radio::class,
                'options' => [
                    'label' => 'Allow quick creation of a resource', // @translate
                    'value_options' => [
                        'yes' => 'Yes', // @translate
                        'no' => 'No', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'quick_new_resource',
                    'value' => 'yes',
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
                'name' => 'value_suggest_keep_original_label',
                'type' => Element\Radio::class,
                'options' => [
                    'label' => 'Value Suggest: keep original label', // @translate
                    'value_options' => [
                        'no' => 'No', // @translate
                        'yes' => 'Yes', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'value_suggest_keep_original_label',
                    'value' => 'no',
                ],
            ])
            ->add([
                'name' => 'value_suggest_require_uri',
                'type' => Element\Radio::class,
                'options' => [
                    'label' => 'Value Suggest: require uri', // @translate
                    'value_options' => [
                        'no' => 'No', // @translate
                        'yes' => 'Yes', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'value_suggest_require_uri',
                    'value' => 'no',
                ],
            ])
            ->add([
                'name' => 'automatic_values',
                'type' => Element\Textarea::class,
                'options' => [
                    'label' => 'Automatic values (on save)', // @translate
                ],
                'attributes' => [
                    'id' => 'automatic_values',
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
            ])
            ->add([
                'name' => 'settings',
                'type' => Element\Textarea::class,
                'options' => [
                    'label' => 'More settings', // @translate
                    'info' => 'Allow to pass some settings, usually for theme and generally via key-value pairs or json.', // @translate
                ],
                'attributes' => [
                    'id' => 'settings',
                ],
            ]);
    }

    public function setAutofillers(array $autofillers)
    {
        $this->autofillers = $autofillers;
        return $this;
    }
}
