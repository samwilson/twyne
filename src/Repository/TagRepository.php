<?php

namespace App\Repository;

use App\Entity\Post;
use App\Entity\Redirect;
use App\Entity\Tag;
use App\Entity\User;
use App\Entity\UserGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method Tag|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tag|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tag[]    findAll()
 * @method Tag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TagRepository extends ServiceEntityRepository
{

    /** @var RedirectRepository */
    private $redirectRepository;

    public function __construct(ManagerRegistry $registry, RedirectRepository $redirectRepository)
    {
        parent::__construct($registry, Tag::class);
        $this->redirectRepository = $redirectRepository;
    }

    /**
     * @inheritDoc
     */
    public function find($id, $lockMode = null, $lockVersion = null): ?Tag
    {
        if (substr($id, 0, 1) === 'T') {
            $id = substr($id, 1);
        }
        return parent::find($id, $lockMode, $lockVersion);
    }

    public function createNew(string $title, ?string $description = null, ?string $wikidata = null): Tag
    {
        $tag = new Tag();
        $tag->setTitle($title);
        $tag->setDescription($description);
        $tag->setWikidata($wikidata);
        $this->getEntityManager()->persist($tag);
        $this->getEntityManager()->flush();
        return $tag;
    }

    public function countPosts(Tag $tag, ?User $user = null): int
    {
        return $this->getPostsQueryBuilder($tag, $user)
            ->select('COUNT(p)')
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_SINGLE_SCALAR);
    }

    /**
     * @param Tag[] $tags
     */
    public function countAllPostsInAny(array $tags): int
    {
        $where = [];
        foreach ($tags as $i => $tag) {
            $where[] = "tag_id = :tag$i";
        }
        $sql = 'SELECT COUNT(DISTINCT post_id) AS total FROM post_tag WHERE ' . join(' OR ', $where);
        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        foreach ($tags as $i => $tag) {
            $stmt->bindValue("tag$i", $tag->getId());
        }
        $out = $stmt->executeQuery()->fetchAssociative();
        return (int)$out['total'];
    }

    /**
     * Get the number of posts that are in both the given tags.
     */
    public function countAllPostsInBoth(Tag $tag1, Tag $tag2): int
    {
        $sql = 'SELECT COUNT(*) AS total FROM post_tag pt1'
            . ' JOIN post_tag pt2 ON pt1.post_id = pt2.post_id'
            . ' WHERE pt1.tag_id = :tag1 AND pt2.tag_id = :tag2';
        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->bindValue('tag1', $tag1->getId());
        $stmt->bindValue('tag2', $tag2->getId());
        $out = $stmt->executeQuery()->fetchAssociative();
        return (int)$out['total'];
    }

    /**
     * @param Tag $tag
     * @param User|null $user
     * @param int|null $pageNum
     * @return array<Post>
     */
    public function findPosts(Tag $tag, ?User $user = null, ?int $pageNum = 1): array
    {
        $pageSize = 10;
        return $this->getPostsQueryBuilder($tag, $user)
            ->setMaxResults($pageSize)
            ->setFirstResult(($pageNum - 1) * $pageSize)
            ->getQuery()
            ->getResult();
    }

    private function getPostsQueryBuilder(Tag $tag, ?User $user = null): QueryBuilder
    {
        if (!$tag->getId()) {
            throw new Exception('Tag not loaded.');
        }
        $groupList = $user ? $user->getGroupIdList() : false;
        if (!$groupList) {
            $groupList = UserGroup::PUBLIC;
        }
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('p')
            ->from(Post::class, 'p')
            ->join('p.tags', 't', Join::WITH, 't.id = :tag_id')
            ->setParameter('tag_id', $tag->getId())
            ->andWhere('p.view_group IN (' . $groupList . ')')
            ->orderBy('p.date', 'DESC');
    }

    public function findAllOrderedByCount(?User $user)
    {
        $groupList = $user ? $user->getGroupIdList() : UserGroup::PUBLIC;
        $sql = 'SELECT tag.*, COUNT(*) AS posts_count'
            . ' FROM tag'
            . '   JOIN post_tag ON (tag_id=tag.id)'
            . '   JOIN post ON post_tag.post_id=post.id'
            . ' WHERE post.view_group_id IN (' . $groupList . ')'
            . ' GROUP BY tag.id'
            . ' ORDER BY posts_count DESC';
        $stmt = $this->getEntityManager()->getConnection()->query($sql);
        $stmt->execute();
        $data = $stmt->fetchAll();
        // Find the max.
        $maxPostCount = 0;
        foreach ($data as $datum) {
            $maxPostCount = max($maxPostCount, $datum['posts_count']);
        }
        // Add the weight.
        $out = [];
        foreach ($data as $datum) {
            $datum['weight'] = round($datum['posts_count'] / $maxPostCount) * 10;
            $out[] = $datum;
        }
        return $out;
    }

    /**
     * Get a map of all Wikidata IDs to Twyne Tag IDs.
     * @return array<string,int>
     */
    public function findWikidata(): array
    {
        $data = $this->getEntityManager()
            ->getConnection()
            ->executeQuery('SELECT wikidata, id FROM tag WHERE wikidata IS NOT NULL')
            ->fetchAllAssociative();
        $out = [];
        foreach ($data as $row) {
            $out[$row['wikidata']] = $row['id'];
        }
        return $out;
    }

    /**
     * Search for tags by title.
     * @param string $term
     * @param int $pageNum
     * @param User|null $user
     * @return array
     */
    public function search(string $term, int $pageNum, ?User $user = null): array
    {
        if (empty($term)) {
            return [];
        }
        $pageSize = 20;
        $groupList = $user ? $user->getGroupIdList() : false;
        if (!$groupList) {
            $groupList = UserGroup::PUBLIC;
        }
        $qb = $this->getEntityManager()->createQueryBuilder();
        return $qb
            ->select('t')
            ->from(Tag::class, 't')
            ->join('t.posts', 'p')
            ->where($qb->expr()->like('t.title', ':q'))
            ->setParameter('q', "%$term%")
            ->andWhere('p.view_group IN (' . $groupList . ')')
            ->orderBy('t.title', 'DESC')
            ->setMaxResults($pageSize)
            ->setFirstResult(($pageNum - 1) * $pageSize)
            ->getQuery()
            ->getResult();
    }

    public function setTagsOnPost(Post $post, array $tags): void
    {
        $post->setTags(new ArrayCollection());
        foreach ($tags as $t) {
            $tag = $this->createQueryBuilder('t')
                ->where('t.title LIKE :t')
                ->setParameter('t', $t)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
            if (!$tag) {
                $tag = new Tag();
                $tag->setTitle($t);
                $this->getEntityManager()->persist($tag);
                $this->getEntityManager()->flush();
            }
            $post->addTag($tag);
        }
    }

    /**
     * Merge Tag 1 into Tag 2.
     */
    public function merge(
        Tag $tag1,
        Tag $tag2,
        string $title = null,
        string $wikidata = null,
        string $description = null
    ): void {
        $this->getEntityManager()->transactional(function () use ($tag1, $tag2, $title, $wikidata, $description) {
            // Move all posts.
            $sql = 'INSERT IGNORE INTO post_tag (post_id, tag_id)'
                . ' SELECT post_id, :tag2_id FROM post_tag WHERE tag_id = :tag1_id';
            $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
            $tag1Id = $tag1->getId();
            $tag2Id = $tag2->getId();
            $stmt->bindParam('tag1_id', $tag1Id);
            $stmt->bindParam('tag2_id', $tag2Id);
            $stmt->executeQuery();

            // Record HTTP redirection.
            $this->redirectRepository->addRedirect(
                '/T' . $tag1->getId(),
                '/T' . $tag2->getId(),
                $this->redirectRepository->getStatuses()['permanent']
            );

            // Remove Tag 1.
            $this->getEntityManager()->remove($tag1);

            // Update tag's metadata.
            if ($title) {
                $tag2->setTitle($title);
            }
            if ($wikidata) {
                $tag2->setWikidata($wikidata);
            }
            if ($description) {
                $tag2->setDescription($description);
            }
        });
    }

    public function saveFromRequest(Tag $tag, Request $request): void
    {
        $tag->setTitle(trim($request->get('title')));
        $tag->setDescription(trim($request->get('description')));
        $wdNum = preg_filter('/[^0-9]/', '', $request->get('wikidata'));
        $wdId = $wdNum ? "Q$wdNum" : null;
        $tag->setWikidata($wdId);
        $this->getEntityManager()->persist($tag);
        $this->getEntityManager()->flush();
    }
}
