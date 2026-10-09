// Entrypoint for the gridview demo pages: boots Stimulus (and registers the
// bundle controllers via bootstrap.js) and pulls in the stylesheets. CSS
// imported here is auto-injected as <link> tags by importmap().
import './bootstrap.js';
import 'bootstrap/dist/css/bootstrap.min.css';
// EasyAdmin "shell" theme (outer layout: .wrapper / .sidebar-wrapper /
// .main-content). Copied into assets/styles/ea/ by bin/sync-gridview-assets.
import './styles/ea/color-palette.css';
import './styles/ea/variables-theme.css';
import './styles/ea/base.css';
import './styles/ea/menu.css';
// Badge palette (.badge-primary/.badge-success/...), used e.g. by the Tag grid's
// name column; without it badges fall back to plain Bootstrap styling.
import './styles/ea/badges.css';
// Grid styles last so the widget's own Bootstrap + .gv-* rules win over EA.
import './styles/grid.scss';
import './styles/gridview-shell.css';
// Theme (light/dark/auto) switching for the shell's settings dropdown.
import './theme-switcher.js';
// Mobile hamburger: toggles the off-screen sidebar via the content-top button.
import './sidebar-toggle.js';
