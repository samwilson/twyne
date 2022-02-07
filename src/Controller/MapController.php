<?php

namespace App\Controller;

use App\Controller\ControllerBase;
use App\Repository\LocationPointRepository;
use App\Repository\PostRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

class MapController extends ControllerBase
{

    private $mapTilesViewUrl;
    private $mapTilesViewConfig;
    private $mapTilesEditUrl;
    private $mapTilesEditConfig;

    public function __construct(
        bool $requireTwoFactorAuth,
        string $mapTilesViewUrl,
        array $mapTilesViewConfig,
        string $mapTilesEditUrl,
        array $mapTilesEditConfig
    ) {
        parent::__construct($requireTwoFactorAuth);
        $this->mapTilesViewUrl = $mapTilesViewUrl;
        $this->mapTilesViewConfig = $mapTilesViewConfig;
        $this->mapTilesEditUrl = $mapTilesEditUrl;
        $this->mapTilesEditConfig = $mapTilesEditConfig;
    }

    /**
     * @Route("/map/{ne_lat}_{ne_lng}_{sw_lat}_{sw_lng}.json", name="mapdata", requirements={
     *     "ne_lat"="[0-9.-]+",
     *     "ne_lng"="[0-9.-]+",
     *     "sw_lat"="[0-9.-]+",
     *     "sw_lng"="[0-9.-]+"
     * })
     */
    public function mapData(Request $request, PostRepository $postRepository, LocationPointRepository $locationPointRepository)
    {
        $geojson = [
            'type' => 'FeatureCollection',
            'features' => [],
        ];
        $postData = $postRepository->findByBoundingBox(
            $request->get('ne_lat'),
            $request->get('ne_lng'),
            $request->get('sw_lat'),
            $request->get('sw_lng'),
            $this->getUser()
        );
        foreach ($postData as $post) {
            $geojson['features'][] = [
                'type' => 'Feature',
                'properties' => [],
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [$post['lng'], $post['lat']]
                ],
            ];
        }
        $trackpoints = $locationPointRepository->findByBoundingBox(
            $request->get('ne_lat'),
            $request->get('ne_lng'),
            $request->get('sw_lat'),
            $request->get('sw_lng')
        );
        foreach ($trackpoints as $point) {
            $geojson['features'][] = [
                'type' => 'Feature',
                'properties' => [],
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [$point['lng'], $point['lat']]
                ],
            ];
        }
        return new JsonResponse($geojson);
    }

    /**
     * @Route("/map-config.json", name="mapconfig")
     */
    public function mapConfig()
    {
        $config = [
            'view_url' => $this->mapTilesViewUrl,
            'view_config' => $this->mapTilesViewConfig,
        ];
        if ($this->getUser()) {
            $config['edit_url'] = $this->mapTilesEditUrl;
            $config['edit_config'] = $this->mapTilesEditConfig;
        }
        if ($this->getUser()->isAdmin()) {
        //    $config['location_points'] = 
        }
        return new JsonResponse($config);
    }

    /**
     * @Route("/map", name="map")
     */
    public function map()
    {
        return $this->render('post/map.html.twig');
    }
}
