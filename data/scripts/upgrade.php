<?php declare(strict_types=1);

namespace AdvancedResourceTemplate;

/**
 * @var Module $this
 * @var \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
 * @var string $newVersion
 * @var string $oldVersion
 *
 * @var \Doctrine\DBAL\Connection $connection
 * @var \Doctrine\ORM\EntityManager $entityManager
 * @var \Omeka\Api\Manager $api
 */
$services = $serviceLocator;
$settings = $services->get('Omeka\Settings');
$config = require dirname(__DIR__, 2) . '/config/module.config.php';
$connection = $services->get('Omeka\Connection');
$entityManager = $services->get('Omeka\EntityManager');
$plugins = $services->get('ControllerPluginManager');
$api = $plugins->get('api');
$space = strtolower(__NAMESPACE__);

if (version_compare($oldVersion, '3.3.3.3', '<')) {
    $this->execSqlFromFile($this->modulePath() . '/data/install/schema.sql');
}

if (version_compare($oldVersion, '3.3.4', '<')) {
    $sql = <<<'SQL'
ALTER TABLE `resource_template_property_data`
DROP INDEX UNIQ_B133BBAA2A6B767B,
ADD INDEX IDX_B133BBAA2A6B767B (`resource_template_property_id`);
SQL;
    $connection->exec($sql);
}
