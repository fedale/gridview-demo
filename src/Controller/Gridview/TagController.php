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
    /** Newspaper glyph for the "View posts" action (feather style, matches CrudButton icons). */
    private const ICON_VIEW_POSTS = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8M15 18h-5M10 6h8v4h-8z"/></svg>';

    protected function getDataClass(): string
    {
        return Tag::class;
    }

    protected function viewConfig(): array
    {
        return [
            // id ('tag'), labels (tag.label / tag.add) are all derived from the
            // entity by convention; only the plural export filename is overridden.
            'export' => ['filename' => 'tags'],
            'form' => [
                // Render the CRUD form with the Bootstrap 5 form theme so the inputs
                // match the EasyAdmin look (Bootstrap CSS is loaded on these pages).
                'theme' => ['bootstrap_5_layout.html.twig'],
                // Drop the in-form submit button; the page renders the action row
                // next to the title instead (placement 'header'). The layout orders
                // the named buttons left to right: back-to-list (no save),
                // save-and-add-another (new records only), then the primary save.
                'actions' => [
                    'placement' => 'header',
                    'layout' => '{returnListing} {addAnother} {save}',
                    'buttons' => [
                        // Plain link back to the index — discards, does not save.
                        'returnListing' => ['type' => 'link', 'route' => 'index', 'variant' => 'link', 'label' => 'action.return_listing'],
                        // Save, then reopen a blank /new form. Only meaningful when creating.
                        'addAnother' => [
                            'type' => 'submit',
                            'action' => 'save_add_another',
                            'redirect' => 'new',
                            'variant' => 'secondary',
                            'modes' => ['add', 'clone'],
                            'label' => 'action.create_add_another'
                        ],
                        // Primary save → back to the listing. Mode-aware label.
                        'save' => [
                            'type' => 'submit',
                            'action' => 'save',
                            'redirect' => 'index',
                            'variant' => 'primary',
                            'label' => ['add' => 'action.create', 'clone' => 'action.create', 'edit' => 'action.save']
                        ],
                    ],
                ],
            ],
            'template' => [
                // Tag-specific index that hosts the "Add Tag" button in the page
                // content-header (non-modal link) instead of the in-grid toolbar.
                'index' => 'gridview/tag/index.html.twig',
                // The "Add Tag" link is a direct (non-XHR) navigation, so the CRUD
                // form is served as a full page. Wrap it in the demo shell (sidebar +
                // main-content) instead of the bundle's bare crud/page.html.twig.
                'page' => 'gridview/crud_page.html.twig',
            ],
            'options' => [
                // Action layout mirroring EasyAdmin's TagCrudController: the custom
                // "View posts" action, then inline edit and the ROLE_ADMIN delete.
                // The buttons themselves are wired in defaultActionButtons().
                'actionLayout' => '{edit} {viewPosts} {delete}',
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

        // Custom "View posts" action: jumps to the post index pre-filtered by this
        // tag, reusing EA's filter query string so the post grid opens already
        // filtered. The title is a `messages`-domain key (the grid's client_domain)
        // swapped client-side by the i18n runtime.
        $buttons['viewPosts'] = fn(array $row): string => CrudButton::link(
            $this->generateUrl('admin_post_index', [
                'filters' => ['tags' => ['value' => $row['id'], 'comparison' => '=']],
            ]),
            self::ICON_VIEW_POSTS,
            'action.view_posts',
        );

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
                // Keep the filter field in the SearchForm but pull it out of the
                // column header: the per-column <thead> input renders only when
                // the column is NOT in the filter bar. The filter bar region is
                // dropped from this grid's layout, and headerMirror stays false,
                // so the field renders nowhere in the grid — it is surfaced by the
                // custom "Filter" modal in templates/gridview/tag/index.html.twig.
                'filterBar' => true,
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
