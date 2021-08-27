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
use App\Settings;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use IntlDateFormatter;
use PHPUnit\Util\Json;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PostController extends ControllerBase
{

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
            'prev_post' => $postRepository->findPrevByDate($post, $this->getUser()),
            'next_post' => $postRepository->findNextByDate($post, $this->getUser()),
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
            $this->addFlash(self::FLASH_SUCCESS, 'Post deleted.');
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
                $this->addFlash(self::FLASH_NOTICE, 'Unable to upload file: ' . $uploadedFile->getClientOriginalName());
                continue;
            }
            $post = new Post();
            $postRepository->saveFromRequest($post, $request, $uploadedFile);
            $this->addFlash(self::FLASH_SUCCESS, 'Uploaded: P' . $post->getId() . ' — ' . $post->getTitle());
        }
        return $this->redirectToRoute('post_upload');
    }

    private function hasApiKey(Request $request, Settings $settings): bool
    {
        $apiKey = $request->headers->get('Authorization');
        return $apiKey && $apiKey === 'Twyne api_key=' . $settings->apiKey();
    }

    /**
     * @Route("/post/search", name="post_search")
     */
    public function infoApi(FileRepository $fileRepository, Request $request, Settings $settings)
    {
        $publicOnly = !$this->hasApiKey($request, $settings);
        $checksums = array_filter(explode('|', $request->get('checksums')));
        $posts = [];
        if ($checksums) {
            $files = $fileRepository->findByChecksums($checksums, $publicOnly);
            /** @var File $file */
            foreach ($files as $file) {
                $postId = $file->getPost()->getId();
                $posts[] = [
                    'id' => $postId,
                    'title' => $file->getPost()->getTitle(),
                    'url' => $this->generateUrl('post_view', ['id' => $postId], UrlGeneratorInterface::ABSOLUTE_URL),
                ];
            }
        }
        return $this->json([
            'post_count' => count($posts),
            'posts' => $posts,
        ]);
    }

    /**
     * @Route("/upload-api", name="post_upload_api")
     */
    public function uploadApi(
        Request $request,
        FileRepository $fileRepository,
        PostRepository $postRepository,
        UserGroupRepository $userGroupRepository,
        Settings $settings
    ) {
        if (!$request->isMethod('POST')) {
            return $this->json(['error' => 'post-request-required']);
        }
        if (!$this->hasApiKey($request, $settings)) {
            return $this->json(['error' => 'invalid-api-key']);
        }
        $files = $request->files->get('files');
        if (!$files) {
            return $this->json(['error' => 'no-files']);
        }
        $out = [
            'success' => [],
            'fail' => [],
        ];
        // Turn a user group's name into its ID.
        $viewGroup = $request->get('view_group');
        if (!is_numeric($viewGroup)) {
            $request->attributes->set('view_group', $userGroupRepository->findOrCreate($viewGroup));
        }
        foreach ($files as $uploadedFile) {
            if (!$fileRepository->checkFile($uploadedFile)) {
                $out['fail'][] = 'Unable to upload file: ' . $uploadedFile->getClientOriginalName();
                continue;
            }
            $post = new Post();
            $postRepository->saveFromRequest($post, $request, $uploadedFile);
            $url = $this->generateUrl('post_view', ['id' => $post->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
            $out['success'][] = "Uploaded: $url — " . $post->getTitle();
        }
        $out['upload_count'] = count($out['success']);
        return $this->json($out);
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
        $this->addFlash(self::FLASH_SUCCESS, 'Post saved.');
        $returnRoute = $request->get('save-edit') ? 'post_edit' : 'post_view';
        return $this->redirectToRoute($returnRoute, ['id' => $post->getId()]);
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
            'prev_post' => $postRepository->findPrevByDate($post, $this->getUser()),
            'next_post' => $postRepository->findNextByDate($post, $this->getUser()),
        ]);
    }

    /**
     * @Route("/dates", name="dates")
     * @Route("/D{year}", name="year", requirements={"year"="\d{4}"})
     * @Route("/D{year}{month}", name="month", requirements={"year"="\d{4}", "month"="\d{2}"})
     * @Route(
     *     "/D{year}{month}/page-{pageNum}",
     *     name="month_page",
     *     requirements={"year"="\d{4}", "month"="\d{2}", "pageNum"="\d+"}
     * )
     */
    public function dates(Request $request, PostRepository $postRepository, $pageNum = 1)
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

        if ($pageNum === 1 && $request->get('_route') === 'month_page' || $pageNum < 1) {
            // Ensure only one form of URL for page 1, and avoid page 0.
            return $this->redirectToRoute('month', ['year' => $year, 'month' => $month]);
        }

        // Page title.
        if ($month) {
            $fmt = new IntlDateFormatter(null, IntlDateFormatter::LONG, IntlDateFormatter::NONE);
            $fmt->setPattern('MMMM YYYY');
            $title = $fmt->format(mktime(0, 0, 0, $month, 1, $year));
        } else {
            $title = $year;
        }

        $postCount = $postRepository->countByDate($year, $month, $this->getUser());
        $pageCount = ceil($postCount / 10);

        return $this->render('post/dates.html.twig', [
            'title' => $title,
            'year' => $year,
            'month' => $month,
            'years' => $postRepository->getYears(),
            'months' => $postRepository->getMonths($year, $this->getUser()),
            'posts' => $postRepository->findByDate($year, $month, $this->getUser(), $pageNum),
            'post_count' => $postCount,
            'page_count' => $pageCount,
            'page_num' => $pageNum,
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
