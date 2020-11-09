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
     * A template property may have multiple data according to data types.
     *
     * @return ResourceTemplatePropertyDataRepresentation[]
     */
    public function data(): array
    {
        $list = [];
        $services = $this->getServiceLocator();
        $rtpDatas = $services->get('Omeka\EntityManager')
            ->getRepository(ResourceTemplatePropertyData::class)
            ->findBy(['resourceTemplateProperty' => $this->templateProperty]);
        foreach ($rtpDatas as $rtpData) {
            $list[] = new ResourceTemplatePropertyDataRepresentation($rtpData, $services);
        }
        return $list;
    }
}
