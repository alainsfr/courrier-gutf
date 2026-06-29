import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Ne pas initialiser Echo si les variables ne sont pas prêtes
const key = import.meta.env.VITE_REVERB_APP_KEY;
const host = import.meta.env.VITE_REVERB_HOST ?? window.location.hostname;
const port = Number(import.meta.env.VITE_REVERB_PORT ?? 8080);
const scheme = import.meta.env.VITE_REVERB_SCHEME ?? 'http';

if (!key) {
  console.warn('[Echo] VITE_REVERB_APP_KEY missing: Echo disabled.');
  window.Echo = null;
} else {
  try {
    window.Echo = new Echo({
      broadcaster: 'pusher',
      key,
      wsHost: host,
      wsPort: port,
      wssPort: port,
      forceTLS: scheme === 'https',
      enabledTransports: ['ws', 'wss'],
    });
  } catch (e) {
    console.error('[Echo] init failed:', e);
    window.Echo = null;
  }
}
