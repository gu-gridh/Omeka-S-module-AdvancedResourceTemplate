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
}
