<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\Service\Form;

use AdvancedResourceTemplate\Form\ResourceTemplateDataFieldset;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ResourceTemplateDataFieldsetFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $autofillers = [];
        foreach ($services->get('Omeka\Settings')->get('advancedresourcetemplate_autofillers', []) as $key => $value) {
            $autofillers[$key] = $value['label'] ?: $key;
        }
        $form = new ResourceTemplateDataFieldset(null, $options);
        return $form
            ->setTranslator($services->get('MvcTranslator'))
            ->setAutofillers($autofillers);
    }
}
