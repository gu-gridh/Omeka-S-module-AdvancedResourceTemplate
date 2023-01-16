<?php declare(strict_types=1);

namespace AdvancedResourceTemplate;

use Omeka\Stdlib\Message;

/**
 * @var Module $this
 * @var \Laminas\ServiceManager\ServiceLocatorInterface $services
 * @var string $newVersion
 * @var string $oldVersion
 *
 * @var \Doctrine\DBAL\Connection $connection
 * @var \Doctrine\ORM\EntityManager $entityManager
 * @var \Omeka\Api\Manager $api
 * @var \Omeka\Mvc\Controller\Plugin\Messenger $messenger
 */
$plugins = $services->get('ControllerPluginManager');
$api = $plugins->get('api');
$config = require dirname(__DIR__, 2) . '/config/module.config.php';
$settings = $services->get('Omeka\Settings');
$connection = $services->get('Omeka\Connection');
$messenger = $plugins->get('messenger');
// $entityManager = $services->get('Omeka\EntityManager');

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

if (version_compare((string) $oldVersion, '3.3.4.13', '<')) {
    // Add the term name to the list of suggested classes.
    $qb = $connection->createQueryBuilder();
    $qb
        ->select('id', 'data')
        ->from('resource_template_data', 'resource_template_data')
        ->orderBy('resource_template_data.id', 'asc')
        ->where('resource_template_data.data LIKE "%suggested_resource_class_ids%"')
    ;
    $templateDatas = $connection->executeQuery($qb)->fetchAllKeyValue();
    foreach ($templateDatas as $id => $templateData) {
        $templateData = json_decode($templateData, true);
        if (empty($templateData['suggested_resource_class_ids'])) {
            continue;
        }
        $result = [];
        foreach ($api->search('resource_classes', ['id' => array_values($templateData['suggested_resource_class_ids'])], ['initialize' => false])->getContent() as $class) {
            $result[$class->term()] = $class->id();
        }
        $templateData['suggested_resource_class_ids'] = $result;
        $quotedTemplateData = $connection->quote(json_encode($templateData));
        $sql = <<<SQL
UPDATE `resource_template_data`
SET
    `data` = $quotedTemplateData
WHERE `id` = $id;
SQL;
        $connection->executeStatement($sql);
    }

    $message = new Message(
        'New settings were added to the resource templates.' // @translate
    );
    $messenger->addSuccess($message);
    $message = new Message(
        'Values are now validated against settings in all cases, included background or direct api process.' // @translate
    );
    $messenger->addWarning($message);
}

if (version_compare((string) $oldVersion, '3.3.4.14', '<')) {
    // Use "yes" for all simple parameters.
    $qb = $connection->createQueryBuilder();
    $qb
        ->select('id', 'data')
        ->from('resource_template_data', 'resource_template_data')
    ;
    $templateDatas = $connection->executeQuery($qb)->fetchAllKeyValue();
    foreach ($templateDatas as $id => $templateData) {
        $templateData = json_decode($templateData, true);
        foreach ([
            'require_resource_class',
            'closed_class_list',
            'closed_property_list',
            'quick_new_resource',
            'no_language',
            'value_suggest_keep_original_label',
            'value_suggest_require_uri',
        ] as $key) {
            if (array_key_exists($key, $templateData)) {
                if (in_array($templateData[$key], [true, 1, '1', 'yes'], true)) {
                    $templateData[$key] = 'yes';
                } else {
                    unset($templateData[$key]);
                }
            }
        }
        $quotedTemplateData = $connection->quote(json_encode($templateData));
        $sql = <<<SQL
UPDATE `resource_template_data`
SET
    `data` = $quotedTemplateData
WHERE `id` = $id;
SQL;
        $connection->executeStatement($sql);
    }

    $qb = $connection->createQueryBuilder();
    $qb
        ->select('id', 'data')
        ->from('resource_template_property_data', 'resource_template_property_data')
    ;
    $templatePropertyDatas = $connection->executeQuery($qb)->fetchAllKeyValue();
    foreach ($templatePropertyDatas as $id => $templatePropertyData) {
        $templatePropertyData = json_decode($templatePropertyData, true);
        foreach ([
            'property_read_only',
            'locked_value',
        ] as $key) {
            if (array_key_exists($key, $templatePropertyData)) {
                if (in_array($templatePropertyData[$key], [true, 1, '1', 'yes'], true)) {
                    $templatePropertyData[$key] = 'yes';
                } else {
                    unset($templatePropertyData[$key]);
                }
            }
        }
        $quotedTemplatePropertyData = $connection->quote(json_encode($templatePropertyData));
        $sql = <<<SQL
UPDATE `resource_template_property_data`
SET
    `data` = $quotedTemplatePropertyData
WHERE `id` = $id;
SQL;
        $connection->executeStatement($sql);
    }

    $settings->set('advancedresourcetemplate_resource_form_elements',
        $config['advancedresourcetemplate']['settings']['advancedresourcetemplate_resource_form_elements']);

    $message = new Message(
        'New settings were added to the template.' // @translate
    );
    $messenger->addSuccess($message);
    $message = new Message(
        'New settings were added to the %1$smain settings%2$s to simplify resource form.', // @translate
        '<a href="' . $plugins->get('url')->fromRoute('admin/default', ['controller' => 'setting', 'action' => 'browse']) . '#advanded-resource-template">', '</a>'
    );
    $message->setEscapeHtml(false);
    $messenger->addSuccess($message);
}

if (version_compare((string) $oldVersion, '3.3.4.15', '<')) {
    $message = new Message(
        'It’s now possible to group a long list of template properties.' // @translate
    );
    $messenger->addSuccess($message);
}

if (version_compare((string) $oldVersion, '3.4.4.16', '<')) {
    // Replace the option "default_language" by the new "o:default_language".
    $qb = $connection->createQueryBuilder();
    $qb
        ->select('*')
        ->from('resource_template_property_data', 'resource_template_property_data')
    ;
    $templatePropertyDatas = $connection->executeQuery($qb)->fetchAllAssociative();
    $sqlRtp = <<<SQL
UPDATE `resource_template_property`
SET
    `default_lang` = :default_lang
WHERE `id` = :rtp_id;
SQL;
    $sqlRtpd = <<<SQL
UPDATE `resource_template_property_data`
SET
    `data` = :data
WHERE `id` = :id;
SQL;
    foreach ($templatePropertyDatas as $templatePropertyData) {
        $rtpData = json_decode($templatePropertyData['data'], true);
        if (!empty($rtpData['default_language'])) {
            $connection->executeStatement($sqlRtp, [
                'default_lang' => $rtpData['default_language'],
                'rtp_id' => (int) $templatePropertyData['resource_template_property_id'],
            ]);
        }
        $rtpData['o:default_lang'] = empty($rtpData['default_language']) ? null : $rtpData['default_language'];
        unset($rtpData['default_language']);
        $connection->executeStatement($sqlRtpd, [
            'data' => json_encode($rtpData),
            'id' => (int) $templatePropertyData['id'],
        ]);
    }
}
