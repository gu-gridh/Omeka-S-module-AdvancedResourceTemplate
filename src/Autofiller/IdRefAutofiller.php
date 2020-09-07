<?php

namespace AdvancedResourceTemplate\Autofiller;

class IdRefAutofiller extends AbstractAutofiller
{
    protected $label = 'IdRef'; // @translate

    public function getResults($query, $lang = null)
    {
        $maxResult = 12;

        $sub = $this->options['sub'] ?? null;
        $urls = [
            'person' => 'https://www.idref.fr/Sru/Solr?wt=json&version=2.2&start=&rows=30&indent=on&fl=id,ppn_z,affcourt_z&q=persname_t%3A',
        ];
        if (empty($urls[$sub])) {
            return null;
        }

        // Convert the query into a Solr query.
        if (strpos($query, ' ')) {
            $query = '(' . implode('%20AND%20', array_map('urlencode', explode(' ', $query))) . ')';
        } else {
            $query = urlencode($query);
        }
        $url = $urls[$sub] . $query;

        $response = $this->httpClient->setUri($url)->send();
        if (!$response->isSuccess()) {
            return null;
        }

        // Parse the JSON response.
        $suggestions = [];
        $results = json_decode($response->getBody(), true);

        // Prepare mapper one time.
        $mapper = $this->mapper->setMapping($this->mapping);

        // First clean results.
        if (empty($results['response']['docs'])) {
            return [];
        }
        $total = 0;
        foreach ($results['response']['docs'] as $result) {
            if (empty($result['ppn_z'])) {
                continue;
            }
            // "affcourt" may be not present in some results (empty words).
            if (isset($result['affcourt_r'])) {
                $value = is_array($result['affcourt_r']) ? reset($result['affcourt_r']) : $result['affcourt_r'];
            } elseif (isset($result['affcourt_z'])) {
                $value = is_array($result['affcourt_z']) ? reset($result['affcourt_z']) : $result['affcourt_z'];
            } else {
                $value = $result['ppn_z'];
            }

            // The results are only one or two labels and an id, so do a second
            // request for each result to get all metadata.
            $urlPpn = 'https://www.idref.fr/' . $result['ppn_z'] . '.xml';
            $metadata = $mapper->urlXml($urlPpn);
            if (!$metadata) {
                continue;
            }
            $suggestions[] = [
                'value' => $value,
                'data' => $metadata,
            ];
            if (++$total > $maxResult) {
                break;
            }
        }

        return $suggestions;
    }
}
