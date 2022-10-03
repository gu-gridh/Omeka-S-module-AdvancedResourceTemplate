<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\Form\Element;

use Omeka\Api\Exception\NotFoundException;
use Omeka\Form\Element\AbstractVocabularyMemberSelect;

class PropertySelect extends AbstractVocabularyMemberSelect
{
    public function getResourceName()
    {
        return 'properties';
    }

    /**
     * Same as parent, except list all alternate labels and some optimizations.
     *
     * {@inheritDoc}
     * @see \Laminas\Form\Element\Select::getInputSpecification()
     */
    public function getValueOptions()
    {
        $applyTemplates = $this->getOption('apply_templates');
        $applyTemplates = is_array($applyTemplates) ? $applyTemplates : false;
        if (!$applyTemplates) {
            // Use default method.
            return parent::getValueOptions();
        }

        // Get only the properties of the configured resource templates.
        $valueOptions = [];
        $termAsValue = $this->getOption('term_as_value');
        $api = $this->getApiManager();
        $translator = $this->getTranslator();
        foreach ($applyTemplates as $templateId) {
            try {
                /** @var \AdvancedResourceTemplate\Api\Representation\ResourceTemplateRepresentation $template */
                $template = $api->read('resource_templates', $templateId)->getContent();
            } catch (NotFoundException $e) {
                continue;
            }
            foreach ($template->resourceTemplateProperties() as $templateProperty) {
                $property = $templateProperty->property();
                $propertyId = $property->id();
                if (!isset($valueOptions[$propertyId])) {
                    $propertyTerm = $property->term();
                    $valueOptions[$propertyId] = [
                        'label' => $translator->translate($property->label()),
                        'value' => $termAsValue ? $propertyTerm : $propertyId,
                        'alternate_labels' => [],
                        'attributes' => [
                            'data-term' => $propertyTerm,
                            'data-property-id' => $propertyId,
                        ],
                    ];
                }
                // Specific to this module: use all labels of the property.
                $valueOptions[$propertyId]['alternate_labels'] = array_merge(
                    $valueOptions[$propertyId]['alternate_labels'],
                    $templateProperty->alternateLabels()
                );
            }
        }

        // Include alternate labels, if any.
        foreach ($valueOptions as $propertyId => $option) {
            $altLabels = array_unique(array_filter($valueOptions[$propertyId]['alternate_labels']));
            if ($altLabels) {
                $valueOptions[$propertyId]['label'] = sprintf(
                    '%s (%s)',
                    $valueOptions[$propertyId]['label'],
                    implode(', ', $altLabels)
                );
            }
        }

        // Sort options alphabetically.
        usort($valueOptions, function ($a, $b) {
            return strcasecmp($a['label'], $b['label']);
        });
        return $valueOptions;
    }
}
