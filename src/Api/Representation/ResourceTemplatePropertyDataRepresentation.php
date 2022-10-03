<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\Api\Representation;

use AdvancedResourceTemplate\Entity\ResourceTemplatePropertyData;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Omeka\Api\Representation\AbstractRepresentation;
use Omeka\Api\Representation\PropertyRepresentation;

/**
 * In order to be compatible with core, it copies original resource template
 * property too.
 */
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

    public function dataValueMetadata(string $name, ?string $metadata = null, $default = null)
    {
        $dt = $this->resource->getData();
        if (!isset($dt[$name])) {
            return $default;
        }
        $meta = $dt[$name];
        switch ($metadata) {
            case 'params':
            case 'params_raw':
                return $meta;
            case 'params_json':
            case 'params_json_array':
                return @json_decode($meta, true) ?: [];
            case 'params_json_object':
                return @json_decode($meta) ?: (object) [];
            case 'params_key_value_array':
                $params = array_map('trim', explode("\n", trim($meta)));
                $list = [];
                foreach ($params as $keyValue) {
                    $list[] = array_map('trim', explode('=', $keyValue, 2));
                }
                return $list;
            case 'params_key_value':
            default:
                $params = array_filter(array_map('trim', explode("\n", trim($meta))), 'strlen');
                $list = [];
                foreach ($params as $keyValue) {
                    list($key, $value) = strpos($keyValue, '=') === false
                        ? [$keyValue, null]
                        : array_map('trim', explode('=', $keyValue, 2));
                    if ($key !== '') {
                        $list[$key] = $value;
                    }
                }
                if ($metadata === 'params_key_value') {
                    return $list;
                }
                return $list[$metadata] ?? null;
        }
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

    public function dataType(): ?string
    {
        $datatypes = $this->dataValue('o:data_type', []);
        return $datatypes ? reset($datatypes) : null;
    }

    public function dataTypes(): array
    {
        return $this->dataValue('o:data_type', []);
    }

    /**
     * @return array List of data type names and default labels.
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
