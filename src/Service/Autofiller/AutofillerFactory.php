<?php

namespace AdvancedResourceTemplate\Service\Autofiller;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class AutofillerFactory implements FactoryInterface
{
    /**
     * This factory allows to prepare the autofillers that extends AbstractAutofiller.
     *
     * {@inheritDoc}
     * @see \Zend\ServiceManager\Factory\FactoryInterface::__invoke()
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new $requestedName($services, $options);
    }
}
