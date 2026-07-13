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

    // Uncomment any part to customize the view. Each key below shows its
    // default; keep only the ones you actually override. Without an override the
    // grid uses 'gridview/with_sidebar.html.twig', which expects a layout
    // template from your app: point 'template.index' at one of yours, or at the
    // bundle's bare '@FedaleGridview/gridview/index.html.twig' to render out of
    // the box.
    // protected function viewConfig(): array
    // {
    //     return [
    //         // Page/modal titles; null derives them from the grid id (e.g. '<id>.label').
    //         'labels' => ['heading' => null, 'add' => null, 'edit' => null],
    //         // Grid <table> wrapper attributes.
    //         'attributes' => ['class' => 'table'],
    //         // Export menu: filename (null = grid id) and formats (null = all registered).
    //         'export' => ['filename' => null, 'formats' => null],
    //         'template' => [
    //             // List page layout; '@FedaleGridview/gridview/index.html.twig' renders standalone.
    //             'index' => 'gridview/with_sidebar.html.twig',
    //             // Full-page wrapper for the 'page'/'custom' form modes; null = bundle default.
    //             'page' => null,
    //         ],
    //         'form' => [
    //             // How the add/edit form is shown: 'modal' | 'page' | 'custom' (null = built-in default).
    //             'mode' => null,
    //             // Symfony form theme(s), e.g. ['bootstrap_5_layout.html.twig'].
    //             'theme' => '@FedaleGridview/form/gv_form_theme.html.twig',
    //             // Custom form layout template; null = automatic rendering.
    //             'view' => null,
    //             // Action buttons: 'header' placement drops the in-form submit; 'layout' orders 'buttons'.
    //             'actions' => ['placement' => 'inline', 'layout' => null, 'buttons' => null],
    //             // Query key of the filter form (used to resolve "all" bulk ids).
    //             'filterName' => 'fedaleForm',
    //         ],
    //         // display / behavior / integration overrides, plus 'actionLayout' for the action column.
    //         'options' => [],
    //     ];
    // }

    protected function dataConfig(): array
    {
        return [
            'model' => Post::class,
            // 'alias' defaults to 'e' (matching the DQL used in 'searchFields'
            // and 'sort' below). Set it only to change the query-builder alias.
            'pagination' => ['defaultPageSize' => 20],
            'searchFields' => [
                'id' => ['number', 'e.id'],
                'title' => ['text', 'e.title', ['trim' => false]],
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
            ['type' => 'checkbox'],
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
                'filter' => ['type' => 'text', 'options' => ['trim' => false]],
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
                'value' => fn (array $data): mixed => $data['author']['id'] ?? null,
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
                'value' => fn (array $data): mixed => $data['category']['name'] ?? $data['category']['id'] ?? null,
            ],
            ['type' => 'action', 'label' => false],
        ];
    }
}
