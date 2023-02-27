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
     * Get a value metadata from the data of the current template.
     */
    public function dataValueMetadata(string $name, ?string $metadata = null, $default = null)
    {
        $data = $this->data();
        if (!isset($data[$name])) {
            return $default;
        }
        $meta = $data[$name];
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
                    $list[] = array_map('trim', explode('=', $keyValue, 2)) + ['', ''];
                }
                return $list;
            case 'params_key_value':
            default:
                $params = array_filter(array_map('trim', explode("\n", trim($meta))), 'strlen');
                $list = [];
                foreach ($params as $keyValue) {
                    [$key, $value] = mb_strpos($keyValue, '=') === false
                        ? [trim($keyValue), '']
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

    /**
     * @return \AdvancedResourceTemplate\Api\Representation\ResourceTemplatePropertyRepresentation[]
     *
     * {@inheritDoc}
     * @see \Omeka\Api\Representation\ResourceTemplateRepresentation::resourceTemplateProperties()
     */
    public function resourceTemplateProperties()
    {
        $resTemProps = [];
        // Get services one time.
        $services = $this->getServiceLocator();
        foreach ($this->resource->getResourceTemplateProperties() as $resTemProp) {
            $resTemProps[] = new ResourceTemplatePropertyRepresentation($resTemProp, $services);
        }
        return $resTemProps;
    }

    /**
     * @return \AdvancedResourceTemplate\Api\Representation\ResourceTemplatePropertyRepresentation|null
     *
     * @todo To be removed.
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
