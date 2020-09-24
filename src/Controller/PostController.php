<?php

namespace App\Controller;

use App\Entity\Post;
use App\Repository\PostRepository;
use App\Rss;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class PostController extends AbstractController
{

    /**
     * @Route("/", name="home")
     */
    public function home(PostRepository $postRepository): Response
    {
        return $this->render('home.html.twig', [
            'posts' => $postRepository->recent(),
        ]);
    }

    /**
     * @Route("/rss.xml", name="rss")
     */
    public function rss(Rss $rss, PostRepository $postRepository): Response
    {
        return new Response($rss->get($postRepository->recent()), 200, [
            'Content-Type' => 'text/xml',
        ]);
    }

    /**
     * @Route("/post/new", name="post_create")
     * @Route("/P{id}/edit", name="post_edit", requirements={"id"="\d+"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function form(PostRepository $postRepository, $id = null)
    {
        $post = $id ? $postRepository->find($id) : new Post();

        return $this->render('post/form.html.twig', [
            'post' => $post,
        ]);
    }

    /**
     * @Route("/post/save", name="post_save")
     * @IsGranted("ROLE_ADMIN")
     */
    public function createPost(Request $request, EntityManagerInterface $entityManager, PostRepository $postRepository)
    {
        $id = $request->get('id');
        $post = $id ? $postRepository->find($id) : new Post();
        $post->setTitle($request->get('title'));
        $post->setBody($request->get('body'));
        $date = new DateTime($request->get('date'), new DateTimeZone('Z'));
        $post->setDate($date);
        $entityManager->persist($post);
        $entityManager->flush();

        return $this->redirectToRoute('post_view', ['id' => $post->getId()]);
    }

    /**
     * @Route("/P{id}", name="post_view", requirements={"id"="\d+"})
     */
    public function view($id, PostRepository $postRepository)
    {
        return $this->render('post/view.html.twig', [
            'post' => $postRepository->find($id),
        ]);
    }
}
