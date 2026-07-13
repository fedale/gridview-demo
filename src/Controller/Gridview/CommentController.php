<?php

namespace App\Controller\Gridview;

use App\Entity\Comment;
use Fedale\GridviewBundle\Controller\AbstractCrudGridController;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/gridview/comment', name: 'gridview_comment_')]
class CommentController extends AbstractCrudGridController
{
    protected function getDataClass(): string
    {
        return Comment::class;
    }

    protected function viewConfig(): array
    {
        return [
            'options' => [
                'display' => [
                    'layout' => [
                        'header' => '{heading}',
                        'footer' => '{pagination} {resultsSummary} {pageSize}',
                    ]
                ]

            ]
        ];
    }

    protected function dataConfig(): array
    {
        return [
            'model' => Comment::class,
            'alias' => 'e',
            'pagination' => ['defaultPageSize' => 20],
            'searchFields' => [
                'id' => ['number', 'e.id'],
                'content' => ['text', 'e.content'],
                'publishedAt' => ['date', 'e.publishedAt'],
                'status' => ['choice', 'e.status'],
                'author' => ['relation', 'e.author'],
                'post' => ['relation', 'e.post'],
            ],
            'sort' => [
                'map' => [
                    'id' => ['asc' => ['e.id'], 'desc' => ['e.id']],
                    'content' => ['asc' => ['e.content'], 'desc' => ['e.content']],
                    'publishedAt' => ['asc' => ['e.publishedAt'], 'desc' => ['e.publishedAt']],
                    'status' => ['asc' => ['e.status'], 'desc' => ['e.status']],
                ],
                'default' => ['id' => 'desc'],
            ],
        ];
    }

    /** @return array<int, mixed> */
    protected function buildColumns(): array
    {
        return [
            ['attribute' => 'id', 'label' => 'Id', 'sortable' => true, 'filter' => ['type' => 'number']],
            [
                'attribute' => 'content',
                'label' => 'Content',
                'sortable' => true,
                'filter' => ['type' => 'text'],
                'control' => ['type' => 'text', 'required' => true],
            ],
            [
                'attribute' => 'publishedAt',
                'label' => 'Published at',
                'sortable' => true,
                'filter' => ['type' => 'date'],
                'control' => ['type' => 'datetime', 'required' => true],
            ],
            [
                'attribute' => 'status',
                'label' => 'Status',
                'sortable' => true,
                'filter' => ['type' => 'choice', 'options' => ['choices' => \App\Enum\CommentStatus::filterChoices(), 'required' => false, 'placeholder' => 'any.status']],
                'control' => ['type' => 'enum', 'required' => true, 'options' => ['class' => 'App\\Enum\\CommentStatus']],
            ],
            [
                'attribute' => 'author',
                'label' => 'Author',
                'filter' => ['type' => 'relation'],
                'control' => ['type' => 'relation', 'required' => false, 'options' => ['class' => \App\Entity\User::class]],
                'value' => fn(array $data): mixed => $data['author']['id'] ?? null,
            ],
            [
                'attribute' => 'post',
                'label' => 'Post',
                'filter' => ['type' => 'relation'],
                'control' => [
                    'type' => 'relation',
                    'required' => false,
                    'options' => ['class' => \App\Entity\Post::class, 'choice_label' => 'title'],
                ],
                'value' => fn(array $data): mixed => $data['post']['title'] ?? $data['post']['id'] ?? null,
            ],
            ['type' => 'action', 'label' => false],
        ];
    }
}
