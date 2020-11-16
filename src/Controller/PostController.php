<?php

namespace App\Controller;

use App\Entity\File;
use App\Entity\Post;
use App\Filesystems;
use App\Repository\ContactRepository;
use App\Repository\FileRepository;
use App\Repository\PostRepository;
use App\Rss;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
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
    public function editPost(PostRepository $postRepository, ContactRepository $contactRepository, $id = null)
    {
        return $this->render('post/form.html.twig', [
            'post' => $id ? $postRepository->find($id) : new Post(),
            'contacts' => $contactRepository->findBy([], ['name' => 'ASC']),
            'max_filesize' => UploadedFile::getMaxFilesize(),
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
        PostRepository $postRepository
    ) {
        if ($request->isMethod('get')) {
            return $this->render('post/upload.html.twig', [
                'max_filesize' => UploadedFile::getMaxFilesize(),
                'contacts' => $contactRepository->findBy([], ['name' => 'ASC']),
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
     *     requirements={"id"="\d+", "size"="(F|D|T)", "ext"="(jpg|png|pdf)"}
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
        return $this->render('post/view.html.twig', [
            'post' => $postRepository->find($id),
        ]);
    }
}
