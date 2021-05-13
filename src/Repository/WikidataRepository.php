<?php

namespace App\Repository;

use DateInterval;
use Exception;
use Mediawiki\Api\MediawikiApi;
use Mediawiki\Api\SimpleRequest;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WikidataRepository
{

    /** @var string */
    private $wikidataUrl;

    /** @var MediawikiApi */
    private $api;

    /** @var HttpClientInterface */
    private $httpClient;

    /** @var CacheItemPoolInterface */
    private $cache;

    /** @var TagRepository */
    private $tagRepository;

    public function __construct(
        HttpClientInterface $httpClient,
        CacheItemPoolInterface $cache,
        TagRepository $tagRepository,
        string $wikidataUrl
    ) {
        $this->httpClient = $httpClient;
        $this->cache = $cache;
        $this->tagRepository = $tagRepository;
        $this->wikidataUrl = $wikidataUrl;
    }

    public function search(string $q)
    {
        if (empty($q)) {
            return [
                'results' => [],
            ];
        }
        $params = [
            'search' => $q,
            'language' => 'en',
            'uselang' => 'en',
            'type' => 'item',
            'format' => 'json',
            'limit' => 20,
            'props' => '',
        ];
        $results = $this->getMediaWikiApi()->getRequest(new SimpleRequest('wbsearchentities', $params));
        $data = [];
        foreach ($results['search'] ?? [] as $result) {
            $data[] = [
                'id' => $result['id'],
                'text' => $result['label'] ?? $result['id'],
                'description' => $result['description'] ?? '',
            ];
        }
        return [
            'results' => $data,
        ];
    }

    /**
     * @param $id
     * @return array With keys 'label', 'description', 'properties' and maybe 'authorities'.
     */
    public function getData($id): array
    {
        $entity = $this->getEntities([$id])[$id];
        $out = [
            'label' => $entity['labels']['en']['value'] ?? '(No label)',
            'description' => $entity['descriptions']['en']['value'] ?? '(No description)',
            'properties' => [],
        ];
        $propIds = [];
        foreach ($entity['claims'] as $propId => $claim) {
            $propIds[] = $propId;
        }
        $tags = $this->tagRepository->findWikidata();
        $props = $this->getEntities($propIds);
        foreach ($entity['claims'] as $propId => $claim) {
            $values = [];
            foreach ($claim as $c) {
                if ($c['mainsnak']['datatype'] === 'wikibase-item') {
                    if (in_array($c['mainsnak']['snaktype'], ['novalue', 'somevalue'])) {
                        $values[] = [
                            'id' => false,
                            'label' => '[No value]',
                            'tag_id' => false,
                        ];
                    } else {
                        $valueId = $c['mainsnak']['datavalue']['value']['id'];
                        $valueEntity = $this->getEntities([$valueId])[$valueId];
                        $valueLabel = isset($valueEntity['labels']['en'])
                            ? $valueEntity['labels']['en']['value']
                            : '[No label]';
                        $values[] = [
                            'id' => $valueEntity['id'],
                            'label' => $valueLabel,
                            'tag_id' => $tags[$valueId] ?? false,
                        ];
                    }
                } elseif (!isset($c['mainsnak']['datavalue'])) {
                    // @TODO: handle novalue and somevalue snaktypes.
                } else {
                    $values[] = $c['mainsnak']['datavalue']['value'];
                }
            }
            $property = $props[$propId];
            if ($property['datatype'] === 'external-id') {
                if (isset($property['claims']['P1630'])) {
                    $formatterUrl = $property['claims']['P1630'][0]['mainsnak']['datavalue']['value'];
                    $formattedValues = [];
                    foreach ($values as $val) {
                        $formattedValues[$val] = str_replace('$1', $val, $formatterUrl);
                    }
                    $out['authorities'][] = [
                        'id' => $property['id'],
                        'label' => $property['labels']['en']['value'],
                        'values' => $formattedValues,
                    ];
                }
            } else {
                $out['properties'][] = [
                    'id' => $property['id'],
                    'label' => $property['labels']['en']['value'],
                    'type' => $property['datatype'],
                    'values' => $values,
                ];
            }
        }
        return $out;
    }

    /**
     * @param array<int,string> $id
     * @return array<string,array>
     */
    public function getEntities(array $ids)
    {
        if (empty($ids)) {
            return [];
        }
        $out = [];
        $idsToFetch = [];
        $cacheItems = [];
        $cachePrefix = 'wikidata-entity-';
        foreach ($ids as $id) {
            $cacheItems[$id] = $this->cache->getItem($cachePrefix . $id);
            if ($cacheItems[$id]->isHit()) {
                $out[$id] = $cacheItems[$id]->get();
            } else {
                $idsToFetch[] = $id;
            }
        }
        if (count($idsToFetch) > 0) {
            foreach (array_chunk($idsToFetch, 50) as $chunkOfIds) {
                $params = ['ids' => join('|', $chunkOfIds)];
                $responseData = $this->getMediaWikiApi()->getRequest(new SimpleRequest('wbgetentities', $params));
                if (!isset($responseData['entities'])) {
                    throw new Exception('Entities not found in response.');
                }
                foreach ($responseData['entities'] as $entity) {
                    // Cache each entity for two weeks.
                    $cacheItems[$entity['id']]->set($entity);
                    $cacheItems[$entity['id']]->expiresAfter(new DateInterval('P14D'));
                    $this->cache->save($cacheItems[$entity['id']]);
                    $out[$entity['id']] = $entity;
                }
            }
            $this->cache->commit();
        }
        return $out;
    }

    private function getMediaWikiApi(): MediawikiApi
    {
        if ($this->api instanceof MediawikiApi) {
            return $this->api;
        }
        $this->api = MediawikiApi::newFromPage($this->wikidataUrl);
        return $this->api;
    }
}
