<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Fedale\GridviewBundle\Form\SearchForm;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private SearchForm $searchForm)
    {
        parent::__construct($registry, Category::class);
    }

    /** QueryBuilder consumed by the gridview EntityDataProvider (filters + sort + bulk). */
    public function search(array $params = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('c')->select('c');

        // Computed post counts surfaced as row attributes. Correlated subqueries
        // keep one row per category (Paginator-friendly) instead of a join+groupBy.
        // Category->Post is a OneToMany, so the subqueries match on p.category = c.
        // `publishedCount` mirrors the admin's Post::isPublished() (status = published).
        $qb
            ->addSelect('(SELECT COUNT(p.id) FROM App\\Entity\\Post p WHERE p.category = c) AS postCount')
            ->addSelect("(SELECT COUNT(pp.id) FROM App\\Entity\\Post pp WHERE pp.category = c AND pp.status = 'published') AS publishedCount");

        $this->searchForm->applyFilters($qb, $params, [
            'name'     => ['text', 'c.name'],
            'position' => ['number', 'c.position'],
        ]);

        return $qb;
    }
}
