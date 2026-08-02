import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// ── Reverb connection gürültüsünü sustur (local test ortamı) ────────────────
Pusher.logToConsole = false;  // WS connect/disconnect kırmızı spam'i kapat

// VITE_BROADCAST_CONNECTION yoksa/hatalıysa ya da WS sunucusu offline ise sessiz kal
const _broadcastEnabled = (import.meta.env.VITE_BROADCAST_CONNECTION ?? 'true') !== 'false' && !!import.meta.env.VITE_REVERB_APP_KEY;

window.Echo = null;

if (_broadcastEnabled) {
    try {
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: import.meta.env.VITE_REVERB_APP_KEY,
            wsHost: import.meta.env.VITE_REVERB_HOST ?? 'localhost',
            wsPort: import.meta.env.VITE_REVERB_PORT ?? 8090,
            wssPort: import.meta.env.VITE_REVERB_PORT ?? 8090,
            forceTLS: false,
            enabledTransports: ['ws', 'wss'],
            disableStats: true,
            debug: false,  // Echo-level debug log kapatıldı (spam)
        });
    } catch (e) {
        console.warn('[Reverb] WS kapalı - Mute');
        window.Echo = null;
    }
}

// WS offline / hata durumlarında dev Console Log kırmızı spam'ini sustur
if (window.Echo && window.Echo.connector && window.Echo.connector.pusher && window.Echo.connector.pusher.connection) {
    const _wsConn = window.Echo.connector.pusher.connection;
    _wsConn.bind('state_change', () => { /* sessiz */ });
    _wsConn.bind('error', () => { console.warn('WSC kapalı - Mute'); });
}

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

// ── Current user context (from meta tags) ─────────────────────────────────────
const _userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
const _userInstId = document.querySelector('meta[name="user-institution-id"]')?.getAttribute('content');
const _userRoles = (document.querySelector('meta[name="user-roles"]')?.getAttribute('content') || '').split(',');
const _isMunicipalityUser = _userRoles.some(r => ['super-admin', 'municipality-admin', 'municipality-staff'].includes(r.trim()));

// ── Listen on admin-notifications channel ────────────────────────────────────
// broadcastAs() values are dot-prefixed in JS listeners
if (window.Echo) console.log('[Reverb] Echo dinlemeye başladı — admin-notifications kanalı aktif.');

// ── Notification badge bump (navbar red dot) ─────────────────────────────────
function _bumpNotifBadge() {
    const badge = document.getElementById('notif-badge');
    if (!badge) return;
    const current = parseInt(badge.textContent) || 0;
    badge.textContent = current + 1;
    badge.classList.remove('hidden');
}

// ── Institution filter: show only if user is municipality/admin OR same inst ──
function _shouldShowNotification(data) {
    if (_isMunicipalityUser) return true;
    if (data.institution_id && _userInstId) {
        return String(data.institution_id) === String(_userInstId);
    }
    return false;
}

if (window.Echo) {
    window.Echo.channel('admin-notifications')

    // ── Saha görevi tamamlandı ────────────────────────────────────────────
    .listen('.field-task.completed', (data) => {
        console.log('[Reverb] field-task.completed alındı:', data);
        if (!_shouldShowNotification(data)) return;
        _toast('success', 'Saha Görevi Tamamlandı', data.message ?? 'Bir saha görevi tamamlandı.');
        _playNotificationSound();
        _bumpNotifBadge();
    })

    // ── Makbuz yüklendi ───────────────────────────────────────────────────
    .listen('.receipt.uploaded', (data) => {
        console.log('[Reverb] receipt.uploaded alındı:', data);
        if (!_shouldShowNotification(data)) return;
        const text = data.message ?? 'Bir başvuruya makbuz yüklendi.';
        _toast('info', 'Makbuz Yüklendi', text, data.detail_url ?? null);
        _playNotificationSound();
        _bumpNotifBadge();
    })

    // ── Başvuru gönderildi (alt kurum → belediye) ─────────────────────────
    .listen('.application.submitted', (data) => {
        console.log('[Reverb] application.submitted alındı:', data);
        if (!_shouldShowNotification(data)) return;
        const text = data.message ?? 'Bir başvuru belediyeye gönderildi.';
        const inst = data.institution ? ' (' + data.institution + ')' : '';
        _toast('success', 'Yeni Başvuru' + inst, text, data.detail_url ?? null);
        _playNotificationSound();
        _bumpNotifBadge();
    });
}

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
