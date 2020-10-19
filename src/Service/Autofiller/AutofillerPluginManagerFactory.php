<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\Service\Autofiller;

use AdvancedResourceTemplate\Autofiller\AutofillerPluginManager;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Omeka\Service\Exception;

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
