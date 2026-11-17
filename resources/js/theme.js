const root = document.documentElement;
const system = window.matchMedia('(prefers-color-scheme: dark)');
const buttons = [...document.querySelectorAll('[data-theme-toggle]')];
let preference;

function readPreference() {
    try { preference = localStorage.getItem('inventaris-theme'); } catch { preference = null; }
}

function apply() {
    const dark = preference === 'dark' || (preference !== 'light' && system.matches);
    root.dataset.theme = dark ? 'dark' : 'light';
    buttons.forEach(button => {
        const label = dark ? 'Aktifkan mode terang' : 'Aktifkan mode gelap';
        button.setAttribute('aria-pressed', String(dark));
        button.setAttribute('aria-label', label);
        button.title = label;
    });
}

buttons.forEach(button => button.addEventListener('click', () => {
    preference = root.dataset.theme === 'dark' ? 'light' : 'dark';
    try { localStorage.setItem('inventaris-theme', preference); } catch { /* Keep this page usable when storage is unavailable. */ }
    apply();
}));
readPreference();
apply();
system.addEventListener('change', apply);
window.addEventListener('storage', event => {
    if (event.key === 'inventaris-theme' || event.key === null) { readPreference(); apply(); }
});
window.addEventListener('pageshow', event => { if (event.persisted) { readPreference(); apply(); } });
