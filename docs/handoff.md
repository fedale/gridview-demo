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

Requisiti: **PHP 8.4**, Composer. Niente Node (asset gestiti senza npm).

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
**Non ancora fatto**: click-test in un browser reale.

## 4. Prossimi passi (in ordine)

1. **Click-test di Tag in browser**: aprire la modale new/edit, filtro live,
   inline-edit, selezione/bulk. (È il primo passo da chiudere.)
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
