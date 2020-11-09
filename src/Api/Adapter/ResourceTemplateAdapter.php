<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\Api\Adapter;

use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Entity\ResourceTemplateProperty;
use Omeka\Stdlib\ErrorStore;

class ResourceTemplateAdapter extends \Omeka\Api\Adapter\ResourceTemplateAdapter
{
    public function getRepresentationClass()
    {
        return \AdvancedResourceTemplate\Api\Representation\ResourceTemplateRepresentation::class;
    }

    public function hydrate(Request $request, EntityInterface $entity, ErrorStore $errorStore): void
    {
        // It's not possible to use parent::hydrate(), because the template
        // properties are not available.

        /** @var \Omeka\Entity\ResourceTemplate $entity */
        $data = $request->getContent();
        $this->hydrateOwner($request, $entity);
        $this->hydrateResourceClass($request, $entity);

        if ($this->shouldHydrate($request, 'o:label')) {
            $entity->setLabel($request->getValue('o:label'));
        }

        if ($this->shouldHydrate($request, 'o:title_property')) {
            $titleProperty = $request->getValue('o:title_property');
            if (isset($titleProperty['o:id']) && is_numeric($titleProperty['o:id'])) {
                $titleProperty = $this->getAdapter('properties')->findEntity($titleProperty['o:id']);
            } else {
                $titleProperty = null;
            }
            $entity->setTitleProperty($titleProperty);
        }

        if ($this->shouldHydrate($request, 'o:description_property')) {
            $descriptionProperty = $request->getValue('o:description_property');
            if (isset($descriptionProperty['o:id']) && is_numeric($descriptionProperty['o:id'])) {
                $descriptionProperty = $this->getAdapter('properties')->findEntity($descriptionProperty['o:id']);
            } else {
                $descriptionProperty = null;
            }
            $entity->setDescriptionProperty($descriptionProperty);
        }

        if ($this->shouldHydrate($request, 'o:data')) {
            (new ResourceTemplateDataHydrator)->hydrate($request, $entity, $this);
        }

        if ($this->shouldHydrate($request, 'o:resource_template_property')
            && isset($data['o:resource_template_property'])
            && is_array($data['o:resource_template_property'])
        ) {

            // Get a resource template property by property ID.
            $getResTemProp = function ($propertyId, $resTemProps) {
                foreach ($resTemProps as $resTemProp) {
                    if ($propertyId == $resTemProp->getProperty()->getId()) {
                        return $resTemProp;
                    }
                }
                return null;
            };

            $propertyAdapter = $this->getAdapter('properties');
            $resTemProps = $entity->getResourceTemplateProperties();
            $resTemPropsToRetain = [];
            // Position is one-based.
            $position = 1;
            foreach ($data['o:resource_template_property'] as $resTemPropData) {
                if (empty($resTemPropData['o:property']['o:id'])) {
                    continue; // skip when no property ID
                }
                $propertyId = (int) $resTemPropData['o:property']['o:id'];
                if (isset($resTemPropsToRetain[$propertyId])) {
                    continue;
                }

                $altLabel = null;
                if (isset($resTemPropData['o:alternate_label'])
                    && '' !== trim($resTemPropData['o:alternate_label'])
                ) {
                    $altLabel = $resTemPropData['o:alternate_label'];
                }
                $altComment = null;
                if (isset($resTemPropData['o:alternate_comment'])
                    && '' !== trim($resTemPropData['o:alternate_comment'])
                ) {
                    $altComment = $resTemPropData['o:alternate_comment'];
                }
                $dataTypes = null;
                if (!empty($resTemPropData['o:data_type'])) {
                    $dataTypes = array_values(array_unique(array_filter(array_map('trim', $resTemPropData['o:data_type']))));
                }
                $isRequired = false;
                if (isset($resTemPropData['o:is_required'])) {
                    $isRequired = (bool) $resTemPropData['o:is_required'];
                }
                $isPrivate = false;
                if (isset($resTemPropData['o:is_private'])) {
                    $isPrivate = (bool) $resTemPropData['o:is_private'];
                }

                // Check whether a passed property is already assigned to this
                // resource template.
                $resTemProp = $getResTemProp($propertyId, $resTemProps);
                if (!$resTemProp) {
                    // It is not assigned. Add a new resource template property.
                    // No need to explicitly add it to the collection since it
                    // is added implicitly when setting the resource template.
                    $property = $propertyAdapter->findEntity($propertyId);
                    $resTemProp = new ResourceTemplateProperty();
                    $resTemProp->setResourceTemplate($entity);
                    $resTemProp->setProperty($property);
                    $entity->getResourceTemplateProperties()->add($resTemProp);
                }
                $resTemProp->setAlternateLabel($altLabel);
                $resTemProp->setAlternateComment($altComment);
                $resTemProp->setDataType($dataTypes);
                $resTemProp->setIsRequired($isRequired);
                $resTemProp->setIsPrivate($isPrivate);
                // Set the position of the property to its intrinsic order
                // within the passed array.
                $resTemProp->setPosition($position++);
                $resTemPropsToRetain[$propertyId] = $resTemProp;
            }

            // Remove resource template properties that were not included in the
            // passed data.
            foreach ($resTemProps as $resTemPropId => $resTemProp) {
                if (!in_array($resTemProp, $resTemPropsToRetain)) {
                    $resTemProps->remove($resTemPropId);
                }
            }

            $rtpdHydrator = new ResourceTemplatePropertyDataHydrator;
            $rtpdHydrator
                ->setResourceTemplateProperties($resTemPropsToRetain)
                ->hydrate($request, $entity, $this);
        }
    }
}
