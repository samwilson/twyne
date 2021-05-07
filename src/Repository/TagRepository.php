<?php

namespace App\Repository;

use App\Entity\Post;
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
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    public function countPosts(Tag $tag, ?User $user = null): int
    {
        return $this->getPostsQueryBuilder($tag, $user)
            ->select('COUNT(p)')
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_SINGLE_SCALAR);
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
