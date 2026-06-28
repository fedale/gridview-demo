import { startStimulusApp } from '@symfony/stimulus-bundle';

const app = startStimulusApp();

// Gridview bundle controllers. Imported via RELATIVE paths from
// assets/vendor-gridview/ (copied from the bundle by bin/sync-gridview-assets):
// relative imports let AssetMapper follow each controller's own ../i18n.js etc.
// and add them to the import map. `gridview-date-filter` is intentionally left
// out — it imports flatpickr, which is deferred (docs/flatpickr-assetmapper-plan.md).
import GridviewColumnOrder from './vendor-gridview/controllers/gridview-column-order_controller.js';
import GridviewCrud from './vendor-gridview/controllers/gridview-crud_controller.js';
import GridviewDropdown from './vendor-gridview/controllers/gridview-dropdown_controller.js';
import GridviewFilter from './vendor-gridview/controllers/gridview-filter_controller.js';
import GridviewFormValidate from './vendor-gridview/controllers/gridview-form-validate_controller.js';
import GridviewI18n from './vendor-gridview/controllers/gridview-i18n_controller.js';
import GridviewInfiniteScroll from './vendor-gridview/controllers/gridview-infinite-scroll_controller.js';
import GridviewInlineEdit from './vendor-gridview/controllers/gridview-inline-edit_controller.js';
import GridviewLocaleSwitcher from './vendor-gridview/controllers/gridview-locale-switcher_controller.js';
import GridviewMercure from './vendor-gridview/controllers/gridview-mercure_controller.js';
import GridviewPageJump from './vendor-gridview/controllers/gridview-page-jump_controller.js';
import GridviewRelationFilter from './vendor-gridview/controllers/gridview-relation-filter_controller.js';
import GridviewResponsive from './vendor-gridview/controllers/gridview-responsive_controller.js';
import GridviewSavedSearch from './vendor-gridview/controllers/gridview-saved-search_controller.js';
import GridviewSelection from './vendor-gridview/controllers/gridview-selection_controller.js';
import GridviewVisibility from './vendor-gridview/controllers/gridview-visibility_controller.js';

app.register('gridview-column-order', GridviewColumnOrder);
app.register('gridview-crud', GridviewCrud);
app.register('gridview-dropdown', GridviewDropdown);
app.register('gridview-filter', GridviewFilter);
app.register('gridview-form-validate', GridviewFormValidate);
app.register('gridview-i18n', GridviewI18n);
app.register('gridview-infinite-scroll', GridviewInfiniteScroll);
app.register('gridview-inline-edit', GridviewInlineEdit);
app.register('gridview-locale-switcher', GridviewLocaleSwitcher);
app.register('gridview-mercure', GridviewMercure);
app.register('gridview-page-jump', GridviewPageJump);
app.register('gridview-relation-filter', GridviewRelationFilter);
app.register('gridview-responsive', GridviewResponsive);
app.register('gridview-saved-search', GridviewSavedSearch);
app.register('gridview-selection', GridviewSelection);
app.register('gridview-visibility', GridviewVisibility);
