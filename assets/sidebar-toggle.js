// Mobile sidebar toggle for the gridview shell. On narrow viewports the EA
// sidebar is positioned off-screen; EA reveals it by adding the
// `ea-mobile-sidebar-visible` class on <body> (see vendor-easyadmin/base.css).
// We drive that class from the hamburger button in .content-top, mirroring the
// delegated-listener pattern of theme-switcher.js (no Stimulus controller).

const OPEN_CLASS = 'ea-mobile-sidebar-visible';

function setOpen(open) {
    document.body.classList.toggle(OPEN_CLASS, open);
    const btn = document.querySelector('[data-gv-sidebar-toggle]');
    if (btn) {
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
}

document.addEventListener('click', (e) => {
    const toggle = e.target.closest('[data-gv-sidebar-toggle]');
    if (toggle) {
        setOpen(!document.body.classList.contains(OPEN_CLASS));
        return;
    }

    // Outside click (incl. the backdrop) closes the open sidebar.
    if (document.body.classList.contains(OPEN_CLASS) && !e.target.closest('.sidebar')) {
        setOpen(false);
    }
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && document.body.classList.contains(OPEN_CLASS)) {
        setOpen(false);
    }
});
