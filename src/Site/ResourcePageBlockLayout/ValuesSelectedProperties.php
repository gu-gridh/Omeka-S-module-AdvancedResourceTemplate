<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\Site\ResourcePageBlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Site\ResourcePageBlockLayout\ResourcePageBlockLayoutInterface;

/**
 * Display values of selected properties of the resource.
 */
class ValuesSelectedProperties implements ResourcePageBlockLayoutInterface
{
    public function getLabel() : string
    {
        return 'Values of selected properties'; // @translate
    }

    public function getCompatibleResourceNames() : array
    {
        return [
            'items',
            'media',
            'item_sets',
        ];
    }

    public function render(PhpRenderer $view, AbstractResourceEntityRepresentation $resource): string
    {
        $plugins = $view->getHelperPluginManager();
        $siteSetting = $plugins->get('siteSetting');
        $selectedProperties = $siteSetting('advancedresourcetemplate_selected_properties');
        if (!$selectedProperties) {
            return '';
        }

        return $view->partial('common/resource-page-block-layout/resource-values-selected-properties', [
            'resource' => $resource,
            'selectedPropertiesByGroup' => $selectedProperties,
        ]);
    }
}
