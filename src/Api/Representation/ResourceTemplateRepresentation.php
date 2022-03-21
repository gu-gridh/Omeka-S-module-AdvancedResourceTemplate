<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\Api\Representation;

use AdvancedResourceTemplate\Entity\ResourceTemplateData;

class ResourceTemplateRepresentation extends \Omeka\Api\Representation\ResourceTemplateRepresentation
{
    /**
     * Authorize the current user.
     *
     * Requests access to the entity and to the corresponding adapter. If the
     * current user does not have access to the adapter, we can assume that it
     * does not have access to the entity.
     *
     * @param string $privilege
     * @return bool
     */
    public function userIsAllowed($privilege)
    {
        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        return $acl->userIsAllowed(\Omeka\Api\Adapter\ResourceTemplateAdapter::class, $privilege)
            && $acl->userIsAllowed($this->resource, $privilege);
    }

    public function getJsonLd()
    {
        $jsonLd = parent::getJsonLd();
        $jsonLd['o:data'] = $this->data();
        // Keep properties at last.
        $rtps = $jsonLd['o:resource_template_property'];
        unset($jsonLd['o:resource_template_property']);
        $jsonLd['o:resource_template_property'] = $rtps;
        return $jsonLd;
    }

    /**
     * @return array
     */
    public function data(): array
    {
        // TODO Currently, static data return are always the same, so use id.
        static $datas = [];
        $id = $this->id();
        if (!isset($datas[$id])) {
            $rtd = $this->getServiceLocator()->get('Omeka\EntityManager')
                ->getRepository(ResourceTemplateData::class)
                ->findOneBy(['resourceTemplate' => $this->id()]);
            $datas[$id] = $rtd ? $rtd->getData() : [];
        }
        return $datas[$id];
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function dataValue($name, $default = null)
    {
        $data = $this->data();
        return $data[$name] ?? $default;
    }

    /**
     * @return \AdvancedResourceTemplate\Api\Representation\ResourceTemplatePropertyRepresentation[]
     *
     * {@inheritDoc}
     * @see \Omeka\Api\Representation\ResourceTemplateRepresentation::resourceTemplateProperties()
     */
    public function resourceTemplateProperties()
    {
        $resTemProps = [];
        $services = $this->getServiceLocator();
        foreach ($this->resource->getResourceTemplateProperties() as $resTemProp) {
            $resTemProps[] = new ResourceTemplatePropertyRepresentation($resTemProp, $services);
        }
        return $resTemProps;
    }

    /**
     * @return \AdvancedResourceTemplate\Api\Representation\ResourceTemplatePropertyRepresentation|null
     *
     * {@inheritDoc}
     * @see \Omeka\Api\Representation\ResourceTemplateRepresentation::resourceTemplateProperty()
     */
    public function resourceTemplateProperty($propertyId)
    {
        $resTemProp = $this->resource->getResourceTemplateProperties()->get($propertyId);
        if ($resTemProp) {
            return new ResourceTemplatePropertyRepresentation($resTemProp, $this->getServiceLocator());
        }
        return null;
    }
}
