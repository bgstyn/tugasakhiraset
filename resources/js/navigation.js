const sidebar = document.getElementById('app-sidebar');
const openButton = document.getElementById('mobile-hamburger');
const closeButton = document.getElementById('sidebar-close-btn');
const collapseButton = document.getElementById('sidebar-collapse-btn');
const overlay = document.getElementById('sidebar-overlay');
const main = document.getElementById('app-main');
const desktop = window.matchMedia('(min-width: 1000px)');

function displayDrawer(open, restoreFocus = false) {
    sidebar.dataset.open = String(open);
    sidebar.inert = !desktop.matches && !open;
    overlay.hidden = !open;
    main.inert = open;
    openButton.setAttribute('aria-expanded', String(open));
    document.body.style.overflow = open ? 'hidden' : '';
    if (open) {
        sidebar.setAttribute('role', 'dialog');
        sidebar.setAttribute('aria-modal', 'true');
        requestAnimationFrame(() => {
            if (sidebar.dataset.open === 'true') closeButton.focus();
        });
    } else {
        sidebar.removeAttribute('role');
        sidebar.removeAttribute('aria-modal');
        if (restoreFocus) requestAnimationFrame(() => openButton.focus());
    }
}

function collapseMenu(collapsed) {
    document.body.dataset.navCollapsed = String(collapsed);
    collapseButton.setAttribute('aria-expanded', String(!collapsed));
    const label = collapsed ? 'Lebarkan menu' : 'Ciutkan menu';
    collapseButton.setAttribute('aria-label', label);
    collapseButton.title = label;
}

collapseMenu(localStorage.getItem('sidebar-collapsed') === 'true');
displayDrawer(false);
desktop.addEventListener('change', () => displayDrawer(false));
openButton.addEventListener('click', () => displayDrawer(true));
closeButton.addEventListener('click', () => displayDrawer(false, true));
overlay.addEventListener('click', () => displayDrawer(false, true));
collapseButton.addEventListener('click', () => {
    const collapsed = document.body.dataset.navCollapsed !== 'true';
    collapseMenu(collapsed);
    localStorage.setItem('sidebar-collapsed', String(collapsed));
});
document.addEventListener('keydown', event => {
    if (sidebar.dataset.open !== 'true') return;
    if (event.key === 'Escape') displayDrawer(false, true);
    if (event.key !== 'Tab') return;
    const items = [...sidebar.querySelectorAll('a, button')];
    const first = items[0];
    const last = items.at(-1);
    if (!sidebar.contains(document.activeElement)) {
        event.preventDefault();
        closeButton.focus();
    } else if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
});
