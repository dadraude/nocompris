const finishAppLoading = () => {
    document.documentElement.classList.remove('app-is-loading');

    window.setTimeout(() => {
        document.querySelector('[data-app-loading-screen]')?.setAttribute('hidden', 'hidden');
    }, 250);
};

document.addEventListener('DOMContentLoaded', () => {
    window.requestAnimationFrame(() => {
        window.setTimeout(finishAppLoading, 120);
    });
});

window.addEventListener('load', finishAppLoading, { once: true });

if ('serviceWorker' in navigator) {
    window.addEventListener('load', async () => {
        try {
            await navigator.serviceWorker.register('/sw.js', {
                scope: '/',
            });
        } catch (error) {
            console.error('Failed to register the service worker.', error);
        }
    });
}
