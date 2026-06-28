# Handoff — continuare il lavoro su un altro PC

Replica dei controller EasyAdmin di **gridview-demo** con **fedale/gridview-bundle**,
affiancando i due backend per confronto. Si procede un controller alla volta.

## 1. Layout dei repo (IMPORTANTE)

gridview-demo usa il bundle via **path repository relativo** (`../gridview-bundle`),
quindi i due repo devono stare **affiancati nella stessa cartella padre**:

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

Entrambi i branch `main` sono aggiornati (esistono anche i branch
`symfony8-compat` e `gridview-integration`, già mergiati).

## 2. Setup di gridview-demo

Requisiti: **PHP 8.4** con estensione **pdo_sqlite** (DB = `var/data.db`),
Composer. Niente Node (asset gestiti senza npm).
Se manca sqlite: `sudo apt install php8.4-sqlite3` e riavvia il server.

```bash
cd gridview-demo
composer install                       # symlinka il path-repo a ../gridview-bundle
bin/sync-gridview-assets               # popola assets/vendor-gridview/ (gitignored)
php bin/console importmap:install      # scarica i vendor JS pinnati (assets/vendor/, gitignored)
php bin/console sass:build             # compila il CSS del bundle (scarica dart-sass al 1° run)
php bin/console cache:clear
```

Cose **gitignored** che i comandi sopra rigenerano: `assets/vendor-gridview/`,
`assets/vendor/`, l'output Sass in `var/`. Il DB SQLite `var/data.db` **è**
committato (con i fixtures), quindi non serve ricrearlo. Se servisse:
`php bin/console doctrine:schema:create` + caricamento fixtures.

Avvio:
```bash
symfony serve            # oppure: php -S 127.0.0.1:8000 -t public public/index.php
```

- Griglia gridview:  `/gridview/tag`
- Admin EasyAdmin (confronto): `/{_locale}/admin`

## 3. Stato attuale (cosa è fatto)

- **gridview-bundle portato a Symfony 7/8 + Doctrine 3** (retro-compatibile 6.4),
  180 test verdi su PHP 8.4. Dettagli nel commit `symfony8-compat`.
- **Bundle integrato** in gridview-demo (bundles.php, gridview.yaml, path-repo).
- **TagController** (`src/Controller/Gridview/TagController.php`) replica
  `Admin/TagCrudController`: list + filtro + sort + CRUD. `TagRepository::search()`
  alimenta filtri/sort/bulk.
- **Pipeline asset (AssetMapper, no Node)**: SassBundle compila lo SCSS del bundle;
  i controller Stimulus sono copiati in `assets/vendor-gridview/` e registrati
  (import **relativi**) in `assets/bootstrap.js`; entrypoint `gridview-page` in
  `importmap.php`.
- **flatpickr rimandato**: stub SCSS + `docs/flatpickr-assetmapper-plan.md`; il
  controller `gridview-date-filter` NON è registrato (serve solo ai filtri data).

Verificato via HTTP che pagina e tutti gli asset (CSS + 18 moduli JS) rispondono.
- **Click-test di Tag: FATTO** (Playwright headless + Chrome). Verdi: caricamento
  pagina + tabella, modale NEW (form), modale EDIT (precompilata), filtro live
  (AJAX), inline-edit. Nessun errore JS; in console solo `GET /favicon.ico 404`
  (innocuo). **Selezione/bulk non testabile**: `TagController::buildColumns()` non
  aggiunge la colonna checkbox, quindi la griglia non espone selezione. Per
  abilitarla basta `['type' => 'checkbox']` nelle colonne (il bundle ha
  `CheckboxColumn` + `Gridview::hasCheckboxColumn()`). Lasciata fuori di proposito
  (vedi docblock del controller), da riattivare per feedback.

Prerequisito ambiente emerso: serve l'estensione PHP **pdo_sqlite** (il DB è
`var/data.db`); su Debian/Ubuntu `sudo apt install php8.4-sqlite3` e riavviare
il server (`symfony server:stop && symfony serve -d`).

## 4. Prossimi passi (in ordine)

1. ~~**Click-test di Tag in browser**~~ — FATTO (vedi §3). Resta solo, se serve,
   abilitare la colonna checkbox per provare selezione/bulk.
2. **Replica controller-per-controller** (semplice → complesso), fermandosi per
   feedback dopo ciascuno:
   Category → Series → User → Subscriber → Comment → FormFieldReference → Post.
   Pattern di riferimento: `TagController` + `TagRepository::search()`.
   Per ogni entità con pagina detail, aggiungere un `AbstractDetailController`
   accoppiato (stesso `id`, stesse colonne) per abilitare il token `{view}`.
3. **flatpickr** (`docs/flatpickr-assetmapper-plan.md`) quando si arriva a un
   controller con filtri data (Subscriber/Comment/Post).
4. **i18n/look**: le label escono come chiavi grezze (`tag.name`) perché il
   dominio di traduzione non è caricato server-side; da rifinire.

## 5. Note / trabocchetti

- **Dopo ogni update di gridview-bundle**, rilanciare `bin/sync-gridview-assets`
  (i controller Stimulus sono una copia) e `sass:build`.
- **Perché la copia in `assets/vendor-gridview/`**: AssetMapper aggiunge all'import-map
  le dipendenze solo degli import **relativi**; con i controller importati come
  bare-specifier dal symlink in vendor, i loro helper interni (`../i18n.js`)
  davano 404. Tenendoli sotto la root asset dell'app e importandoli relativamente
  AssetMapper li risolve.
- **repara-demo condivide lo stesso bundle via symlink** ma gira su Symfony 6.4:
  le modifiche sono retro-compatibili ma conviene fare uno smoke-test di
  repara-demo, dato che il sorgente del bundle è cambiato.
- Le rotte gridview **non** usano `{_locale}` di proposito: il bundle genera URL
  per le azioni senza quel parametro.

## 6. Layout EA-style per le pagine gridview (IN CORSO — riprendere da qui)

Obiettivo: shell esterna delle pagine gridview **identica a EasyAdmin**
(`.wrapper` > `.sidebar-wrapper` + `.main-content`), riusando il CSS reale di EA.

### Fatto (funziona, verificato)
- **Shell + tema EA**: `templates/gridview/layout.html.twig` (nuovo) con
  `.wrapper / .sidebar-wrapper / .main-content` (niente `.responsive-header`).
  `templates/gridview/index.html.twig` ora estende il layout.
- **CSS shell EA riusato**: i 4 file `color-palette/variables-theme/base/menu.css`
  sono copiati da EA in `assets/vendor-easyadmin/` (gitignored) da
  `bin/sync-gridview-assets`, e importati in `assets/gridview-page.js` (prima di
  `grid.scss`, così la griglia vince). I file componente EA (buttons/forms/
  datagrids) NON sono caricati per non confliggere con la griglia.
- **Sidebar = replica del menu EA reale**: costruita da `src/Twig/GridviewMenuExtension.php`
  (`gridview_menu()`), con sezioni (Content/Community/Administration/Resources/Links),
  icone Font Awesome, badge (Blog Posts/Comments/Subscribers, conteggi dai repo).
  Le voci entità linkano a `/gridview/<entity>` (senza `{_locale}`) e si
  **auto-abilitano** quando esiste la rotta `gridview_<slug>_index`; finché no,
  restano grigie/disabilitate. Dashboard/Fixtures → admin reale; Docs/Demo/Sponsor → URL.
- **Font Awesome**: via CDN nel `<head>` del layout (scelta utente).
- **content-top**: barra con **search** (collegata alla global search della griglia,
  param `myform[_q]`; box global search in-grid nascosto via CSS) + dropdown
  **impostazioni** (tema Light/Dark/Auto + lingua EN/ES/FR) che usa il controller
  `gridview-dropdown` (no Bootstrap JS); stile del menu in `assets/styles/gridview-shell.css`.
- **Tema**: `assets/theme-switcher.js` applica light/dark/auto su `data-bs-theme`
  (html+body) e `data-ea-color-scheme`/`data-gv-theme` (body), persistito in
  localStorage; snippet inline nel `<head>` per evitare il flash.
- **Search Tag**: abilitata `globalSearch: ['name']` in `TagController` per
  alimentare la search della content-top.
- **Locale**: `src/EventListener/GridviewLocaleListener.php` (nuovo) gestisce
  `?_locale=xx` via sessione SOLO per i path `/gridview` (le rotte non hanno `{_locale}`).
- **Verifica**: click-test Playwright **6/7** come prima (l'unico FAIL è la
  selezione/bulk non configurata su Tag, invariato); search 18→8; dropdown e
  toggle tema OK. Screenshot nello scratchpad della sessione.

### DA FARE / da rifinire (riprendere da qui)
1. ~~**Dark mode della griglia**~~ — FATTO. La causa era il trigger sbagliato:
   EA attiva il dark con la **classe `.ea-dark-scheme` sul `<body>`** (non
   `data-ea-color-scheme`), e quella stessa classe attiva anche il dark della
   griglia. `theme-switcher.js` ora fa toggle di `ea-dark-scheme`/`ea-light-scheme`
   (+ `data-bs-theme` per Bootstrap/grid). Verificato: shell + tabella tutto dark
   coerente (body bg #0a0a0a, testo chiaro leggibile).
2. ~~**Locale switch**~~ — FATTO e verificato: `?_locale=es|fr` cambia lingua e il
   `GridviewLocaleListener` la **persiste in sessione** (un reload semplice di
   `/gridview/tag` resta in `es`).
3. **Voci disabilitate**: l'opacità .45 è poco evidente in light; valutare di
   marcarle meglio. (aperto)
4. Le label entità nel menu sono testo inglese fisso nell'extension (coerente con
   docs in inglese); non passano dal dominio di traduzione. (aperto)
5. **Prossimo passo principale**: replicare `CategoryController` (poi Series →
   User → ...). Appena esiste la rotta `gridview_category_index`, la voce in
   sidebar si **auto-abilita** (nessun edit al menu).

### Modifiche di questa sessione (tutte non committate)
- Nuovi: `templates/gridview/layout.html.twig`, `src/Twig/GridviewMenuExtension.php`,
  `src/EventListener/GridviewLocaleListener.php`, `assets/theme-switcher.js`,
  `assets/styles/gridview-shell.css`.
- Modificati: `templates/gridview/index.html.twig`, `assets/gridview-page.js`,
  `bin/sync-gridview-assets` (+copia EA shell), `.gitignore` (+`/assets/vendor-easyadmin/`),
  `src/Controller/Gridview/TagController.php` (+globalSearch).
- Rigenerato (gitignored): `assets/vendor-easyadmin/` (4 file). Su un nuovo PC
  basta `bin/sync-gridview-assets`.
