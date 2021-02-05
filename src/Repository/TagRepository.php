<?php

namespace App\Repository;

use App\Entity\Post;
use App\Entity\Tag;
use App\Entity\User;
use App\Entity\UserGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;

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

    public function getFromIdsString(string $ids): array
    {
        $include = [];
        $exclude = [];
        foreach (array_map('trim', explode(',', $ids)) as $t) {
        }
        return [
            'include' => $include,
            'exclude' => $exclude,
        ];
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

    public function getFromString($str): Collection
    {
        $out = new ArrayCollection();
        foreach (explode(';', $str) as $t) {
            $tag = $this->findBy(['title' => $t]);
            if (!$tag) {
                $tag = new Tag();
                $tag->setTitle($t);
            }
            $out->add($tag);
        }
        return $out;
    }

    public function setTagsOnPost(Post $post, string $tags): void
    {
        $post->setTags(new ArrayCollection());
        foreach (array_filter(array_map('trim', explode(';', $tags))) as $t) {
            $tag = $this->findOneBy(['title' => $t]);
            if (!$tag) {
                $tag = new Tag();
                $tag->setTitle($t);
                $this->getEntityManager()->persist($tag);
            }
            $post->addTag($tag);
        }
    }
}
