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
// $settings = $services->get('Omeka\Settings');
// $config = require dirname(__DIR__, 2) . '/config/module.config.php';
$connection = $services->get('Omeka\Connection');
// $entityManager = $services->get('Omeka\EntityManager');
// $plugins = $services->get('ControllerPluginManager');
// $api = $plugins->get('api');

if (version_compare((string) $oldVersion, '3.3.3.3', '<')) {
    $this->execSqlFromFile($this->modulePath() . '/data/install/schema.sql');
}

if (version_compare((string) $oldVersion, '3.3.4', '<')) {
    $sql = <<<'SQL'
ALTER TABLE `resource_template_property_data`
DROP INDEX UNIQ_B133BBAA2A6B767B,
ADD INDEX IDX_B133BBAA2A6B767B (`resource_template_property_id`);
SQL;
    $connection->executeStatement($sql);
}

if (version_compare((string) $oldVersion, '3.3.4.3', '<')) {
    // @link https://www.doctrine-project.org/projects/doctrine-dbal/en/2.6/reference/types.html#array-types
    $sql = <<<'SQL'
ALTER TABLE `resource_template_data`
CHANGE `data` `data` LONGTEXT NOT NULL COMMENT '(DC2Type:json)';
SQL;
    $connection->executeStatement($sql);
    $sql = <<<'SQL'
ALTER TABLE `resource_template_property_data`
CHANGE `data` `data` LONGTEXT NOT NULL COMMENT '(DC2Type:json)';
SQL;
    $connection->executeStatement($sql);
}
