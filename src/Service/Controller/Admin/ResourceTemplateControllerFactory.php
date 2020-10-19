<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\Service\Controller\Admin;

use AdvancedResourceTemplate\Controller\Admin\ResourceTemplateController;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ResourceTemplateControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ResourceTemplateController($services->get('Omeka\DataTypeManager'));
    }
}
