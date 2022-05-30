<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\Service\Form\Element;

use AdvancedResourceTemplate\Form\Element\OptionalRoleSelect;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class OptionalRoleSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $acl = $services->get('Omeka\Acl');
        $roles = $acl->getRoleLabels();

        $element = new OptionalRoleSelect;
        return $element
            ->setValueOptions($roles)
            ->setEmptyOption('Select role…'); // @translate
    }
}
