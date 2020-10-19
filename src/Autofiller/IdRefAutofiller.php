<?php declare(strict_types=1);

namespace AdvancedResourceTemplate\Autofiller;

class IdRefAutofiller extends AbstractAutofiller
{
    protected $label = 'IdRef'; // @translate

    /**
     * @see \ValueSuggest\Service\IdRefDataTypeFactory::types
     * @link http://documentation.abes.fr/aideidrefdeveloppeur/index.html#presentation
     *
     * @todo Supprimer "fl" et traiter directement le json (plus rapide mais moins complet).
     *
     * Tri par pertinence par défaut.
     */
    protected $types = [
        'idref' => [
            'label' => 'IdRef', // @translate
            'url' => 'https://www.idref.fr/Sru/Solr?wt=json&version=2.2&start=0&rows=30&indent=on&fl=id,ppn_z,affcourt_r&q=all%3A',
        ],
        'idref:person' => [
            'label' => 'IdRef: Person names', // @translate
            'url' => 'https://www.idref.fr/Sru/Solr?wt=json&version=2.2&start=&rows=30&indent=on&fl=id,ppn_z,affcourt_z&q=persname_t%3A',
        ],
        'idref:corporation' => [
            'label' => 'IdRef: Collectivities', // @translate
            'url' => 'https://www.idref.fr/Sru/Solr?wt=json&version=2.2&start=&rows=30&indent=on&fl=id,ppn_z,affcourt_z&q=corpname_t%3A',
        ],
        'idref:conference' => [
            'label' => 'IdRef: Conferences', // @translate
            'url' => 'https://www.idref.fr/Sru/Solr?wt=json&version=2.2&start=&rows=30&indent=on&fl=id,ppn_z,affcourt_z&q=conference_t%3A',
        ],
        'idref:subject' => [
            'label' => 'IdRef: Subjects', // @translate
            'url' => 'https://www.idref.fr/Sru/Solr?wt=json&version=2.2&start=&rows=30&indent=on&fl=id,ppn_z,affcourt_z&q=subjectheading_t%3A',
        ],
        'idref:rameau' => [
            'label' => 'IdRef: Subjects Rameau', // @translate
            'url' => 'https://www.idref.fr/Sru/Solr?wt=json&version=2.2&start=&rows=30&indent=on&fl=id,ppn_z,affcourt_z&q=recordtype_z%3Ar%20AND%20subjectheading_t%3A',
        ],
        'idref:fmesh' => [
            'label' => 'IdRef: Subjects F-MeSH', // @translate
            'url' => 'https://www.idref.fr/Sru/Solr?wt=json&version=2.2&start=&rows=30&indent=on&fl=id,ppn_z,affcourt_z&q=recordtype_z%3At%20AND%20subjectheading_t%3A',
        ],
        'idref:geo' => [
            'label' => 'IdRef: Geography', // @translate
            'url' => 'https://www.idref.fr/Sru/Solr?wt=json&version=2.2&start=&rows=30&indent=on&fl=id,ppn_z,affcourt_z&q=geogname_t%3A',
        ],
        'idref:family' => [
            'label' => 'IdRef: Family names', // @translate
            'url' => 'https://www.idref.fr/Sru/Solr?wt=json&version=2.2&start=&rows=30&indent=on&fl=id,ppn_z,affcourt_z&q=famname_t%3A',
        ],
        'idref:title' => [
            'label' => 'IdRef: Uniform titles', // @translate
            'url' => 'https://www.idref.fr/Sru/Solr?wt=json&version=2.2&start=&rows=30&indent=on&fl=id,ppn_z,affcourt_z&q=uniformtitle_t%3A',
        ],
        'idref:authorTitle' => [
            'label' => 'IdRef: Authors-Titles', // @translate
            'url' => 'https://www.idref.fr/Sru/Solr?wt=json&version=2.2&start=&rows=30&indent=on&fl=id,ppn_z,affcourt_z&q=nametitle_t%3A',
        ],
        'idref:trademark' => [
            'label' => 'IdRef: Trademarks', // @translate
            'url' => 'https://www.idref.fr/Sru/Solr?wt=json&version=2.2&start=&rows=30&indent=on&fl=id,ppn_z,affcourt_z&q=trademark_t%3A',
        ],
        'idref:ppn' => [
            'label' => 'IdRef: PPN id', // @translate
            'url' => 'https://www.idref.fr/Sru/Solr?wt=json&version=2.2&start=&rows=30&indent=on&fl=id,ppn_z,affcourt_z&q=ppn_z%3A',
        ],
        'idref:library' => [
            'label' => 'IdRef: Library registry (RCR)', // @translate
            'url' => 'https://www.idref.fr/Sru/Solr?wt=json&version=2.2&start=&rows=30&indent=on&fl=id,ppn_z,affcourt_r&q=rcr_t%3A',
        ],
    ];

    public function getResults($query, $lang = null)
    {
        $maxResult = 12;

        $service = empty($this->options['sub']) ? 'idref' : 'idref:' . $this->options['sub'];
        if (empty($this->types[$service])) {
            return null;
        }

        // Convert the query into a Solr query.
        if (strpos($query, ' ')) {
            $query = '(' . implode('%20AND%20', array_map('urlencode', explode(' ', $query))) . ')';
        } else {
            $query = urlencode($query);
        }
        if (!empty($this->options['query'])) {
            $query .= '&' . $this->options['query'];
        }

        $url = $this->types[$service]['url'] . $query;

        $response = $this->httpClient->setUri($url)->send();
        if (!$response->isSuccess()) {
            return null;
        }

        $suggestions = [];

        // Parse the JSON response.
        $results = json_decode($response->getBody(), true);

        // Prepare mapper one time.
        $this->mapper->setMapping($this->mapping);

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
            $metadata = $this->mapper->urlXml($urlPpn);
            if (!$metadata) {
                continue;
            }
            $suggestions[] = [
                'value' => $value,
                'data' => $metadata,
            ];
            if (++$total >= $maxResult) {
                break;
            }
        }

        return $suggestions;
    }
}
