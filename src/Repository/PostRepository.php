<?php

namespace App\Repository;

use App\Entity\Post;
use App\Entity\Syndication;
use App\Entity\User;
use App\Entity\UserGroup;
use CrEOF\Spatial\PHP\Types\Geometry\Point;
use DateTime;
use DateTimeZone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\QueryBuilder;
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
    /** @var ContactRepository */
    private $contactRepository;

    /** @var TagRepository */
    private $tagRepository;

    /** @var FileRepository */
    private $fileRepository;

    /** @var SyndicationRepository */
    private $syndicationRepository;

    /** @var UserGroupRepository */
    private $userGroupRepository;

    public function __construct(
        ManagerRegistry $registry,
        ContactRepository $contactRepository,
        TagRepository $tagRepository,
        FileRepository $fileRepository,
        SyndicationRepository $syndicationRepository,
        UserGroupRepository $userGroupRepository
    ) {
        parent::__construct($registry, Post::class);
        $this->contactRepository = $contactRepository;
        $this->tagRepository = $tagRepository;
        $this->fileRepository = $fileRepository;
        $this->syndicationRepository = $syndicationRepository;
        $this->userGroupRepository = $userGroupRepository;
    }

    private function createQueryBuilderForPosts(?User $user = null): QueryBuilder
    {
        $groupList = $user ? $user->getGroupIdList() : false;
        if (!$groupList) {
            $groupList = UserGroup::PUBLIC;
        }
        return $this->createQueryBuilder('p')
            ->andWhere("p.view_group IN ($groupList)");
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

    public function findReplies(Post $post, ?User $user = null)
    {
        return $this->createQueryBuilderForPosts($user)
            ->andWhere('p.in_reply_to = :in_reply_to')
            ->setParameter('in_reply_to', $post)
            ->orderBy('p.date', 'asc')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Post[]
     */
    public function recent(int $limit = 10, ?User $user = null): array
    {
        return $this->createQueryBuilderForPosts($user)
            ->orderBy('p.date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all posts that have no location recorded.
     */
    public function findWithoutLocation()
    {
        return $this->createQueryBuilder('p')
            ->where("p.location IS NULL")
            ->orderBy('p.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByBoundingBox(string $neLat, string $neLng, string $swLat, string $swLng, ?User $user = null)
    {
        $ne = "$neLng $neLat";
        $se = "$neLng $swLat";
        $sw = "$swLng $swLat";
        $nw = "$swLng $neLat";
        // Note start and end points are the same.
        $wkt = "Polygon(($ne, $se, $sw, $nw, $ne))";
        $groupList = $user ? $user->getGroupIdList() : UserGroup::PUBLIC;
        $sql = "SELECT ST_X(location) AS lng, ST_Y(location) AS lat"
            . " FROM post"
            . " WHERE"
            . "   location IS NOT NULL"
            . "   AND ST_Contains(GeomFromText(:wkt), location)"
            . "   AND view_group_id IN ($groupList)"
            . " LIMIT 5000";
        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->bindParam('wkt', $wkt);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function createNew(): Post
    {
        $post = new Post();
        $publicGroup = $this->userGroupRepository->find(UserGroup::PUBLIC);
        $post->setViewGroup($publicGroup);
        $this->getEntityManager()->persist($post);
        return $post;
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
     * @return string[][]
     */
    public function getMonths($year, ?User $user = null): array
    {
        $groupList = $user ? $user->getGroupIdList() : false;
        if (!$groupList) {
            $groupList = UserGroup::PUBLIC;
        }
        $sql = "SELECT DATE_FORMAT(date, '%m') AS month, COUNT(*) AS count FROM post
            WHERE YEAR(date) = :year
                AND post.view_group_id IN ($groupList)
            GROUP BY MONTH(date)
            ORDER BY month DESC";
        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->bindParam('year', $year);
        $stmt->execute();
        $months = [];
        $fmt = new IntlDateFormatter(null, IntlDateFormatter::LONG, IntlDateFormatter::NONE);
        $fmt->setPattern('MMMM');
        foreach ($stmt->fetchAll() as $row) {
            $months[$row['month']] = [
                'name' => $fmt->format(mktime(0, 0, 0, $row['month'], 1, $year)),
                'count' => $row['count'],
                'num' => $row['month']
            ];
        }
        return $months;
    }

    public function findPrevByDate(Post $post, ?User $user = null): ?Post
    {
        $qb = $this->createQueryBuilderForPosts($user);
        $orderBy = new OrderBy();
        $orderBy->add('p.date', 'DESC');
        $orderBy->add('p.id', 'DESC');
        $out = $qb
            ->andWhere($qb->expr()->orX('p.date < :date', 'p.date = :date AND p.id < :id'))
            ->setParameter('date', $post->getDate())
            ->setParameter('id', $post->getId())
            ->orderBy($orderBy)
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();
        return $out[0] ?? null;
    }

    public function findNextByDate(Post $post, ?User $user = null): ?Post
    {
        $qb = $this->createQueryBuilderForPosts($user);
        $orderBy = new OrderBy();
        $orderBy->add('p.date', 'ASC');
        $orderBy->add('p.id', 'ASC');
        $out = $qb
            ->andWhere($qb->expr()->orX('p.date > :date', 'p.date = :date AND p.id > :id'))
            ->setParameter('date', $post->getDate())
            ->setParameter('id', $post->getId())
            ->orderBy($orderBy)
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();
        return $out[0] ?? null;
    }

    public function findByDate($year, $month, User $user = null, int $pageNum = 1)
    {
        $pageSize = 10;
        return $this->getDateQueryBuilder($year, $month, $user)
            ->setMaxResults($pageSize)
            ->setFirstResult(($pageNum - 1) * $pageSize)
            ->getQuery()
            ->getResult();
    }

    public function countByDate($year, $month, $user): int
    {
        return $this->getDateQueryBuilder($year, $month, $user)
            ->select('COUNT(p)')
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_SINGLE_SCALAR);
    }

    private function getDateQueryBuilder($year, $month, $user): QueryBuilder
    {
        return $this->createQueryBuilderForPosts($user)
            ->andWhere('YEAR(p.date) = :year')
            ->setParameter('year', $year)
            ->andWhere('MONTH(p.date) = :month')
            ->setParameter('month', $month)
            ->orderBy('p.date', 'DESC');
    }

    public function saveFromRequest(Post $post, Request $request, ?UploadedFile $uploadedFile = null): void
    {
        $this->getEntityManager()->transactional(function () use ($post, $request, $uploadedFile) {
            $this->doSaveFromRequest($post, $request, $uploadedFile);
        });
    }

    private function doSaveFromRequest(Post $post, Request $request, ?UploadedFile $uploadedFile = null): void
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

        // Get Exif data for the date and location (below).
        $exif = [];
        if ($uploadedFile) {
            $cmd = new Process(['exiftool', '-json', '-n', $uploadedFile->getPathname()]);
            $cmd->mustRun();
            $data = json_decode($cmd->getOutput(), true);
            // We only request one file's metadata, so it's always going to be the first one.
            $exif = isset($data[0]) ? $data[0] : [];
        }
        //dump($exif);

        // Date.
        if ($request->get('date')) {
            $post->setDate(new DateTime($request->get('date', '@' . time()), new DateTimeZone('Z')));
        } elseif ($uploadedFile) {
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

        // View group.
        $viewGroup = $request->get('view_group');
        if ($viewGroup) {
            $post->setViewGroup($this->userGroupRepository->find($viewGroup));
        }

        // Tags.
        $this->tagRepository->setTagsOnPost($post, $request->get('tags', []));

        // URL.
        $post->setUrl($request->get('url'));

        // Location.
        $longitude = $request->get('longitude');
        $latitude = $request->get('latitude');
        if (!($longitude || $latitude) && isset($exif['GPSLongitude']) && isset($exif['GPSLatitude'])) {
            $longitude = $exif['GPSLongitude'];
            $latitude = $exif['GPSLatitude'];
        }
        if ($longitude || $latitude) {
            $post->setLocation(new Point($longitude, $latitude));
        } else {
            $post->setLocation(null);
        }

        // In reply to.
        $inReplyToId = $request->get('in_reply_to');
        if ($inReplyToId) {
            $post->setInReplyTo($this->find($inReplyToId));
        }

        // Syndications. First add the new one, then delete any requested for deletion.
        $newSyndication = $request->get('new_syndication');
        if (!empty($newSyndication['url'])) {
            $syn = new Syndication();
            $syn->setPost($post);
            $syn->setUrl($newSyndication['url']);
            $syn->setLabel($newSyndication['label']);
            $this->getEntityManager()->persist($syn);
        }
        foreach ($request->get('syndications_to_delete', []) as $synToDeleteId) {
            $synToDelete = $this->syndicationRepository->find($synToDeleteId);
            if ($synToDelete) {
                $this->getEntityManager()->remove($synToDelete);
            }
        }

        // Save post thus far.
        $this->getEntityManager()->persist($post);
        $this->getEntityManager()->flush();

        // File.
        if ($uploadedFile && $uploadedFile->isReadable()) {
            if (!$this->fileRepository->checkFile($uploadedFile)) {
                throw new Exception('Unable to save file.');
            }
            $this->fileRepository->saveFile(
                $post,
                $uploadedFile->getPathname(),
                $uploadedFile->getMimeType(),
                $uploadedFile->getSize()
            );
        }
    }
}
