<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\Entity;

use Omeka\Entity\AbstractEntity;
use Omeka\Entity\ResourceTemplateProperty;

/**
 * @Entity
 * @Table(
 *     name="resource_template_property_data"
 * )
 */
class ResourceTemplatePropertyData extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @var ResourceTemplateProperty
     * @OneToOne(
     *     targetEntity=\Omeka\Entity\ResourceTemplateProperty::class,
     *     fetch="EXTRA_LAZY"
     * )
     * @JoinColumn(
     *     nullable=false
     * )
     */
    protected $resourceTemplateProperty;

    /**
     * @Column(
     *     type="json_array",
     *     nullable=false
     * )
     */
    protected $data;

    public function getId()
    {
        return $this->id;
    }

    /**
     * @param ResourceTemplateProperty $resourceTemplateProperty
     * @return self
     */
    public function setResourceTemplateProperty(ResourceTemplateProperty $resourceTemplateProperty)
    {
        $this->resourceTemplateProperty = $resourceTemplateProperty;
        return $this;
    }

    /**
     * @return \Omeka\Entity\ResourceTemplateProperty
     */
    public function getResourceTemplateProperty(): ResourceTemplateProperty
    {
        return $this->resourceTemplateProperty;
    }

    /**
     * @param array $data
     * @return self
     */
    public function setData(array $data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }
}
