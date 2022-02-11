<?php

namespace App\Controller;

use App\Controller\ControllerBase;
use App\Repository\PostRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Settings;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use DateTime;
use DateTimeZone;
use CrEOF\Spatial\PHP\Types\Geometry\Point;
use App\Entity\TrackPoint;
use App\Repository\TrackPointRepository;

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
    public function mapData(
        Request $request,
        PostRepository $postRepository,
        TrackPointRepository $trackPointRepository
    ) {
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
            $postUrl = $this->generateUrl('post_view', ['id' => $post['id']]);
            $geojson['features'][] = [
                'type' => 'Feature',
                'properties' => [
                    'type' => 'post',
                    'popupContent' => '<a href="' . $postUrl . '">' . ( $post['title'] ?: 'Untitled' ) . '</a>',
                ],
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [$post['lng'], $post['lat']]
                ],
            ];
        }
        if ($this->getUser() && $this->getUser()->isAdmin()) {
            $trackpoints = $trackPointRepository->findByBoundingBox(
                $request->get('ne_lat'),
                $request->get('ne_lng'),
                $request->get('sw_lat'),
                $request->get('sw_lng')
            );
            foreach ($trackpoints as $point) {
                $geojson['features'][] = [
                    'type' => 'Feature',
                    'properties' => [
                        'type' => 'trackpoint'
                    ],
                    'geometry' => [
                        'type' => 'Point',
                        'coordinates' => [$point['lng'], $point['lat']]
                    ],
                ];
            }
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
        return new JsonResponse($config);
    }

    /**
     * @Route("/map", name="map")
     */
    public function map()
    {
        return $this->render('post/map.html.twig');
    }

    /**
     * @Route("/overland", name="overland")
     */
    public function overland(
        Request $request,
        Settings $settings,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$settings->overlandKey()) {
            return new JsonResponse(['result' => 'error', 'error' => 'not configured'], Response::HTTP_NOT_IMPLEMENTED);
        }
        if (
            $settings->overlandKey()
            && $request->get('key') !== $settings->overlandKey()
        ) {
            return new JsonResponse(['result' => 'error', 'error' => 'unauthorized'], Response::HTTP_UNAUTHORIZED);
        }
        $json = $request->getContent();
        if (empty($json)) {
            return new JsonResponse(['result' => 'ok']);
        }
        $data = json_decode($json);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse(
                ['result' => 'error', 'error' => json_last_error()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
        if (!$data || !isset($data->locations) || !is_array($data->locations)) {
            return new JsonResponse(['result' => 'ok']);
        }
        $entityManager->transactional(function () use ($entityManager, $data) {
            foreach ($data->locations as $location) {
                $lp = new TrackPoint();
                $timestamp = new DateTime($location->properties->timestamp);
                $timestamp->setTimezone(new DateTimeZone('Z'));
                $lp->setTimestamp($timestamp);
                $lp->setLocation(new Point($location->geometry->coordinates[0], $location->geometry->coordinates[1]));
                $entityManager->persist($lp);
            }
            $entityManager->flush();
        });
        return new JsonResponse([ 'result' => 'ok' ]);
    }

    /**
     * @Route("/gpslogger", name="gpslogger")
     */
    public function gpslogger(
        Request $request,
        Settings $settings,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        if (!$settings->overlandKey()) {
            $logger->warning('GPSLogger key not configured');
            return new JsonResponse(['result' => 'error', 'error' => 'not configured'], Response::HTTP_NOT_IMPLEMENTED);
        }
        if (
            $settings->overlandKey()
            && $request->headers->get('Authorization') !== 'Bearer ' . $settings->overlandKey()
        ) {
            $logger->warning('GPSLogger unauthorized');
            return new JsonResponse(['result' => 'error', 'error' => 'unauthorized'], Response::HTTP_FORBIDDEN);
        }

        $lat = $request->get('lat');
        $lon = $request->get('lon');
        $time = $request->get('time');
        if (!$lat || !$lon || !$time) {
            $logger->warning('GPSLogger has not provided all fields (lat, lon, and time).');
            return new JsonResponse(
                ['result' => 'error', 'error' => 'Not all fields set.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $lp = new TrackPoint();
        $timestamp = new DateTime($time);
        $timestamp->setTimezone(new DateTimeZone('Z'));
        $lp->setTimestamp($timestamp);
        $lp->setLocation(new Point($lon, $lat));
        $entityManager->persist($lp);
        $entityManager->flush();

        return new JsonResponse([ 'result' => 'ok' ]);
    }
}
