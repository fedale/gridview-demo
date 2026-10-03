<?php

namespace App\Repository;

use App\Entity\PostTranslation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Fedale\GridviewBundle\Form\SearchForm;

/**
 * @extends ServiceEntityRepository<PostTranslation>
 */
class PostTranslationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private SearchForm $searchForm)
    {
        parent::__construct($registry, PostTranslation::class);
    }

    /** QueryBuilder consumed by the gridview EntityDataProvider (filters + sort + bulk). */
    public function search(array $params = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('e')->select('e');

        $this->searchForm->applyFilters($qb, $params, [
            'locale' => ['text', 'e.locale'],
            'title' => ['text', 'e.title'],
            'post' => ['relation', 'e.post'],
            'isReviewed' => ['boolean', 'e.isReviewed'],
        ]);

        return $qb;
    }
}
