const notifications = [...document.querySelectorAll('[data-toast]')];
for (const toast of notifications) {
    let remaining = toast.dataset.tone === 'error' ? 8000 : 5000;
    let timer;
    let started;
    let hovering = false;
    const pause = () => {
        if (timer) remaining = Math.max(0, remaining - (performance.now() - started));
        clearTimeout(timer);
        timer = undefined;
    };
    const resume = () => {
        pause();
        if (!toast.isConnected || document.hidden || hovering || toast.contains(document.activeElement)) return;
        started = performance.now();
        timer = setTimeout(() => toast.remove(), remaining);
    };
    toast.addEventListener('mouseenter', () => { hovering = true; pause(); });
    toast.addEventListener('mouseleave', () => { hovering = false; resume(); });
    toast.addEventListener('focusin', pause);
    toast.addEventListener('focusout', () => queueMicrotask(resume));
    toast.querySelector('[data-toast-close]').addEventListener('click', () => { pause(); toast.remove(); });
    document.addEventListener('visibilitychange', resume);
    window.addEventListener('pagehide', () => { pause(); toast.remove(); }, { once: true });
    resume();
}
