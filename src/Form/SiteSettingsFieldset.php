<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\Form;

use Common\Form\Element as CommonElement;
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

            ->add([
                'name' => 'advancedresourcetemplate_block_metadata_fields',
                'type' => CommonElement\GroupTextarea::class,
                'options' => [
                    'element_group' => 'resources',
                    'label' => 'Metadata for resource block Metadata', // @translate
                    'info' => 'This option may be used to display a short record. Each line is the term and the optional alternatif label. A specific label can be set on each line, separated with a "=". A division to group a list of properties may be added with "# div-class" and an optional label separated with a "=".', // @translate
                    'as_key_value' => true,
                ],
                'attributes' => [
                    'id' => 'advancedresourcetemplate_block_metadata_fields',
                    'rows' => 5,
                    'placeholder' => '# values-title
dcterms:title

# values-type
dcterms:type

# values-creator
dcterms:creator

# values-date
dcterms:date
dcterms:created
dcterms:issued

# values-rights
dcterms:rights
dcterms:license

# values-subject = Keywords
dcterms:subject
dcterms:coverage
dcterms:spatial
dcterms:temporal
',
                ],
            ])
        ;
    }
}
