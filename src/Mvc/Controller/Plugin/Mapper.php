<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\Mvc\Controller\Plugin;

use ArrayObject;
use DOMDocument;
use DOMXPath;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;

/**
 * Extract data from a string with a mapping.
 */
class Mapper extends AbstractPlugin
{
    /**
     * @var \AdvancedResourceTemplate\Mvc\Controller\Plugin\MapperHelper
     */
    protected $mapperHelper;

    /**
     * Normalize a mapping.
     *
     * Mapping is either a list of xpath or json path mapped with properties:
     * [
     *     [/xpath/to/data => [field => dcterms:title]],
     *     [object.to.data => [field => dcterms:title]],
     * ]
     *
     * @var array
     */
    protected $mapping = [];

    /**
     * Only extract metadata, don't map them.
     *
     * @var bool
     */
    protected $simpleExtract = false;

    /**
     * @var ArrayObject
     */
    protected $result;

    /**
     * @return self
     */
    public function __invoke()
    {
        return $this;
    }

    public function setMapping(array $mapping)
    {
        $this->mapperHelper = $this->getController()->mapperHelper();
        $this->mapping = $this->normalizeMapping($mapping);
        return $this;
    }

    public function setSimpleExtract($simpleExtract)
    {
        $this->simpleExtract = (bool) $simpleExtract;
        return $this;
    }

    /**
     * Extract data from an url that returns a json.
     *
     * @param string $url
     * @return array A resource array by property, suitable for api creation
     * or update.
     */
    public function urlArray($url)
    {
        $content = file_get_contents($url);
        $content = json_decode($content, true);
        if (!is_array($content)) {
            return [];
        }
        return $this->array($content);
    }

    /**
     * Extract data from an url that returns an xml.
     *
     * @param string $url
     * @return array A resource array by property, suitable for api creation
     * or update.
     */
    public function urlXml($url)
    {
        $content = file_get_contents($url);
        if (empty($content)) {
            return [];
        }
        return $this->xml($content);
    }

    /**
     * Extract data from an array.
     *
     * @param array $input Array of metadata.
     * @return array A resource array by property, suitable for api creation
     * or update.
     */
    public function array(array $input)
    {
        if (empty($this->mapping)) {
            return [];
        }

        $this->result = new ArrayObject([], ArrayObject::ARRAY_AS_PROPS);

        $input = $this->flatArray($input);

        foreach ($this->mapping as $map) {
            $target = $map['to'];
            if (!empty($target['replace'])) {
                $target['replace'] = array_fill_keys($target['replace'], '');
                foreach ($target['replace'] as $query => &$replacement) {
                    if (in_array($query, ['{__value__}', '{__label__}'])) {
                        continue;
                    }
                    $query = mb_substr($query, 1, -1);
                    if (isset($input[$query])) {
                        $replacement = $input[$query];
                    }
                }
                unset($replacement);
            }

            $query = $map['from'];
            if ($query === '~') {
                $value = '';
            } else {
                if (!isset($input[$query])) {
                    continue;
                }
                $value = $input[$query];
            }

            $this->simpleExtract
                ? $this->simpleExtract($value, $target, $query)
                : $this->appendValueToTarget($value, $target);
        }

        return $this->result->exchangeArray([]);
    }

    /**
     * Extract data from a xml string with a mapping.
     *
     * @param string $string
     * @return array A resource array by property, suitable for api creation
     * or update.
     */
    public function xml($xml)
    {
        if (empty($this->mapping)) {
            return [];
        }

        // Check if the xml is fully formed.
        $xml = trim($xml);
        if (strpos($xml, '<?xml ') !== 0) {
            $xml = '<?xml version="1.1" encoding="utf-8"?>' . $xml;
        }

        $this->result = new ArrayObject([], ArrayObject::ARRAY_AS_PROPS);

        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $xpath = new DOMXPath($doc);

        // Register all namespaces to allow prefixes.
        $xpathN = new DOMXPath($doc);
        foreach ($xpathN->query('//namespace::*') as $node) {
            $xpath->registerNamespace($node->prefix, $node->nodeValue);
        }

        foreach ($this->mapping as $map) {
            $target = $map['to'];
            if (!empty($target['replace'])) {
                $target['replace'] = array_fill_keys($target['replace'], '');
                foreach ($target['replace'] as $query => &$value) {
                    if (in_array($query, ['{__value__}', '{__label__}'])) {
                        continue;
                    }
                    $nodeList = $xpath->query(mb_substr($query, 1, -1));
                    if (!$nodeList || !$nodeList->length) {
                        continue;
                    }
                    $value = $nodeList->item(0)->nodeValue;
                }
                unset($value);
            }

            $query = $map['from'];
            if ($query === '~') {
                $value = '';
                $this->simpleExtract
                    ? $this->simpleExtract($value, $target, $query)
                    : $this->appendValueToTarget($value, $target);
            } else {
                $nodeList = $xpath->query($query);
                if (!$nodeList || !$nodeList->length) {
                    continue;
                }
                // The answer has many nodes.
                foreach ($nodeList as $node) {
                    $this->simpleExtract
                        ? $this->simpleExtract($node->nodeValue, $target, $query)
                        : $this->appendValueToTarget($node->nodeValue, $target);
                }
            }
        }

        return $this->result->exchangeArray([]);
    }

    /**
     * @param array $array
     * @param string $path
     * @return array|null
     */
    public function extractSubArray(array $array, $path)
    {
        foreach (explode('.', $path) as $subpath) {
            if (isset($array[$subpath])) {
                $array = $array[$subpath];
            } else {
                return null;
            }
        }
        return is_array($array) ? $array : null;
    }

    /**
     * @param string $xml
     * @param string $xpath
     * @return array|null
     */
    public function extractSubArrayXml($xml, $path)
    {
        // Check if the xml is fully formed.
        $xml = trim($xml);
        if (strpos($xml, '<?xml ') !== 0) {
            $xml = '<?xml version="1.1" encoding="utf-8"?>' . $xml;
        }

        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $xpath = new DOMXPath($doc);

        // Register all namespaces to allow prefixes.
        $xpathN = new DOMXPath($doc);
        foreach ($xpathN->query('//namespace::*') as $node) {
            $xpath->registerNamespace($node->prefix, $node->nodeValue);
        }

        $nodeList = $xpath->query($path);
        if (!$nodeList || !$nodeList->length) {
            return null;
        }

        $array = [];
        foreach ($nodeList as $node) {
            $array[] = $node->C14N();
        }
        return $array;
    }

    protected function simpleExtract($value, $target, $source): void
    {
        $this->result[] = [
            'field' => $source,
            'target' => $target,
            'value' => $value,
        ];
    }

    protected function appendValueToTarget($value, $target): void
    {
        $v = $target;
        unset($v['field'], $v['pattern'], $v['replace']);

        if (!empty($target['pattern'])) {
            if (!empty($target['replace'])) {
                $target['replace']['{__value__}'] = $value;
                $target['replace']['{__label__}'] = $value;
                $value = str_replace(array_keys($target['replace']), array_values($target['replace']), $target['pattern']);
            }
            if (!empty($target['twig'])) {
                $value = $this->twig($value, $target);
            }
        }

        switch ($v['type']) {
            default:
            case 'literal':
            // case strpos($v['type'], 'customvocab:') === 0:
                $v['@value'] = $value;
                $this->result[$target['field']][] = $v;
                break;
            case 'uri':
            case strpos($target['type'], 'valuesuggest:') === 0:
                $v['@id'] = $value;
                $this->result[$target['field']][] = $v;
                break;
            case 'resource':
            case 'resource:item':
            case 'resource:media':
            case 'resource:itemset':
                // The mapping from an external service cannot be an internal
                // resource.
                break;
        }
    }

    /**
     * Convert a value into another value via twig filters.
     *
     * Only some filters are managed basically on value.
     *
     * @param string $value
     * @param array $target
     * @return string
     */
    protected function twig($value, $target)
    {
        $matches = [];
        $target['twig'] = array_fill_keys($target['twig'], '');
        foreach ($target['twig'] as $query => &$output) {
            $v = $value;
            $filters = array_filter(array_map('trim', explode('|', mb_substr($query, 3, -3))));
            unset($filters[0]);
            foreach ($filters as $filter) switch ($filter) {
                case 'abs':
                    $v = is_numeric($v) ? abs($v) : $v;
                    break;
                case 'capitalize':
                    $v = ucfirst($v);
                    break;
                case 'e':
                case 'escape':
                    $v = htmlspecialchars($v);
                    break;
                case 'first':
                    $v = mb_substr($v, 0, 1);
                    break;
                case 'last':
                    $v = mb_substr($v, -1);
                    break;
                case 'length':
                    $v = mb_strlen($v);
                    break;
                case 'lower':
                    $v = mb_strtolower($v);
                    break;
                case 'striptags':
                    $v = strip_tags($v);
                    break;
                case 'title':
                    $v = ucwords($v);
                    break;
                case 'trim':
                    $v = trim($v);
                    break;
                case 'upper':
                    $v = mb_strtoupper($v);
                    break;
                case 'url_encode':
                    $v = rawurlencode($v);
                    break;
                case preg_match('~date\s*\(\s*["|\'](?<format>[^"\']+?)["|\']\s*\)~', $filter, $matches) > 0:
                    try {
                        $v = @date($matches['format'], @strtotime($v));
                    } catch (\Exception $e) {
                        // Nothing.
                    }
                    break;
                case preg_match('~format\s*\(\s*(?<args>.*?)\s*\)~', $filter, $matches) > 0:
                    $args = $matches['args'];
                    preg_match_all('~\s*(?<args>__value__|"[^"]*?"|\'[^\']*?\')\s*,?\s*~', $args, $matches);
                    $args = array_map(function ($v) {
                        return $v === '__value__' ? $v : mb_substr($v, 1, -1);
                    }, $matches['args']);
                    try {
                        $v = @vsptintf($v, $args);
                    } catch (\Exception $e) {
                        // Nothing.
                    }
                    break;
                case preg_match('~slice\s*\(\s*(?<start>-?\d+)\s*,\s*(?<length>-?\d+\s*)\s*\)~', $filter, $matches) > 0:
                    $v = mb_substr($v, $matches['start'], (int) $matches['length']);
                    break;
                default:
                    // Nothing.
                    break;
            }
            $output = $v;
        }
        unset($output);
        return str_replace(array_keys($target['twig']), array_values($target['twig']), $target['pattern']);
    }

    protected function normalizeMapping(array $mapping)
    {
        $translate = $this->getController()->plugin('translate');
        foreach ($mapping as &$map) {
            $to = &$map['to'];
            $to['property_id'] = $this->mapperHelper->getPropertyId($to['field']);
            if (empty($to['type'])) {
                $to['type'] = 'literal';
            }
            if (empty($to['property_label'])) {
                $to['property_label'] = $translate($this->mapperHelper->getPropertyLabel($to['field']));
            }
        }
        return $mapping;
    }

    /**
     * Create a flat array from a recursive array.
     *
     * @example
     * ```
     * // The following recursive array:
     * [
     *     'video' => [
     *         'dataformat' => 'jpg',
     *         'bits_per_sample' => 24,
     *     ],
     * ]
     * // is converted into:
     * [
     *     'video.dataformat' => 'jpg',
     *     'video.bits_per_sample' => 24,
     * ]
     * ```
     *
     * @param array $data
     * @return array
     */
    protected function flatArray(array $array)
    {
        $flatArray = [];
        $this->_flatArray($array, $flatArray);
        return $flatArray;
    }

    /**
     * Recursive helper to flat an array with separator ".".
     *
     * @todo Find a way to keep the last level of array (list of subjects…).
     *
     * @param array $array
     * @param array $flatArray
     * @param string $keys
     */
    private function _flatArray(array &$array, &$flatArray, $keys = null): void
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $this->_flatArray($value, $flatArray, $keys . '.' . $key);
            } else {
                $flatArray[trim($keys . '.' . $key, '.')] = $value;
            }
        }
    }
}
