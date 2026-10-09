# Handoff — continuing the work on another PC

Replicating the EasyAdmin controllers of **gridview-demo** with **fedale/gridview-bundle**,
keeping the two backends side by side for comparison, and using the demo to exercise
new bundle features (JSON data providers, per-view columns, export, grouping, ...).

Last updated: 2026-10-08.

## 1. Repo layout (IMPORTANT)

gridview-demo uses the bundle through a **relative path repository** (`../gridview-bundle`),
so the two repos must sit **side by side in the same parent directory**:

```
<parent>/
├── gridview-bundle     # git@github.com:fedale/gridview-bundle.git
└── gridview-demo       # git@github.com:fedale/gridview-demo.git
```

```bash
mkdir gridview && cd gridview
git clone git@github.com:fedale/gridview-bundle.git
git clone git@github.com:fedale/gridview-demo.git
```

Use the SSH remotes: HTTPS push needs a personal access token.

## 2. gridview-demo setup

Requirements: **PHP 8.4** with the **pdo_sqlite** extension, Composer. No Node
(assets are managed by AssetMapper + SassBundle).
If sqlite is missing: `sudo apt install php8.4-sqlite3` and restart the server.

```bash
cd gridview-demo
composer install                                   # symlinks the path repo to ../gridview-bundle
bin/sync-gridview-assets                           # copies the EasyAdmin shell CSS into assets/styles/ea/
php bin/console importmap:install                  # downloads the pinned JS vendors into assets/vendor/
php bin/console sass:build                         # compiles the grid SCSS (downloads dart-sass on the first run)
php bin/console foundry:load-fixtures initial_state  # creates the SQLite schema and loads the demo data
```

The SQLite DB (`var/data.db`) is **not** versioned anymore (commit `dc39d8c`):
`foundry:load-fixtures` resets the schema from the Doctrine mapping and loads
`App\Story\InitialStateStory`. A DB created this way has no migration metadata:
to use `doctrine:migrations:migrate` on it later, first run
`doctrine:migrations:sync-metadata-storage` and
`doctrine:migrations:version --add --all`. The "Fixtures data" item in the sidebar
(`/{_locale}/admin/regenerate-fixtures`) does the same from the browser.

The token-gated JSON grids need `INTERNAL_API_BASE_URL`, `INTERNAL_API_TOKEN`,
`REFERENCE_API_BASE_URL` and `REFERENCE_API_TOKEN` in `.env.local`.

Start:
```bash
symfony serve            # or: php -S 127.0.0.1:8000 -t public public/index.php
```

- Gridview backend: `/gridview` (dashboard) and `/gridview/<entity>`
- EasyAdmin admin (comparison): `/{_locale}/admin`

Tests: `./vendor/bin/phpunit` (`SmokeTest`, `GridviewGroupingLazyTest`,
`InternalProductApiTest`). Don't use `simple-phpunit`: it installs its own
PHPUnit 9.5, which clashes with the 9.6 in `vendor/`.

## 3. Current state (what is done)

### Bundle integration
- **Bundle config** in `config/packages/gridview.yaml`: `bootstrap5` theme, client-side
  i18n (EN/ES/FR/IT, reusing the app's `messages` catalogs), global defaults
  (Turbo, `bootstrap_5_layout.html.twig` form theme, page sizes 10/20/50/100),
  `JsonDataProvider` for the three JSON grids.
- **Stimulus controllers**: auto-discovered by AssetMapper from
  `vendor/fedale/gridview-bundle/assets` and enabled in `assets/controllers.json`.
  No manual copy or registration anymore. Only `date-filter` is disabled (see §4).
- **EA-style shell** (`templates/gridview/layout.html.twig`): EasyAdmin
  `.wrapper / .sidebar-wrapper / .main-content` layout, reusing the EA shell CSS
  (`color-palette`, `variables-theme`, `base`, `menu`, `badges`) copied by
  `bin/sync-gridview-assets`; Font Awesome via CDN; mobile hamburger
  (`assets/sidebar-toggle.js`).
- **Sidebar**: `src/Twig/GridviewMenuExtension.php` (`gridview_menu()`) replicates
  the EA menu. Entity items link to the router-generated `gridview_<slug>_index`
  route and are disabled until that route exists.
- **content-top**: search wired to the grid global search + settings dropdown
  (Light/Dark/Auto theme, language). Theme handled by `assets/theme-switcher.js`
  (`ea-dark-scheme`/`ea-light-scheme` on `<body>` + `data-bs-theme`), no flash.
- **Locale**: gridview routes have no `{_locale}`; `src/EventListener/GridviewLocaleListener.php`
  handles `?_locale=xx` and persists it in the session for `/gridview` paths.
- **App default grid template**: one shared template, no per-controller overrides.

### Grids (`src/Controller/Gridview/`)

| Grid | Route | Highlights |
| --- | --- | --- |
| Dashboard | `/gridview` | landing page |
| Tag | `/gridview/tag` | reference pattern; UUID key, popularity bar column, checkbox selection + bulk delete, custom filter modal, "View posts" action, row actions leave the grid frame; detail view in `TagDetailController` |
| Category | `/gridview/category` | EA parity, position filter, filter-clear chips, list/card renderers with view switcher and custom item templates |
| Post | `/gridview/posts` | fetch-joined author/category, author and category filters, per-view columns, form/detail-only fields, EA-like form (fieldsets, Bootstrap 5 theme), new post saveable |
| Post translation | `/gridview/post-translations` | composite primary key (`post_id`, `locale`) |
| User | `/gridview/users` | lazy grouping: expands each user's posts |
| Comment | `/gridview/comment` | date filter (plain input until flatpickr lands), datetime control |
| Subscriber | `/gridview/subscribers` | virtual column |
| DummyJSON user | `/gridview/dummy-json-user` | read-only, public dummyjson.com API via `JsonDataProvider` |
| Internal product | `/gridview/internal-product` | read-only, the app's own token-gated API (`/internal-api/products`) |
| Reference | `/gridview/reference` | read-only, remote token-gated reference API (only the "column" category for now) |

Export (`gridview-export` controller) is enabled.

## 4. Next steps / open items

1. **Remaining EasyAdmin controllers**: `Series` and `FormFieldReference` have no
   gridview replica yet. Follow `TagController` / `TagRepository::search()`; their
   sidebar items enable themselves as soon as the `gridview_<slug>_index` route exists.
   For entities with a detail page, add a paired `AbstractDetailController`
   (same `id`, same columns) to enable the `{view}` token.
2. **flatpickr** (`docs/flatpickr-assetmapper-plan.md`): still deferred. The
   `date-filter` controller is disabled in `assets/controllers.json` and the
   flatpickr CSS is an empty stub in `assets/styles/stubs/`, so date filters
   (e.g. Comment) render as a plain input.
3. **Reference grid**: only the "column" category is rendered; the other categories
   of the Angular screen are still to do.
4. **Sidebar polish** (open): the .45 opacity of disabled items is not very visible
   in light mode; menu labels are fixed English strings in the extension and don't go
   through the translation domain.

## 5. Notes / pitfalls

- **After each gridview-bundle update**, run `bin/sync-gridview-assets` and
  `php bin/console sass:build` again, then clear the cache.
- **Migrations**: `migrations/` is in sync with the mapping (`Version20261008180453`
  adds `post_translation` and the Tag UUID key; it recreates `tag`/`post_tag` empty,
  so reload the fixtures after it). `doctrine:schema:validate` still reports
  `post_translation` and `post_tag` out of sync: it's a DBAL/SQLite false positive
  on composite-key tables (`ON UPDATE NO ACTION`) that persists after
  `schema:update`; ignore it.
- **repara-demo shares the same bundle via symlink** but runs on Symfony 6.4:
  bundle changes must stay backward compatible; smoke-test repara-demo after
  changing the bundle source.
- The gridview routes do **not** use `{_locale}` on purpose: the bundle generates URLs
  for the actions without that parameter.
