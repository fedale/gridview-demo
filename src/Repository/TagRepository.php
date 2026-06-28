<?php

namespace App\Repository;

use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Fedale\GridviewBundle\Form\SearchForm;

/**
 * @extends ServiceEntityRepository<Tag>
 */
class TagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private SearchForm $searchForm)
    {
        parent::__construct($registry, Tag::class);
    }

    /** QueryBuilder consumed by the gridview EntityDataProvider (filters + sort + bulk). */
    public function search(array $params = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('t')->select('t');

        // Computed post counts surfaced as row attributes. Correlated subqueries
        // keep one row per tag (Paginator-friendly) instead of a join+groupBy.
        // `publishedCount` mirrors the admin's Post::isPublished() (status = published).
        $qb
            ->addSelect('(SELECT COUNT(p.id) FROM App\\Entity\\Post p JOIN p.tags pt WHERE pt = t) AS postCount')
            ->addSelect("(SELECT COUNT(pp.id) FROM App\\Entity\\Post pp JOIN pp.tags ppt WHERE ppt = t AND pp.status = 'published') AS publishedCount");

        $this->searchForm->applyFilters($qb, $params, [
            'name' => ['text', 't.name'],
        ]);

        return $qb;
    }

    //    /**
    //     * @return Tag[] Returns an array of Tag objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Tag
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
