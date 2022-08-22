<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\Form;

use Laminas\Form\Form;

class ResourceTemplateImportForm extends Form
{
    public function init(): void
    {
        $this->add([
            'name' => 'file',
            'type' => 'file',
            'options' => [
                'label' => 'Resource template file (json, csv, tsv)', // @translate
            ],
            'attributes' => [
                'required' => true,
                'id' => 'file',
            ],
        ]);
    }
}
