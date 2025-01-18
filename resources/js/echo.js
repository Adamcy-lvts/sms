// import Echo from 'laravel-echo';

// import Pusher from 'pusher-js';
// window.Pusher = Pusher;

// window.Echo = new Echo({
//     broadcaster: 'reverb',
//     key: import.meta.env.VITE_REVERB_APP_KEY,
//     wsHost: import.meta.env.VITE_REVERB_HOST,
//     wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
//     wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
//     forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
//     enabledTransports: ['ws', 'wss'],
// });


import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
window.Pusher = Pusher;

if (typeof window.Echo === 'undefined') {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: window.location.hostname,
        wsPort: 8080,
        forceTLS: false,
        enabledTransports: ['ws', 'wss'],
        enableLogging: true,
        wsPath: '/reverb'
    });

    window.Echo.connector.socket.on('connecting', () => {
        console.log('Attempting to connect to Reverb...');
    });

    window.Echo.connector.socket.on('connected', () => {
        console.log('Successfully connected to Reverb');
    });

    window.Echo.connector.socket.on('error', (error) => {
        console.error('Reverb connection error:', error);
    });
}

// Test channel
window.Echo.channel('test-channel')
    .listen('.test-event', (e) => {
        console.log('Received message:', e);
    });