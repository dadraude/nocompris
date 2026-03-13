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
