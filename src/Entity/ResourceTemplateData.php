<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\Entity;

use Omeka\Entity\AbstractEntity;
use Omeka\Entity\ResourceTemplate;

/**
 * @Entity
 * @Table(
 *     name="resource_template_data"
 * )
 */
class ResourceTemplateData extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @var ResourceTemplate
     * @OneToOne(
     *     targetEntity=\Omeka\Entity\ResourceTemplate::class,
     *     fetch="EXTRA_LAZY"
     * )
     * @JoinColumn(
     *     nullable=false,
     *     onDelete="CASCADE"
     * )
     */
    protected $resourceTemplate;

    /**
     * @Column(
     *     type="json",
     *     nullable=false
     * )
     */
    protected $data;

    public function getId()
    {
        return $this->id;
    }

    /**
     * @param ResourceTemplate $resourceTemplate
     * @return self
     */
    public function setResourceTemplate(ResourceTemplate $resourceTemplate)
    {
        $this->resourceTemplate = $resourceTemplate;
        return $this;
    }

    /**
     * @return \Omeka\Entity\ResourceTemplate
     */
    public function getResourceTemplate(): ResourceTemplate
    {
        return $this->resourceTemplate;
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
