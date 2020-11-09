<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\Api\Representation;

use AdvancedResourceTemplate\Entity\ResourceTemplatePropertyData;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Omeka\Api\Representation\AbstractRepresentation;
use Omeka\Api\Representation\PropertyRepresentation;

class ResourceTemplatePropertyDataRepresentation extends AbstractRepresentation
{
    /**
     * @var ResourceTemplatePropertyData
     */
    protected $resource;

    public function __construct(ResourceTemplatePropertyData $rtpData, ServiceLocatorInterface $serviceLocator)
    {
        $this->setServiceLocator($serviceLocator);
        $this->resource = $rtpData;
    }

    public function jsonSerialize()
    {
        // This is not a json-ld resource, so no need to encapsulate it.
        return $this->data();
    }

    public function data(): array
    {
        return $this->resource->getData();
    }

    public function dataValue(string $name, $default = null)
    {
        return $this->resource->getData()[$name] ?? $default;
    }

    public function template(): ResourceTemplateRepresentation
    {
        return $this->getAdapter('resource_templates')
            ->getRepresentation($this->resource->getResourceTemplate());
    }

    public function resourceTemplateProperty(): ResourceTemplatePropertyRepresentation
    {
        $resTemProp = $this->resource->getResourceTemplateProperty();
        return new ResourceTemplatePropertyRepresentation($resTemProp, $this->getServiceLocator());
    }

    public function property(): PropertyRepresentation
    {
        return $this->resourceTemplateProperty()->property();
    }

    public function alternateLabel(): ?string
    {
        return $this->dataValue('o:alternate_label');
    }

    public function alternateComment(): ?string
    {
        return $this->dataValue('o:alternate_comment');
    }

    public function dataTypes(): array
    {
        return $this->dataValue('o:data_type', []);
    }

    /**
     * @return array List of data type names and labels.
     */
    public function dataTypeLabels(): array
    {
        $result = [];
        $dataTypeManager = $this->getServiceLocator()->get('Omeka\DataTypeManager');
        foreach ($this->dataTypes() as $dataType) {
            $result[] = [
                'name' => $dataType,
                'label' => $dataTypeManager->get($dataType)->getLabel(),
            ];
        }
        return $result;
    }

    public function isRequired(): bool
    {
        return (bool) $this->dataValue('o:is_required');
    }

    public function isPrivate(): bool
    {
        return (bool) $this->dataValue('o:is_private');
    }
}
