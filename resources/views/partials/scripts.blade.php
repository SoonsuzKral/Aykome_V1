{{--
    HGB Bilişim AYKOME — Global Scripts Partial
    Premium SA2 Toast Notification Center
    Okunur flash key'ler: success, error, warning, info
--}}
@if(session('success') || session('error') || session('warning') || session('info') || $errors->any())
<style>
/* ── Premium Toast Base ─────────────────────────────────────────────────── */
.swal2-aykome-toast {
    font-family: system-ui, -apple-system, 'Inter', sans-serif !important;
    border-radius: 14px !important;
    padding: 14px 18px 14px 14px !important;
    min-width: 300px !important;
    max-width: 400px !important;
    box-shadow: 0 10px 40px rgba(0,0,0,0.35), 0 2px 8px rgba(0,0,0,0.2) !important;
    border: 1px solid rgba(255,255,255,0.08) !important;
    overflow: hidden !important;
}
/* ── Toast Variants ─────────────────────────────────────────────────────── */
.swal2-aykome-toast.swal2-icon-success {
    background: linear-gradient(135deg, #0D1B2A, #0A2540) !important;
    border-color: rgba(2, 224, 251, 0.25) !important;
    box-shadow: 0 10px 40px rgba(2,224,251,0.18), 0 2px 8px rgba(0,0,0,0.3) !important;
}
.swal2-aykome-toast.swal2-icon-error {
    background: linear-gradient(135deg, #1A0A0A, #2A0D0D) !important;
    border-color: rgba(239,68,68,0.3) !important;
    box-shadow: 0 10px 40px rgba(239,68,68,0.2), 0 2px 8px rgba(0,0,0,0.3) !important;
}
.swal2-aykome-toast.swal2-icon-warning {
    background: linear-gradient(135deg, #1A1200, #2A1E00) !important;
    border-color: rgba(245,158,11,0.3) !important;
    box-shadow: 0 10px 40px rgba(245,158,11,0.15), 0 2px 8px rgba(0,0,0,0.3) !important;
}
.swal2-aykome-toast.swal2-icon-info {
    background: linear-gradient(135deg, #0A1020, #0D1A30) !important;
    border-color: rgba(59,130,246,0.25) !important;
    box-shadow: 0 10px 40px rgba(59,130,246,0.15), 0 2px 8px rgba(0,0,0,0.3) !important;
}
/* ── Typography ─────────────────────────────────────────────────────────── */
.swal2-aykome-toast .swal2-title {
    color: #F1F5F9 !important;
    font-size: 0.88rem !important;
    font-weight: 700 !important;
    letter-spacing: -0.01em !important;
    line-height: 1.3 !important;
    padding: 0 !important;
    margin-bottom: 2px !important;
}
.swal2-aykome-toast .swal2-html-container,
.swal2-aykome-toast .swal2-content {
    color: #94A3B8 !important;
    font-size: 0.8rem !important;
    line-height: 1.5 !important;
    margin: 0 !important;
    padding: 0 !important;
}
/* ── Icons ──────────────────────────────────────────────────────────────── */
.swal2-aykome-toast .swal2-icon.swal2-success { border-color: #02E0FB !important; }
.swal2-aykome-toast .swal2-icon.swal2-success [class^='swal2-success-line'] { background-color: #02E0FB !important; }
.swal2-aykome-toast .swal2-icon.swal2-success .swal2-success-ring { border-color: rgba(2,224,251,0.3) !important; }
/* ── Progress Bar ───────────────────────────────────────────────────────── */
.swal2-aykome-progress {
    height: 3px !important;
    border-radius: 0 0 14px 14px !important;
}
.swal2-icon-success .swal2-aykome-progress { background: linear-gradient(90deg, #02E0FB, #0284C7) !important; }
.swal2-icon-error   .swal2-aykome-progress { background: linear-gradient(90deg, #EF4444, #DC2626) !important; }
.swal2-icon-warning .swal2-aykome-progress { background: linear-gradient(90deg, #F59E0B, #D97706) !important; }
.swal2-icon-info    .swal2-aykome-progress { background: linear-gradient(90deg, #3B82F6, #1D4ED8) !important; }
/* ── Entry / Exit Animations ────────────────────────────────────────────── */
@keyframes aykomeSlideIn {
    from { opacity: 0; transform: translateX(60px) scale(0.96); }
    to   { opacity: 1; transform: translateX(0) scale(1); }
}
@keyframes aykomeSlideOut {
    from { opacity: 1; transform: translateX(0) scale(1); }
    to   { opacity: 0; transform: translateX(60px) scale(0.96); }
}
.swal2-aykome-in  { animation: aykomeSlideIn  0.35s cubic-bezier(.16,1,.3,1) both !important; }
.swal2-aykome-out { animation: aykomeSlideOut 0.25s ease-in both !important; }
/* ── Close button ───────────────────────────────────────────────────────── */
.swal2-aykome-toast .swal2-close {
    color: #64748B !important;
    font-size: 1.1rem !important;
    margin: 0 !important;
}
.swal2-aykome-toast .swal2-close:hover { color: #F1F5F9 !important; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Swal === 'undefined') return;

    const toastBase = {
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        showCloseButton: true,
        timerProgressBar: true,
        customClass: {
            popup: 'swal2-aykome-toast',
            timerProgressBar: 'swal2-aykome-progress',
        },
        showClass:  { popup: 'swal2-aykome-in' },
        hideClass:  { popup: 'swal2-aykome-out' },
    };

    @if(session('success'))
    Swal.fire({
        ...toastBase,
        icon:  'success',
        title: '{{ __("İşlem Başarılı") }}',
        html:  @json(session('success')),
        timer: 4500,
        iconColor: '#02E0FB',
    });
    @endif

    @if(session('info'))
    Swal.fire({
        ...toastBase,
        icon:  'info',
        title: '{{ __("Bilgi") }}',
        html:  @json(session('info')),
        timer: 5000,
        iconColor: '#3B82F6',
    });
    @endif

    @if(session('warning'))
    Swal.fire({
        ...toastBase,
        icon:         'warning',
        title:        '{{ __("Uyarı") }}',
        html:         @json(session('warning')),
        timer:        6000,
        iconColor:    '#F59E0B',
        timerProgressBar: true,
    });
    @endif

    @if(session('error'))
    Swal.fire({
        icon:  'error',
        title: '{{ __("Hata!") }}',
        html:  @json(session('error')),
        confirmButtonColor: '#EF4444',
        confirmButtonText:  'Tamam',
        background:   '#1A0A0A',
        color:        '#F1F5F9',
        customClass:  { popup: 'swal2-aykome-toast swal2-icon-error' },
        showClass:    { popup: 'swal2-aykome-in' },
        hideClass:    { popup: 'swal2-aykome-out' },
        toast:        true,
        position:     'top-end',
        showCloseButton: true,
    });
    @endif

    @if($errors->any())
    Swal.fire({
        icon:  'error',
        title: '{{ __("Form Hatası") }}',
        html: '<ul style="text-align:left;padding-left:1rem;font-size:0.85rem;color:#94A3B8;line-height:1.7">'
            @foreach($errors->all() as $err)
            + '<li style="margin-bottom:2px">{{ addslashes($err) }}</li>'
            @endforeach
            + '</ul>',
        background:        '#1A0808',
        color:             '#F1F5F9',
        confirmButtonColor: '#EF4444',
        confirmButtonText:  'Tamam',
        customClass: {
            title: 'swal2-error-title',
        },
    });
    @endif
});
</script>
@endif
