<?php

namespace App\Controller;

use App\Repository\TagRepository;
use App\Repository\WikidataRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class TagController extends ControllerBase
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
        WikidataRepository $wikidataRepository,
        int $id,
        int $pageNum = 1
    ): Response {
        $tag = $tagRepository->find($id);
        if (!$tag) {
            throw $this->createNotFoundException();
        }
        $postCount = $tagRepository->countPosts($tag, $this->getUser());
        $pageCount = ceil($postCount / 10);
        if ($pageCount > 0 && $pageNum > $pageCount) {
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
        $entity = false;
        if ($tag->getWikidata()) {
            $entity = $wikidataRepository->getData($tag->getWikidata());
        }
        return $this->render('tag/view.html.twig', [
            'tag' => $tag,
            'posts' => $tagRepository->findPosts($tag, $this->getUser(), $pageNum),
            'post_count' => $postCount,
            'page_count' => $pageCount,
            'page_num' => $pageNum,
            'entity' => $entity,
        ]);
    }

    /**
     * @Route("/tags/search", name="tag_search")
     */
    public function search(TagRepository $tagRepository, Request $request)
    {
        return $tagRepository->findBy(['title' => $request->get('q')]);
    }

    /**
     * @Route("/tags.json", name="tag_search_json")
     */
    public function tagSearchJson(
        Request $request,
        TagRepository $tagRepository,
        WikidataRepository $wikidataRepository
    ) {
        $q = $request->get('q', '');
        $page = (int)$request->get('page', 1);
        $tagsResults = $tagRepository->search($q, $page, $this->getUser());
        $tags = [
            'results' => [],
        ];
        if ($page === 1) {
            $tags['pagination'] = ['more' => true];
        }
        foreach ($tagsResults as $tag) {
            $tags['results'][] = [
                'id' => $tag->getTitle(),
                'text' => $tag->getTitle(),
            ];
        }
        // If not many local results are found, augment them with Wikidata items.
        if ($q && $page > 1 && count($tags['results']) < 10) {
            $wikidata = $wikidataRepository->search($q);
            foreach ($wikidata['results'] as $result) {
                $tags['results'][] = [
                    'id' => $result['text'],
                    'text' => $result['text'] . ' (' . $result['id'] . ' - ' . $result['description'] . ')',
                ];
            }
        }
        return new JsonResponse($tags);
    }

    /**
     * @Route("/wikidata.json", name="tag_wikidata_search")
     */
    public function searchWikidata(Request $request, WikidataRepository $wikidataRepository)
    {
        return new JsonResponse($wikidataRepository->search($request->get('q', '')));
    }

    /**
     * @Route("/T{id}/edit", name="tag_edit", requirements={"id"="\d+"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function edit(WikidataRepository $wikidataRepository, TagRepository $tagRepository, $id)
    {
        $tag = $tagRepository->find($id);
        if (!$tag) {
            throw $this->createNotFoundException();
        }
        $entity = false;
        if ($tag->getWikidata()) {
            $entity = $wikidataRepository->getData($tag->getWikidata());
        }
        return $this->render('tag/edit.html.twig', [
            'tag' => $tag,
            'entity' => $entity,
        ]);
    }

    /**
     * @Route("/tag-save", name="tag_save")
     * @IsGranted("ROLE_ADMIN")
     */
    public function save(Request $request, TagRepository $tagRepository)
    {
        $tag = $tagRepository->find($request->get('id'));
        if (!$tag) {
            throw $this->createNotFoundException();
        }
        $tagRepository->saveFromRequest($tag, $request);
        return $this->redirectToRoute('tag_view', ['id' => $tag->getId()]);
    }
}
