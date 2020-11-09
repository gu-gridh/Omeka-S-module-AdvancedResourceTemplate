<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\Api\Adapter;

use AdvancedResourceTemplate\Entity\ResourceTemplateData;
use Omeka\Api\Adapter\ResourceTemplateAdapter;
use Omeka\Api\Request;
use Omeka\Entity\ResourceTemplate;

class ResourceTemplateDataHydrator
{
    /**
     * Hydrate data of a resource template in a request.
     *
     * @param Request $request
     * @param ResourceTemplate $entity
     * @param ResourceTemplateAdapter $adapter
     */
    public function hydrate(Request $request, ResourceTemplate $entity, ResourceTemplateAdapter $adapter): void
    {
        $entityManager = $adapter->getEntityManager();
        $id = $entity->getId();
        if ($id) {
            $rtData = $entityManager
                ->getRepository(ResourceTemplateData::class)
                ->findOneBy(['resourceTemplate' => $id]);
        }
        if (empty($rtData)) {
            $rtData = new ResourceTemplateData();
            $rtData->setResourceTemplate($entity);
        }
        $data = $request->getValue('o:data', []) ?: [];
        $rtData->setData($data);
        $entityManager->persist($rtData);
    }
}
