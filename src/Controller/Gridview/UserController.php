<?php

namespace App\Controller\Gridview;

use App\Entity\User;
use Fedale\GridviewBundle\Controller\AbstractCrudGridController;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/gridview/users', name: 'gridview_user_')]
class UserController extends AbstractCrudGridController
{
    protected function getDataClass(): string
    {
        return User::class;
    }

    protected function viewConfig(): array
    {
        return [
            // The default 'gridview/with_sidebar.html.twig' expects a template
            // supplied by the host app; fall back to the bundle's own bare
            // layout so this controller renders out of the box. Swap this for
            // an app template once one exists.
            'template' => ['index' => '@FedaleGridview/gridview/index.html.twig'],
            // Group each user's posts as expandable child rows.
            'options' => [
                'behavior' => [
                    'grouping' => [
                        'enabled' => true,
                        'mode' => 'eager',
                        'relation' => 'posts',
                        'label' => 'Posts',
                        'columns' => [
                            ['attribute' => 'id', 'label' => 'Id', 'type' => 'number'],
                            ['attribute' => 'title', 'label' => 'Title'],
                            ['attribute' => 'status', 'label' => 'Status'],
                            ['attribute' => 'publishedAt', 'label' => 'Published at', 'type' => 'datetime'],
                            ['attribute' => 'viewCount', 'label' => 'Views', 'type' => 'number'],
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function dataConfig(): array
    {
        return [
            'model' => User::class,
            'alias' => 'e',
            'pagination' => ['defaultPageSize' => 20],
            'searchFields' => [
                'id' => ['number', 'e.id'],
                'fullName' => ['text', 'e.fullName'],
                'email' => ['text', 'e.email'],
                'isVerified' => ['boolean', 'e.isVerified'],
                'lastLoginAt' => ['date', 'e.lastLoginAt'],
            ],
            'sort' => [
                'map' => [
                    'id' => ['asc' => ['e.id'], 'desc' => ['e.id']],
                    'fullName' => ['asc' => ['e.fullName'], 'desc' => ['e.fullName']],
                    'email' => ['asc' => ['e.email'], 'desc' => ['e.email']],
                    'isVerified' => ['asc' => ['e.isVerified'], 'desc' => ['e.isVerified']],
                    'lastLoginAt' => ['asc' => ['e.lastLoginAt'], 'desc' => ['e.lastLoginAt']],
                ],
                'default' => ['id' => 'desc'],
            ],
        ];
    }

    /** @return array<int, mixed> */
    protected function buildColumns(): array
    {
        return [
            [
                'attribute' => 'id',
                'label' => 'Id',
                'type' => 'number',
                'sortable' => true,
                'filter' => ['type' => 'number'],
            ],
            [
                'attribute' => 'fullName',
                'label' => 'Full name',
                'sortable' => true,
                'filter' => ['type' => 'text'],
                'control' => ['type' => 'text', 'required' => true],
            ],
            [
                'attribute' => 'email',
                'label' => 'Email',
                'sortable' => true,
                'filter' => ['type' => 'text'],
                'control' => ['type' => 'text', 'required' => true],
            ],
            ['attribute' => 'roles', 'label' => 'Roles', 'type' => 'json'],
            [
                'attribute' => 'isVerified',
                'label' => 'Is verified',
                'type' => 'boolean',
                'sortable' => true,
                'filter' => ['type' => 'boolean'],
                'control' => ['type' => 'boolean', 'required' => true],
            ],
            [
                'attribute' => 'lastLoginAt',
                'label' => 'Last login at',
                'type' => 'datetime',
                'sortable' => true,
                'filter' => ['type' => 'date'],
                'control' => ['type' => 'datetime', 'required' => false],
            ],
            ['type' => 'action', 'label' => false],
        ];
    }
}
