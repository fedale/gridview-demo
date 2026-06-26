# Plan — Wiring flatpickr under AssetMapper (deferred)

## Context

gridview-demo uses **AssetMapper** (no Webpack/Node). The gridview-bundle depends on
the **flatpickr** date-picker library in two places:

- **CSS** — `assets/styles/gridview.scss:13` does `@use 'flatpickr/dist/flatpickr';`
  and styles `.flatpickr-input` (line 233).
- **JS** — `assets/controllers/gridview-date-filter_controller.js` does
  `import flatpickr from 'flatpickr'` and `import { Italian } from 'flatpickr/dist/l10n/it.js'`.

flatpickr is only exercised by **date-type column filters** (Comment, Subscriber, Post).
Tag/Category/Series/User in their functional-first form have no date filter, so the rest
of the asset integration can proceed with flatpickr **stubbed out** and this handled later.

## Interim state (what the main work leaves in place)

To unblock Sass compilation now, a **load path** points at `assets/vendor-sass/` containing an
**empty** `flatpickr/dist/flatpickr.css`, so `@use 'flatpickr/dist/flatpickr'` resolves to a
no-op. Result: everything compiles; the date picker is simply unstyled and the
`gridview-date-filter` controller is **not registered** (date filters degrade to a plain
text/date input). This plan replaces the stub with the real library.

## Options to do it properly

### A. AssetMapper + importmap (recommended, no Node)
1. `php bin/console importmap:require flatpickr` — pins flatpickr's JS (and pulls the package
   into `assets/vendor/`), making `import flatpickr from 'flatpickr'` resolve.
2. Also require the locale: `importmap:require flatpickr/dist/l10n/it.js` (or map it explicitly),
   so the `Italian` import resolves.
3. **CSS for Sass**: AssetMapper's importmap covers JS only — Dart Sass still needs the `.css`
   on a load path. Point the SassBundle `load_paths` at the downloaded
   `assets/vendor/flatpickr/dist/` (or copy `flatpickr.css` into `assets/vendor-sass/flatpickr/dist/`),
   replacing the empty stub. Then `@use 'flatpickr/dist/flatpickr'` pulls the real CSS.
4. Register the `gridview-date-filter` Stimulus controller (same mechanism as the other bundle
   controllers — see the main asset-integration work).

### B. Vendor the assets manually
Copy `flatpickr.css`, `flatpickr.js` (ESM build) and `l10n/it.js` into `assets/vendor-sass/` /
`assets/` and reference them by local path. More explicit, no importmap resolution, but the files
become committed artifacts to update by hand on a flatpickr bump.

## Verification

- Open a grid with a date filter (e.g. the future CommentController) and confirm the date filter
  opens a styled flatpickr calendar, in Italian when `_locale=it`.
- `php bin/console sass:build` succeeds with the real flatpickr CSS on the load path.
- `importmap:check` / page network panel: `flatpickr` and its locale resolve with no 404.

## Decision needed before executing

Option **A** keeps the no-Node promise of AssetMapper and is the idiomatic path; choose B only if
pinning via importmap proves troublesome offline. Pick the locale set to ship (just `it`, or more).
