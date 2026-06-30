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
            'id'             => 'category',
            'title'          => 'category.label',
            'addLabel'       => 'New category',
            'exportFilename' => 'categories',
            'indexTemplate'  => 'gridview/index.html.twig',
            'options'        => ['globalSearch' => ['name']],
        ];
    }

    protected function dataConfig(): array
    {
        return [
            'models'     => Category::class,
            'pagination' => ['defaultPageSize' => 20],
            'sort'       => [
                'id'             => ['asc' => ['c.id'],       'desc' => ['c.id']],
                'name'           => ['asc' => ['c.name'],     'desc' => ['c.name']],
                'position'       => ['asc' => ['c.position'], 'desc' => ['c.position'], 'default' => 'asc'],
                'postCount'      => ['asc' => ['postCount'],      'desc' => ['postCount']],
                'publishedCount' => ['asc' => ['publishedCount'], 'desc' => ['publishedCount']],
            ],
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
                'label'     => 'category.name',
                'sortable'  => true,
                'filter'    => ['type' => 'text'],
                'editable'  => true,
                'control'   => ['type' => 'text', 'required' => true],
            ],
            // Slug: auto-generated from the name on create (see beforeSave), so the
            // control only appears when editing. Hidden from the grid; kept unique.
            [
                'attribute' => 'slug',
                'label'     => 'category.slug',
                'visible'   => false,
                'control'   => ['type' => 'text', 'modes' => ['edit'], 'required' => false, 'unique' => true],
            ],
            // Description: form-only (hidden from the grid), matching the admin.
            [
                'attribute' => 'description',
                'label'     => 'category.description',
                'visible'   => false,
                'control'   => ['type' => 'html', 'required' => false],
            ],
            [
                'attribute' => 'color',
                'label'     => 'category.color',
                // Display a small swatch followed by the hex value.
                'value'     => static fn(array $data): Markup => new Markup(\sprintf(
                    '<span class="gv-color-swatch" style="display:inline-block;width:14px;height:14px;border-radius:3px;vertical-align:middle;background:%1$s"></span> %1$s',
                    htmlspecialchars((string) ($data['color'] ?? ''), ENT_QUOTES)
                ), 'UTF-8'),
                'control'   => ['type' => 'color', 'required' => true],
            ],
            [
                'attribute' => 'position',
                'label'     => 'category.position',
                'type'      => 'number',
                'sortable'  => true,
                'editable'  => true,
                'control'   => ['type' => 'integer', 'required' => true],
            ],
            [
                'attribute' => 'postCount',
                'label'     => 'category.posts',
                'type'      => 'badge',
                'sortable'  => true,
            ],
            [
                'attribute' => 'publishedCount',
                'label'     => 'post.status.published',
                'type'      => 'badge',
                'sortable'  => true,
            ],
            ['type' => 'action', 'label' => 'Actions'],
        ];
    }
}
