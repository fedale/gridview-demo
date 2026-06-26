<?php

namespace App\Controller\Gridview;

use App\Entity\Tag;
use Fedale\GridviewBundle\Controller\AbstractCrudGridController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Gridview replica of {@see \App\Controller\Admin\TagCrudController} (EasyAdmin).
 *
 * Functional-first: list + filter + sort + full CRUD on the single `name` field.
 * The EasyAdmin extras (post-count template, custom "view posts" action) are left
 * out on purpose and added back per feedback.
 */
#[Route('/gridview/tag', name: 'gridview_tag_')]
class TagController extends AbstractCrudGridController
{
    protected function getDataClass(): string
    {
        return Tag::class;
    }

    protected function configure(): array
    {
        return [
            'id'             => 'tag',
            'title'          => 'tag.label',
            'addLabel'       => 'New tag',
            'exportFilename' => 'tags',
            'indexTemplate'  => 'gridview/index.html.twig',
        ];
    }

    protected function getDataProviderConfig(): array
    {
        return [
            'models'     => Tag::class,
            'pagination' => ['defaultPageSize' => 20],
            'sort'       => [
                'id'   => ['asc' => ['t.id'],   'desc' => ['t.id'],   'default' => 'desc'],
                'name' => ['asc' => ['t.name'], 'desc' => ['t.name'], 'default' => 'asc'],
            ],
        ];
    }

    /** @return array<int, mixed> */
    protected function buildColumns(): array
    {
        return [
            'id',
            [
                'attribute' => 'name',
                'label'     => 'tag.name',
                'sortable'  => true,
                'filter'    => ['type' => 'text'],
                'editable'  => true,
                'control'   => [
                    'type'     => 'text',
                    'required' => true,
                ],
            ],
            ['type' => 'action', 'label' => 'Actions'],
        ];
    }
}
