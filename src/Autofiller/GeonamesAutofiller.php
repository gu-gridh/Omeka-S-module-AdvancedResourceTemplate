<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\Autofiller;

class GeonamesAutofiller extends AbstractAutofiller
{
    protected $label = 'Geonames'; // @translate

    /**
     * The json service of geonames is restricted to authenticated users.
     * The username can be set as sub (username:xxx).
     *
     * @link https://www.geonames.org/export/geonames-search.html
     */
    public function getResults($query, $lang = null)
    {
        $params = [];
        if (!empty($this->options['query'])) {
            parse_str($this->options['query'], $params);
        }
        if ($lang && !array_key_exists('lang', $params)) {
            // Geonames requires an ISO-639 2-letter language code.
            $params['lang'] = strtok($lang, '_');
        }
        $params['q'] = $query;

        // The Geonames api requires a username. Some common names can be used:
        // "demo", "johnsmith", "google"… The requests may be limited per hour.
        if (empty($params['username'])) {
            return null;
        }

        // "http" is used to avoid issues with certificates on the server.
        // @see \ValueSuggest\Suggester\Geonames\GeonamesSuggest::getSuggestions()
        $response = $this->httpClient
            ->setUri('http://api.geonames.org/searchJSON')
            ->setParameterGet($params)
            ->send();
        if (!$response->isSuccess()) {
            return null;
        }

        $suggestions = [];

        // Parse the JSON response.
        $results = json_decode($response->getBody(), true);
        if (empty($results['geonames'])) {
            return null;
        }

        // Prepare mapper one time.
        $this->mapper->setMapping($this->mapping);

        foreach ($results['geonames'] as $result) {
            $metadata = $this->mapper->array($result);
            if (!$metadata) {
                continue;
            }
            $suggestions[] = [
                'value' => $result['name'],
                'data' => $metadata,
            ];
        }

        return $suggestions;
    }
}
