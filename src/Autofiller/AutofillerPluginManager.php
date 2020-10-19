<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\Autofiller;

use Omeka\ServiceManager\AbstractPluginManager;

class AutofillerPluginManager extends AbstractPluginManager
{
    protected $instanceOf = AutofillerInterface::class;
}
