<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\Autofiller;

interface AutofillerInterface
{
    /**
     * The label of this autofiller service.
     *
     * @return string
     */
    public function getLabel();

    /**
     * The mapping is an array of xpath or object to a property.
     *
     * @param array $mapping
     * @return self
     */
    public function setMapping(array $mapping);

    /**
     * Get results from any service given a query.
     *
     * Each result is an array containing a main value, that is the main string
     * displayed to the user, and data, that are a list of standard Omeka
     * properties (only keys of the data type are needed):
     *
     * It may be recommended to keep the original url as an id in a property
     * with the type uri.
     *
     * @example
     * [
     *     [
     *          'value' => <value>,
     *          'data' => [
     *              <property term> => [
     *                  [
     *                      'type' => <type>,
     *                      '@value' => <value>,
     *                      '@language' => <language>,
     *                      '@id' => <uri>,
     *                      'o:label' => <label>,
     *                      'value_resource_id' => <resource id>,
     *                  ],
     *                  <…>,
     *              ],
     *              <…>,
     *          ],
     *      ],
     *      <...>,
     * ]
     *
     * @link https://www.devbridge.com/sourcery/components/jquery-autocomplete
     *
     * @param string $query The query
     * @param string $lang The language code
     * @return array|null Return null when an error occurs.
     */
    public function getResults($query, $lang = null);
}
