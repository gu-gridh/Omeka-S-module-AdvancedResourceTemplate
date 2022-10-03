<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\Api\Representation;

use AdvancedResourceTemplate\Entity\ResourceTemplatePropertyData;

class ResourceTemplatePropertyRepresentation extends \Omeka\Api\Representation\ResourceTemplatePropertyRepresentation
{
    public function jsonSerialize()
    {
        $json = parent::jsonSerialize();
        $json['o:data'] = $this->data();
        return $json;
    }

    public function alternateLabels(): array
    {
        $result = [];
        foreach ($this->data() ?: [$this] as $rtpData) {
            $result[] = $rtpData->alternateLabel();
        }
        return $result;
    }

    public function alternateComments(): array
    {
        $result = [];
        foreach ($this->data() ?: [$this] as $rtpData) {
            $result[] = $rtpData->alternateComment();
        }
        return $result;
    }

    /**
     * Get all the template data associated to the current template property.
     *
     * Note: A template property may have multiple data according to data types.
     *
     * @return ResourceTemplatePropertyDataRepresentation[]|ResourceTemplatePropertyDataRepresentation|null
     */
    public function data(?int $index = null)
    {
        // TODO Currently, static data returns are always the same, so use id.
        static $lists = [];
        $id = $this->templateProperty->getId();
        if (!isset($lists[$id])) {
            $lists[$id] = [];
            $services = $this->getServiceLocator();
            $rtpDatas = $services->get('Omeka\EntityManager')
                ->getRepository(ResourceTemplatePropertyData::class)
                ->findBy(['resourceTemplateProperty' => $this->templateProperty]);
            foreach ($rtpDatas as $rtpData) {
                $lists[$id][] = new ResourceTemplatePropertyDataRepresentation($rtpData, $services);
            }
        }
        return is_null($index)
            ? $lists[$id]
            : ($lists[$id][$index] ?? null);
    }

    /**
     * Get the main data of the current template property.
     *
     * @return ResourceTemplatePropertyDataRepresentation[]|ResourceTemplatePropertyDataRepresentation
     */
    public function mainData(): ?ResourceTemplatePropertyDataRepresentation
    {
        return $this->data(0);
    }

    /**
     * Get all values from the main data of the current template property.
     */
    public function mainDataValues(): array
    {
        $dt = $this->data(0);
        return $dt ? $dt->data() : [];
    }

    /**
     * Get a value from the main data of the current template property.
     */
    public function mainDataValue(string $name, $default = null)
    {
        $dt = $this->data(0);
        return is_null($dt)
            ? $default
            : $dt->dataValue($name, $default);
    }

    /**
     * Get a value metadata from the main data of the current template property.
     */
    public function mainDataValueMetadata(string $name, ?string $metadata = null, $default = null)
    {
        $dt = $this->data(0);
        return is_null($dt)
            ? $default
            : $dt->dataValueMetadata($name, $metadata, $default);
    }
}
