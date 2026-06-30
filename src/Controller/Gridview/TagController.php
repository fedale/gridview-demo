<?php

namespace App\Controller\Gridview;

use App\Entity\Tag;
use Fedale\GridviewBundle\Column\DataColumn;
use Fedale\GridviewBundle\Controller\AbstractCrudGridController;
use Fedale\GridviewBundle\Crud\CrudButton;
use Symfony\Component\Routing\Attribute\Route;

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

    protected function viewConfig(): array
    {
        return [
            'id' => 'tag',
            'title' => 'tag.label',
            'addLabel' => 'tag.add',
            'exportFilename' => 'tags',
            // Tag-specific index that hosts the "Add Tag" button in the page
            // content-header (non-modal link) instead of the in-grid toolbar.
            'indexTemplate' => 'gridview/tag/index.html.twig',
            // The "Add Tag" link is a direct (non-XHR) navigation, so the CRUD
            // form is served as a full page. Wrap it in the demo shell (sidebar +
            // main-content) instead of the bundle's bare crud/page.html.twig.
            'pageTemplate' => 'gridview/crud_page.html.twig',
            // Action layout mirroring EasyAdmin's TagCrudController: the custom
            // "View posts" action, then inline edit and the ROLE_ADMIN delete.
            // The buttons themselves are wired in defaultActionButtons().
            'actionLayout' => '{edit} {viewPosts} {delete}',
            'options' => [
                // Feeds the content-top search box; the in-grid global search is
                // gone entirely now that the header region is dropped (below).
                'globalSearch' => ['name'],
                // Drop the whole gv-region--header (heading + toolbar): the Add
                // button lives in the page content-header instead, and this grid
                // has no other toolbar controls. {dataview} + {footer} remain.
                'layout' => ['shell' => '{dataview} {footer}'],
            ],
        ];
    }

    /**
     * EA parity for the action column: keep the auto-wired edit/delete buttons but
     * (a) restrict delete to ROLE_ADMIN — EA's
     * `->setPermission(Action::DELETE, 'ROLE_ADMIN')` — and (b) add the custom
     * "View posts" action that links to the post index pre-filtered by this tag,
     * reusing the same EA filter query string (`filters[tags][value]` + comparison)
     * so the post grid opens already filtered.
     */
    protected function defaultActionButtons(): array
    {
        $buttons = parent::defaultActionButtons();

        if (isset($buttons['delete'])) {
            $buttons['delete'] = ['content' => $buttons['delete'], 'roles' => ['ROLE_ADMIN']];
        }

        /*
        $theme = $this->actionButtonTheme();
        $label = $this->actionLabel('action.view_posts', 'messages');
        $buttons['viewPosts'] = fn(array $row): string => CrudButton::link(
            $theme,
            $this->generateUrl('admin_post_index', [
                'filters' => ['tags' => ['value' => $row['id'], 'comparison' => '=']],
            ]),
            'viewPosts',
            'newspaper',
            $label,
            'action.view_posts',
        );
*/
        return $buttons;
    }

    protected function dataConfig(): array
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
            'defaultSort' => ['name' => 'asc', 'id' => 'asc'],

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
                'value' => fn(array $data, int $index, DataColumn $column): string => \sprintf(
                    '<span class="badge badge-primary" style="font-size: 0.95em;">%s</span>',
                    htmlspecialchars((string) ($data['name'] ?? ''), ENT_QUOTES),
                    'UTF-8'
                ),
                'twigFilter' => 'raw',
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
                'value' => fn(array $data, int $index, DataColumn $column): string =>
                $column->renderTemplate('gridview/tag/_posts_popularity.html.twig', [
                    'count' => (int) ($data['postCount'] ?? 0),
                    'published' => (int) ($data['publishedCount'] ?? 0),
                ]),
                'twigFilter' => 'raw',
            ],
            // Auto-wired to the CRUD routes; the buttons/layout come from
            // viewConfig() + defaultActionButtons() above (EA parity).
            ['type' => 'action', 'label' => 'Actions'],
        ];
    }
}
