const APP_LOADING_MIN_DURATION = 420;
const APP_LOADING_FADE_DURATION = 250;

let appLoadingStartedAt = performance.now();
let appLoadingHideTimer = null;
let appLoadingRemoveTimer = null;

const appLoadingScreen = () => document.querySelector('[data-app-loading-screen]');

const startAppLoading = () => {
    const loadingScreen = appLoadingScreen();

    appLoadingStartedAt = performance.now();

    if (appLoadingHideTimer !== null) {
        window.clearTimeout(appLoadingHideTimer);
        appLoadingHideTimer = null;
    }

    if (appLoadingRemoveTimer !== null) {
        window.clearTimeout(appLoadingRemoveTimer);
        appLoadingRemoveTimer = null;
    }

    loadingScreen?.removeAttribute('hidden');
    document.documentElement.classList.add('app-is-loading');
};

const finishAppLoading = () => {
    const loadingScreen = appLoadingScreen();
    const elapsed = performance.now() - appLoadingStartedAt;
    const remaining = Math.max(APP_LOADING_MIN_DURATION - elapsed, 0);

    if (appLoadingHideTimer !== null) {
        window.clearTimeout(appLoadingHideTimer);
    }

    appLoadingHideTimer = window.setTimeout(() => {
        document.documentElement.classList.remove('app-is-loading');

        if (appLoadingRemoveTimer !== null) {
            window.clearTimeout(appLoadingRemoveTimer);
        }

        appLoadingRemoveTimer = window.setTimeout(() => {
            loadingScreen?.setAttribute('hidden', 'hidden');
        }, APP_LOADING_FADE_DURATION);
    }, remaining);
};

document.addEventListener('livewire:navigate', startAppLoading);
document.addEventListener('livewire:navigated', () => {
    window.requestAnimationFrame(finishAppLoading);
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
