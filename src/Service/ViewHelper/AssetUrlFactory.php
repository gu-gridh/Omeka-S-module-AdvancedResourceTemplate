<?php

namespace AdvancedResourceTemplate\Service\ViewHelper;

use Omeka\Module\Manager as ModuleManager;
use Omeka\View\Helper\AssetUrl;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

/**
 * Service factory for the assetUrl view helper.
 */
class AssetUrlFactory implements FactoryInterface
{
    /**
     * Create and return the assetUrl view helper
     *
     * @return AssetUrl
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $currentTheme = $serviceLocator->get('Omeka\Site\ThemeManager')->getCurrentTheme();
        $activeModules = $serviceLocator->get('Omeka\ModuleManager')
            ->getModulesByState(ModuleManager::STATE_ACTIVE);

        $assetConfig = $serviceLocator->get('Config')['assets'];
        if ($assetConfig['use_externals']) {
            $externals = $assetConfig['externals'];
        } else {
            $externals = [];
        }

        // Bypass the core js.
        // TODO To be removed with #omeka/omeka-s/1623.
        $externals['Omeka']['js/resource-form.js'] = $serviceLocator->get('ViewHelperManager')->get('BasePath')->__invoke()
            . '/modules/AdvancedResourceTemplate/asset/js/resource-form.js';

        $helper = new AssetUrl($currentTheme, $activeModules, $externals);
        return $helper;
    }
}
