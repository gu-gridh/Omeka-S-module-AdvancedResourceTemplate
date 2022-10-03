<?php declare(strict_types=1);

namespace AdvancedResourceTemplate;

if (!class_exists(\Generic\AbstractModule::class)) {
    require file_exists(dirname(__DIR__) . '/Generic/AbstractModule.php')
        ? dirname(__DIR__) . '/Generic/AbstractModule.php'
        : __DIR__ . '/src/Generic/AbstractModule.php';
}

use AdvancedResourceTemplate\Api\Representation\ResourceTemplateRepresentation;
use Generic\AbstractModule;
use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Mvc\MvcEvent;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;

class Module extends AbstractModule
{
    const NAMESPACE = __NAMESPACE__;

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

        // Display values according to options of the resource template.
        // For compatibility with other modules (HideProperties, Internationalisation)
        // that use the term as key in the list of displayed values, the event
        // should be triggered lastly.
        $sharedEventManager->attach(
            \Omeka\Api\Representation\ItemRepresentation::class,
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
            \Omeka\Api\Representation\MediaRepresentation::class,
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
        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');
        if ($settings->get('advancedresourcetemplate_skip_checks')) {
            return;
        }

        /** @var \Omeka\Entity\Resource $entity */
        $entity = $event->getParam('entity');

        /** @var \Omeka\Entity\ResourceTemplate $templateEntity */
        $templateEntity = $entity->getResourceTemplate();
        if (!$templateEntity) {
            return;
        }

        /** @var \Omeka\Api\Adapter\AbstractResourceEntityAdapter $adapter */
        $adapter = $event->getTarget();

        /** @var \AdvancedResourceTemplate\Api\Representation\ResourceTemplateRepresentation $template */
        $template = $adapter->getAdapter('resource_templates')->getRepresentation($templateEntity);

        /** @var \Omeka\Api\Request $request */
        // $request = $event->getParam('request');

        /** @var \Omeka\Stdlib\ErrorStore $errorStore */
        $errorStore = $event->getParam('errorStore');

        // Because the form doesn't contain the properties, that are added
        // dynamically, and because the resource controllers don't include the
        // stored messages from create/update events, error messages may be
        // added directly.
        // TODO Include the check in the resource form. Add a fake hidden element? Or fix api plugin (the form is static in plugin api, so it is removed when called somewhere else)? For now, just js (issue is only on the min/max numbers of values).
        /** @var \Omeka\Mvc\Status $status */
        $status = $services->get('Omeka\Status');
        $routeMatch = $status->getRouteMatch();
        // RouteMatch may be unavailable during background process.
        $routeName = $routeMatch ? $routeMatch->getMatchedRouteName() : null;
        // Module Contribute can use the error store, so no issue here.
        $directMessage = $routeName === 'admin/default'
            && in_array($routeMatch->getParam('__CONTROLLER__'), ['item', 'item-set', 'media', 'annotation'])
            && in_array($routeMatch->getParam('action'), ['add', 'edit']);
        $messenger = $directMessage ? new \Omeka\Mvc\Controller\Plugin\Messenger() : null;

        // Template level.
        $resourceClass = $entity->getResourceClass();
        $requireClass = $template->dataValue('require_resource_class') === 'yes';
        if ($requireClass && !$resourceClass) {
            $message = new \Omeka\Stdlib\Message('A class is required.'); // @translate
            $errorStore->addError('o:resource_class', $message);
            if ($directMessage) {
                $messenger->addError($message);
            }
        }
        $closedClassList = $template->dataValue('closed_class_list') === 'yes';
        if ($closedClassList && $resourceClass) {
            $suggestedClasses = $template->dataValue('suggested_resource_class_ids', []);
            if ($suggestedClasses && !in_array($resourceClass->getId(), $suggestedClasses)) {
                if (count($suggestedClasses) === 1) {
                    $message = new \Omeka\Stdlib\Message(
                        'The class should be "%s".', // @translate
                        key($suggestedClasses)
                    );
                    $errorStore->addError('o:resource_class', $message);
                    if ($directMessage) {
                        $messenger->addError($message);
                    }
                } else {
                    $message = new \Omeka\Stdlib\Message(
                        'The class should be one of "%s".', // @translate
                        implode('", "', array_keys($suggestedClasses))
                    );
                    $errorStore->addError('o:resource_class', $message);
                    if ($directMessage) {
                        $messenger->addError($message);
                    }
                }
            }
        }

        // TODO Manage closed property list: but good data can be added via modules (identifier, etc.).

        // Some checks can be done simpler via representation.
        /** @var \Omeka\Api\Representation\AbstractResourceEntityRepresentation $resource */
        $resource = $adapter->getRepresentation($entity);

        // Property level.
        foreach ($template->resourceTemplateProperties() as $templateProperty) {
            foreach ($templateProperty->data() as $rtpData) {
                $term = $templateProperty->property()->term();
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

                // TODO Fix api($form) to manage the minimum number of values in admin resource form.
                if ($directMessage) {
                    continue;
                }

                $minValues = (int) $rtpData->dataValue('min_values');
                $maxValues = (int) $rtpData->dataValue('max_values');
                if ($minValues || $maxValues) {
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

                // TODO Check language (but they are suggested languages).
            }
        }
    }

    /**
     * Prepare specific data to display the list of the resource values data.
     *
     * Specific data passed to display values for this module are:
     * - resource: added to each property to simplify view template because it
     *   is not passed by default
     * - duplicated properties with a specific label and comments
     * - groups of properties, managed in overridden view template resource-values
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
        if ($template) {
            $groups = $template->dataValue('groups', []);
            $templateProperties = $template->resourceTemplateProperties();
        } else {
            $groups = [];
            $templateProperties = [];
        }

        $newValues = count($templateProperties)
            ? $this->prepareResourceAndGroupsValues($resource, $templateProperties, $values, $groups)
            : $this->prependResourceAndGroupsToValues($resource, $values, $groups);

        $event->setParam('values', $newValues);
    }

    /**
     * Prepend keys "resouce" and "group" to display values.
     *
     * Warning: Duplicate properties are not managed here.
     */
    protected function prependResourceAndGroupsToValues(
        AbstractResourceEntityRepresentation $resource,
        array $values,
        array $groups
    ): array {
        if (!$groups) {
            foreach ($values as $term => &$propertyData) {
                $propertyData = [
                    'resource' => $resource,
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
                'resource' => $resource,
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
     * In that case, modify the key "term" as term + index, and update label and
     * comment, so the default template "common/resource-values" will be able to
     * display them as standard ones.
     *
     * @see \Omeka\Api\Representation\AbstractResourceEntityRepresentation::values()
     * @see \Omeka\Api\Representation\AbstractResourceEntityRepresentation::displayValues()
     */
    protected function prepareResourceAndGroupsValues(
        AbstractResourceEntityRepresentation $resource,
        array $templateProperties,
        array $values,
        array $groups
    ): array {
        // The process should take care of values appended to a resource that
        // have a data type that is not specified in template properties, in
        // particular the default ones (literal, resource, uri). It may fix bad
        // imports too, or resources with a template that was updated later.

        $services = $this->getServiceLocator();
        $translate = $services->get('ViewHelperManager')->get('translate');

        // TODO Check if this process can be simplified (three double loops, even if loops are small and for one resource a time).

        // The alternate comments are included too, even if they are not
        // displayed in the default resource template.

        // Check and prepare values when a property have multiple labels.
        $labelsAndComments = [];
        $hasMultipleLabels = false;
        foreach ($templateProperties as $rtp) {
            $property = $rtp->property();
            $term = $property->term();
            $labelsAndComments[$term] = $rtp->labelsAndCommentsByDataType();
            $hasMultipleLabels = $hasMultipleLabels
                || count($rtp->labels()) > 1;
        }

        if (!$hasMultipleLabels) {
            return $this->prependResourceAndGroupsToValues($resource, $values, $groups);
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
                    ?? $translate($property->label());
                $valuesWithLabel[$term][$dataTypeLabel]['values'][] = $value;
                $dataTypesLabelsToComments[$dataTypeLabel] = $labelsAndComments[$term][$dataType]['comment']
                    ?? $labelsAndComments[$term]['default']['comment']
                    ?? $translate($property->comment());
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
                'resource' => $resource,
                'group' => $currentGroup,
                'term' => $term,
            ] + $propertyData;
        }
        unset($propertyData);

        $newValues = [];
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
                unset($propertyData['values']);
                $propertyData['resource'] = $resource;
                $propertyData['group'] = $currentGroup;
                $propertyData['term'] = $term;
                $propertyData['term_label'] = $termLabel;
                $propertyData['property'] = $values[$term]['property'];
                $propertyData['alternate_label'] = $dataTypeLabel;
                $propertyData['alternate_comment'] = $dataTypesLabelsToComments[$dataTypeLabel];
                $propertyData['values'] = $valuesWithLabel[$term][$dataTypeLabel]['values'];
                $newValues[$termLabel] = $propertyData;
            }
        }

        return $newValues;
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
        $closedPropertyList = (bool) (int) $settings->get('advancedresourcetemplate_closed_property_list');
        if (!$closedPropertyList) {
            return;
        }

        /** @var \Omeka\Form\ResourceForm $form */
        $form = $event->getTarget();
        $form->setAttribute('class', trim($form->getAttribute('class') . ' closed-property-list on-load'));
    }

    public function handleMainSettings(Event $event): void
    {
        parent::handleMainSettings($event);

        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');

        $autofillers = $settings->get('advancedresourcetemplate_autofillers') ?: [];
        $value = $this->autofillersToString($autofillers);

        $event
            ->getTarget()
            ->get('advancedresourcetemplate')
            ->get('advancedresourcetemplate_autofillers')
            ->setValue($value);
    }

    public function handleMainSettingsFilters(Event $event): void
    {
        $event->getParam('inputFilter')
            ->get('advancedresourcetemplate')
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
}
