<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\View\Helper;

use Laminas\Form\FormElementManager\FormElementManagerV3Polyfill as FormElementManager;
use Omeka\DataType\Manager as DataTypeManager;

/**
 * View helper for rendering data types.
 */
class DataType extends \Omeka\View\Helper\DataType
{
    /**
     * @param FormElementManager
     */
    protected $formElementManager;

    public function __construct(DataTypeManager $dataTypeManager, FormElementManager $formElementManager)
    {
        $this->manager = $dataTypeManager;
        $this->dataTypes = $this->manager->getRegisteredNames();
        $this->formElementManager = $formElementManager;
    }

    public function getSelect($name, $value = null, $attributes = [])
    {
        $element = $this->formElementManager->get(\AdvancedResourceTemplate\Form\Element\DataTypeSelect::class);
        $element
            ->setName($name)
            ->setEmptyOption('Default')
            ->setAttributes($attributes);
        if (!$element->getAttribute('multiple') && is_array($value)) {
            $value = reset($value);
        }
        $element->setValue($value);
        // Fix an issue with chosen select.
        if (isset($attributes['class']) && strpos($attributes['class'], 'chosen-select') !== false) {
            $element->setEmptyOption('');
        }
        return $this->getView()->formSelect($element);
    }

    public function getTemplates()
    {
        $view = $this->getView();
        $templates = '';
        $resource = isset($view->resource) ? $view->resource : null;
        $partial = $view->plugin('partial');
        foreach ($this->dataTypes as $dataType) {
            $templates .= $partial('common/data-type-wrapper', [
                'dataType' => $dataType,
                'resource' => $resource,
            ]);
        }
        return $templates;
    }
}
