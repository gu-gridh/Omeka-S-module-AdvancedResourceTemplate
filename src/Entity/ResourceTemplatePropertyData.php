<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\Entity;

use Omeka\Entity\AbstractEntity;
use Omeka\Entity\ResourceTemplate;
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
     * The template is not required but this denormalization simplifies search.
     * Of course, the resource template must be the property one, so there is
     * no setter.
     *
     * @var ResourceTemplate
     * @ManyToOne(
     *     targetEntity=\Omeka\Entity\ResourceTemplate::class,
     *     fetch="EXTRA_LAZY"
     * )
     * @JoinColumn(
     *     onDelete="CASCADE",
     *     nullable=false
     * )
     */
    protected $resourceTemplate;

    /**
     * @var ResourceTemplateProperty
     * @OneToOne(
     *     targetEntity=\Omeka\Entity\ResourceTemplateProperty::class,
     *     fetch="EXTRA_LAZY"
     * )
     * @JoinColumn(
     *     onDelete="CASCADE",
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
     * @return \Omeka\Entity\ResourceTemplate
     */
    public function getResourceTemplate(): ResourceTemplate
    {
        return $this->resourceTemplate;
    }

    /**
     * @param ResourceTemplateProperty $resourceTemplateProperty
     * @return self
     */
    public function setResourceTemplateProperty(ResourceTemplateProperty $resourceTemplateProperty)
    {
        $this->resourceTemplate = $resourceTemplateProperty->getResourceTemplate();
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
