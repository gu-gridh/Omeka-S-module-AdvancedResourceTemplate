<?php

namespace AdvancedResourceTemplate\Mvc\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class FieldNameToProperty extends AbstractPlugin
{
    /**
     * Convert a field name as a property array.
     *
     * It returns the property name and identified metadata. For example,
     * `dcterms:title @fr-fr ^^literal §private` will be converted into:
     * [
     *     'field' => 'dcterms:title',
     *     'type' => 'literal',
     *     '@language' => 'fr-fr',
     *     'is_public' => false,
     * ]
     *
     * The format of each part is checked, but not if it has a meaning. The
     * property id is not checked, but the term is required.
     *
     * @param string $field
     * @return array|null
     */
    public function __invoke($field)
    {
        $base = [
            'field' => null,
            'type' => null,
            '@language' => null,
            'is_public' => null,
        ];
        $matches = [];
        $fieldArray = array_filter(explode(' ', $this->cleanString($field)));
        foreach ($fieldArray as $part) {
            if (mb_substr($part, 0, 2) === '^^') {
                // Manage standard.
                if (preg_match('~^(?<type>[a-zA-Z][a-zA-Z0-9]*:[a-zA-Z][\w-]*|[a-zA-Z][\w-]*|)~', trim(mb_substr($part, 2)), $matches)) {
                    $base['type'] = $matches['type'];
                }
                continue;
            }
            $first = mb_substr($part, 0, 1);
            if ($first === '@') {
                // Use the same regex than application/asset/js/admin.js.
                // @link http://stackoverflow.com/questions/7035825/regular-expression-for-a-language-tag-as-defined-by-bcp47
                if (preg_match('/^(?<language>((en-GB-oed|i-ami|i-bnn|i-default|i-enochian|i-hak|i-klingon|i-lux|i-mingo|i-navajo|i-pwn|i-tao|i-tay|i-tsu|sgn-BE-FR|sgn-BE-NL|sgn-CH-DE)|(art-lojban|cel-gaulish|no-bok|no-nyn|zh-guoyu|zh-hakka|zh-min|zh-min-nan|zh-xiang))|((([A-Za-z]{2,3}(-([A-Za-z]{3}(-[A-Za-z]{3}){0,2}))?))(-([A-Za-z]{4}))?(-([A-Za-z]{2}|[0-9]{3}))?(-([A-Za-z0-9]{5,8}|[0-9][A-Za-z0-9]{3}))*(-([0-9A-WY-Za-wy-z](-[A-Za-z0-9]{2,8})+))*(-(x(-[A-Za-z0-9]{1,8})+))?)|(x(-[A-Za-z0-9]{1,8})+))$/', trim(mb_substr($part, 1)), $matches)) {
                    $base['@language'] = $matches['language'];
                }
                continue;
            }
            if ($first === '§') {
                $second = trim(mb_substr($part, 1));
                if ($second === 'public') {
                    $base['is_public'] = true;
                } elseif ($second === 'private') {
                    $base['is_public'] = false;
                }
                continue;
            }
            if (preg_match('~^(?<field>[a-zA-Z][a-zA-Z0-9]*:[a-zA-Z][a-zA-Z0-9]*)$~', $part, $matches)) {
                $base['field'] = $matches['field'];
            }
        }
        return $base['field']
            ? $base
            : null;
    }

    /**
     * Clean and trim all whitespace, and remove spaces around colon.
     *
     * It fixes whitespaces added by some spreadsheets before or after a colon,
     * and unicode ones.
     *
     * @param $string
     * @return array
     */
    protected function cleanString($string)
    {
        return preg_replace('~\s*:\s*~', ':', trim(preg_replace('/[\s\h\v[:blank:][:space:]]+/u', ' ', $string)));
    }
}