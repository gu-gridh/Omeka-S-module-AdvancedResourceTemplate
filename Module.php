<?php declare(strict_types=1);

namespace AdvancedResourceTemplate;

if (!class_exists(\Generic\AbstractModule::class)) {
    require file_exists(dirname(__DIR__) . '/Generic/AbstractModule.php')
        ? dirname(__DIR__) . '/Generic/AbstractModule.php'
        : __DIR__ . '/src/Generic/AbstractModule.php';
}

use Generic\AbstractModule;
use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Mvc\MvcEvent;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Mvc\Status;
use Omeka\Stdlib\ErrorStore;

class Module extends AbstractModule
{
    const NAMESPACE = __NAMESPACE__;

    /**
     * @var array
     */
    protected $propertiesByTerms;

    /**
     * @var array
     */
    protected $propertiesByTermsAndIds;

    protected function postInstall(): void
    {
        $filepath = __DIR__ . '/data/mapping/mappings.ini';
        if (!file_exists($filepath) || is_file($filepath) || !is_readable($filepath)) {
            return;
        }
        $mapping = $this->stringToAutofillers(file_get_contents($filepath));
        $settings = $this->getServiceLocator()->get('Omeka\Settings');
        $settings->set('advancedresourcetemplate_autofillers', $mapping);
    }

    public function onBootstrap(MvcEvent $event): void
    {
        parent::onBootstrap($event);
        // Copy or rights of the main Resource Template.
        /** @var \Omeka\Permissions\Acl $acl */
        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        $roles = $acl->getRoles();
        $acl
            ->allow(
                null,
                [\AdvancedResourceTemplate\Api\Adapter\ResourceTemplateAdapter::class],
                ['search', 'read']
            )
            ->allow(
                ['author', 'editor'],
                [\AdvancedResourceTemplate\Api\Adapter\ResourceTemplateAdapter::class],
                ['create', 'update', 'delete']
            )
            ->allow(
                null,
                [
                    \AdvancedResourceTemplate\Entity\ResourceTemplateData::class,
                    \AdvancedResourceTemplate\Entity\ResourceTemplatePropertyData::class,
                ],
                ['read']
            )
            ->allow(
                ['author', 'editor'],
                [
                    \AdvancedResourceTemplate\Entity\ResourceTemplateData::class,
                    \AdvancedResourceTemplate\Entity\ResourceTemplatePropertyData::class,
                ],
                ['create', 'update', 'delete']
            )
            ->allow(
                $roles,
                ['AdvancedResourceTemplate\Controller\Admin\Index']
            )
        ;
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager): void
    {
        // Store some template settings in main settings for simple access.
        $sharedEventManager->attach(
            \AdvancedResourceTemplate\Api\Adapter\ResourceTemplateAdapter::class,
            'api.create.post',
            [$this, 'handleTemplateConfigOnSave']
        );
        $sharedEventManager->attach(
            \AdvancedResourceTemplate\Api\Adapter\ResourceTemplateAdapter::class,
            'api.update.post',
            [$this, 'handleTemplateConfigOnSave']
        );
        $sharedEventManager->attach(
            \AdvancedResourceTemplate\Api\Adapter\ResourceTemplateAdapter::class,
            'api.delete.post',
            [$this, 'handleTemplateConfigOnSave']
        );

        // Manage the auto-value setting for each resource type.
        $sharedEventManager->attach(
            \Omeka\Api\Adapter\ItemAdapter::class,
            'api.create.pre',
            [$this, 'handleTemplateSettingsOnSave']
        );
        $sharedEventManager->attach(
            \Omeka\Api\Adapter\ItemAdapter::class,
            'api.update.pre',
            [$this, 'handleTemplateSettingsOnSave']
        );
        $sharedEventManager->attach(
            \Omeka\Api\Adapter\MediaAdapter::class,
            'api.create.pre',
            [$this, 'handleTemplateSettingsOnSave']
        );
        $sharedEventManager->attach(
            \Omeka\Api\Adapter\MediaAdapter::class,
            'api.update.pre',
            [$this, 'handleTemplateSettingsOnSave']
        );
        $sharedEventManager->attach(
            \Omeka\Api\Adapter\ItemSetAdapter::class,
            'api.create.pre',
            [$this, 'handleTemplateSettingsOnSave']
        );
        $sharedEventManager->attach(
            \Omeka\Api\Adapter\ItemSetAdapter::class,
            'api.update.pre',
            [$this, 'handleTemplateSettingsOnSave']
        );
        $sharedEventManager->attach(
            \Annotate\Api\Adapter\AnnotationAdapter::class,
            'api.create.pre',
            [$this, 'handleTemplateSettingsOnSave']
        );
        $sharedEventManager->attach(
            \Annotate\Api\Adapter\AnnotationAdapter::class,
            'api.update.pre',
            [$this, 'handleTemplateSettingsOnSave']
        );

        // Check the resource according to the specified template settings.
        $sharedEventManager->attach(
            \Omeka\Api\Adapter\ItemAdapter::class,
            'api.hydrate.post',
            [$this, 'validateEntityHydratePost']
        );
        $sharedEventManager->attach(
            \Omeka\Api\Adapter\ItemAdapter::class,
            'api.hydrate.post',
            [$this, 'validateEntityHydratePost']
        );
        $sharedEventManager->attach(
            \Omeka\Api\Adapter\MediaAdapter::class,
            'api.hydrate.post',
            [$this, 'validateEntityHydratePost']
        );
        $sharedEventManager->attach(
            \Omeka\Api\Adapter\MediaAdapter::class,
            'api.hydrate.post',
            [$this, 'validateEntityHydratePost']
        );
        $sharedEventManager->attach(
            \Omeka\Api\Adapter\ItemSetAdapter::class,
            'api.hydrate.post',
            [$this, 'validateEntityHydratePost']
        );
        $sharedEventManager->attach(
            \Omeka\Api\Adapter\ItemSetAdapter::class,
            'api.hydrate.post',
            [$this, 'validateEntityHydratePost']
        );
        $sharedEventManager->attach(
            \Annotate\Api\Adapter\AnnotationAdapter::class,
            'api.hydrate.post',
            [$this, 'validateEntityHydratePost']
        );
        $sharedEventManager->attach(
            \Annotate\Api\Adapter\AnnotationAdapter::class,
            'api.hydrate.post',
            [$this, 'validateEntityHydratePost']
        );

        // Manage the items to append to item sets.
        // The item should be created to be able to do a search on it.
        $sharedEventManager->attach(
            \Omeka\Api\Adapter\ItemAdapter::class,
            'api.create.post',
            [$this, 'handleApiSavePostItem']
        );
        $sharedEventManager->attach(
            \Omeka\Api\Adapter\ItemAdapter::class,
            'api.update.post',
            [$this, 'handleApiSavePostItem']
        );
        $sharedEventManager->attach(
            \Omeka\Api\Adapter\ItemSetAdapter::class,
            'api.create.post',
            [$this, 'handleApiSavePostItemSet']
        );
        $sharedEventManager->attach(
            \Omeka\Api\Adapter\ItemSetAdapter::class,
            'api.update.post',
            [$this, 'handleApiSavePostItemSet']
        );

        // Display values according to options of the resource template.
        // For compatibility with other modules (HideProperties, Internationalisation)
        // that use the term as key in the list of displayed values, the event
        // should be triggered lastly.
        // Anyway, this is now an iterator that keeps the same key for multiple
        // values.
        $sharedEventManager->attach(
            \Omeka\Api\Representation\ItemRepresentation::class,
            'rep.resource.display_values',
            [$this, 'handleResourceDisplayValues'],
            -100
        );
        $sharedEventManager->attach(
            \Omeka\Api\Representation\MediaRepresentation::class,
            'rep.resource.display_values',
            [$this, 'handleResourceDisplayValues'],
            -100
        );
        $sharedEventManager->attach(
            \Omeka\Api\Representation\ItemSetRepresentation::class,
            'rep.resource.display_values',
            [$this, 'handleResourceDisplayValues'],
            -100
        );
        $sharedEventManager->attach(
            \Annotate\Api\Representation\AnnotationRepresentation::class,
            'rep.resource.display_values',
            [$this, 'handleResourceDisplayValues'],
            -100
        );

        // Display subject values according to options of the resource template.
        $sharedEventManager->attach(
            \Omeka\Api\Adapter\ItemAdapter::class,
            'api.subject_values.query',
            [$this, 'handleResourceDisplaySubjectValues'],
            -100
        );
        $sharedEventManager->attach(
            \Omeka\Api\Adapter\ItemAdapter::class,
            'api.subject_values_simple.query',
            [$this, 'handleResourceDisplaySubjectValues'],
            -100
        );
        $sharedEventManager->attach(
            \Omeka\Api\Adapter\MediaAdapter::class,
            'api.subject_values.query',
            [$this, 'handleResourceDisplaySubjectValues'],
            -100
        );
        $sharedEventManager->attach(
            \Omeka\Api\Adapter\MediaAdapter::class,
            'api.subject_values_simple.query',
            [$this, 'handleResourceDisplaySubjectValues'],
            -100
        );
        $sharedEventManager->attach(
            \Omeka\Api\Adapter\ItemSetAdapter::class,
            'api.subject_values.query',
            [$this, 'handleResourceDisplaySubjectValues'],
            -100
        );
        $sharedEventManager->attach(
            \Omeka\Api\Adapter\ItemSetAdapter::class,
            'api.subject_values_simple.query',
            [$this, 'handleResourceDisplaySubjectValues'],
            -100
        );
        $sharedEventManager->attach(
            \Annotate\Api\Adapter\AnnotationAdapter::class,
            'api.subject_values.query',
            [$this, 'handleResourceDisplaySubjectValues'],
            -100
        );
        $sharedEventManager->attach(
            \Annotate\Api\Adapter\AnnotationAdapter::class,
            'api.subject_values_simple.query',
            [$this, 'handleResourceDisplaySubjectValues'],
            -100
        );

        // Add css/js to some admin pages.
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.layout',
            [$this, 'addAdminResourceHeaders']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\ItemSet',
            'view.layout',
            [$this, 'addAdminResourceHeaders']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Media',
            'view.layout',
            [$this, 'addAdminResourceHeaders']
        );
        // For simplicity, some modules that use resource form are added here.
        $sharedEventManager->attach(
            \Annotate\Controller\Admin\AnnotationController::class,
            'view.layout',
            [$this, 'addAdminResourceHeaders']
        );

        // Display the item set query for items in advanced tab.
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\ItemSet',
            'view.add.form.advanced',
            [$this, 'addAdvancedTabElements']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\ItemSet',
            'view.edit.form.advanced',
            [$this, 'addAdvancedTabElements']
        );

        $sharedEventManager->attach(
            \Omeka\Form\ResourceForm::class,
            'form.add_elements',
            [$this, 'handleResourceForm']
        );

        $sharedEventManager->attach(
            \Omeka\Form\SettingForm::class,
            'form.add_elements',
            [$this, 'handleMainSettings']
        );
        $sharedEventManager->attach(
            \Omeka\Form\SettingForm::class,
            'form.add_input_filters',
            [$this, 'handleMainSettingsFilters']
        );

        $sharedEventManager->attach(
            // \Omeka\Form\ResourceTemplateForm::class,
            \AdvancedResourceTemplate\Form\ResourceTemplateForm::class,
            'form.add_elements',
            [$this, 'addResourceTemplateFormElements']
        );
        $sharedEventManager->attach(
            // \Omeka\Form\ResourceTemplatePropertyFieldset::class,
            \AdvancedResourceTemplate\Form\ResourceTemplatePropertyFieldset::class,
            'form.add_elements',
            [$this, 'addResourceTemplatePropertyFieldsetElements']
        );
    }

    public function handleTemplateConfigOnSave(Event $event): void
    {
        $this->storeResourceTemplateSettings();
    }

    public function handleTemplateSettingsOnSave(Event $event): void
    {
        /** @var \Omeka\Api\Request $request */
        $request = $event->getParam('request');

        // This is the resource representation array passed to the api for
        // creation/update.
        $resource = $request->getContent();

        $templateId = $resource['o:resource_template']['o:id'] ?? null;
        if (!$templateId) {
            return;
        }

        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        try {
            /** @var \AdvancedResourceTemplate\Api\Representation\ResourceTemplateRepresentation $template */
            $template = $api->read('resource_templates', ['id' => $templateId])->getContent();
        } catch (\Exception $e) {
            return;
        }

        // Simply add the value if not present.

        // Template level.
        $resource = $this->appendAutomaticValuesFromTemplateData($template, $resource);

        // Property level.
        foreach ($template->resourceTemplateProperties() as $templateProperty) {
            foreach ($templateProperty->data() as $rtpData) {
                $resource = $this->explodeValueFromTemplatePropertyData($rtpData, $resource);
                $automaticValue = $this->automaticValueFromTemplatePropertyData($rtpData, $resource);
                if (!is_null($automaticValue)) {
                    $resource[$templateProperty->property()->term()][] = $automaticValue;
                }
            }
        }

        $request->setContent($resource);
    }

    public function validateEntityHydratePost(Event $event): void
    {
        /** @var \Omeka\Entity\Resource $entity */
        $entity = $event->getParam('entity');

        /** @var \Omeka\Entity\ResourceTemplate $templateEntity */
        $templateEntity = $entity->getResourceTemplate();
        if (!$templateEntity) {
            return;
        }

        // Update open custom vocabs in any cases, when checks are skipped.
        $this->updateCustomVocabOpen($event);

        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');
        $skipChecks = (bool) $settings->get('advancedresourcetemplate_skip_checks');
        if ($skipChecks) {
            return;
        }

        /**
         * @var \Omeka\Api\Adapter\AbstractResourceEntityAdapter $adapter
         * @var \AdvancedResourceTemplate\Api\Representation\ResourceTemplateRepresentation $template
         * @var \Omeka\Api\Request $request
         * @var \Omeka\Stdlib\ErrorStore $errorStore
         * @var \Doctrine\DBAL\Connection $connection
         */
        $adapter = $event->getTarget();
        $template = $adapter->getAdapter('resource_templates')->getRepresentation($templateEntity);
        // $request = $event->getParam('request');
        $errorStore = $event->getParam('errorStore');
        $connection = $services->get('Omeka\Connection');

        $directMessage = $this->displayDirectMessage();
        $messenger = $directMessage ? $services->get('ControllerPluginManager')->get('messenger') : null;

        // Template level.

        $useForResources = $template->dataValue('use_for_resources', []);
        $resourceName = $entity->getResourceName();
        if ($useForResources && !in_array($resourceName, $useForResources)) {
            $message = new \Omeka\Stdlib\Message('This template cannot be used for this resource.'); // @translate
            $errorStore->addError('o:resource_template[o:id]', $message);
            if ($directMessage) {
                $messenger->addError($message);
            }
        }

        $resourceClass = $entity->getResourceClass();
        $requireClass = $this->valueIsTrue($template->dataValue('require_resource_class'));
        if ($requireClass && !$resourceClass) {
            $message = new \Omeka\Stdlib\Message('A class is required.'); // @translate
            $errorStore->addError('o:resource_class[o:id]', $message);
        }

        $closedClassList = $this->valueIsTrue($template->dataValue('closed_class_list'));
        if ($closedClassList && $resourceClass) {
            $suggestedClasses = $template->dataValue('suggested_resource_class_ids', []);
            if ($suggestedClasses && !in_array($resourceClass->getId(), $suggestedClasses)) {
                if (count($suggestedClasses) === 1) {
                    $message = new \Omeka\Stdlib\Message(
                        'The class should be "%s".', // @translate
                        key($suggestedClasses)
                    );
                    $errorStore->addError('o:resource_class[o:id]', $message);
                } else {
                    $message = new \Omeka\Stdlib\Message(
                        'The class should be one of "%s".', // @translate
                        implode('", "', array_keys($suggestedClasses))
                    );
                    $errorStore->addError('o:resource_class[o:id]', $message);
                }
            }
        }

        // TODO Manage closed property list: but good data can be added via modules (identifier, etc.).

        // Some checks can be done simpler via representation.
        /** @var \Omeka\Api\Representation\AbstractResourceEntityRepresentation $resource */
        $resource = $adapter->getRepresentation($entity);

        // Property level.
        $this->validateResourceProperty($resource, $errorStore, $directMessage);
    }

    protected function validateResourceProperty(
        AbstractResourceEntityRepresentation $resource,
        ErrorStore $errorStore,
        bool $directMessage
    ) {
        $services = $this->getServiceLocator();
        $template = $resource->resourceTemplate();
        $resourceId = (int) $resource->id();
        $messenger = $directMessage ? $services->get('ControllerPluginManager')->get('messenger') : null;

        foreach ($template->resourceTemplateProperties() as $templateProperty) {
            foreach ($templateProperty->data() as $rtpData) {
                $term = $templateProperty->property()->term();

                $inputControl = $rtpData->dataValue('input_control', '');
                if (strlen($inputControl)) {
                    // Check that the input control is a valid regex first.
                    $anchors = ['/', '#', '~', '%', '`', ';', '§', 'µ'];
                    foreach ($anchors as $anchor) {
                        if (mb_strpos($inputControl, $anchor) === false) {
                            $regex = $anchor . '^(?:' . $inputControl . ')$' . $anchor . 'u';
                            if (preg_match($regex, '') === false) {
                                $anchor = '';
                            }
                            break;
                        }
                    }
                    if (empty($anchor) || empty($regex)) {
                        $message = new \Omeka\Stdlib\Message(
                            'The html input pattern "%1$s" for template "%2$s" cannot be processed.', // @translate
                            $inputControl, $template->label()
                        );
                        $services->get('Omeka\Logger')->warn((string) $message);
                    } else {
                        foreach ($resource->value($term, ['all' => true, 'type' => 'literal']) as $value) {
                            $val = $value->value();
                            if (!preg_match($regex, $val)) {
                                $message = new \Omeka\Stdlib\Message(
                                    'The value "%1$s" for term "%2$s" does not follow the input pattern "%3$s".', // @translate
                                    $val, $term, $inputControl
                                );
                                $errorStore->addError($term, $message);
                                if ($directMessage) {
                                    $messenger->addError($message);
                                }
                            }
                        }
                    }
                }

                $minLength = (int) $rtpData->dataValue('min_length');
                $maxLength = (int) $rtpData->dataValue('max_length');
                if ($minLength || $maxLength) {
                    foreach ($resource->value($term, ['all' => true, 'type' => 'literal']) as $value) {
                        $length = mb_strlen($value->value());
                        if ($minLength && $length < $minLength) {
                            $message = new \Omeka\Stdlib\Message(
                                'The value for term "%1$s" is shorter (%2$d characters) than the minimal size (%3$d characters).', // @translate
                                $term, $length, $minLength
                            );
                            $errorStore->addError($term, $message);
                            if ($directMessage) {
                                $messenger->addError($message);
                            }
                        }
                        if ($maxLength && $length > $maxLength) {
                            $message = new \Omeka\Stdlib\Message(
                                'The value for term "%1$s" is longer (%2$d characters) than the maximal size (%3$d characters).', // @translate
                                $term, $length, $maxLength
                            );
                            $errorStore->addError($term, $message);
                            if ($directMessage) {
                                $messenger->addError($message);
                            }
                        }
                    }
                }

                $minValues = (int) $rtpData->dataValue('min_values');
                $maxValues = (int) $rtpData->dataValue('max_values');
                // TODO Fix api($form) to manage the minimum number of values in admin resource form.
                // The check for directMessage is to be removed with the fix.
                if (!$directMessage && ($minValues || $maxValues)) {
                    // The number of values may be specific for each type.
                    $isRequired = $rtpData->isRequired();
                    $values = $resource->value($term, ['all' => true, 'type' => $rtpData->dataTypes()]);
                    $countValues = count($values);
                    if ($isRequired && $minValues && $countValues < $minValues) {
                        $message = new \Omeka\Stdlib\Message(
                            'The number of values (%1$d) for term "%2$s" is lower than the minimal number (%3$d).', // @translate
                            $countValues, $term, $minValues
                        );
                        $errorStore->addError($term, $message);
                        if ($directMessage) {
                            $messenger->addError($message);
                        }
                        break;
                    }
                    if ($maxValues && $countValues > $maxValues) {
                        $message = new \Omeka\Stdlib\Message(
                            'The number of values (%1$d) for term "%2$s" is greater than the maximal number (%3$d).', // @translate
                            $countValues, $term, $maxValues
                        );
                        $errorStore->addError($term, $message);
                        if ($directMessage) {
                            $messenger->addError($message);
                        }
                        break;
                    }
                }

                $uniqueValue = (bool) $rtpData->dataValue('unique_value');
                if ($uniqueValue) {
                    $values = $resource->value($term, ['all' => true]);
                    if ($values) {
                        $sqlWhere = [];
                        // Get all values by main type in one query.
                        $bind = [
                            'resource_id' => $resourceId,
                            'property_id' => $templateProperty->property()->id(),
                        ];
                        $types = [
                            'resource_id' => \Doctrine\DBAL\ParameterType::INTEGER,
                            'property_id' => \Doctrine\DBAL\ParameterType::INTEGER,
                        ];
                        foreach ($values as $value) {
                            if ($k = $value->valueResource()) {
                                $bind['resource'][] = $k->id();
                            } elseif ($k = $value->uri()) {
                                $bind['uri'][] = $k;
                            } else {
                                $bind['literal'][] = $value->value();
                            }
                        }
                        if (isset($bind['resource'])) {
                            $sqlWhere[] = 'value.value_resource_id IN (:resource)';
                            $types['resource'] = $connection::PARAM_INT_ARRAY;
                        }
                        if (isset($bind['uri'])) {
                            $sqlWhere[] = 'value.uri IN (:uri)';
                            $types['uri'] = $connection::PARAM_STR_ARRAY;
                        }
                        if (isset($bind['literal'])) {
                            $sqlWhere[] = 'value.value IN (:literal)';
                            $types['literal'] = $connection::PARAM_STR_ARRAY;
                        }
                        $sqlWhere = implode(' OR ', $sqlWhere);
                        $sql = <<<SQL
SELECT value.resource_id
FROM value
WHERE value.resource_id != :resource_id
    AND value.property_id = :property_id
    AND ($sqlWhere)
LIMIT 1;
SQL;
                        $resId = $connection->executeQuery($sql, $bind, $types)->fetchOne();
                        if ($resId) {
                            $message = new \Omeka\Stdlib\Message(
                                'The value for term "%1$s" should be unique, but already set for resource #%2$s.', // @translate
                                $term, $resId
                            );
                            $errorStore->addError($term, $message);
                            if ($directMessage) {
                                $messenger->addError($message);
                            }
                            break;
                        }
                    }
                }

                // TODO Check language (but they are suggested languages).
            }
        }
    }

    /**
     * Prepare specific data to display the list of the resource values data.
     *
     * Specific data passed to display values for this module are:
     * - groups of properties, managed in overridden view template resource-values
     * - term, like the key
     * - duplicated properties with a specific label and comments
     *
     * Some of the complexity of the module is needed to kept compatibility
     * with core, even if the module is removed.
     *
     * @see \Omeka\Api\Representation\AbstractResourceEntityRepresentation::displayValues()
     */
    public function handleResourceDisplayValues(Event $event): void
    {
        /**
         * @var \Omeka\Api\Representation\AbstractResourceEntityRepresentation $resource
         * @var \AdvancedResourceTemplate\Api\Representation\ResourceTemplateRepresentation $template
         * @var \AdvancedResourceTemplate\Api\Representation\ResourceTemplatePropertyRepresentation[] $templateProperties
         * @var array $values
         * @var array $groups
         */
        $resource = $event->getTarget();
        $template = $resource->resourceTemplate();
        $values = $event->getParam('values');

        $services = $this->getServiceLocator();
        $status = $services->get('Omeka\Status');
        if ($status->isSiteRequest()
            && $services->get('Omeka\Settings')->get('advancedresourcetemplate_skip_private_values')
        ) {
            foreach ($values as $term => &$propertyData) {
                /** @var \Omeka\Api\Representation\ValueRepresentation $value */
                foreach ($propertyData['values'] as $key => $value) {
                    if (!$value->isPublic()) {
                        unset($propertyData['values'][$key]);
                    }
                }
                if (count($propertyData['values'])) {
                    $propertyData['values'] = array_values($propertyData['values']);
                } else {
                    unset($values[$term]);
                }
            }
        }

        if ($template) {
            $groups = $template->dataValue('groups', []);
            $templateProperties = $template->resourceTemplateProperties();
        } else {
            $groups = [];
            $templateProperties = [];
        }

        $newValues = count($templateProperties)
            ? $this->prepareGroupsValues($resource, $templateProperties, $values, $groups)
            : $this->prependGroupsToValues($resource, $values, $groups);

        $event->setParam('values', $newValues);
    }

    /**
     * Prepare specific data to display the list of the linked resources.
     *
     * Specific data passed to display values for this module are:
     * - order of subject values
     *
     * @see \Omeka\Api\Adapter\AbstractResourceEntityAdapter::getSubjectValues()
     */
    public function handleResourceDisplaySubjectValues(Event $event): void
    {
        /**
         * @var \Omeka\Api\Adapter\AbstractResourceEntityAdapter $adapter
         * @var \Doctrine\ORM\QueryBuilder $qb
         * @var \Omeka\Entity\Resource $resource
         * @var int|string|null $propertyId
         * @var string|null $resourceType
         * @var int|null $siteId
         * @var \AdvancedResourceTemplate\Api\Representation\ResourceTemplateRepresentation $template
         *
         * Warning: the property id may not be the property id, but the property
         * id and a resource template property id like "123-234".
         * @see \Omeka\Api\Adapter\AbstractResourceEntityAdapter::getSubjectValuesQueryBuilder()
         */
        $resource = $event->getParam('resource');
        $template = $resource->getResourceTemplate();
        if (!$template) {
            return;
        }

        $adapter = $event->getTarget();
        $templateAdapter = $adapter->getAdapter('resource_templates');
        $template = $templateAdapter->getRepresentation($template);

        // Use the template order for the property when propertyId is set.
        $order = $template->dataValue('subject_values_order');
        if (!$order) {
            return;
        }

        $propertyId = $event->getParam('propertyId');
        $propertyTerm = $propertyId
            ? $this->getPropertyTerm(strtok((string) $propertyId, '-'))
            : null;
        if (empty($order[$propertyTerm])) {
            return;
        }

        $order = $order[$propertyTerm];

        // Filter order early.
        $orderPropertyIds = $this->getPropertyIds(array_keys($order));
        $order = array_replace($orderPropertyIds, array_intersect_key($order, $orderPropertyIds));
        if (!$order) {
            return;
        }

        $qb = $event->getParam('queryBuilder');
        $qb
            // Default order without "resource.title".
            ->orderBy('property.id, resource_template_property.alternateLabel');

        foreach ($order as $property => $sort) {
            $property = $this->getPropertyId($property);
            if (!$property) {
                continue;
            }
            $alias = $adapter->createAlias();
            $aliasProperty = $adapter->createAlias();
            $sort = strtoupper((string) $sort) === 'DESC' ? 'DESC' : 'ASC';
            $qb
                ->leftJoin(\Omeka\Entity\Value::class, $alias, 'WITH', "$alias.resource = value.resource AND $alias.property = :$aliasProperty AND $alias.value IS NOT NULL")
                ->setParameter($aliasProperty, $property, \Doctrine\DBAL\ParameterType::INTEGER)
                ->addOrderBy("$alias.value", $sort);
        }
    }

    /**
     * Prepend keys "group" and "term" to display values.
     *
     * Manage the rare case where there is a template without property.
     *
     * Warning: Duplicate properties are not managed here.
     */
    protected function prependGroupsToValues(
        AbstractResourceEntityRepresentation $resource,
        array $values,
        array $groups
    ): array {
        if (!$groups) {
            foreach ($values as $term => &$propertyData) {
                $propertyData = [
                    'group' => null,
                    'term' => $term,
                ] + $propertyData;
            }
            unset($propertyData);
            return $values;
        }

        // Here, there is no duplicate labels,
        foreach ($values as $term => &$propertyData) {
            $currentGroup = null;
            foreach ($groups as $groupLabel => $termLabels) {
                if (in_array($term, $termLabels)) {
                    $currentGroup = $groupLabel;
                    break;
                }
            }
            $propertyData = [
                'group' => $currentGroup,
                'term' => $term,
            ] + $propertyData;
        }
        unset($propertyData);
        return $values;
    }

    /**
     * Prepare duplicate properties with specific labels and comments.
     *
     * In that case, convert the array into an IteratorIterator, so the key
     * (term) stays the same, but there are more values with it.
     *
     * In the previous version, the key "term" was modified as term + index,
     * and the label and comment were updated, so the default template "common/resource-values"
     * was wrapped and was able to display values as standard ones.
     *
     * @see \Omeka\Api\Representation\AbstractResourceEntityRepresentation::values()
     * @see \Omeka\Api\Representation\AbstractResourceEntityRepresentation::displayValues()
     */
    protected function prepareGroupsValues(
        AbstractResourceEntityRepresentation $resource,
        array $templateProperties,
        array $values,
        array $groups
    ): iterable {
        // The process should take care of values appended to a resource that
        // have a data type that is not specified in template properties, in
        // particular the default ones (literal, resource, uri). It may fix bad
        // imports too, or resources with a template that was updated later.

        $services = $this->getServiceLocator();
        $translator = $services->get('MvcTranslator');

        // TODO Check if this process can be simplified (three double loops, even if loops are small and for one resource a time).

        // The alternate comments are included too, even if they are not
        // displayed in the default resource template.

        // Check and prepare values when a property have multiple labels.
        $labelsAndComments = [];
        $hasMultipleLabels = false;
        /** @var \AdvancedResourceTemplate\Api\Representation\ResourceTemplatePropertyRepresentation $rtp */
        foreach ($templateProperties as $rtp) {
            $property = $rtp->property();
            $term = $property->term();
            $labelsAndComments[$term] = $rtp->labelsAndCommentsByDataType();
            $hasMultipleLabels = $hasMultipleLabels
                || count($rtp->labels()) > 1;
        }

        if (!$hasMultipleLabels) {
            return $this->prependGroupsToValues($resource, $values, $groups);
        }

        // Prepare values to display when specific labels are defined for some
        // data types for some properties.
        // So add a key with the prepared label for the data type.
        $valuesWithLabel = [];
        $dataTypesLabelsToComments = [];
        foreach ($values as $term => $propertyData) {
            /** @var \Omeka\Api\Representation\PropertyRepresentation $property */
            $property = $propertyData['property'];
            foreach ($propertyData['values'] as $value) {
                $dataType = $value->type();
                $dataTypeLabel = $labelsAndComments[$term][$dataType]['label']
                    ?? $labelsAndComments[$term]['default']['label']
                    // Manage properties appended to a resource that are not in
                    // the template for various reasons.
                    ?? $translator->translate($property->label());
                $valuesWithLabel[$term][$dataTypeLabel]['values'][] = $value;
                $dataTypesLabelsToComments[$dataTypeLabel] = $labelsAndComments[$term][$dataType]['comment']
                    ?? $labelsAndComments[$term]['default']['comment']
                    ?? $translator->translate($property->comment());
            }
        }

        foreach ($values as $term => &$propertyData) {
            $currentGroup = null;
            foreach ($groups as $groupLabel => $termLabels) {
                if (in_array($term, $termLabels)) {
                    $currentGroup = $groupLabel;
                    break;
                }
            }
            $propertyData = [
                'group' => $currentGroup,
                'term' => $term,
            ] + $propertyData;
        }
        unset($propertyData);

        // Instead of an array, use an iterator to keep the same term for
        // multiple propertyDatas.
        $newValues = new \AppendIterator();
        $hasGroups = !empty($groups);
        $currentGroup = null;
        foreach ($valuesWithLabel as $term => $propData) {
            foreach ($propData as $dataTypeLabel => $propertyData) {
                $termLabel = "$term/$dataTypeLabel";
                if ($hasGroups) {
                    $currentGroup = null;
                    foreach ($groups as $groupLabel => $termLabels) {
                        foreach ($termLabels as $termLab) {
                            $simpleTerm = strpos($termLab, '/') === false;
                            if ($termLab === ($simpleTerm ? $term : $termLabel)) {
                                $currentGroup = $groupLabel;
                                break 2;
                            }
                        }
                    }
                }
                // Unset values to keep it at the end of the array.
                unset($propertyData['values']);
                $propertyData['group'] = $currentGroup;
                $propertyData['term'] = $term;
                $propertyData['term_label'] = $termLabel;
                $propertyData['property'] = $values[$term]['property'];
                $propertyData['alternate_label'] = $dataTypeLabel;
                $propertyData['alternate_comment'] = $dataTypesLabelsToComments[$dataTypeLabel];
                $propertyData['values'] = $valuesWithLabel[$term][$dataTypeLabel]['values'];
                $newValues->append(new \ArrayIterator([$term => $propertyData]));
            }
        }

        return $newValues;
    }

    /**
     * Append item to items sets according to each request.
     *
     * A post event is required else the search query cannot be done.
     * Else process differently for "add".
     */
    public function handleApiSavePostItem(Event $event): void
    {
        /**
         * @var \Omeka\Api\Manager $api
         * @var \Omeka\Api\Request $request
         * @var \Omeka\Api\Response $response
         * @var \Omeka\Settings\Settings $settings
         * @var \Omeka\Api\Adapter\ItemAdapter $adapter
         * @var \Omeka\Entity\Item|\Omeka\Api\Representation\ItemRepresentation $item
         */
        $services = $this->getServiceLocator();
        $request = $event->getParam('request');

        if ($request->getOption('advancedresourcetemplate_save_post_item')) {
            return;
        }

        $settings = $services->get('Omeka\Settings');

        $queries = $settings->get('advancedresourcetemplate_item_set_queries') ?: [];
        if (!$queries) {
            return;
        }

        $adapter = $event->getTarget();
        $response = $event->getParam('response');

        $item = $response->getContent();

        if ($item instanceof \Omeka\Api\Representation\ItemRepresentation) {
            $item = $adapter->getEntityManager()->find(\Omeka\Entity\Item::class, $item->id());
        }

        $itemId = $item->getId();

        $existingItemSetIds = [];
        foreach ($item->getItemSets() as $itemSet) {
            $existingItemSetIds[$itemSet->getId()] = $itemSet->getId();
        }

        // Don't check for existing item sets.
        // It may avoid an infinite loop too.
        $queries = array_diff_key($queries, $existingItemSetIds);
        if (!$queries) {
            return;
        }

        // The adapter cannot be used directly when module AdvancedSearch is
        // enabled, because some arguments are not supported.
        $api = $services->get('Omeka\ApiManager');

        // Check if the item belongs to each item set.
        $newItemSetIds = [];
        foreach ($queries as $itemSetId => $query) {
            $query['id'] = [$itemId];
            $result = $api->search('items', $query, ['returnScalar' => 'id'])->getTotalResults();
            if ($result) {
                $newItemSetIds[$itemSetId] = $itemSetId;
            }
        }

        if (!$newItemSetIds) {
            return;
        }

        // In a post event, an infinite loop should be avoided, so skip api.

        $data = [
            'o:item_set' => $newItemSetIds,
        ];

        $updateRequest = new \Omeka\Api\Request('update', 'items');
        $updateRequest
            ->setId($itemId)
            ->setOption('initialize', false)
            ->setOption('finalize', false)
            ->setOption('isPartial', true)
            ->setOption('collectionAction', 'append')
            // Manage single and batch update processes.
            ->setOption('flushEntityManager', (bool) $request->getOption('flushEntityManager', true))
            ->setContent($data);
        $newItem = $adapter->update($updateRequest)->getContent();

        // Set right content in response.
        $responseContent = $request->getOption('responseContent');
        if ($responseContent === 'representation') {
            $newItem = $adapter->getRepresentation($newItem);
        } elseif ($responseContent === 'reference') {
            $newItem = $adapter->getRepresentation($newItem)->getReference();
        }

        $response->setContent($newItem);
    }

    public function handleApiSavePostItemSet(Event $event): void
    {
        /**
         * @var \Omeka\Settings\Settings $settings
         * @var \Omeka\Api\Request $request
         * @var \Omeka\Api\Response $response
         * @var \Omeka\Entity\ItemSet|\Omeka\Api\Representation\ItemSetRepresentation $itemSet
         * @var \Omeka\Mvc\Controller\Plugin\Messenger $messenger
         */
        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');
        $request = $event->getParam('request');
        $response = $event->getParam('response');
        $messenger = $services->get('ControllerPluginManager')->get('messenger');

        $itemSet = $response->getContent();
        $itemSetId = method_exists($itemSet, 'getId') ? $itemSet->getId() : $itemSet->id();

        $queries = $settings->get('advancedresourcetemplate_item_set_queries') ?: [];

        $existingQuery = $queries[$itemSetId] ?? null;

        // Store queries as array for cleaner storage and to avoid to parse it
        // each time and for quicker process.
        $queryString = $request->getValue('item_set_query_items') ?: null;
        if ($queryString) {
            $query = null;
            parse_str($queryString, $query);
        }

        if (empty($query)) {
            unset($queries[$itemSetId]);
            $query = null;
        } else {
            // Simplify the query for "id" if any (normally not present).
            if (empty($query['id'])) {
                unset($query['id']);
            } elseif (!is_array($query['id'])) {
                $query['id'] = [$query['id']];
            }
            // Of course, remove the current item set id from the query, else it
            // won't contains anything.
            if (!empty($query['item_set_id'])) {
                // Take care of module Advanced Search, that can search multiple
                // item set ids.
                $check = false;
                if (is_array($query['item_set_id'])) {
                    $query['item_set_id'] = array_diff($query['item_set_id'], [$itemSetId]);
                    $check = true;
                } elseif ((int) $query['item_set_id'] === (int) $itemSetId) {
                    unset($query['item_set_id']);
                    $check = true;
                }
                if ($check) {
                    $message = new \Omeka\Stdlib\Message(
                        'The query to attach items cannot contain the item set itself.' // @translate
                    );
                    $messenger->addWarning($message);
                }
            }
            $queries[$itemSetId] = $query;
        }

        $settings->set('advancedresourcetemplate_item_set_queries', $queries);

        if ($query === $existingQuery) {
            return;
        }

        // Exclude all existing items with this query and add new ones.
        // Don't use a sql query, but a batch update in order to manage api
        // calls (indexations).
        // Use a job: the process via api can be long with many items.
        $args =[
            'item_set_id' => $itemSetId,
        ];
        $job = $services->get(\Omeka\Job\Dispatcher::class)->dispatch(\AdvancedResourceTemplate\Job\AttachItemsToItemSet::class, $args);
        $urlPlugin = $services->get('ControllerPluginManager')->get('url');
        $message = new \Omeka\Stdlib\Message(
            'The query for the item set was changed: a job is run in background to detach and to attach items (%1$sjob #%2$d%3$s, %4$slogs%3$s).', // @translate
            sprintf('<a href="%s">',
                htmlspecialchars($urlPlugin->fromRoute('admin/id', ['controller' => 'job', 'id' => $job->getId()]))
            ),
            $job->getId(),
            '</a>',
            sprintf('<a href="%s">',
                htmlspecialchars($this->isModuleActive('Log')
                    ? $urlPlugin->fromRoute('admin/log', [], ['query' => ['job_id' => $job->getId()]])
                    : $urlPlugin->fromRoute('admin/id', ['controller' => 'job', 'id' => $job->getId(), 'action' => 'log'])
                )
            )
        );
        $message->setEscapeHtml(false);
        $messenger->addSuccess($message);
    }

    public function addAdminResourceHeaders(Event $event): void
    {
        /** @var \Laminas\View\Renderer\PhpRenderer $view */
        $view = $event->getTarget();

        $plugins = $view->getHelperPluginManager();
        $params = $plugins->get('params');
        $action = $params->fromRoute('action');
        if (!in_array($action, ['add', 'edit'])) {
            return;
        }

        $setting = $plugins->get('setting');
        $resourceFormElements = $setting('advancedresourcetemplate_resource_form_elements', [
            'metadata_collapse',
            'metadata_description',
            'language',
            'visibility',
            'value_annotation',
            'more_actions',
        ]) ?: [];

        $classes = [];
        $classesElements = [
            'art-no-metadata-description' => 'metadata_description',
            'art-no-language' => 'language',
            'art-no-visibility' => 'visibility',
            'art-no-value-annotation' => 'value_annotation',
            'art-no-more-actions' => 'more_actions',
        ];

        $classes = array_diff($classesElements, $resourceFormElements);

        if (isset($classes['art-no-visibility']) || isset($classes['art-no-value-annotation'])) {
            $classes['art-no-more-actions'] = true;
        } elseif (isset($classes['art-no-more-actions'])
            && !isset($classes['art-no-visibility'])
            && !isset($classes['art-no-value-annotation'])
        ) {
            $classes['art-direct-buttons'] = true;
        }
        if (!isset($classes['art-no-metadata-description']) && in_array('metadata_collapse', $resourceFormElements)) {
            $classes['art-metadata-collapse'] = true;
        }

        $isModal = $params->fromQuery('window') === 'modal';
        if ($isModal) {
            $classes[] = 'modal';
        }

        if (count($classes)) {
            $plugins->get('htmlElement')('body')->appendAttribute('class', implode(' ', array_keys($classes)));
        }

        $assetUrl = $plugins->get('assetUrl');
        $plugins->get('headLink')->appendStylesheet($assetUrl('css/advanced-resource-template-admin.css', 'AdvancedResourceTemplate'));
        $plugins->get('headScript')
            ->appendFile($assetUrl('vendor/jquery-autocomplete/jquery.autocomplete.min.js', 'AdvancedResourceTemplate'), 'text/javascript', ['defer' => 'defer'])
            ->appendFile($assetUrl('js/advanced-resource-template-admin.js', 'AdvancedResourceTemplate'), 'text/javascript', ['defer' => 'defer']);
    }

    public function handleResourceForm(Event $event): void
    {
        // TODO Remove the admin check for contribute (or copy the feature in the module).

        /** @var \Omeka\Mvc\Status $status */
        $services = $this->getServiceLocator();
        $status = $services->get('Omeka\Status');
        if (!$status->isAdminRequest()) {
            return;
        }

        $settings = $services->get('Omeka\Settings');

        // Limit resource templates to the current resource type.
        $resourceName = $this->getRouteResourceName($status);
        if ($resourceName) {
            /** @var \Omeka\Form\ResourceForm $form */
            $form = $event->getTarget();
            if ($form->has('o:resource_template[o:id]')) {
                /** @var \Omeka\Form\Element\ResourceSelect $templateSelect */
                $templateSelect = $form->get('o:resource_template[o:id]');
                $templateSelectOptions = $templateSelect->getOptions();
                $templateSelectOptions['resource_value_options']['query'] = $templateSelectOptions['resource_value_options']['query'] ?? [];
                $templateSelectOptions['resource_value_options']['query']['resource'] = $resourceName;
                // TODO The process is not optimal in the core, since the value options are set early when options are set.
                $templateSelect->setOptions($templateSelectOptions);
            }
        }

        $closedPropertyList = (bool) (int) $settings->get('advancedresourcetemplate_closed_property_list');
        if ($closedPropertyList) {
            /** @var \Omeka\Form\ResourceForm $form */
            $form = $event->getTarget();
            $form->setAttribute('class', trim($form->getAttribute('class') . ' closed-property-list on-load'));
        }
    }

    public function addAdvancedTabElements(Event $event): void
    {
        $services = $this->getServiceLocator();
        $view = $event->getTarget();
        $resource = $view->resource;

        /** @var \Omeka\Settings\Settings $settings */
        $settings = $services->get('Omeka\Settings');
        $queries = $settings->get('advancedresourcetemplate_item_set_queries') ?: [];
        $query = $resource ? $queries[$resource->id()] ?? null : null;

        $query = $query ? http_build_query($query, '', '&', PHP_QUERY_RFC3986) : null;

        /** @var \Omeka\Form\Element\Query $element */
        $formManager = $services->get('FormElementManager');
        $element = $formManager->get(\Omeka\Form\Element\Query::class);
        $element
            ->setName('item_set_query_items')
            ->setLabel('Query to attach items automatically to this item set') // @translate
            ->setOptions([
                'query_resource_type' => 'items',
            ])
            ->setAttributes([
                'id' => 'item_set_query_items',
                'value' => $query,
            ]);
        echo $view->formRow($element);
    }

    public function handleMainSettings(Event $event): void
    {
        parent::handleMainSettings($event);

        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');

        $autofillers = $settings->get('advancedresourcetemplate_autofillers') ?: [];
        $value = $this->autofillersToString($autofillers);

        $fieldset = version_compare(\Omeka\Module::VERSION, '4', '<')
            ? $event->getTarget()->get('advancedresourcetemplate')
            : $event->getTarget();
        $fieldset
            ->get('advancedresourcetemplate_autofillers')
            ->setValue($value);
    }

    public function handleMainSettingsFilters(Event $event): void
    {
        $inputFilter = version_compare(\Omeka\Module::VERSION, '4', '<')
            ? $event->getParam('inputFilter')->get('advancedresourcetemplate')
            : $event->getParam('inputFilter');
        $inputFilter
            ->add([
                'name' => 'advancedresourcetemplate_autofillers',
                'required' => false,
                'filters' => [
                    [
                        'name' => \Laminas\Filter\Callback::class,
                        'options' => [
                            'callback' => [$this, 'stringToAutofillers'],
                        ],
                    ],
                ],
            ]);
    }

    public function addResourceTemplateFormElements(Event $event): void
    {
        // For an example, see module Contribute (fully standard anyway).

        /** @var \Omeka\Form\ResourceTemplateForm $form */
        $form = $event->getTarget();
        $advancedFieldset = $this->getServiceLocator()->get('FormElementManager')
            ->get(\AdvancedResourceTemplate\Form\ResourceTemplateDataFieldset::class)
            ->setName('advancedresourcetemplate');
        // To simplify saved data, the elements are added directly to fieldset.
        $fieldset = $form->get('o:data');
        foreach ($advancedFieldset->getElements() as $element) {
            $fieldset->add($element);
        }
    }

    public function addResourceTemplatePropertyFieldsetElements(Event $event): void
    {
        // For an example, see module Contribute (fully standard anyway).

        /**
         * // @var \Omeka\Form\ResourceTemplatePropertyFieldset $fieldset
         * @var \AdvancedResourceTemplate\Form\ResourceTemplatePropertyFieldset $fieldset
         * @var \AdvancedResourceTemplate\Form\ResourceTemplatePropertyDataFieldset $advancedFieldset
         */
        $fieldset = $event->getTarget();
        $advancedFieldset = $this->getServiceLocator()->get('FormElementManager')
            ->get(\AdvancedResourceTemplate\Form\ResourceTemplatePropertyDataFieldset::class)
            ->setName('advancedresourcetemplate_property');
        // The bug inside the fieldset for o:data implies to set elements at the root.
        // Anyway, it simplifies saving data.
        // $fieldset
        //     ->get('o:data')
        //     ->add($advancedFieldset);
        foreach ($advancedFieldset->getElements() as $element) {
            $fieldset->add($element);
        }
    }

    /**
     * Check if messages should be displayed to end user.
     *
     * Because the form doesn't contain the properties, that are added
     * dynamically, and because the resource controllers don't include the
     * stored messages from create/update events, error messages may be added
     * directly.
     *
     * @todo Include the check in the resource form. Add a fake hidden element? Or fix api plugin (the form is static in plugin api, so it is removed when called somewhere else)? For now, just js (issue is only on the min/max numbers of values).
     */
    protected function displayDirectMessage(): bool
    {
        /** @var \Omeka\Mvc\Status $status */
        $services = $this->getServiceLocator();
        $status = $services->get('Omeka\Status');
        $routeMatch = $status->getRouteMatch();
        // RouteMatch may be unavailable during background process.
        $routeName = $routeMatch ? $routeMatch->getMatchedRouteName() : null;
        // Module Contribute can use the error store so don't output issue here.
        return $routeName === 'admin/default'
            && in_array($routeMatch->getParam('__CONTROLLER__'), ['item', 'item-set', 'media', 'annotation'])
            && in_array($routeMatch->getParam('action'), ['add', 'edit']);
    }

    /**
     * Update open custom vocabs with new terms.
     */
    protected function updateCustomVocabOpen(Event $event): void
    {
        /** @var \Omeka\Entity\Resource $entity */
        $entity = $event->getParam('entity');

        /** @var \Omeka\Entity\ResourceTemplate $templateEntity */
        $templateEntity = $entity->getResourceTemplate();
        if (!$templateEntity) {
            return;
        }

        /**
         * @var \Omeka\Api\Adapter\AbstractResourceEntityAdapter $adapter
         * @var \AdvancedResourceTemplate\Api\Representation\ResourceTemplateRepresentation $template
         * @var \Omeka\Api\Request $request
         * @var \Omeka\Stdlib\ErrorStore $errorStore
         * @var \Omeka\Api\Representation\AbstractResourceEntityRepresentation $resource
         * @var \Omeka\Api\Manager $api
         * @var \Omeka\Mvc\Controller\Plugin\Messenger $messenger
         */
        $adapter = $event->getTarget();
        $template = $adapter->getAdapter('resource_templates')->getRepresentation($templateEntity);
        $resource = $adapter->getRepresentation($entity);

        // First, quick check if some custom vocabs are open.
        $hasCustomVocabOpen = false;
        foreach ($template->resourceTemplateProperties() as $templateProperty) {
            foreach ($templateProperty->data() as $rtpData) {
                if ($this->valueIsTrue($rtpData->dataValue('custom_vocab_open', false))) {
                    $hasCustomVocabOpen = true;
                    break 2;
                }
            }
        }

        if (!$hasCustomVocabOpen) {
            return;
        }

        $services = $this->getServiceLocator();
        $api = $services->get('Omeka\ApiManager');

        $customVocabs = [];

        // Only literal custom vocabs are managed for now, but no query.
        /** @var \CustomVocab\Api\Representation\CustomVocabRepresentation $customVocab */
        foreach ($api->search('custom_vocabs')->getContent() as $customVocab) {
            // CustomVocab v2.0.0 changed method names.
            $customVocabType = method_exists($customVocab, 'typeValues')
                ? $customVocab->typeValues()
                : $customVocab->type();
            if ($customVocabType === 'literal') {
                $id = $customVocab->id();
                $customVocabs['customvocab:' . $id] = [
                    'id' => $id,
                    'label' => $customVocab->label(),
                    'terms' => $customVocab->listValues(),
                    'new' => [],
                    'term' => null,
                ];
            }
        }
        if (!$customVocabs) {
            return;
        }

        foreach ($template->resourceTemplateProperties() as $templateProperty) {
            foreach ($templateProperty->data() as $rtpData) {
                if (!$this->valueIsTrue($rtpData->dataValue('custom_vocab_open', false))) {
                    continue;
                }
                $term = $templateProperty->property()->term();
                foreach ($resource->value($term, ['all' => true, 'type' => array_keys($customVocabs)]) as $value) {
                    $val = trim((string) $value->value());
                    $dataType = $value->type();
                    if (strlen($val) && !in_array($val, $customVocabs[$dataType]['terms'])) {
                        $customVocabs[$dataType]['term'] = $term;
                        $customVocabs[$dataType]['new'][] = $val;
                    }
                }
            }
        }

        /** @var \Omeka\Permissions\Acl $acl */
        $acl = $services->get('Omeka\Acl');
        $roles = $acl->getRoles();
        $acl
            ->allow(
                $roles,
                [\CustomVocab\Api\Adapter\CustomVocabAdapter::class],
                ['update']
            );

        $errorStore = $event->getParam('errorStore');
        $directMessage = $this->displayDirectMessage();
        $messenger = $directMessage ? $services->get('ControllerPluginManager')->get('messenger') : null;

        foreach ($customVocabs as $customVocab) {
            if (!$customVocab['new']) {
                continue;
            }
            // Update custom vocab.
            $newTerms = array_merge($customVocab['terms'], $customVocab['new']);
            try {
                $api->update('custom_vocabs', $customVocab['id'], ['o:terms' => $newTerms], [], ['isPartial' => true]);
                if ($directMessage) {
                    if (count($customVocab['new']) <= 1) {
                        $message = new \Omeka\Stdlib\Message(
                            'New descriptor appended to custom vocab "%1$s": %2$s', // @translate
                            $customVocab['label'], $customVocab['new']
                        );
                    } else {
                        $message = new \Omeka\Stdlib\Message(
                            'New descriptors appended to custom vocab "%1$s": %2$s', // @translate
                            $customVocab['label'], implode('", "', $customVocab['new'])
                        );
                    }
                    $messenger->addSuccess($message);
                }
            } catch (\Exception $e) {
                $message = new \Omeka\Stdlib\Message(
                    'Unable to append new descriptors to custom vocab "%1$s": %2$s', // @translate
                    $customVocab['label'], $e->getMessage()
                );
                $errorStore->addError($customVocab['term'], $message);
                if ($directMessage) {
                    $messenger->addError($message);
                }
            }
        }
    }

    protected function appendAutomaticValuesFromTemplateData(
        \AdvancedResourceTemplate\Api\Representation\ResourceTemplateRepresentation $template,
        array $resource
    ): array {
        $automaticValues = trim((string) $template->dataValue('automatic_values'));
        if ($automaticValues === '') {
            return $resource;
        }

        $mapping = $this->stringToAutofillers("[automatic_values]\n$automaticValues");
        if (!$mapping || !$mapping['automatic_values']['mapping']) {
            return $resource;
        }

        /**
         * @var array $customVocabBaseTypes
         * @var \AdvancedResourceTemplate\Mvc\Controller\Plugin\ArtMapper $mapper
         */
        $services = $this->getServiceLocator();
        $customVocabBaseTypes = $services->get('ViewHelperManager')->get('customVocabBaseType')();
        $mapper = $services->get('ControllerPluginManager')->get('artMapper');

        $newResourceData = $mapper
            ->setMapping($mapping['automatic_values']['mapping'])
            ->setIsSimpleExtract(false)
            ->setIsInternalSource(true)
            ->array($resource);

        // Append only new data.
        foreach ($newResourceData as $term => $newValues) {
            foreach ($newValues as $newValue) {
                $dataType = $newValue['type'];
                $dataTypeColon = strtok($dataType, ':');
                $baseType = $dataTypeColon === 'customvocab' ? $customVocabBaseTypes[(int) substr($dataType, 12)] ?? 'literal' : null;
                switch ($dataType) {
                    case $dataTypeColon === 'resource':
                    case $baseType === 'resource':
                        $check = [
                            'type' => $dataType,
                            'value_resource_id' => (int) $newValue['value_resource_id'],
                        ];
                        break;
                    case 'uri':
                    case $dataTypeColon === 'valuesuggest':
                    case $dataTypeColon === 'valuesuggestall':
                    case $baseType === 'uri':
                        $check = array_intersect_key($newValue, ['type' => null, '@id' => null]);
                        break;
                    case 'literal':
                    // case $baseType === 'literal':
                    default:
                        $check = array_intersect_key($newValue, ['type' => null, '@value' => null]);
                        break;
                }
                ksort($check);
                foreach ($resource[$term] ?? [] as $value) {
                    $checkValue = array_intersect_key($value, $check);
                    if (isset($checkValue['value_resource_id'])) {
                        $checkValue['value_resource_id'] = (int) $checkValue['value_resource_id'];
                    }
                    ksort($checkValue);
                    if ($check === $checkValue) {
                        continue 2;
                    }
                }
                $resource[$term][] = $newValue;
            }
        }

        return $resource;
    }

    protected function explodeValueFromTemplatePropertyData(
        \AdvancedResourceTemplate\Api\Representation\ResourceTemplatePropertyDataRepresentation $rtpData,
        array $resource
    ): array {
        // Explode value requires a literal value.
        if ($rtpData->dataType() !== 'literal') {
            return $resource;
        }

        $separator = trim((string) $rtpData->dataValue('split_separator'));
        if ($separator === '') {
            return $resource;
        }

        $term = $rtpData->property()->term();
        if (!isset($resource[$term])) {
            return $resource;
        }

        // Check for literal value and explode when possible.
        $result = [];
        foreach ($resource[$term] as $value) {
            if ($value['type'] !== 'literal' || !isset($value['@value'])) {
                $result[] = $value;
                continue;
            }
            foreach (array_filter(array_map('trim', explode($separator, $value['@value'])), 'strlen') as $val) {
                $v = $value;
                $v['@value'] = $val;
                $result[] = $v;
            }
        }
        $resource[$term] = $result;

        return $resource;
    }

    protected function automaticValueFromTemplatePropertyData(
        \AdvancedResourceTemplate\Api\Representation\ResourceTemplatePropertyDataRepresentation $rtpData,
        array $resource
    ): ?array {
        $automaticValue = trim((string) $rtpData->dataValue('automatic_value'));
        if ($automaticValue === '') {
            return null;
        }

        $property = $rtpData->property();
        return $this->appendAutomaticPropertyValueToResource($resource, [
            'data_types' => $rtpData->dataTypes(),
            'is_public' => !$rtpData->isPrivate(),
            'term' => $property->term(),
            'property_id' => $property->id(),
            'value' => $automaticValue,
        ]);
    }

    protected function appendAutomaticPropertyValueToResource(
        array $resource,
        ?array $map
    ): ?array {
        if (empty($map) || empty($map['property_id'])) {
            return null;
        }

        $term = $map['term'];
        $propertyId = $map['property_id'];
        $automaticValue = $map['value'];
        $dataTypes = $map['data_types'];
        $isPublic = $map['is_public'] ?? true;
        // Use the first data type by default.
        $dataType = count($dataTypes) ? reset($dataTypes) : 'literal';

        /**
         * @var \Omeka\Api\Manager $api
         * @var array $customVocabBaseTypes
         * @var \AdvancedResourceTemplate\Mvc\Controller\Plugin\FieldNameToProperty $fieldNameToProperty
         * @var \AdvancedResourceTemplate\Mvc\Controller\Plugin\ArtMapper $mapper
         */
        $services = $this->getServiceLocator();
        $api = $services->get('Omeka\ApiManager');
        $customVocabBaseTypes = $services->get('ViewHelperManager')->get('customVocabBaseType')();
        $fieldNameToProperty = $services->get('ControllerPluginManager')->get('fieldNameToProperty');
        $mapper = $services->get('ControllerPluginManager')->get('artMapper');

        // TODO Use mapper metaMapper from module Bulk Import (json dot notation or jmespath + basic twig).

        // Only the main rdf data is checked for transformation.

        $automaticValueArray = json_decode($automaticValue, true);
        if (is_array($automaticValueArray)) {
            if (empty($automaticValueArray['type'])) {
                $automaticValueArray['type'] = $dataType;
            } else {
                // Check validity of the data type.
                /** @var \Omeka\DataType\Manager $dataTypeManager */
                $dataTypeManager = $this->getServiceLocator()->get('Omeka\DataTypeManager');
                if (!$dataTypeManager->has($automaticValueArray['type'])) {
                    return null;
                }
                if ($dataTypes && !in_array($automaticValueArray['type'], $dataTypes)) {
                    return null;
                }
            }
            // Check the validity of the data with the data type.
            $dataTypeColon = strtok($automaticValueArray['type'], ':');
            $baseType = $dataTypeColon === 'customvocab' ? $customVocabBaseTypes[(int) substr($automaticValueArray['type'], 12)] ?? 'literal' : null;

            switch ($automaticValueArray['type']) {
                case $dataTypeColon === 'resource':
                case $baseType === 'resource':
                    if (empty($automaticValue['value_resource_id'])) {
                        return null;
                    }

                    $to = "$term ^^{$automaticValueArray['type']} ~ {$automaticValue['value_resource_id']}";
                    $to = $fieldNameToProperty($to);
                    if (!$to) {
                        return null;
                    }
                    $automaticValue['value_resource_id'] = (int) $mapper
                        ->setMapping([])
                        ->setIsSimpleExtract(false)
                        ->setIsInternalSource(true)
                        ->extractValueOnly($resource, ['from' => '~', 'to' => $to]);

                    // Check the value.
                    try {
                        $api->read('resources', ['id' => $automaticValue['value_resource_id']], ['initialize' => false, 'finalize' => false]);
                    } catch (\Exception $e) {
                        return null;
                    }
                    $check = array_intersect_key($automaticValueArray, ['type' => null, 'value_resource_id' => null]);
                    break;
                case 'uri':
                case $dataTypeColon === 'valuesuggest':
                case $dataTypeColon === 'valuesuggestall':
                case $baseType === 'uri':
                    if (empty($automaticValue['@id'])) {
                        return null;
                    }

                    $to = "$term ^^{$automaticValueArray['type']} ~ {$automaticValue['@id']}";
                    $to = $fieldNameToProperty($to);
                    if (!$to) {
                        return null;
                    }
                    $automaticValue['@id'] = $mapper
                        ->setMapping([])
                        ->setIsSimpleExtract(false)
                        ->setIsInternalSource(true)
                        ->extractValueOnly($resource, ['from' => '~', 'to' => $to]);

                    $check = array_intersect_key($automaticValueArray, ['type' => null, '@id' => null]);
                    break;
                case 'literal':
                // case $baseType === 'literal':
                default:
                    if (!isset($automaticValueArray['@value']) || !strlen((string) $automaticValueArray['@value'])) {
                        return null;
                    }

                    $to = "$term ^^{$automaticValueArray['type']} ~ {$automaticValue['@value']}";
                    $to = $fieldNameToProperty($to);
                    if (!$to) {
                        return null;
                    }
                    $automaticValue['@value'] = $mapper
                        ->setMapping([])
                        ->setIsSimpleExtract(false)
                        ->setIsInternalSource(true)
                        ->extractValueOnly($resource, ['from' => '~', 'to' => $to]);

                    $check = array_intersect_key($automaticValueArray, ['type' => null, '@value' => null]);
                    break;
            }
        } else {
            $dataTypeColon = strtok($dataType, ':');
            $baseType = $dataTypeColon === 'customvocab' ? $customVocabBaseTypes[(int) substr($dataType, 12)] ?? 'literal' : null;

            $to = "$term ^^$dataType ~ $automaticValue";
            $to = $fieldNameToProperty($to);
            if (!$to) {
                return null;
            }
            $automaticValueTransformed = $mapper
                ->setMapping([])
                ->setIsSimpleExtract(false)
                ->setIsInternalSource(true)
                ->extractValueOnly($resource, ['from' => '~', 'to' => $to]);

            switch ($dataType) {
                case $dataTypeColon === 'resource':
                case $baseType === 'resource':
                    // Check the value.
                    $automaticValueTransformed = (int) $automaticValueTransformed;
                    try {
                        $api->read('resources', ['id' => $automaticValueTransformed], ['initialize' => false, 'finalize' => false]);
                    } catch (\Exception $e) {
                        return null;
                    }
                    $automaticValueArray = [
                        'type' => $dataType,
                        'value_resource_id' => $automaticValueTransformed,
                    ];
                    break;
                case 'uri':
                case $dataTypeColon === 'valuesuggest':
                case $dataTypeColon === 'valuesuggestall':
                case $baseType === 'uri':
                    $automaticValueArray = [
                        'type' => $dataType,
                        '@id' => $automaticValueTransformed,
                    ];
                    break;
                case 'literal':
                // case $baseType === 'literal':
                default:
                    $automaticValueArray = [
                        'type' => $dataType,
                        '@value' => $automaticValueTransformed,
                    ];
                    break;
            }
            $check = $automaticValueArray;
        }

        // Check if the value is already set on the main value data.
        ksort($check);
        foreach ($resource[$term] ?? [] as $value) {
            $checkValue = array_intersect_key($value, $check);
            if (isset($checkValue['value_resource_id'])) {
                $checkValue['value_resource_id'] = (int) $checkValue['value_resource_id'];
            }
            ksort($checkValue);
            if ($check === $checkValue) {
                return null;
            }
        }

        // The value does not exist, so return it.
        return ['property_id' => $propertyId]
            + $automaticValueArray
            + ['is_public' => $isPublic];
    }

    protected function getRouteResourceName(?Status $status = null): ?string
    {
        if (!$status) {
            /** @var \Omeka\Mvc\Status $status */
            $services = $this->getServiceLocator();
            $status = $services->get('Omeka\Status');
        }

        // Limit resource templates to the current resource type.
        // The resource type can be known only via the route.
        $controllerToResourceNames = [
            'Omeka\Controller\Admin\Item' => 'items',
            'Omeka\Controller\Admin\Media' => 'media',
            'Omeka\Controller\Admin\ItemSet' => 'item_sets',
            'Omeka\Controller\Site\Item' => 'items',
            'Omeka\Controller\Site\Media' => 'media',
            'Omeka\Controller\Site\ItemSet' => 'item_sets',
            'item' => 'items',
            'media' => 'media',
            'item-set' => 'item_sets',
            'items' => 'items',
            'itemset' => 'item_sets',
            'item_sets' => 'item_sets',
            // Module Annotate.
            'Annotate\Controller\Admin\Annotation' => 'annotations',
            'Annotate\Controller\Site\Annotation' => 'annotations',
            'annotation' => 'annotations',
            'annotations' => 'annotations',
        ];
        $params = $status->getRouteMatch()->getParams();
        $controller = $params['controller'] ?? $params['__CONTROLLER__'] ?? null;

        return $controllerToResourceNames[$controller] ?? null;
    }

    /**
     * Get a property id by JSON-LD term or by numeric id.
     *
     * @param int|string|null $termsOrId An id or a term.
     */
    protected function getPropertyId($termOrId): ?int
    {
        if ($this->propertiesByTermsAndIds === null) {
            $this->prepareProperties();
        }
        return $termOrId
            ? $this->propertiesByTermsAndIds[$termOrId] ?? null
            : null;
    }

    /**
     * Get a property JSON-LD term or by JSON-LD term or numeric id.
     *
     * @param int|string|null $termsOrId An id or a term.
     */
    protected function getPropertyTerm($termOrId): ?string
    {
        if ($this->propertiesByTermsAndIds === null) {
            $this->prepareProperties();
        }
        return $termOrId && !empty($this->propertiesByTermsAndIds[$termOrId])
            ? array_search($this->propertiesByTermsAndIds[$termOrId], $this->propertiesByTerms)
            : null;
    }

    /**
     * Get property ids by JSON-LD terms or by numeric ids.
     *
     * @param array|int|string|null $termsOrIds One or multiple ids or terms.
     * @return int[] The property ids matching terms or ids, or all properties
     * by term. Order of input is kept.
     *
     * Replace feature in the adapter to get the id, that is heavy, because most
     * of the time only the property id is needed.
     * @see \Omeka\Api\Adapter\AbstractResourceEntityAdapter::isTerm()
     * @see \Omeka\Api\Adapter\AbstractResourceEntityAdapter::getPropertyByTerm()
     *
     * @see \AdvancedResourceTemplate\Module::getPropertyIds()
     */
    protected function getPropertyIds($termsOrIds = null): array
    {
        if ($this->propertiesByTermsAndIds === null) {
            $this->prepareProperties();
        }

        if ($termsOrIds === null) {
            return $this->propertiesByTerms;
        }

        if (is_scalar($termsOrIds)) {
            return isset($this->propertiesByTermsAndIds[$termsOrIds])
                ? [$termsOrIds => $this->propertiesByTermsAndIds[$termsOrIds]]
                : [];
        }

        // Keep original order of ids.
        // return array_intersect_key($propertiesByTermsAndIds, array_flip($termsOrIds));
        $input = array_fill_keys($termsOrIds, null);
        return array_filter(array_replace($input, array_intersect_key($this->propertiesByTermsAndIds, $input)));
    }

    /**
     * Get property JSON-LD terms by JSON-LD terms or by numeric ids.
     *
     * @param array|int|string|null $termsOrIds One or multiple ids or terms.
     * @return string[] The property terms matching terms or ids, or all
     * properties by id. Order of input is kept.
     */
    protected function getPropertyTerms($termsOrIds = null): array
    {
        if ($this->propertiesByTermsAndIds === null) {
            $this->prepareProperties();
        }

        if ($termsOrIds === null) {
            return array_flip($this->propertiesByTerms);
        }

        if (is_scalar($termsOrIds)) {
            return isset($this->propertiesByTermsAndIds[$termsOrIds])
                ? [$termsOrIds => array_search($this->propertiesByTermsAndIds[$termsOrIds], $this->propertiesByTerms)]
                : [];
        }

        // TODO Should table of property terms by terms and ids be stored? Not use often.
        $propertyTermsByTermsAndIds = array_combine(array_keys($this->propertiesByTerms), array_keys($this->propertiesByTerms))
            + array_flip($this->propertiesByTerms);

        // Keep original order of ids.
        $input = array_fill_keys($termsOrIds, null);
        return array_filter(array_replace($input, array_intersect_key($propertyTermsByTermsAndIds, $input)));
    }

    /**
     * Store properties ids and terms one time.
     */
    protected function prepareProperties(): void
    {
        if ($this->propertiesByTermsAndIds !== null) {
            return;
        }

        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = $this->getServiceLocator()->get('Omeka\Connection');
        $qb = $connection->createQueryBuilder();
        $qb
            ->select(
                'DISTINCT CONCAT(vocabulary.prefix, ":", property.local_name) AS term',
                'property.id AS id',
                // Required with only_full_group_by.
                'vocabulary.id',
                'property.id'
            )
            ->from('property', 'property')
            ->innerJoin('property', 'vocabulary', 'vocabulary', 'property.vocabulary_id = vocabulary.id')
            ->orderBy('vocabulary.id', 'asc')
            ->addOrderBy('property.id', 'asc')
        ;
        $this->propertiesByTerms = array_map('intval', $connection->executeQuery($qb)->fetchAllKeyValue());
        $this->propertiesByTermsAndIds = array_replace($this->propertiesByTerms, array_combine($this->propertiesByTerms, $this->propertiesByTerms));
    }

    /**
     * Store some settings of ressource templates in settngs for easier process.
     *
     * Instead of multiplying columns in the database table resource_template_data,
     * some settings are managed differently for now.
     */
    protected function storeResourceTemplateSettings(): void
    {
        // Resource templates can be searched only by id or by label, not data,
        // but they should be searched by option "use_for_resources" in many
        // places, so it is stored in main settings too.
        // TODO To store the options for available templates by resource is possible, but probably useless.

        /**
         * @var \Doctrine\DBAL\Connection $connection
         * @var \Omeka\Settings\Settings $settings
         */
        $services = $this->getServiceLocator();
        $connection = $services->get('Omeka\Connection');
        $settings = $services->get('Omeka\Settings');

        // The connection is required because the module entities are not
        // available during upgrade.

        // Since data are json, it's hard to extract them with mysql < 8, so
        // process here.
        $qb = $connection->createQueryBuilder();
        $qb
            ->select(
                'resource_template.id',
                'resource_template_data.data',
            )
            ->from('resource_template')
            ->leftJoin('resource_template', 'resource_template_data', 'resource_template_data', 'resource_template_data.resource_template_id = resource_template.id')
        ;
        $templatesData = $connection->executeQuery($qb)->fetchAllKeyValue();
        $templatesByResourceNames = [
            'items' => [],
            'media' => [],
            'item_sets' => [],
            'value_annotations' => [],
            // Module Annotate.
            'annotations' => [],
        ];
        foreach ($templatesData as $templateId => $templateData) {
            $templateId = (int) $templateId;
            $templateData = $templateData ? json_decode($templateData, true) : null;
            if ($templateData === null
                // When null or empty array, the template is not used.
                || !array_key_exists('use_for_resources', $templateData)
            ) {
                $templatesByResourceNames['items'][] = $templateId;
                $templatesByResourceNames['media'][] = $templateId;
                $templatesByResourceNames['item_sets'][] = $templateId;
                $templatesByResourceNames['value_annotations'][] = $templateId;
                $templatesByResourceNames['annotations'][] = $templateId;
            } elseif (is_array($templateData['use_for_resources'])) {
                foreach ($templateData['use_for_resources'] as $resourceName) {
                    $templatesByResourceNames[$resourceName][] = $templateId;
                }
            }
        }
        $settings->set('advancedresourcetemplate_templates_by_resource', $templatesByResourceNames);
    }

    protected function autofillersToString($autofillers)
    {
        if (is_string($autofillers)) {
            return $autofillers;
        }

        $result = '';
        foreach ($autofillers as $key => $autofiller) {
            $label = empty($autofiller['label']) ? '' : $autofiller['label'];
            $result .= $label ? "[$key] = $label\n" : "[$key]\n";
            if (!empty($autofiller['url'])) {
                $result .= $autofiller['url'] . "\n";
            }
            if (!empty($autofiller['query'])) {
                $result .= '?' . $autofiller['query'] . "\n";
            }
            if (!empty($autofiller['mapping'])) {
                // For generic resource, display the label and the list first.
                $mapping = $autofiller['mapping'];
                foreach ($autofiller['mapping'] as $key => $map) {
                    if (isset($map['to']['pattern'])
                        && in_array($map['to']['pattern'], ['{__label__}', '{list}'])
                    ) {
                        unset($mapping[$key]);
                        unset($map['to']['pattern']);
                        $mapping = [$key => $map] + $mapping;
                    }
                }
                $autofiller['mapping'] = $mapping;
                foreach ($autofiller['mapping'] as $map) {
                    $to = &$map['to'];
                    if (!empty($map['from'])) {
                        $result .= $map['from'];
                    }
                    $result .= ' = ';
                    if (!empty($to['field'])) {
                        $result .= $to['field'];
                    }
                    if (!empty($to['type'])) {
                        $result .= ' ^^' . $to['type'];
                    }
                    if (!empty($to['@language'])) {
                        $result .= ' @' . $to['@language'];
                    }
                    if (!empty($to['is_public'])) {
                        $result .= ' §' . ($to['is_public'] === 'private' ? 'private' : 'public');
                    }
                    if (!empty($to['pattern'])) {
                        $result .= ' ~ ' . $to['pattern'];
                    }
                    $result .= "\n";
                }
            }
            $result .= "\n";
        }

        return mb_substr($result, 0, -1);
    }

    public function stringToAutofillers($string)
    {
        if (is_array($string)) {
            return $string;
        }

        /** @var \AdvancedResourceTemplate\Mvc\Controller\Plugin\FieldNameToProperty $fieldNameToProperty */
        $fieldNameToProperty = $this->getServiceLocator()->get('ControllerPluginManager')->get('fieldNameToProperty');

        $result = [];
        $lines = $this->stringToList($string);
        $matches = [];
        $autofillerKey = null;
        foreach ($lines as $line) {
            // Start a new autofiller.
            $first = mb_substr($line, 0, 1);
            if ($first === '[') {
                preg_match('~^\[\s*(?<service>[a-zA-Z][\w-]*)\s*(?:\:\s*(?<sub>[a-zA-Z][a-zA-Z0-9:]*))?\s*(?:#\s*(?<variant>[^\]]+))?\s*\]\s*(?:=?\s*(?<label>.*))$~', $line, $matches);
                if (empty($matches['service'])) {
                    continue;
                }
                $autofillerKey = $matches['service']
                    . (empty($matches['sub']) ? '' : ':' . $matches['sub'])
                    . (empty($matches['variant']) ? '' : ' #' . $matches['variant']);
                $result[$autofillerKey] = [
                    'service' => $matches['service'],
                    'sub' => $matches['sub'],
                    'label' => empty($matches['label']) ? null : $matches['label'],
                    'mapping' => [],
                ];
            } elseif (!$autofillerKey) {
                // Nothing.
            } elseif ($first === '?') {
                $result[$autofillerKey]['query'] = mb_substr($line, 1);
            } elseif (mb_strpos($line, 'https://') === 0 || mb_strpos($line, 'http://') === 0) {
                $result[$autofillerKey]['url'] = $line;
            } else {
                // Fill a map of an autofiller.
                $pos = $first === '~'
                    ? mb_strpos($line, '=')
                    : mb_strrpos(strtok($line, '~'), '=');
                $from = trim(mb_substr($line, 0, $pos));
                $to = trim(mb_substr($line, $pos + 1));
                if (!$from || !$to) {
                    continue;
                }
                $to = $fieldNameToProperty($to);
                if (!$to) {
                    continue;
                }
                $result[$autofillerKey]['mapping'][] = [
                    'from' => $from,
                    'to' => array_filter($to, function ($v) {
                        return !is_null($v);
                    }),
                ];
            }
        }
        return $result;
    }

    /**
     * Get each line of a string separately.
     */
    public function stringToList($string): array
    {
        return array_filter(array_map('trim', explode("\n", $this->fixEndOfLine($string))), 'strlen');
    }

    /**
     * Clean the text area from end of lines.
     *
     * This method fixes Windows and Apple copy/paste from a textarea input.
     */
    public function fixEndOfLine($string): string
    {
        return str_replace(["\r\n", "\n\r", "\r"], ["\n", "\n", "\n"], (string) $string);
    }

    /**
     * Check if a value is true (true, 1, "1", "true", yes", "on").
     *
     * This function avoids issues with values stored directly or with a form.
     * A value can be neither true or false.
     */
    protected function valueIsTrue($value): bool
    {
        return in_array($value, [true, 1, '1', 'true', 'yes', 'on'], true);
    }

    /**
     * Check if a value is false (false, 0, "0", "false", "no", "off").
     *
     * This function avoids issues with values stored directly or with a form.
     * A value can be neither true or false.
     */
    protected function valueIsFalse($value): bool
    {
        return in_array($value, [false, 0, '0', 'false', 'no', 'off'], true);
    }
}
