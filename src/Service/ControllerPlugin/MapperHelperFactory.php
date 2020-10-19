<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\Service\ControllerPlugin;

use AdvancedResourceTemplate\Mvc\Controller\Plugin\MapperHelper;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class MapperHelperFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new MapperHelper(
            $services->get('Omeka\Connection'),
            $services->get('Omeka\DataTypeManager')
        );
    }
}
