<?php

namespace AdvancedResourceTemplate\Service\Autofiller;

use AdvancedResourceTemplate\Autofiller\AutofillerPluginManager;
use Interop\Container\ContainerInterface;
use Omeka\Service\Exception;
use Laminas\ServiceManager\Factory\FactoryInterface;

class AutofillerPluginManagerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $config = $services->get('Config');
        if (!isset($config['autofillers'])) {
            throw new Exception\ConfigException('Missing autofiller configuration.'); // @translate
        }
        return new AutofillerPluginManager($services, $config['autofillers']);
    }
}
