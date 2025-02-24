import './bootstrap';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: 'a60b7882d562ae41987b',
    cluster: 'mt1',
    forceTLS: true
});

window.Echo.channel('import-channel')
    .listen('.row.imported', (e) => {
        console.log('New Row Imported:', e.row);
    });
