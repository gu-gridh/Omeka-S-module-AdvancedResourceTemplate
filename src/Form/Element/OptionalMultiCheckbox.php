<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\Form\Element;

use Laminas\Form\Element\MultiCheckbox;

class OptionalMultiCheckbox extends MultiCheckbox
{
    use TraitOptionalElement;
}
