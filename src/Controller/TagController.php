<?php

namespace App\Controller;

use App\Repository\PostRepository;
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
    public function tags(TagRepository $tagRepository, string $ids = '')
    {
        return $this->render('tag/index.html.twig', [
            'tags' => $tagRepository->findAllOrderedByCount($this->getUser()),
        ]);
    }

    /**
     * @Route("/T{id}", name="tag_view", requirements={"id"="\d+"})
     * @Route("/T{id}/page-{pageNum}", name="tag_view_page", requirements={"id"="\d+", "pageNum"="\d+"})
     */
    public function viewTag(
        Request $request,
        TagRepository $tagRepository,
        int $id,
        int $pageNum = 1
    ): Response {
        $tag = $tagRepository->find($id);
        if (!$tag) {
            throw $this->createNotFoundException();
        }
        $postCount = $tagRepository->countPosts($tag, $this->getUser());
        $pageCount = ceil($postCount / 10);
        if ($pageNum > $pageCount) {
            // Redirect to last page.
            return $this->redirectToRoute('tag_view_page', ['id' => $tag->getId(), 'pageNum' => $pageCount]);
        }
        if (
            $pageNum === 1 && $request->get('_route') === 'tag_view_page'
            || $pageNum < 1
        ) {
            // Ensure only one form of URL for page 1, and avoid page 0.
            return $this->redirectToRoute('tag_view', ['id' => $tag->getId()]);
        }
        return $this->render('tag/view.html.twig', [
            'tag' => $tag,
            'posts' => $tagRepository->findPosts($tag, $this->getUser(), $pageNum),
            'post_count' => $postCount,
            'page_count' => $pageCount,
            'page_num' => $pageNum,
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
