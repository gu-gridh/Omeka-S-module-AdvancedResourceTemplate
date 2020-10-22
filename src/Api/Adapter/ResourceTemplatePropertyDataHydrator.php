<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\Api\Adapter;

use AdvancedResourceTemplate\Entity\ResourceTemplatePropertyData;
use Omeka\Api\Adapter\ResourceTemplateAdapter;
use Omeka\Api\Request;
use Omeka\Entity\ResourceTemplate;

class ResourceTemplatePropertyDataHydrator
{
    /**
     * @var \Omeka\Entity\ResourceTemplateProperty[]
     */
    protected $resourceTemplateProperties;

    /**
     * Hydrate data of a resource template in a request.
     *
     * @todo Simplify.
     *
     * @param Request $request
     * @param ResourceTemplate $entity
     * @param ResourceTemplateAdapter $adapter
     */
    public function hydrate(Request $request, ResourceTemplate $entity, ResourceTemplateAdapter $adapter)
    {
        if (is_null($this->resourceTemplateProperties)) {
            $this->resourceTemplateProperties = $entity->getResourceTemplateProperties()->toArray();
        }

        $entityManager = $adapter->getEntityManager();
        $rtpDataRepository = $entityManager
            ->getRepository(ResourceTemplatePropertyData::class);

        // For simplicity, re-use existing template properties.
        $id = $entity->getId();
        $existings = $id ? $rtpDataRepository->findBy(['resourceTemplate' => $entity]) : [];

        // See \Omeka\Api\Adapter\ResourceTemplateAdapter
        if (count($this->resourceTemplateProperties)) {
            // To avoid a flush and issues with remove/persist, get templates
            // properties by propertiies, that are unique.
            $list = [];
            foreach ($this->resourceTemplateProperties as $rtp) {
                $list[$rtp->getProperty()->getId()] = $rtp;
            }
            $this->resourceTemplateProperties = $list;
            $list = [];
            foreach ($existings as $rtpData) {
                $list[$rtpData->getResourceTemplateProperty()->getProperty()->getId()] = $rtpData;
            }
            $existings = $list;
            unset($list);

            $data = $request->getContent();
            foreach ($data['o:resource_template_property'] as $resTemPropData) {
                if (empty($resTemPropData['o:property']['o:id'])) {
                    // Skip when no property ID.
                    continue;
                }
                $propertyId = $resTemPropData['o:property']['o:id'];
                if (!isset($this->resourceTemplateProperties[$propertyId])) {
                    // The existing template property data will be removed.
                    continue;
                }

                if (isset($existings[$propertyId])) {
                    $rtpData = $existings[$propertyId];
                    unset($existings[$propertyId]);
                } else {
                    $rtpData = new ResourceTemplatePropertyData();
                }
                $rtpData
                    ->setResourceTemplateProperty($this->resourceTemplateProperties[$propertyId])
                    ->setData($resTemPropData['o:data'] ?? []);
                $entityManager->persist($rtpData);
            }
        }

        // Remove remaining template properties.
        foreach ($existings as $rtpData) {
            $entityManager->remove($rtpData);
        }
    }

    public function setResourceTemplateProperties(array $resourceTemplateProperties)
    {
        $this->resourceTemplateProperties = $resourceTemplateProperties;
        return $this;
    }
}
