import { startStimulusApp } from '@symfony/stimulus-bundle';

const app = startStimulusApp();

// Gridview bundle controllers are auto-discovered: the bundle declares them in
// assets/package.json (symfony.controllers) and this app enables them in
// assets/controllers.json, so AssetMapper resolves each one straight from
// vendor/fedale/gridview-bundle/assets — no manual import/register needed.
// `gridview-date-filter` is disabled there on purpose — it imports flatpickr,
// which is deferred (docs/flatpickr-assetmapper-plan.md).
