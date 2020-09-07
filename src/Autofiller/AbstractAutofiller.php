<?php

namespace AdvancedResourceTemplate\Autofiller;

use Zend\ServiceManager\ServiceLocatorInterface;

abstract class AbstractAutofiller implements AutofillerInterface
{
    /**
     * @var string
     */
    protected $label;

    /**
     * @var array
     */
    protected $mapping;

    /**
     * @var ServiceLocatorInterface
     */
    protected $services;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var \Zend\Http\Client
     */
    protected $httpClient;

    /**
     * @var \AdvancedResourceTemplate\Mvc\Controller\Plugin\Mapper
     */
    protected $mapper;

    public function __construct(ServiceLocatorInterface $services, array $options = null)
    {
        $this->services = $services;
        $this->options = $options ?: [];
        $this->httpClient = $services->get('Omeka\HttpClient');
        $pluginManager = $services->get('ControllerPluginManager');
        $this->mapper = $pluginManager->get('mapper');
    }

    public function getLabel()
    {
        return empty($this->options['sub'])
            ? $this->label
            : sprintf('%1$s: %2$s', $this->label, $this->options['sub']); // @translate
    }

    public function setMapping(array $mapping)
    {
        $this->mapping = $mapping;
        return $this;
    }

    abstract public function getResults($query, $lang = null);
}
