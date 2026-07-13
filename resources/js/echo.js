import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// ── Reverb debug log ─────────────────────────────────────────────────────────
Pusher.logToConsole = true;   // shows all WS connect/disconnect events in console

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST ?? 'localhost',
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 8090,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 8090,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
    debug: true,   // Echo-level debug: logs subscribe/auth/error to console
});

// Log connection state changes so you can see WHY it keeps closing
window.Echo.connector.pusher.connection.bind('state_change', (states) => {
    console.log('[Reverb] state:', states.previous, '→', states.current, new Date().toLocaleTimeString());
});
window.Echo.connector.pusher.connection.bind('error', (err) => {
    console.error('[Reverb] connection error:', err);
});

// ── AudioContext unlock — fires once on first user interaction ────────────────
let _audioUnlocked = false;
function _unlockAudio() {
    if (_audioUnlocked) return;
    _audioUnlocked = true;
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    ctx.resume().then(() => console.log('[Audio] context unlocked'));
    document.removeEventListener('click', _unlockAudio);
    document.removeEventListener('keydown', _unlockAudio);
}
document.addEventListener('click',   _unlockAudio, { once: true });
document.addEventListener('keydown', _unlockAudio, { once: true });

// ── Helper: play notification sound ──────────────────────────────────────────
function _playNotificationSound() {
    new Audio('/sounds/notification.mp3')
        .play()
        .catch(e => console.log('[Audio] ses izni bekleniyor:', e));
}

// ── Listen on admin-notifications channel ────────────────────────────────────
// broadcastAs() values are dot-prefixed in JS listeners
console.log('Echo dinlemeye başladı — admin-notifications kanalı aktif.');

// ── Notification badge bump (navbar red dot) ─────────────────────────────────
function _bumpNotifBadge() {
    const badge = document.getElementById('notif-badge');
    if (!badge) return;
    const current = parseInt(badge.textContent) || 0;
    badge.textContent = current + 1;
    badge.classList.remove('hidden');
}

window.Echo.channel('admin-notifications')

    // ── Saha görevi tamamlandı ────────────────────────────────────────────
    .listen('.field-task.completed', (data) => {
        console.log('[Reverb] field-task.completed alındı:', data);
        _toast('success', 'Saha Görevi Tamamlandı', data.message ?? 'Bir saha görevi tamamlandı.');
        _playNotificationSound();
        _bumpNotifBadge();
    })

    // ── Makbuz yüklendi ───────────────────────────────────────────────────
    .listen('.receipt.uploaded', (data) => {
        console.log('[Reverb] receipt.uploaded alındı:', data);
        const text = data.message ?? 'Bir başvuruya makbuz yüklendi.';
        _toast('info', 'Makbuz Yüklendi', text, data.detail_url ?? null);
        _playNotificationSound();
        _bumpNotifBadge();
    });

function _toast(icon, title, text, actionUrl = null) {
    if (typeof Swal === 'undefined') return;
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: icon,
        title: title,
        html: actionUrl
            ? `${text} <a href="${actionUrl}" class="underline text-xs" style="margin-left:6px">Görüntüle →</a>`
            : text,
        showConfirmButton: false,
        timer: 7000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        },
    });
}
