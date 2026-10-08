Gridview Demo Application
=========================

This project is a demo application for [fedale/gridview-bundle][1], a
Symfony bundle for data grids with filtering, sorting, pagination, inline
editing and CRUD modals.

It started from the [EasyAdmin demo][2]. The original EasyAdmin backend is
kept, and each of its CRUD controllers is rebuilt with gridview-bundle, so
you can compare the two backends side by side.

Requirements
------------

  * PHP 8.4 or higher;
  * PDO-SQLite PHP extension enabled;
  * [Composer][3];
  * and the [usual Symfony application requirements][4].

You don't need Node.js. Assets are managed with AssetMapper and SassBundle.

Installation
------------

The application loads gridview-bundle from a relative path repository
(`../gridview-bundle`), so you must clone both repositories into the same
parent directory:

```bash
$ mkdir gridview && cd gridview/
$ git clone git@github.com:fedale/gridview-bundle.git
$ git clone git@github.com:fedale/gridview-demo.git
```

Then install the application:

```bash
$ cd gridview-demo/
$ composer install                    # symlinks ../gridview-bundle into vendor/
$ bin/sync-gridview-assets            # copies the EasyAdmin shell CSS
$ php bin/console importmap:install
$ php bin/console sass:build
$ php bin/console foundry:load-fixtures initial_state
```

The last command creates the SQLite database (`var/data.db`) and loads the
demo data. You can also regenerate the demo data from the "Fixtures data"
item in the sidebar.

After each update of gridview-bundle, run `bin/sync-gridview-assets` and
`php bin/console sass:build` again.

Usage
-----

If you have [installed Symfony CLI][5], run this command:

```bash
$ symfony serve
```

If you don't have the Symfony binary installed, run
`php -S localhost:8000 -t public/` to use the built-in PHP web server.

Then open these URLs in your browser:

  * `/gridview`: the gridview backend;
  * `/{_locale}/admin` (for example `/en/admin`): the original EasyAdmin
    backend, for comparison.

What the demo shows
-------------------

Doctrine grids with CRUD (`src/Controller/Gridview/`):

  * Posts, post translations, categories, tags, comments, users and
    subscribers;
  * filters (including date filters), global search, per-view columns,
    virtual columns, composite keys, export and detail views.

Read-only grids backed by JSON APIs (through the built-in
`JsonDataProvider`):

  * `/gridview/dummy-json-user`: the public [dummyjson.com][6] users API;
  * `/gridview/internal-product`: the token-gated JSON API of this
    application (`/internal-api/products`);
  * `/gridview/reference`: a remote token-gated reference API.

The token-gated grids need these environment variables. Set them in
`.env.local`:

```dotenv
INTERNAL_API_BASE_URL=...
INTERNAL_API_TOKEN=...
REFERENCE_API_BASE_URL=...
REFERENCE_API_TOKEN=...
```

The bundle configuration is in `config/packages/gridview.yaml`. This demo
uses the `bootstrap5` theme and ships the EN, ES, FR and IT catalogs to the
browser.

License
-------

This demo application is published under the MIT license. See LICENSE.md
for details.

[1]: https://github.com/fedale/gridview-bundle
[2]: https://github.com/EasyCorp/easyadmin-demo
[3]: https://getcomposer.org/
[4]: https://symfony.com/doc/current/setup.html#technical-requirements
[5]: https://symfony.com/download
[6]: https://dummyjson.com/
