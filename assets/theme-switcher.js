// Light/dark/auto theme switching for the gridview shell. EasyAdmin's dark
// palette is keyed off the `.ea-dark-scheme` class on <body> (which also triggers
// the grid's own dark mode), and Bootstrap/grid components additionally read
// data-bs-theme. We set both and persist the choice in localStorage. The <head>
// has an inline snippet that sets data-bs-theme early to avoid a flash on paint.

function effective(choice) {
    if (choice === 'auto') {
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    return choice;
}

function applyTheme(choice) {
    const scheme = effective(choice);
    const dark = scheme === 'dark';
    document.documentElement.setAttribute('data-bs-theme', scheme);
    if (document.body) {
        document.body.setAttribute('data-bs-theme', scheme);
        document.body.classList.toggle('ea-dark-scheme', dark);
        document.body.classList.toggle('ea-light-scheme', !dark);
    }
}

// Reflect the current choice in the settings dropdown by marking the matching
// theme button as active (mirrors the language links' server-rendered is-active).
function markActive(choice) {
    document.querySelectorAll('[data-gv-theme-value]').forEach((btn) => {
        btn.classList.toggle('is-active', btn.dataset.gvThemeValue === choice);
    });
}

const saved = localStorage.getItem('gv-theme') || 'light';
applyTheme(saved);
document.addEventListener('DOMContentLoaded', () => markActive(saved));

document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-gv-theme-value]');
    if (!btn) return;
    const choice = btn.dataset.gvThemeValue;
    localStorage.setItem('gv-theme', choice);
    applyTheme(choice);
    markActive(choice);
});
