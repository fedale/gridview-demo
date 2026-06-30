<?php

namespace App\Controller\Gridview;

use App\Entity\Tag;
use Fedale\GridviewBundle\Column\DataColumn;
use Fedale\GridviewBundle\Controller\AbstractCrudGridController;
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

    /** FontAwesome pencil — matches the EasyAdmin edit-action markup. */
    private const ICON_EDIT = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="currentColor"><path d="M362.7 19.3L314.3 67.7 444.3 197.7l48.4-48.4c25-25 25-65.5 0-90.5L453.3 19.3c-25-25-65.5-25-90.5 0zm-71 71L58.6 323.5c-10.4 10.4-18 23.3-22.2 37.4L1 481.2C-1.5 489.7 .8 498.8 7 505s15.3 8.5 23.7 6.1l120.3-35.4c14.1-4.2 27-11.8 37.4-22.2L421.7 220.3 291.7 90.3z"/></svg>';

    /** FontAwesome eye for the (not-yet-wired) show action. */
    private const ICON_SHOW = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" fill="currentColor"><path d="M288 32c-80.8 0-145.5 36.8-192.6 80.6C48.6 156 17.3 208 2.5 243.7c-3.3 7.9-3.3 16.7 0 24.6C17.3 304 48.6 356 95.4 399.4 142.5 443.2 207.2 480 288 480s145.5-36.8 192.6-80.6c46.8-43.5 78.1-95.4 93-131.1 3.3-7.9 3.3-16.7 0-24.6-14.9-35.7-46.2-87.7-93-131.1C433.5 68.8 368.8 32 288 32zM144 256a144 144 0 1 1 288 0 144 144 0 1 1 -288 0zm144-64c0 35.3-28.7 64-64 64-7.1 0-13.9-1.2-20.3-3.3-5.5-1.8-11.9 1.6-11.7 7.4.3 6.9 1.3 13.8 3.2 20.7 13.7 51.2 66.4 81.6 117.6 67.9s81.6-66.4 67.9-117.6c-11.1-41.5-47.8-69.4-88.6-71.1-5.8-.2-9.2 6.1-7.4 11.7 2.1 6.4 3.3 13.2 3.3 20.3z"/></svg>';

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
                'actionLayout' => '{edit} {show} {viewPosts}',
                // Feeds the content-top search box; the in-grid global search is
                // gone entirely now that the header region is dropped (below).
                'globalSearch' => ['name'],
                // Drop the whole gv-region--header (heading + toolbar): the Add
                // button lives in the page content-header instead, and this grid
                // has no other toolbar controls. {dataview} + {footer} remain.
                'layout' => ['shell' => '{dataview} {footer}'],
                'filterControls' => ['inHeader' => false],
            ],
        ];
    }

    /**
     * EasyAdmin-style action triggers (Bootstrap button shell + icon + i18n label),
     * keyed by the tokens used in `options.actionLayout` ({edit} {show} {viewPosts}):
     * inline edit, the not-yet-wired show, and the custom "View posts" jump to the
     * post index pre-filtered by this tag (reusing EA's filter query string so the
     * post grid opens already filtered). Built explicitly instead of via
     * parent::defaultActionButtons() so each button carries the EA markup.
     */
    protected function defaultActionButtons(): array
    {
        $buttons = [];

        $buttons['edit'] = fn(array $row): string => $this->eaAction(
            'edit',
            $this->generateUrl($this->routeName('update'), ['id' => $row['id']]),
            self::ICON_EDIT,
            'action.edit',
            'Edit',
        );

        // 'show' route not wired yet — href '#' keeps the button visible so the
        // column layout is final. Swap to
        // $this->generateUrl($this->routeName('show'), ['id' => $row['id']])
        // once the show route exists.
        $buttons['show'] = fn(array $row): string => $this->eaAction(
            'show',
            '#',
            self::ICON_SHOW,
            'action.show',
            'Show',
        );

        $buttons['viewPosts'] = fn(array $row): string => $this->eaAction(
            'viewPosts',
            $this->generateUrl('admin_post_index', [
                'filters' => ['tags' => ['value' => $row['id'], 'comparison' => '=']],
            ]),
            self::ICON_VIEW_POSTS,
            'action.view_posts',
            'View posts',
        );

        return $buttons;
    }

    /**
     * EasyAdmin-style action trigger: a Bootstrap button shell
     * (.btn .btn-secondary .action-<name>) wrapping an icon span and an
     * i18n-ready label span. $labelKey is a `messages`-domain key (the grid's
     * client_domain) swapped client-side by the i18n runtime; $labelFallback is
     * the literal no-JS text. $icon is raw inline SVG.
     */
    private function eaAction(string $name, string $url, string $icon, string $labelKey, string $labelFallback): string
    {
        $esc = static fn(string $v): string => htmlspecialchars($v, \ENT_QUOTES, 'UTF-8');

        return \sprintf(
            '<a class="btn btn-secondary action-%1$s" href="%2$s" role="button" data-action-name="%1$s">'
            . '<span class="icon btn-icon">%3$s</span>'
            . '<span class="btn-label"><span class="action-label" data-gv-i18n="%4$s">%5$s</span></span>'
            . '</a>',
            $esc($name),
            $esc($url),
            $icon,
            $esc($labelKey),
            $esc($labelFallback),
        );
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
            ['attribute' => 'checkbox', 'type' => 'checkbox'],
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
