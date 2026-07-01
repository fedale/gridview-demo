<?php

namespace App\Controller\Gridview;

use App\Entity\Category;
use Fedale\GridviewBundle\Controller\AbstractCrudGridController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Twig\Markup;

/**
 * Gridview replica of {@see \App\Controller\Admin\CategoryCrudController} (EasyAdmin).
 *
 * Full CRUD on name/slug/description/color/position, plus the computed read-only
 * `postCount` / `publishedCount` columns (COUNT subqueries in CategoryRepository,
 * surfaced as row attributes by the data provider). The slug is auto-generated
 * from the name on create — mirroring the admin's SlugField — and only becomes
 * editable when updating.
 */
#[Route('/gridview/category', name: 'gridview_category_')]
class CategoryController extends AbstractCrudGridController
{
    protected function getDataClass(): string
    {
        return Category::class;
    }

    protected function viewConfig(): array
    {
        return [
            // id ('category') and the heading ('category.label') derive from the
            // entity; only the add label (no `category.add` key) and the plural
            // export filename are overridden.
            'labels' => ['add' => 'New category'],
            'export' => ['filename' => 'categories'],
            'template' => ['index' => 'gridview/index.html.twig'],
            'options' => [
                'globalSearch' => ['name'],
                // Render active-filter chips on their own row under the toolbar
                // (the `name` column opts into the `chip` clear mode below).
                'layout' => ['header' => '{heading} {toolbar} {filterChips}'],
                'filterControls' => ['clear' => 'chip'],
            ],
        ];
    }

    protected function dataConfig(): array
    {
        return [
            'models' => Category::class,
            'pagination' => ['defaultPageSize' => 20],
            'sort' => [
                'id' => ['asc' => ['c.id'], 'desc' => ['c.id']],
                'name' => ['asc' => ['c.name'], 'desc' => ['c.name']],
                'position' => ['asc' => ['c.position'], 'desc' => ['c.position'], 'default' => 'asc'],
                'postCount' => ['asc' => ['postCount'], 'desc' => ['postCount']],
            ],
            'defaultSort' => ['position' => 'asc'],
        ];
    }


    /** Auto-generate the unique slug from the name on create (admin parity). */
    protected function beforeSave(FormInterface $form, string $mode): void
    {
        $category = $form->getData();
        if ($category instanceof Category && ($category->getSlug() === null || $category->getSlug() === '')) {
            $category->setSlug(
                (new AsciiSlugger())->slug((string) $category->getName())->lower()->toString()
            );
        }
    }

    /** @return array<int, mixed> */
    protected function buildColumns(): array
    {
        return [
            'id',
            [
                'attribute' => 'name',
                'label' => 'category.name',
                'sortable' => true,
                // Clear affordances: the header funnel icon AND an external chip
                // (rendered by the {filterChips} section, see viewConfig()).
                'filter' => ['type' => 'text', 'clear' => ['header', 'chip']],
                'editable' => true,
                'control' => ['type' => 'text', 'required' => true],
            ],
            // Slug: auto-generated from the name on create (see beforeSave), so the
            // control only appears when editing. Hidden from the grid; kept unique.
            [
                'attribute' => 'slug',
                'label' => 'category.slug',
                'visible' => false,
                'control' => ['type' => 'text', 'modes' => ['edit'], 'required' => false, 'unique' => true],
            ],
            // Description: form-only (hidden from the grid), matching the admin.
            [
                'attribute' => 'description',
                'label' => 'category.description',
                'visible' => false,
                'control' => ['type' => 'html', 'required' => false],
            ],
            [
                'attribute' => 'color',
                'label' => 'category.color',
                // Replica of EasyAdmin's ColorField with ->showValue(): the swatch
                // (styled via `.field-color .color-sample`) followed by the hex value.
                'value' => static function (array $data): Markup {
                    $color = htmlspecialchars((string) ($data['color'] ?? ''), ENT_QUOTES);

                    return new Markup(\sprintf(
                        '<span class="field-color"><span class="color-sample" style="background: %1$s; margin-right: 5px;" title="%1$s">&nbsp;</span>%1$s</span>',
                        $color
                    ), 'UTF-8');
                },
                'control' => ['type' => 'color', 'required' => true],
            ],
            [
                'attribute' => 'position',
                'label' => 'category.position',
                'type' => 'number',
                'filter' => ['type' => 'text'],
                'sortable' => true,
                'editable' => true,
                'control' => ['type' => 'integer', 'required' => true],
            ],
            // Post stats merged into one column: total count + published count,
            // with a left accent bar tinted with the category color.
            [
                'attribute' => 'postCount',
                'label' => 'category.posts',
                'sortable' => true,
                'type' => 'number',
                'filter' => true,
                'value' => static function (array $data): Markup {
                    $color = htmlspecialchars((string) ($data['color'] ?? ''), ENT_QUOTES);
                    $posts = (int) ($data['postCount'] ?? 0);
                    $published = (int) ($data['publishedCount'] ?? 0);

                    return new Markup(\sprintf(
                        '<div class="category-stats d-flex align-items-center gap-2" style="border-left: 3px solid %s; padding-left: 8px;">'
                        . '<strong>%d</strong>'
                        . '<span>posts</span>'
                        . '<small class="text-muted">(%d published)</small>'
                        . '</div>',
                        $color,
                        $posts,
                        $published
                    ), 'UTF-8');
                },
            ],
            // EasyAdmin-style actions: a 3-dots toggle opening a menu with Edit
            // (opens the CRUD modal) and Show (the EA detail page — the grid has
            // no detail route of its own). Rendered as a single button so the
            // whole dropdown lands in one cell; see renderActionsMenu().
            [
                'type' => 'action',
                'label' => false,
                'layout' => '{menu}',
                'buttons' => [
                    'menu' => fn(array $row): string => $this->renderActionsMenu($row),
                ],
            ],
        ];
    }

    /** 'dots-horizontal' from heroicons.com — matches EasyAdmin's actions toggle. */
    private const ICON_DOTS = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"></path></svg>';
    /** Font Awesome 'pen' (edit). */
    private const ICON_EDIT = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="currentColor"><path d="M362.7 19.3L314.3 67.7 444.3 197.7l48.4-48.4c25-25 25-65.5 0-90.5L453.3 19.3c-25-25-65.5-25-90.5 0zm-71 71L58.6 323.5c-10.4 10.4-18 23.3-22.2 37.4L1 481.2C-1.5 489.7 .8 498.8 7 505s15.3 8.5 23.7 6.1l120.3-35.4c14.1-4.2 27-11.8 37.4-22.2L421.7 220.3 291.7 90.3z"></path></svg>';
    /** Font Awesome 'eye' (show/detail). */
    private const ICON_SHOW = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" fill="currentColor"><path d="M288 80c-65.2 0-118.8 29.6-159.9 67.7C89.6 183.5 63 226 49.4 256c13.6 30 40.2 72.5 78.6 108.3C169.2 402.4 222.8 432 288 432s118.8-29.6 159.9-67.7C486.4 328.5 513 286 526.6 256c-13.6-30-40.2-72.5-78.6-108.3C406.8 109.6 353.2 80 288 80zM95.4 112.6C142.5 68.8 207.2 32 288 32s145.5 36.8 192.6 80.6c46.8 43.5 78.1 95.4 93 131.1c3.3 7.9 3.3 16.7 0 24.6c-14.9 35.7-46.2 87.7-93 131.1C433.5 443.2 368.8 480 288 480s-145.5-36.8-192.6-80.6C48.6 356 17.3 304 2.5 268.3c-3.3-7.9-3.3-16.7 0-24.6C17.3 208 48.6 156 95.4 112.6zM288 336c44.2 0 80-35.8 80-80s-35.8-80-80-80c-.7 0-1.3 0-2 0c1.3 5.1 2 10.5 2 16c0 35.3-28.7 64-64 64c-5.5 0-10.9-.7-16-2c0 .7 0 1.3 0 2c0 44.2 35.8 80 80 80zm0-208a128 128 0 1 1 0 256 128 128 0 1 1 0-256z"></path></svg>';

    /**
     * EasyAdmin-style actions dropdown for one row. Uses the bundle's own
     * `gridview-dropdown` Stimulus controller for open/close (no Bootstrap JS
     * needed): `.gv-dropdown` wrapper, a `#toggle` trigger and a `.gv-dropdown-menu`.
     * Edit reuses the CRUD modal hook (`gridview-crud#open`); Show links to the
     * EasyAdmin detail page, since the grid exposes no detail route.
     *
     * @param array<string, mixed> $row
     */
    private function renderActionsMenu(array $row): string
    {
        $id = (int) $row['id'];
        $locale = $this->container->get('request_stack')->getCurrentRequest()?->getLocale() ?? 'en';
        $editUrl = htmlspecialchars($this->generateUrl($this->routeName('update'), ['id' => $id]), \ENT_QUOTES);
        $showUrl = htmlspecialchars(
            $this->generateUrl('admin_category_detail', ['entityId' => $id, '_locale' => $locale]),
            \ENT_QUOTES
        );

        return \sprintf(
            '<div class="gv-dropdown dropdown-actions" data-controller="gridview-dropdown">'
            . '<button type="button" class="gv-actions-toggle" data-action="gridview-dropdown#toggle" aria-haspopup="true" aria-expanded="false">'
            . '<span class="icon">%s</span>'
            . '</button>'
            . '<ul class="gv-dropdown-menu gv-dropdown-menu--end">'
            . '<li><a class="gv-dropdown-item action-edit" data-action-name="edit" href="%s"'
            . ' data-action="gridview-crud#open" data-gridview-crud-url-param="%s">'
            . '<span class="icon action-icon">%s</span><span class="action-label">Edit</span></a></li>'
            . '<li><a class="gv-dropdown-item action-detail" data-action-name="detail" href="%s">'
            . '<span class="icon action-icon">%s</span><span class="action-label">Show</span></a></li>'
            . '</ul>'
            . '</div>',
            self::ICON_DOTS,
            $editUrl,
            $editUrl,
            self::ICON_EDIT,
            $showUrl,
            self::ICON_SHOW
        );
    }
}
