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
     * @return array
     */
    public function data()
    {
        $data = $this->getServiceLocator()->get('Omeka\EntityManager')
            ->getRepository(ResourceTemplatePropertyData::class)
            ->findOneBy(['resourceTemplateProperty' => $this->templateProperty->getId()]);
        return $data ? $data->getData() : [];
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function dataValue($name, $default = null)
    {
        $data = $this->templateProperty->getData();
        return $data[$name] ?? $default;
    }
}
