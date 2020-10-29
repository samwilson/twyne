<?php

namespace App\Controller;

use App\Repository\TagRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TagController extends AbstractController
{

    /**
     * @Route("/tags/{ids}", name="tags")
     */
    public function tags(TagRepository $tagRepository, $ids = '')
    {
        return $this->render('tag/index.html.twig', [
            'tags' => $tagRepository->findAllOrderedByCount(),
        ]);
    }

    /**
     * @Route("/T{id}", name="tag_view", requirements={"id"="\d+"})
     */
    public function viewTag(TagRepository $tagRepository, $id): Response
    {
        $tag = $tagRepository->find($id);
        if (!$tag) {
            throw $this->createNotFoundException();
        }
        return $this->render('tag/view.html.twig', [
            'tag' => $tag,
        ]);
    }

    /**
     * @Route("/tags/search", name="tag_search")
     */
    public function search(TagRepository $tagRepository, Request $request)
    {
        return $tagRepository->findBy(['title' => $request->get('q')]);
    }
}
