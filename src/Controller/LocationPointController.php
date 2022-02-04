<?php

namespace App\Controller;

use App\Entity\LocationPoint;
use App\Settings;
use CrEOF\Spatial\PHP\Types\Geometry\Point;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LocationPointController extends ControllerBase
{
    /**
     * @Route("/overland", name="overland")
     */
    public function overland(
        Request $request,
        Settings $settings,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ): Response {
        $json = $request->getContent();
        $logger->debug($json);
        if (
            $settings->overlandKey()
            && $request->get('key') !== $settings->overlandKey()
        ) {
            return new JsonResponse(['result' => 'error', 'error' => 'unauthorized']);
        }
        if (empty($json)) {
            return new JsonResponse(['result' => 'ok']);
        }
        $data = json_decode($json);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse(['result' => 'error', 'error' => json_last_error()]);
        }
        if (!$data || !isset($data->locations) || !is_array($data->locations)) {
            return new JsonResponse(['result' => 'ok']);
        }
        $entityManager->transactional(function () use ($entityManager, $data) {
            foreach ($data->locations as $location) {
                $lp = new LocationPoint();
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
}
