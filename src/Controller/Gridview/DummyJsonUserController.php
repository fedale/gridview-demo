<?php

namespace App\Controller\Gridview;

use App\Model\DummyJsonUser;
use Fedale\GridviewBundle\Controller\AbstractGridController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Read-only grid backed by the public https://dummyjson.com/users API instead
 * of Doctrine — proves the data layer is pluggable without touching the
 * bundle's default (global, Doctrine-backed) `fedale_gridview.entity_data_provider`
 * service, which every other demo grid (Category/Comment/Tag/User) still uses.
 * The provider is the bundle's built-in
 * {@see \Fedale\GridviewBundle\DataProvider\JsonDataProvider}, selected in
 * config/packages/gridview.yaml
 * (`gridviews.dummyjsonuser.dataProvider: ...JsonDataProvider`); the dummyjson
 * response shape (users/total keys, the /users/search endpoint) is described
 * below in dataConfig()['model'] — no custom provider class needed. Extends the
 * read-only {@see AbstractGridController} (not the CRUD variant): there is no
 * GridCrudHandlerInterface equivalent for a non-Doctrine backend today, so
 * add/edit/delete are out of scope here.
 */
#[Route('/gridview/dummy-json-user', name: 'gridview_dummy_json_user_')]
class DummyJsonUserController extends AbstractGridController
{
    protected function getDataClass(): string
    {
        return DummyJsonUser::class;
    }

    protected function viewConfig(): array
    {
        return [
            'labels' => ['heading' => 'Users (public API demo)'],
            // No 'template' override: falls back to the app default
            // ('gridview/with_sidebar.html.twig'), same as every other grid here.
            'options' => [
                'behavior' => ['globalSearch' => ['firstName', 'lastName', 'email']],
            ],
        ];
    }

    protected function dataConfig(): array
    {
        return [
            'model' => [
                'baseUri' => 'https://dummyjson.com',
                'resource' => 'users',
                // dummyjson exposes full-text search on a separate endpoint.
                'searchResource' => 'users/search',
                'listPath' => 'users',
                'totalPath' => 'total',
            ],
            'pagination' => ['defaultPageSize' => 10],
            'sort' => [
                'map' => [
                    'firstName' => ['asc' => ['firstName'], 'desc' => ['firstName']],
                    'age' => ['asc' => ['age'], 'desc' => ['age']],
                ],
                'default' => ['firstName' => 'asc'],
            ],
        ];
    }

    /** @return array<int, mixed> */
    protected function buildColumns(): array
    {
        return [
            'id',
            ['attribute' => 'firstName', 'label' => 'First name', 'sortable' => true],
            ['attribute' => 'lastName', 'label' => 'Last name'],
            ['attribute' => 'email', 'label' => 'Email'],
            ['attribute' => 'age', 'label' => 'Age', 'type' => 'number', 'sortable' => true],
            ['attribute' => 'gender', 'label' => 'Gender'],
            ['attribute' => 'phone', 'label' => 'Phone'],
        ];
    }
}
