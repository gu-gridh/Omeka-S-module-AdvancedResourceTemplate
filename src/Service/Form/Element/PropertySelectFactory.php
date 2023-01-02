<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\Service\Form\Element;

use AdvancedResourceTemplate\Form\Element\PropertySelect;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class PropertySelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $element = new PropertySelect(null, $options ?? []);
        $element->setApiManager($services->get('Omeka\ApiManager'));
        $element->setEventManager($services->get('EventManager'));
        $element->setTranslator($services->get('MvcTranslator'));
        return $element;
    }
}
