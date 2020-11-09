<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\Service\ViewHelper;

use AdvancedResourceTemplate\View\Helper\DataType;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * Service factory for the dataType view helper.
 */
class DataTypeFactory implements FactoryInterface
{
    /**
     * Create and return the dataType view helper
     *
     * @return DataType
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new DataType(
            $services->get('Omeka\DataTypeManager'),
            $services->get('FormElementManager')
        );
    }
}
