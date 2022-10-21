<?php

namespace App\Controller;

use App\Filesystems;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Samwilson\PhpFlickr\PhpFlickr;
use App\Repository\PostRepository;
use App\Repository\SyndicationRepository;
use App\Settings;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use OAuth\OAuth1\Token\StdOAuth1Token;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Post;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FlickrController extends ControllerBase
{
    private function getPhpFlickr(Settings $settings): PhpFlickr
    {
        $phpFlickr = new PhpFlickr($settings->flickrApiKey(), $settings->flickrApiSecret());
        $accessToken = new StdOAuth1Token();
        $accessToken->setAccessToken($settings->flickrToken());
        $accessToken->setAccessTokenSecret($settings->flickrTokenSecret());
        $phpFlickr->getOauthTokenStorage()->storeAccessToken('Flickr', $accessToken);
        return $phpFlickr;
    }

    /**
     * @Route("/P{id}/flickr", name="flickr", methods={"GET"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function form(
        PostRepository $postRepository,
        Settings $settings,
        string $id
    ): Response {
        $post = $postRepository->find($id);
        if (!$post) {
            throw $this->createNotFoundException();
        }
        $userInfo = $this->getPhpFlickr($settings)->test()->login();
        return $this->render('post/flickr.html.twig', [
            'flickr_user' => $userInfo,
            'post' => $post,
        ]);
    }

    /**
     * @Route("/P{id}/flickr", name="flickr_save", methods={"POST"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function upload(
        PostRepository $postRepository,
        SyndicationRepository $syndicationRepository,
        Settings $settings,
        Filesystems $filesystems,
        Request $request,
        $id
    ) {
        /** @var Post $post */
        $post = $postRepository->find($id);
        if (!$post) {
            throw $this->createNotFoundException();
        }
        $fullTempPath = $filesystems->getLocalTempFilepath($post->getFile());
        $tags = [];
        foreach ($request->get('tags', []) as $tag) {
            // implode(' ', $request->get('tags', [])),
            $tags[] = '"' . str_replace('"', "'", $tag) . '"';
        }
        $phpFlickr = $this->getPhpFlickr($settings);
        $postUrl = $this->generateUrl('post_view', ['id' => $post->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        $result = $phpFlickr->uploader()->upload(
            $fullTempPath,
            $request->get('title'),
            trim($request->get('description')) . "\n\n" . $postUrl,
            implode(' ', $tags),
            $request->get('is_public') !== null,
            $request->get('is_friend') !== null,
            $request->get('is_family') !== null,
        );
        if (isset($result['message'])) {
            $this->addFlash(self::FLASH_NOTICE, $result['message']);
            return $this->redirectToRoute('flickr', ['id' => $id]);
        }
        if (isset($result['stat']) && $result['stat'] === 'ok') {
            if ($post->getLocation()) {
                $phpFlickr->photosGeo()->setLocation(
                    $result['photoid'],
                    $post->getLocation()->getY(),
                    $post->getLocation()->getX()
                );
            }
            $userInfo = $this->getPhpFlickr($settings)->test()->login();
            $flickrUrl = 'https://www.flickr.com/photos/' . $userInfo['path_alias'] . '/' . $result['photoid'];
            $syndicationRepository->addSyndication($post, $flickrUrl, 'Flickr');
            $this->addFlash(self::FLASH_SUCCESS, 'Uploaded: ' . $flickrUrl);
            return $this->redirectToRoute('post_view', ['id' => $id]);
        }
    }
}
