<?php

namespace App\Controller;

use App\Entity\Post;
use App\Repository\CommonsRepository;
use App\Repository\PostRepository;
use App\Repository\SyndicationRepository;
use App\Repository\WikidataRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CommonsController extends AbstractController
{

    /**
     * @Route("/P{id}/commons", name="commons", methods={"GET"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function form(
        PostRepository $postRepository,
        WikidataRepository $wikidataRepository,
        CommonsRepository $commonsRepository,
        string $id
    ): Response {
        $post = $postRepository->find($id);
        if (!$post) {
            throw $this->createNotFoundException();
        }
        return $this->render('post/commons.html.twig', [
            'post' => $post,
            'commons_filename' => $post->getTitle() . '.' . $post->getFile()->getExtension(),
            'wikitext' => $this->getWikitext($wikidataRepository, $post),
            'commons_url' => $commonsRepository->getCommonsUrl(),
        ]);
    }

    private function getWikitext(WikidataRepository $wikidataRepository, Post $post): string
    {
        $url = $this->generateUrl('post_view', ['id' => $post->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        $author = $post->getAuthor()->getHomepage()
            ? "[" . $post->getAuthor()->getHomepage() . ' ' . $post->getAuthor()->getName() . "]"
            : $post->getAuthor()->getName();
        $information = "{{Information\n"
        . "| description    = \n"
        . "| date           = " . $post->getDate()->format('Y-m-d H:i:s') . "Z\n"
        . "| source         = $url\n"
        . "| author         = $author\n"
        . "| permission     = \n"
        . "}}\n";
        $location = '';
        if ($post->getLocation()) {
            $location = "{{location|"
                . $post->getLocation()->getLatitude()
                . "|" . $post->getLocation()->getLongitude()
                . "}}\n";
        }
        $categories = [];
        foreach ($post->getTags() as $tag) {
            if ($tag->getWikidata()) {
                $entity = $wikidataRepository->getData($tag->getWikidata());
                foreach ($entity['properties'] as $property) {
                    if ($property['id'] === 'P373') {
                        foreach ($property['values'] as $propVal) {
                            $categories[] = "[[Category:" . $propVal . "]]";
                        }
                    }
                }
            }
        }
        return "== {{int:filedesc}} ==\n"
            . $information
            . $location
            . "\n"
            . "== {{int:license-header}} ==\n"
            . "{{cc-by-sa-4.0}}\n"
            . "\n"
            . implode("\n", $categories);
    }

    /**
     * @Route("/P{id}/commons", name="commons_save", methods={"POST"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function save(
        PostRepository $postRepository,
        CommonsRepository $commonsRepository,
        SyndicationRepository $syndicationRepository,
        string $id,
        Request $request
    ): Response {
        /** @var Post $post */
        $post = $postRepository->find($id);
        if (!$post) {
            throw $this->createNotFoundException();
        }
        $uploaded = $commonsRepository->upload(
            $post,
            $request->get('filename', ''),
            $request->get('wikitext', ''),
            $request->get('caption', ''),
            $request->get('depicts', [])
        );
        if (isset($uploaded['warnings'])) {
            foreach ($uploaded['warnings'] as $key => $val) {
                $formattedVal = is_string($val) ? $val : var_export($val, true);
                $this->addFlash('error', "Unable to upload to Commons. $key: $formattedVal");
            }
        }

        $this->addFlash('success', 'Uploaded: ' . $uploaded['url']);
        return $this->redirectToRoute('post_view', ['id' => $id]);
    }
}
