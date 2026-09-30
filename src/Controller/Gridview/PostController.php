<?php

namespace App\Controller\Gridview;

use App\Entity\Category;
use App\Entity\Post;
use App\Entity\Series;
use App\Entity\Tag;
use App\Entity\User;
use App\Enum\PostStatus;
use Fedale\GridviewBundle\Column\Config\ActionColumn;
use Fedale\GridviewBundle\Column\Config\BooleanColumn;
use Fedale\GridviewBundle\Column\Config\CheckboxColumn;
use Fedale\GridviewBundle\Column\Config\DatetimeColumn;
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
                // The Bootstrap 5 form theme is set globally in gridview.yaml
                // (defaults.behavior.formTheme), so it isn't repeated per grid.
                // Custom form layout: a template with a {attribute} token per field
                // (EasyAdmin-style two-column + fieldsets). Fields with a control
                // but no token still render at the end via form_end().
                'view' => 'gridview/post_form.html.twig',
                //             // Action buttons: 'header' placement drops the in-form submit; 'layout' orders 'buttons'.
                //             'actions' => ['placement' => 'inline', 'layout' => null, 'buttons' => null],
                //             // Query key of the filter form (used to resolve "all" bulk ids).
                //             'filterName' => 'fedaleForm',
            ],
            // display / behavior / integration overrides, plus 'actionLayout' for the action column.
            'options' => [
                'display' => [
                    // Runtime view switch: table (default) / cards / list. The
                    // {viewSwitcher} token is injected into the toolbar on its own
                    // as soon as the map holds more than one entry.
                    //
                    // Both card and list use the BUILT-IN item templates (unlike the
                    // Category grid, which overrides them), so what each view draws
                    // comes straight from the per-view axis on the columns below.
                    'renderer' => [
                        'default' => 'table',
                        'map' => [
                            'table' => [],
                            'card' => ['min' => '20rem', 'titleField' => 'title'],
                            'list' => [],
                        ],
                    ],
                ],
            ],
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
            // Per-view visibility: a surrogate key earns its place in a dense
            // table and nowhere else, so it sits out the card and list views.
            NumberColumn::new('id')->label('Id')->sortable()->filterNumber()
                ->hideInViews('card', 'list'),
            TextColumn::new('title')->label('Title')->sortable()
                ->filterText(trim: false)->required(),
            SelectColumn::new('status')->label('Status')->sortable()
                ->enum(PostStatus::class, required: true),
            // control(true) keeps the checkbox in the form as an optional field.
            // Avoid required() here: on a checkbox it means "must be checked"
            // (HTML5 required), which would block saving an unfeatured post.
            BooleanColumn::new('isFeatured')->label('Is featured')->sortable()
                ->filterBoolean()->control(true)
                // A ✓/✗ pair reads fine in a table row or a list line; on a card
                // it costs a whole label/value block for one glyph.
                ->hideInViews('card'),
            // choiceLabel drives both the cell (the related row's `fullName`
            // instead of its id) and the relation control's option labels.
            // The relation FILTER is a plain choice list, so it needs its own
            // `choices` map — the bundle never queries the database to fill a
            // filter (use `ajax_url` for lists too big to inline).
            RelationColumn::new('author')->label('Author')
                ->relation(User::class, choiceLabel: 'fullName')
                ->filter(['type' => 'relation', 'options' => [
                    'choices' => $this->relationChoices(User::class, 'getFullName', 'fullName'),
                    'searchable' => true,
                ]]),
            RelationColumn::new('category')->label('Category')
                ->relation(Category::class, choiceLabel: 'name')
                ->filter(['type' => 'relation', 'options' => [
                    'choices' => $this->relationChoices(Category::class, 'getName', 'name'),
                ]]),

            // The teaser, card-side only: a card has room for a paragraph, a table
            // row does not. onlyOnIndex() keeps it out of the form and the detail
            // view (the `summary` control below owns the write side), and
            // onlyInViews('card') keeps it out of the table and the list — the two
            // axes compose.
            TextColumn::new('summary')->label('Summary')->notSortable()
                ->value(static function (array $data): string {
                    $text = trim(strip_tags((string) ($data['summary'] ?? '')));

                    return mb_strlen($text) > 140 ? mb_substr($text, 0, 140) . '…' : $text;
                })
                ->onlyOnIndex()
                ->onlyInViews('card'),

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

            // Status & visibility fieldset (form only).
            DatetimeColumn::new('publishedAt')->label('Published at')
                ->control(['type' => 'datetime'])->onlyOnForm(),
            DatetimeColumn::new('scheduledAt')->label('Scheduled at')
                ->control(['type' => 'datetime'])->onlyOnForm(),

            // Classification fieldset: tags is a many-to-many, so the relation
            // control is multiple + by_reference:false (Post has add/removeTag()).
            RelationColumn::new('tags')->label('Tags')
                ->control([
                    'type'    => 'relation',
                    'options' => ['class' => Tag::class, 'choice_label' => 'name', 'multiple' => true, 'by_reference' => false],
                ])
                ->onlyOnForm(),

            // Series fieldset (form only).
            RelationColumn::new('series')->label('Series')
                ->control(['type' => 'relation', 'options' => ['class' => Series::class, 'choice_label' => 'title']])
                ->onlyOnForm(),
            NumberColumn::new('seriesPosition')->label('Series position')
                ->control(['type' => 'integer'])->onlyOnForm(),

            ActionColumn::new()->label(false),
        ];
    }

    /**
     * `['Label' => id]` for a relation filter's option list, ordered by the label
     * field. Kept generic so both filters above read the same way.
     *
     * @param class-string $class
     *
     * @return array<string, int>
     */
    private function relationChoices(string $class, string $labelGetter, string $orderBy): array
    {
        $choices = [];
        foreach ($this->em()->getRepository($class)->findBy([], [$orderBy => 'ASC']) as $entity) {
            $label = (string) $entity->{$labelGetter}();
            $choices[$label !== '' ? $label : '#' . $entity->getId()] = $entity->getId();
        }

        return $choices;
    }
}
