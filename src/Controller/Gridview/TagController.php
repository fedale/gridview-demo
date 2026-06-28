<?php

namespace App\Controller\Gridview;

use App\Entity\Tag;
use Fedale\GridviewBundle\Column\DataColumn;
use Fedale\GridviewBundle\Controller\AbstractCrudGridController;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Markup;

/**
 * Gridview replica of {@see \App\Controller\Admin\TagCrudController} (EasyAdmin).
 *
 * Functional-first: list + filter + sort + full CRUD on the single `name` field,
 * plus a computed read-only posts column (COUNT subqueries in TagRepository,
 * surfaced as `postCount`/`publishedCount` row attributes by the data provider).
 * The EasyAdmin "popularity bar" visual is ported as a single column (progress
 * bar + count badge + published hint), mirroring admin/tag/_posts_popularity.
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
            'id' => 'tag',
            'title' => 'tag.label',
            'addLabel' => 'tag.addTag',
            'exportFilename' => 'tags',
            'indexTemplate' => 'gridview/index.html.twig',
            // Feed the shell's content-top search box (the in-grid global-search
            // widget is hidden via CSS so the top bar is the single search).
            'options' => ['globalSearch' => ['name']],
        ];
    }

    protected function getDataProviderConfig(): array
    {
        return [
            'models' => Tag::class,
            'pagination' => ['defaultPageSize' => 20],
            'sort' => [
                'id' => ['asc' => ['t.id'], 'desc' => ['t.id'], 'default' => 'desc'],
                'name' => ['asc' => ['t.name'], 'desc' => ['t.name'], 'default' => 'asc'],
                'postCount' => ['asc' => ['postCount'], 'desc' => ['postCount']],
            ],
            'enableMultiSort' => true,
            'defaultOrder' => ['name' => 'asc', 'id' => 'asc'],

        ];
    }

    /** @return array<int, mixed> */
    protected function buildColumns(): array
    {
        return [
            'id',
            [
                'attribute' => 'name',
                'label' => 'tag.name',
                'sortable' => true,
                'filter' => ['type' => 'text'],
                'editable' => true,
                // Render the name inside a primary badge.
                'value' => static fn(array $data): Markup => new Markup(\sprintf(
                    '<span class="badge badge-primary" style="font-size: 0.95em;">%s</span>',
                    htmlspecialchars((string) ($data['name'] ?? ''), ENT_QUOTES)
                ), 'UTF-8'),
                'control' => [
                    'type' => 'text',
                    'required' => true,
                ],
            ],
            [
                // Single "popularity bar" column mirroring the EasyAdmin admin
                // (templates/admin/tag/_posts_popularity.html.twig): a progress
                // bar sized on postCount, the total-count badge, and a muted
                // "(N published)" hint shown only when not all posts are published.
                'attribute' => 'postCount',
                'label' => 'tag.posts',
                'sortable' => true,
                'value' => fn(array $data, int $index, DataColumn $column): Markup => new Markup(
                    $column->renderTemplate('gridview/tag/_posts_popularity.html.twig', [
                        'count' => (int) ($data['postCount'] ?? 0),
                        'published' => (int) ($data['publishedCount'] ?? 0),
                    ]),
                    'UTF-8'
                ),
            ],
            ['type' => 'action', 'label' => 'Actions'],
        ];
    }
}
