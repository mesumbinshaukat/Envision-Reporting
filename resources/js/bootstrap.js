import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const reverbScheme = (import.meta.env.VITE_REVERB_SCHEME ?? window.location.protocol.replace(':', '')).toLowerCase();
const reverbHost = import.meta.env.VITE_REVERB_HOST ?? window.location.hostname;
const reverbPort = Number(
    import.meta.env.VITE_REVERB_PORT
        ?? (window.location.port ? window.location.port : reverbScheme === 'https' ? 443 : 80),
);

const echoConfig = {
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY ?? 'local',
    wsHost: reverbHost,
    wsPort: reverbPort,
    wssPort: reverbPort,
    forceTLS: reverbScheme === 'https',
    authEndpoint: '/broadcasting/auth',
    auth: {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
    },
};

echoConfig.enabledTransports = echoConfig.forceTLS ? ['wss'] : ['ws'];

window.Echo = new Echo(echoConfig);
