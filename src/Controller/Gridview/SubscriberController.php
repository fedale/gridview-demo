<?php

namespace App\Controller\Gridview;

use App\Entity\Subscriber;
use Fedale\GridviewBundle\Column\Config\ActionColumn;
use Fedale\GridviewBundle\Column\Config\BooleanColumn;
use Fedale\GridviewBundle\Column\Config\DatetimeColumn;
use Fedale\GridviewBundle\Column\Config\NumberColumn;
use Fedale\GridviewBundle\Column\Config\SelectColumn;
use Fedale\GridviewBundle\Column\Config\TextColumn;
use Fedale\GridviewBundle\Column\Config\VirtualColumn;
use Fedale\GridviewBundle\Controller\AbstractCrudGridController;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/gridview/subscribers', name: 'gridview_subscriber_')]
class SubscriberController extends AbstractCrudGridController
{
    protected function getDataClass(): string
    {
        return Subscriber::class;
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

    protected function viewConfig(): array
    {
        return [
            // No `subscriber.add` in the client catalog, so the convention key
            // would render as itself on the button.
            'labels' => ['add' => 'New subscriber'],
            'options' => [
                // The `subscriber` column is joined at render time, so there is
                // nothing in the database to match it against. The global search
                // covers the two real fields behind it instead — one input, both
                // columns — and the default CRUD toolbar already shows it.
                'behavior' => ['globalSearch' => ['e.name', 'e.email']],
            ],
        ];
    }

    protected function dataConfig(): array
    {
        return [
            'model' => Subscriber::class,
            // 'alias' defaults to 'e' (matching the DQL used in 'search' and
            // 'sort' below). Set it only to change the query-builder alias.
            'pagination' => ['defaultPageSize' => 20],
            // Declarative filter map (query-side). Mirrors 'sort' below: both are
            // an attribute-keyed 'map'.
            'search' => [
                'map' => [
                    'id' => ['number', 'e.id'],
                    'email' => ['text', 'e.email'],
                    'name' => ['text', 'e.name'],
                    'subscribedAt' => ['date', 'e.subscribedAt'],
                    'isConfirmed' => ['boolean', 'e.isConfirmed'],
                    'confirmedAt' => ['date', 'e.confirmedAt'],
                    'source' => ['choice', 'e.source'],
                    'unsubscribedAt' => ['date', 'e.unsubscribedAt'],
                    'locale' => ['text', 'e.locale'],
                    'notes' => ['text', 'e.notes'],
                    'ipAddress' => ['text', 'e.ipAddress'],
                    'country' => ['text', 'e.country'],
                    'timezone' => ['text', 'e.timezone'],
                ],
            ],
            'sort' => [
                'map' => [
                    'id' => ['asc' => ['e.id'], 'desc' => ['e.id']],
                    // Sorting a virtual column: one key, the real fields behind
                    // it. The header link appears because the key is mapped.
                    'subscriber' => ['asc' => ['e.name', 'e.email'], 'desc' => ['e.name', 'e.email']],
                    'email' => ['asc' => ['e.email'], 'desc' => ['e.email']],
                    'name' => ['asc' => ['e.name'], 'desc' => ['e.name']],
                    'subscribedAt' => ['asc' => ['e.subscribedAt'], 'desc' => ['e.subscribedAt']],
                    'isConfirmed' => ['asc' => ['e.isConfirmed'], 'desc' => ['e.isConfirmed']],
                    'confirmedAt' => ['asc' => ['e.confirmedAt'], 'desc' => ['e.confirmedAt']],
                    'source' => ['asc' => ['e.source'], 'desc' => ['e.source']],
                    'unsubscribedAt' => ['asc' => ['e.unsubscribedAt'], 'desc' => ['e.unsubscribedAt']],
                    'locale' => ['asc' => ['e.locale'], 'desc' => ['e.locale']],
                    'notes' => ['asc' => ['e.notes'], 'desc' => ['e.notes']],
                    'ipAddress' => ['asc' => ['e.ipAddress'], 'desc' => ['e.ipAddress']],
                    'country' => ['asc' => ['e.country'], 'desc' => ['e.country']],
                    'timezone' => ['asc' => ['e.timezone'], 'desc' => ['e.timezone']],
                ],
                'default' => ['id' => 'desc'],
            ],
        ];
    }

    /** @return array<int, mixed> */
    protected function buildColumns(): array
    {
        return [
            NumberColumn::new('id')->label('Id')->sortable()->filterNumber(),
            // One column to read, two fields to write. The grid and the detail
            // view show "Ada Lovelace · ada@example.com"; the create/update form
            // gets `name` and `email` back as separate inputs, each with its own
            // label and validation. Without this the same two fields would be
            // declared twice — once to display, once to edit.
            VirtualColumn::new('subscriber')->label('Subscriber')->from([
                TextColumn::new('name')->label('Name')->control(true),
                TextColumn::new('email')->label('Email')->required(),
            ], separator: ' · '),
            DatetimeColumn::new('subscribedAt')->label('Subscribed at')->sortable()->filterDate()->required(),
            BooleanColumn::new('isConfirmed')->label('Is confirmed')->sortable()->filterBoolean()->required(),
            DatetimeColumn::new('confirmedAt')->label('Confirmed at')->sortable()->filterDate()->control(true),
            SelectColumn::new('source')->label('Source')->sortable()->enum(\App\Enum\SubscriberSource::class, required: true),
            DatetimeColumn::new('unsubscribedAt')->label('Unsubscribed at')->sortable()->filterDate()->control(true),
            TextColumn::new('locale')->label('Locale')->sortable()->filterText()->required(),
            TextColumn::new('notes')->label('Notes')->sortable()->filterText()->control(true),
            TextColumn::new('ipAddress')->label('Ip address')->sortable()->filterText()->control(true),
            TextColumn::new('country')->label('Country')->sortable()->filterText()->control(true),
            TextColumn::new('timezone')->label('Timezone')->sortable()->filterText()->control(true),
            ActionColumn::new()->label(false),
        ];
    }
}
