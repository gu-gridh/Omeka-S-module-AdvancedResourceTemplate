<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\Service\Autofiller;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class AutofillerFactory implements FactoryInterface
{
    /**
     * This factory allows to prepare the autofillers that extends AbstractAutofiller.
     *
     * {@inheritDoc}
     * @see \Laminas\ServiceManager\Factory\FactoryInterface::__invoke()
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new $requestedName($services, $options);
    }
}
