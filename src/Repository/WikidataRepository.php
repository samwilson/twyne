<?php

namespace App\Repository;

use App\Entity\Tag;
use DateInterval;
use Exception;
use Psr\Cache\CacheItemPoolInterface;
use stdClass;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WikidataRepository
{

    /** @var string */
    private const API_URL = 'https://www.wikidata.org/w/api.php';

    /** @var HttpClientInterface */
    private $httpClient;

    /** @var CacheItemPoolInterface */
    private $cache;

    /** @var TagRepository */
    private $tagRepository;

    public function __construct(
        HttpClientInterface $httpClient,
        CacheItemPoolInterface $cache,
        TagRepository $tagRepository
    ) {
        $this->httpClient = $httpClient;
        $this->cache = $cache;
        $this->tagRepository = $tagRepository;
    }

    public function search(string $q)
    {
        $params = [
            'action' => 'wbsearchentities',
            'search' => $q,
            'language' => 'en',
            'uselang' => 'en',
            'type' => 'item',
            'format' => 'json',
            'limit' => 20,
            'props' => '',
        ];
        $response = $this->httpClient->request('GET', self::API_URL . '?' . http_build_query($params));
        $results = json_decode($response->getContent());
        $data = [];
        foreach ($results->search ?? [] as $result) {
            $data[] = [
                'value' => $result->id,
                'title' => $result->label ?? $result->id,
                'description' => $result->description ?? '',
            ];
        }
        return $data;
    }

    public function getData($id): array
    {
        $entity = $this->getEntities([$id])[$id];
        $out = [
            'label' => $entity->labels->en->value,
            'description' => $entity->descriptions->en->value,
            'properties' => [],
        ];
        $propIds = [];
        foreach ($entity->claims as $propId => $claim) {
            $propIds[] = $propId;
        }
        $tags = $this->tagRepository->findWikidata();
        $props = $this->getEntities($propIds);
        foreach ($entity->claims as $propId => $claim) {
            $values = [];
            foreach ($claim as $c) {
                if ($c->mainsnak->datatype === 'wikibase-item') {
                    if (in_array($c->mainsnak->snaktype, ['novalue', 'somevalue'])) {
                        $values[] = [
                            'id' => false,
                            'label' => '[No value]',
                            'tag_id' => false,
                        ];
                    } else {
                        $valueId = $c->mainsnak->datavalue->value->id;
                        $valueEntity = $this->getEntities([$valueId])[$valueId];
                        $values[] = [
                            'id' => $valueEntity->id,
                            'label' => isset($valueEntity->labels->en) ? $valueEntity->labels->en->value : '[No label]',
                            'tag_id' => $tags[$valueId] ?? false,
                        ];
                    }
                } elseif (!isset($c->mainsnak->datavalue)) {
                    // @TODO: handle novalue and somevalue snaktypes.
                } else {
                    $values[] = $c->mainsnak->datavalue->value;
                }
            }
            $property = $props[$propId];
            if ($property->datatype === 'external-id') {
                if (isset($property->claims->P1630)) {
                    $formatterUrl = $property->claims->P1630[0]->mainsnak->datavalue->value;
                    $formattedValues = [];
                    foreach ($values as $val) {
                        $formattedValues[$val] = str_replace('$1', $val, $formatterUrl);
                    }
                    $out['authorities'][] = [
                        'id' => $property->id,
                        'label' => $property->labels->en->value,
                        'values' => $formattedValues,
                    ];
                }
            } else {
                $out['properties'][] = [
                    'id' => $property->id,
                    'label' => $property->labels->en->value,
                    'type' => $property->datatype,
                    'values' => $values,
                ];
            }
        }
        //dd($out);
        return $out;
    }

    /**
     * @param array<int,string> $id
     * @return array<string,stdClass>
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
                $params = [
                    'action' => 'wbgetentities',
                    'format' => 'json',
                    'ids' => join('|', $chunkOfIds),
                ];
                $url = self::API_URL . '?' . http_build_query($params);
                $response = $this->httpClient->request('GET', $url);
                $json = $response->getContent();
                $responseData = json_decode($json);
                if ($responseData === null) {
                    throw new Exception('Unable to decode Wikidata response.');
                }
                if (!isset($responseData->entities)) {
                    throw new Exception('Entities not found in response.');
                }
                foreach ($responseData->entities as $entity) {
                    // Cache each entity for two weeks.
                    $cacheItems[$entity->id]->set($entity);
                    $cacheItems[$entity->id]->expiresAfter(new DateInterval('P14D'));
                    $this->cache->save($cacheItems[$entity->id]);
                    $out[$entity->id] = $entity;
                }
            }
            $this->cache->commit();
        }
        return $out;
    }
}
