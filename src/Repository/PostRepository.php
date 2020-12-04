<?php

namespace App\Repository;

use App\Entity\File;
use App\Entity\Post;
use App\Filesystems;
use CrEOF\Spatial\PHP\Types\Geometry\Point;
use DateTime;
use DateTimeZone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use IntlDateFormatter;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Process\Process;

/**
 * @method Post|null findOneBy(array $criteria, array $orderBy = null)
 * @method Post[]    findAll()
 * @method Post[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PostRepository extends ServiceEntityRepository
{

    /** @var Filesystems */
    private $filesystems;

    /** @var ContactRepository */
    private $contactRepository;

    /** @var TagRepository */
    private $tagRepository;

    /** @var FileRepository */
    private $fileRepository;

    public function __construct(
        ManagerRegistry $registry,
        Filesystems $filesystems,
        ContactRepository $contactRepository,
        TagRepository $tagRepository,
        FileRepository $fileRepository
    ) {
        parent::__construct($registry, Post::class);
        $this->filesystems = $filesystems;
        $this->contactRepository = $contactRepository;
        $this->tagRepository = $tagRepository;
        $this->fileRepository = $fileRepository;
    }

    /**
     * @inheritDoc
     */
    public function find($id, $lockMode = null, $lockVersion = null): ?Post
    {
        if (substr($id, 0, 1) === 'P') {
            $id = substr($id, 1);
        }
        return parent::find($id, $lockMode, $lockVersion);
    }

    /**
     * @return Post[]
     */
    public function recent(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return string[]
     */
    public function getYears(): array
    {
        $sql = "SELECT YEAR(date) AS year FROM post GROUP BY YEAR(date) ORDER BY year DESC";
        $stmt = $this->getEntityManager()->getConnection()->query($sql);
        $stmt->execute();
        $years = [];
        foreach ($stmt->fetchAll() as $row) {
            $years[] = $row['year'];
        }
        return $years;
    }

    /**
     * @return string[]
     */
    public function getMonths($year): array
    {
        $sql = "SELECT DATE_FORMAT(date, '%m') AS month FROM post
            WHERE YEAR(date) = :year
            GROUP BY MONTH(date)
            ORDER BY month DESC";
        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->bindParam('year', $year);
        $stmt->execute();
        $months = [];
        foreach ($stmt->fetchAll() as $row) {
            $fmt = new IntlDateFormatter(null, IntlDateFormatter::LONG, IntlDateFormatter::NONE);
            $fmt->setPattern('MMMM');
            $months[$row['month']] = $fmt->format(mktime(0, 0, 0, $row['month'], 1, $year));
        }
        return $months;
    }

    public function findByDateRange($year, $month)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('YEAR(p.date) = :year')
            ->setParameter('year', $year)
            ->andWhere('MONTH(p.date) = :month')
            ->setParameter('month', $month)
            ->orderBy('p.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function saveFromRequest(Post $post, Request $request, ?UploadedFile $uploadedFile = null): void
    {
        // Title (the filename without the extension).
        if ($request->get('title')) {
            $post->setTitle($request->get('title'));
        } elseif ($uploadedFile) {
            // Somewhat normalize a filename into a title.
            $title = str_replace('_', ' ', pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME));
            $post->setTitle($title);
        }

        // Body.
        $post->setBody($request->get('body'));

        // Date.
        if ($request->get('date')) {
            $post->setDate(new DateTime($request->get('date', '@' . time()), new DateTimeZone('Z')));
        } elseif ($uploadedFile) {
            $cmd = new Process(['exiftool', '-json', $uploadedFile->getPathname()]);
            $cmd->mustRun();
            $data = json_decode($cmd->getOutput(), true);
            // We only request one file's metadata, so it's always going to be the first one.
            $exif = isset($data[0]) ? $data[0] : [];
            if (isset($exif['DateTimeOriginal'])) {
                // Format is YYYY:mm:dd HH:MM:SS[.ss][+/-HH:MM|Z]) in ExifIFD:DateTimeOriginal
                $tz = $request->get('timezone');
                if ($tz) {
                    $date = new DateTime($exif['DateTimeOriginal'] . ' ' . $tz);
                } else {
                    $date = new DateTime($exif['DateTimeOriginal'], new DateTimeZone('Z'));
                }
                $date->setTimezone(new DateTimeZone('Z'));
                $post->setDate($date);
            }
        }

        // Author.
        $author = $request->get('author');
        if ($author) {
            $post->setAuthor($this->contactRepository->getOrCreate($author));
        }

        // Tags.
        $this->tagRepository->setTagsOnPost($post, $request->get('tags', ''));

        // URL.
        $post->setUrl($request->get('url'));

        // Location.
        $longitude = $request->get('longitude');
        $latitude = $request->get('latitude');
        if ($longitude && $latitude) {
            $post->setLocation(new Point($longitude, $latitude));
        } else {
            $post->setLocation(null);
        }

        // In reply to.
        $inReplyToId = $request->get('in_reply_to');
        if ($inReplyToId) {
            $post->setInReplyTo($this->find($inReplyToId));
        }

        // Save post thus far.
        $this->getEntityManager()->persist($post);
        $this->getEntityManager()->flush();

        // File.
        if ($uploadedFile && $uploadedFile->isReadable()) {
            if (!$this->fileRepository->checkFile($uploadedFile)) {
                throw new Exception('Unable to save file.');
            }
            $file = $post->getFile() ?? new File();
            $file->setPost($post);
            $file->setMimeType($uploadedFile->getMimeType());
            $file->setSize($uploadedFile->getSize());
            $file->setChecksum(sha1_file($uploadedFile->getPathname()));
            $this->getEntityManager()->persist($file);
            $post->setFile($file);
            $this->getEntityManager()->persist($post);
            $this->getEntityManager()->flush();
            // Remove before adding, for replacement files with new extensions.
            $this->filesystems->remove($file);
            $this->filesystems->write($this->filesystems->data(), $file, $uploadedFile->getPathname());
        }
    }
}
