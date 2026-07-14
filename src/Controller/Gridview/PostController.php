<?php

namespace App\Controller\Gridview;

use App\Entity\Category;
use App\Entity\Post;
use App\Entity\User;
use App\Enum\PostStatus;
use Fedale\GridviewBundle\Column\Config\ActionColumn;
use Fedale\GridviewBundle\Column\Config\BooleanColumn;
use Fedale\GridviewBundle\Column\Config\CheckboxColumn;
use Fedale\GridviewBundle\Column\Config\HtmlColumn;
use Fedale\GridviewBundle\Column\Config\MediaColumn;
use Fedale\GridviewBundle\Column\Config\NumberColumn;
use Fedale\GridviewBundle\Column\Config\RelationColumn;
use Fedale\GridviewBundle\Column\Config\RichTextColumn;
use Fedale\GridviewBundle\Column\Config\SelectColumn;
use Fedale\GridviewBundle\Column\Config\TextColumn;
use Fedale\GridviewBundle\Controller\AbstractCrudGridController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
    protected function viewConfig(): array
    {
        return [
            //         // Page/modal titles; null derives them from the grid id (e.g. '<id>.label').
            //         'labels' => ['heading' => null, 'add' => null, 'edit' => null],
            //         // Grid <table> wrapper attributes.
            //         'attributes' => ['class' => 'table'],
            //         // Export menu: filename (null = grid id) and formats (null = all registered).
            //         'export' => ['filename' => null, 'formats' => null],
            //         'template' => [
            //             // List page layout; '@FedaleGridview/gridview/index.html.twig' renders standalone.
            //             'index' => 'gridview/with_sidebar.html.twig',
            //             // Full-page wrapper for 'page'/'custom' modes; defaults to
            //             // the demo shell 'gridview/crud_page.html.twig'. Use
            //             // '@FedaleGridview/crud/page.html.twig' for the bare wrapper.
            //             'page' => 'gridview/crud_page.html.twig',
            //         ],
            'form' => [
                //             // How the add/edit form is shown: 'modal' | 'page' | 'custom' (null = built-in default).
                'mode' => 'page',
                // Render the form with Symfony's Bootstrap 5 theme so the inputs
                // pick up .form-control/.form-select/.form-check (styled by the
                // bootstrap.min.css this page already loads), matching the
                // EasyAdmin look. The bundle default gv_form_theme only adds gv-*
                // hooks and leaves the input itself unstyled.
                'theme' => ['bootstrap_5_layout.html.twig'],
                //             // Custom form layout template; null = automatic rendering.
                //             'view' => null,
                //             // Action buttons: 'header' placement drops the in-form submit; 'layout' orders 'buttons'.
                //             'actions' => ['placement' => 'inline', 'layout' => null, 'buttons' => null],
                //             // Query key of the filter form (used to resolve "all" bulk ids).
                //             'filterName' => 'fedaleForm',
            ],
            //         // display / behavior / integration overrides, plus 'actionLayout' for the action column.
            //         'options' => [],
        ];
    }

    protected function dataConfig(): array
    {
        return [
            'model' => Post::class,
            // 'alias' defaults to 'e' (matching the DQL used in 'search' and
            // 'sort' below). Set it only to change the query-builder alias.
            'pagination' => ['defaultPageSize' => 20],
            // Fetch-join the to-one relations the columns read, so they are
            // hydrated with the list query instead of one lazy load per row.
            'eager' => ['author', 'category'],
            // 'search' and 'sort' are parallel: both are an attribute-keyed 'map'.
            'search' => [
                'map' => [
                    'id' => ['number', 'e.id'],
                    'title' => ['text', 'e.title', ['trim' => false]],
                    'status' => ['choice', 'e.status'],
                    'isFeatured' => ['boolean', 'e.isFeatured'],
                    'author' => ['relation', 'e.author'],
                    'category' => ['relation', 'e.category'],
                ],
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
            CheckboxColumn::new(),
            NumberColumn::new('id')->label('Id')->sortable()->filterNumber(),
            TextColumn::new('title')->label('Title')->sortable()
                ->filterText(trim: false)->required(),
            SelectColumn::new('status')->label('Status')->sortable()
                ->enum(PostStatus::class, required: true),
            BooleanColumn::new('isFeatured')->label('Is featured')->sortable()
                ->filterBoolean()->required(),
            RelationColumn::new('author')->label('Author')->relation(User::class),
            RelationColumn::new('category')->label('Category')
                ->relation(Category::class, choiceLabel: 'name'),

            // Fields kept out of the grid but shown in the CRUD form and/or the
            // detail view through the per-context visibility sugar. No visible()
            // toggle needed: onlyOnForm() = create + update, onlyOnShow() = detail.
            TextColumn::new('slug')->label('Slug')->required()->onlyOnForm(),
            TextColumn::new('summary')->label('Summary')
                ->control(['type' => 'html', 'required' => true])->onlyOnForm(),
            // The same attribute twice: a textarea editor in the form, the
            // rendered HTML on the detail page.
            RichTextColumn::new('content')->label('Content')
                ->control(['type' => 'html', 'required' => true])->onlyOnForm(),
            HtmlColumn::new('content')->label('Content')->onlyOnShow(),
            // Media upload (unmapped control): the bundle validates the upload,
            // this callback stores it and populates the entity.
            MediaColumn::new('featuredImage')->label('Featured image')
                ->control([
                    'type'   => 'media',
                    'upload' => function (UploadedFile $file, Post $post): void {
                        $name = uniqid('', true) . '-' . $file->getClientOriginalName();
                        $file->move($this->getParameter('kernel.project_dir') . '/public/uploads/posts', $name);
                        $post->setFeaturedImage($name);
                    },
                ])
                ->onlyOnForm(),
            NumberColumn::new('viewCount')->label('Views')->onlyOnShow(),

            ActionColumn::new()->label(false),
        ];
    }
}
