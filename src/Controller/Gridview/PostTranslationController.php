<?php

namespace App\Controller\Gridview;

use App\Entity\Post;
use App\Entity\PostTranslation;
use Fedale\GridviewBundle\Column\Config\ActionColumn;
use Fedale\GridviewBundle\Column\Config\BooleanColumn;
use Fedale\GridviewBundle\Column\Config\CheckboxColumn;
use Fedale\GridviewBundle\Column\Config\RelationColumn;
use Fedale\GridviewBundle\Column\Config\SelectColumn;
use Fedale\GridviewBundle\Column\Config\TextColumn;
use Fedale\GridviewBundle\Controller\AbstractCrudGridController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * A grid over an entity with a COMPOSITE key: (post, locale).
 *
 * Nothing here configures that. The edit link carries both parts as one token —
 * /gridview/post-translations/update/7~it — the checkboxes select by the same
 * token, and the bulk actions resolve it back to find() criteria. The grid is
 * declared exactly as one over an auto-increment int would be.
 */
#[Route('/gridview/post-translations', name: 'gridview_post_translation_')]
class PostTranslationController extends AbstractCrudGridController
{
    protected function getDataClass(): string
    {
        return PostTranslation::class;
    }

    protected function viewConfig(): array
    {
        return [
            'labels' => ['heading' => 'Post translations', 'add' => 'New translation'],
            'options' => [
                'behavior' => ['globalSearch' => ['e.title', 'e.locale']],
            ],
        ];
    }

    protected function dataConfig(): array
    {
        return [
            'model' => PostTranslation::class,
            'pagination' => ['defaultPageSize' => 20],
            'eager' => ['post'],
            'sort' => [
                'map' => [
                    'locale' => ['asc' => ['e.locale'], 'desc' => ['e.locale']],
                    'title' => ['asc' => ['e.title'], 'desc' => ['e.title']],
                    'isReviewed' => ['asc' => ['e.isReviewed'], 'desc' => ['e.isReviewed']],
                ],
                'default' => ['locale' => 'asc'],
            ],
        ];
    }

    /** @return array<int, mixed> */
    protected function buildColumns(): array
    {
        return [
            CheckboxColumn::new(),
            // Both halves of the key are ordinary columns: one a relation, one a
            // plain string. They are what the record is, so they are editable on
            // create and frozen on update — changing either would be a different
            // record, not an edit of this one.
            RelationColumn::new('post')->label('Post')
                ->relation(Post::class, choiceLabel: 'title', required: true)
                ->control([
                    'type' => 'relation',
                    'required' => true,
                    'options' => ['class' => Post::class, 'choice_label' => 'title'],
                    'modes' => ['add'],
                ]),
            SelectColumn::new('locale')->label('Locale')->sortable()
                ->choices(['Italiano' => 'it', 'English' => 'en', 'Français' => 'fr', 'Español' => 'es'])
                ->control(['type' => 'choice', 'required' => true, 'modes' => ['add'], 'options' => [
                    'choices' => ['Italiano' => 'it', 'English' => 'en', 'Français' => 'fr', 'Español' => 'es'],
                ]]),
            TextColumn::new('title')->label('Title')->sortable()->filterText()->required()->editable(),
            TextColumn::new('summary')->label('Summary')->control(['type' => 'html', 'required' => false])
                ->hideOnIndex(),
            BooleanColumn::new('isReviewed')->label('Reviewed')->sortable()->filterBoolean()->control(true)
                ->batchUpdate(),
            ActionColumn::new()->label(false),
        ];
    }
}
