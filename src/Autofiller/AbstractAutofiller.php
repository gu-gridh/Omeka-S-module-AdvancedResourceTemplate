<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\Autofiller;

use Laminas\ServiceManager\ServiceLocatorInterface;

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
     * @var \Laminas\Http\Client
     */
    protected $httpClient;

    /**
     * @var \AdvancedResourceTemplate\Mvc\Controller\Plugin\ArtMapper
     */
    protected $mapper;

    public function __construct(ServiceLocatorInterface $services, array $options = null)
    {
        $this->services = $services;
        $this->options = $options ?: [];
        $this->httpClient = $services->get('Omeka\HttpClient');
        $pluginManager = $services->get('ControllerPluginManager');
        $this->mapper = $pluginManager->get('artMapper');
    }

    public function getLabel()
    {
        if (!empty($this->options['label'])) {
            return $this->options['label'];
        }
        if (empty($this->options['sub'])) {
            return $this->label;
        }
        return sprintf(
            '%1$s: %2$s', // @translate
            $this->label,
            is_array($this->options['sub']) && $this->options['sub']['label']
                ? $this->options['sub']['label']
                : $this->options['sub']
        );
    }

    public function setMapping(array $mapping)
    {
        $this->mapping = $mapping;
        return $this;
    }

    abstract public function getResults($query, $lang = null);
}
