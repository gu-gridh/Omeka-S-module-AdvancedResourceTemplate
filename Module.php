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
            'Omeka\Controller\Admin\Annotation',
            'view.layout',
            [$this, 'addAdminResourceHeaders']
        );
        $sharedEventManager->attach(
            \Article\Controller\Admin\ArticleController::class,
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
        // creation/update. So simply add the value if not present.
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

    public function addAdminResourceHeaders(Event $event): void
    {
        $view = $event->getTarget();

        $action = $view->params()->fromRoute('action');
        if (!in_array($action, ['add', 'edit'])) {
            return;
        }

        $isModal = $view->params()->fromQuery('window') === 'modal';
        if ($isModal) {
            $view->htmlElement('body')->appendAttribute('class', 'modal');
        }

        $assetUrl = $view->getHelperPluginManager()->get('assetUrl');
        $view->headLink()->appendStylesheet($assetUrl('css/advanced-resource-template-admin.css', 'AdvancedResourceTemplate'));
        $view->headScript()
            ->appendScript(sprintf('var baseUrl = %s;', json_encode($view->basePath('/'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)))
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

        // TODO Use mapper transformSource from module Bulk Import (json dot notation or jmespath + basic twig).

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
