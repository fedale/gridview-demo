<?php

namespace App\Controller\Gridview;

use App\Entity\Post;
use Fedale\GridviewBundle\Controller\AbstractCrudGridController;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/gridview/posts', name: 'gridview_post_')]
class PostController extends AbstractCrudGridController
{
    protected function getDataClass(): string
    {
        return Post::class;
    }

    protected function dataConfig(): array
    {
        return [
            'model' => Post::class,
            'alias' => 'e',
            'pagination' => ['defaultPageSize' => 20],
            'searchFields' => [
                'id' => ['number', 'e.id'],
                'title' => ['text', 'e.title'],
                'status' => ['choice', 'e.status'],
                'isFeatured' => ['boolean', 'e.isFeatured'],
                'author' => ['relation', 'e.author'],
                'category' => ['relation', 'e.category'],
            ],
            'sort' => [
                'map' => [
                    'id' => ['asc' => ['e.id'], 'desc' => ['e.id']],
                    'title' => ['asc' => ['e.title'], 'desc' => ['e.title']],
                    'status' => ['asc' => ['e.status'], 'desc' => ['e.status']],
                    'isFeatured' => ['asc' => ['e.isFeatured'], 'desc' => ['e.isFeatured']],
                ],
                'default' => ['id' => 'desc'],
            ],
        ];
    }

    /** @return array<int, mixed> */
    protected function buildColumns(): array
    {
        return [
            [
                'attribute' => 'id',
                'label' => 'Id',
                'type' => 'number',
                'sortable' => true,
                'filter' => ['type' => 'number'],
            ],
            [
                'attribute' => 'title',
                'label' => 'Title',
                'sortable' => true,
                'filter' => ['type' => 'text'],
                'control' => ['type' => 'text', 'required' => true],
            ],
            [
                'attribute' => 'status',
                'label' => 'Status',
                'type' => 'select',
                'sortable' => true,
                'filter' => ['type' => 'choice'],
                'control' => ['type' => 'enum', 'required' => true, 'options' => ['class' => 'App\\Enum\\PostStatus']],
            ],
            [
                'attribute' => 'isFeatured',
                'label' => 'Is featured',
                'type' => 'boolean',
                'sortable' => true,
                'filter' => ['type' => 'boolean'],
                'control' => ['type' => 'boolean', 'required' => true],
            ],
            [
                'attribute' => 'author',
                'label' => 'Author',
                'filter' => ['type' => 'relation'],
                'control' => ['type' => 'relation', 'required' => false, 'options' => ['class' => \App\Entity\User::class]],
                'value' => fn(array $data): mixed => $data['author']['id'] ?? null,
            ],
            [
                'attribute' => 'category',
                'label' => 'Category',
                'filter' => ['type' => 'relation'],
                'control' => [
                    'type' => 'relation',
                    'required' => false,
                    'options' => ['class' => \App\Entity\Category::class, 'choice_label' => 'name'],
                ],
                'value' => fn(array $data): mixed => $data['category']['name'] ?? $data['category']['id'] ?? null,
            ],
            ['type' => 'action', 'label' => false],
        ];
    }
}
