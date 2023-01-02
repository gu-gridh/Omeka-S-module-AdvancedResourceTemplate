<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\Form\Element;

use Laminas\Form\Element\Textarea;
use Laminas\InputFilter\InputProviderInterface;

class GroupTextarea extends Textarea implements InputProviderInterface
{
    public function setValue($value)
    {
        $this->value = $this->arrayToString($value);
        return $this;
    }

    public function getInputSpecification(): array
    {
        return [
            'name' => $this->getName(),
            'required' => false,
            'allow_empty' => true,
            'filters' => [
                [
                    'name' => \Laminas\Filter\Callback::class,
                    'options' => [
                        'callback' => [$this, 'stringToArray'],
                    ],
                ],
            ],
        ];
    }

    public function arrayToString($array): string
    {
        if (is_string($array)) {
            return $array;
        }

        if (!count($array)) {
            return '';
        }

        $string = '';
        foreach ($array as $group => $values) {
            $string .= '# ' . $group . "\n" . implode("\n", $values) . "\n\n";
        }
        return trim($string) . "\n";
    }

    public function stringToArray($string)
    {
        if (is_array($string)) {
            return $string;
        }

        if ($string === '') {
            return [];
        }

        $groupsArray = [];

        // Clean the text area from end of lines.
        // Fixes Windows and Apple copy/paste.
        $string = str_replace(["\r\n", "\n\r", "\r"], ["\n", "\n", "\n"], $string);
        $array = array_filter(array_map('trim', explode("\n", $string)), 'strlen');

        // No check: if a property or any value doesn't exist, it won't be used.
        $id = 0;
        foreach ($array as $string) {
            $cleanString = preg_replace('/\s+/', ' ', $string);
            if (mb_substr($cleanString, 0, 1) === '#') {
                ++$id;
                $groupName = trim(mb_substr($cleanString, 1));
                $groupsArray[$groupName] = [];
                continue;
            } elseif ($id === 0) {
                ++$id;
                // Set a default group name when missing.
                $groupName = sprintf('Group %d', $id); // $translate
            }
            $groupsArray[$groupName][] = $cleanString;
        }

        return $groupsArray;
    }
}
