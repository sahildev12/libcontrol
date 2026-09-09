import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

function setRealtimeStatus(connected) {
    const dot = document.querySelector('[data-realtime-dot]');
    const label = document.querySelector('[data-realtime-label]');

    if (! dot || ! label) {
        return;
    }

    dot.classList.toggle('bg-emerald-500', connected);
    dot.classList.toggle('bg-amber-400', ! connected);
    label.textContent = connected ? 'Live' : 'Connecting';
}

export function initRealtime() {
    const branchId = document.querySelector('meta[name="branch-id"]')?.content;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    if (! branchId || ! window.Pusher) {
        return;
    }

    const key = import.meta.env.VITE_REVERB_APP_KEY;
    const host = import.meta.env.VITE_REVERB_HOST ?? window.location.hostname;
    const port = Number(import.meta.env.VITE_REVERB_PORT ?? 8080);
    const scheme = import.meta.env.VITE_REVERB_SCHEME ?? 'http';

    if (! key) {
        startPolling(branchId, csrfToken);
        return;
    }

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key,
        wsHost: host,
        wsPort: port,
        wssPort: port,
        forceTLS: scheme === 'https',
        enabledTransports: ['ws', 'wss'],
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
            },
        },
    });

    window.Echo.private(`branch.${branchId}`)
        .listen('.seat-map.updated', (payload) => {
            setRealtimeStatus(true);
            window.dispatchEvent(new CustomEvent('LibControl:seats-updated', { detail: payload }));
        })
        .error(() => {
            setRealtimeStatus(false);
            startPolling(branchId, csrfToken);
        });

    const connection = window.Echo?.connector?.pusher?.connection;
    if (connection) {
        connection.bind('error', () => {
            setRealtimeStatus(false);
            startPolling(branchId, csrfToken);
        });
        connection.bind('unavailable', () => {
            setRealtimeStatus(false);
            startPolling(branchId, csrfToken);
        });
    }

    setRealtimeStatus(true);
}

function startPolling(branchId, csrfToken) {
    if (window.__LibControlSeatPolling) {
        return;
    }

    window.__LibControlSeatPolling = true;
    const poll = async () => {
        try {
            const response = await window.axios.get('/seats/data', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            window.dispatchEvent(new CustomEvent('LibControl:seats-updated', { detail: response.data }));
            setRealtimeStatus(true);
        } catch (error) {
            setRealtimeStatus(false);
        }
    };

    poll();
    window.setInterval(poll, 15000);
}
