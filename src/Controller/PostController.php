<?php

namespace App\Controller;

use App\Entity\File;
use App\Entity\Post;
use App\Entity\Syndication;
use App\Entity\User;
use App\Filesystems;
use App\Repository\ContactRepository;
use App\Repository\FileRepository;
use App\Repository\PostRepository;
use App\Repository\UserGroupRepository;
use App\Rss;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use IntlDateFormatter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class PostController extends AbstractController
{

    /**
     * @return User|null
     */
    protected function getUser()
    {
        return parent::getUser();
    }


    /**
     * @Route("/", name="home")
     */
    public function home(PostRepository $postRepository): Response
    {
        return $this->render('home.html.twig', [
            'posts' => $postRepository->recent(10, $this->getUser()),
        ]);
    }

    /**
     * @Route("/rss.xml", name="rss")
     */
    public function rss(Rss $rss, PostRepository $postRepository): Response
    {
        return new Response($rss->get($postRepository->recent(20, $this->getUser())), 200, [
            'Content-Type' => 'text/xml',
        ]);
    }

    /**
     * @Route("/post/new", name="post_create")
     * @Route("/P{id}/edit", name="post_edit", requirements={"id"="\d+"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function editPost(
        Request $request,
        PostRepository $postRepository,
        ContactRepository $contactRepository,
        UserGroupRepository $userGroupRepository,
        $id = null
    ) {
        $post = $id ? $postRepository->find($id) : new Post();
        if ($request->get('in_reply_to')) {
            $post->setInReplyTo($postRepository->find($request->get('in_reply_to')));
        }
        if (!$post->getAuthor()) {
            $post->setAuthor($this->getUser()->getContact());
        }
        return $this->render('post/form.html.twig', [
            'post' => $post,
            'contacts' => $contactRepository->findBy([], ['name' => 'ASC']),
            'max_filesize' => UploadedFile::getMaxFilesize(),
            'user_groups' => $userGroupRepository->findAll(),
        ]);
    }

    /**
     * @Route("/P{id}/delete", name="post_delete", requirements={"id"="\d+"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function deletePost(
        Request $request,
        EntityManagerInterface $entityManager,
        PostRepository $postRepository,
        Filesystems $filesystems,
        string $id
    ) {
        $post = $postRepository->find($id);
        $submittedToken = $request->request->get('token');
        if ($request->isMethod('post') && $this->isCsrfTokenValid('delete-post', $submittedToken)) {
            $filesystems->remove($post->getFile());
            foreach ($post->getSyndications() as $syndication) {
                $entityManager->remove($syndication);
            }
            $entityManager->remove($post);
            $entityManager->flush();
            $this->addFlash('success', 'Post deleted.');
            return $this->redirectToRoute('home');
        }
        return $this->render('post/delete.html.twig', [
            'post' => $post,
        ]);
    }

    /**
     * @Route("/upload", name="post_upload")
     * @IsGranted("ROLE_ADMIN")
     */
    public function uploadPosts(
        Request $request,
        ContactRepository $contactRepository,
        FileRepository $fileRepository,
        UserGroupRepository $userGroupRepository,
        PostRepository $postRepository
    ) {
        if ($request->isMethod('get')) {
            return $this->render('post/upload.html.twig', [
                'max_filesize' => UploadedFile::getMaxFilesize(),
                'contacts' => $contactRepository->findBy([], ['name' => 'ASC']),
                'timezones' => DateTimeZone::listIdentifiers(),
                'user_groups' => $userGroupRepository->findAll(),
            ]);
        }
        /** @var UploadedFile[] $files */
        $files = $request->files->get('files');
        foreach ($files as $uploadedFile) {
            if (!$fileRepository->checkFile($uploadedFile)) {
                $this->addFlash('notice', 'Unable to upload file: ' . $uploadedFile->getClientOriginalName());
                continue;
            }
            $post = new Post();
            $postRepository->saveFromRequest($post, $request, $uploadedFile);
            $this->addFlash('success', 'Uploaded: P' . $post->getId() . ' — ' . $post->getTitle());
        }
        return $this->redirectToRoute('post_upload');
    }

    /**
     * @Route("/post/save", name="post_save")
     * @IsGranted("ROLE_ADMIN")
     */
    public function savePost(Request $request, PostRepository $postRepository)
    {
        $id = $request->get('id');
        /** @var Post $post */
        $post = $id ? $postRepository->find($id) : new Post();
        $postRepository->saveFromRequest($post, $request, $request->files->get('new_file'));
        $this->addFlash('success', 'Post saved.');
        return $this->redirectToRoute('post_view', ['id' => $post->getId()]);
    }

    /**
     * @Route(
     *     "/P{id}{size}.{ext}",
     *     name="file",
     *     requirements={"id"="\d+", "size"="(F|D|T)", "ext"="(jpg|png|gif|pdf)"}
     * )
     */
    public function renderFile(
        PostRepository $postRepository,
        Filesystems $filesystems,
        string $id,
        string $size,
        string $ext
    ) {
        // Get the metadata.
        $post = $postRepository->find($id);
        if (!$post) {
            throw $this->createNotFoundException();
        }
        if (!$post->canBeViewedBy($this->getUser())) {
            throw $this->createAccessDeniedException();
        }
        $fileRecord = $post->getFile();
        if (!$fileRecord) {
            throw $this->createNotFoundException();
        }
        // Other sizes are all JPEGs.
        if ($size !== File::SIZE_FULL && $ext !== 'jpg') {
            return $this->redirectToRoute('file', ['id' => $id, 'size' => $size, 'ext' => 'jpg']);
        }
        if ($size === File::SIZE_FULL && $ext !== $fileRecord->getExtension()) {
            return $this->redirectToRoute('file', ['id' => $id, 'size' => $size, 'ext' => $fileRecord->getExtension()]);
        }
        // Return the stream.
        $outStream = $filesystems->read($fileRecord, $size);
        $response = new StreamedResponse(function () use ($outStream) {
            $stdOut = fopen('php://output', 'w');
            stream_copy_to_stream($outStream, $stdOut);
        });
        $mimeType = $size === File::SIZE_FULL ? $fileRecord->getMimeType() : 'image/jpg';
        $response->headers->set('Content-Type', $mimeType);
        return $response;
    }

    /**
     * @Route("/P{id}", name="post_view", requirements={"id"="\d+"})
     */
    public function viewPost($id, PostRepository $postRepository)
    {
        $post = $postRepository->find($id);
        if (!$post) {
            throw $this->createNotFoundException();
        }
        if (!$post->canBeViewedBy($this->getUser())) {
            throw $this->createAccessDeniedException();
        }
        return $this->render('post/view.html.twig', [
            'post' => $post,
        ]);
    }

    /**
     * @Route("/dates", name="dates")
     * @Route("/D{year}", name="year", requirements={"year"="\d{4}"})
     * @Route("/D{year}{month}", name="month", requirements={"year"="\d{4}", "month"="\d{2}"})
     */
    public function dates(Request $request, PostRepository $postRepository)
    {
        $year = $request->get('year');
        $month = $request->get('month');

        // Redirect to current month if no date given.
        if (is_null($year) && is_null($month)) {
            $recent = $postRepository->recent();
            if (isset($recent[0])) {
                $date = $recent[0]->getDate();
                $params = ['year' => $date->format('Y'), 'month' => $date->format('m')];
                return $this->redirectToRoute('month', $params);
            }
        }

        // Page title.
        $fmt = new IntlDateFormatter(null, IntlDateFormatter::LONG, IntlDateFormatter::NONE);
        $fmt->setPattern('MMMM YYYY');
        $title = $fmt->format(mktime(0, 0, 0, $month, 1, $year));

        return $this->render('post/dates.html.twig', [
            'title' => $title,
            'year' => $year,
            'month' => $month,
            'years' => $postRepository->getYears(),
            'months' => $postRepository->getMonths($year),
            'posts' => $postRepository->findByDateRange($year, $month, $this->getUser()),
        ]);
    }

    /**
     * @Route("/map/{ne_lat}_{ne_lng}_{sw_lat}_{sw_lng}.json", name="mapdata", requirements={
     *     "ne_lat"="[0-9.-]+",
     *     "ne_lng"="[0-9.-]+",
     *     "sw_lat"="[0-9.-]+",
     *     "sw_lng"="[0-9.-]+"
     * })
     */
    public function mapData(Request $request, PostRepository $postRepository)
    {
        return new JsonResponse($postRepository->findByBoundingBox(
            $request->get('ne_lat'),
            $request->get('ne_lng'),
            $request->get('sw_lat'),
            $request->get('sw_lng'),
            $this->getUser()
        ));
    }

    /**
     * @Route("/map", name="map")
     */
    public function map()
    {
        return $this->render('post/map.html.twig');
    }
}
