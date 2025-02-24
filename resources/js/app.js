import './bootstrap';
import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: "pusher", // Если используешь Pusher, если Redis — укажи "socket.io"
    key: "local", // Не обязателен для Redis
    wsHost: window.location.hostname,
    wsPort: 6001,
    forceTLS: false,
    disableStats: true,
});

window.Echo.channel("import-channel")
    .listen(".row.imported", (e) => {
        console.log("New Row Imported:", e.row);
    });
