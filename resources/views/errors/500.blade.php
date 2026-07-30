<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 — Sunucu Hatası | {{ config('app.name') }}</title>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@2.0.8/dist/lottie-player.js"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: system-ui, -apple-system, 'Inter', sans-serif;
            background: #0F172A;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            overflow: hidden;
        }
        .orb1 { position: fixed; top: -8rem; right: -8rem; width: 30rem; height: 30rem; background: radial-gradient(circle, rgba(239,68,68,0.18), transparent 70%); border-radius: 50%; pointer-events: none; animation: orbFloat 8s ease-in-out infinite; }
        .orb2 { position: fixed; bottom: -8rem; left: -8rem; width: 28rem; height: 28rem; background: radial-gradient(circle, rgba(250,96,1,0.14), transparent 70%); border-radius: 50%; pointer-events: none; animation: orbFloat 10s ease-in-out infinite reverse; }
        @keyframes orbFloat { 0%,100%{transform:translate(0,0)} 50%{transform:translate(20px,-20px)} }

        .card {
            position: relative;
            background: rgba(15,23,42,0.90);
            border: 1px solid rgba(239,68,68,0.22);
            border-radius: 2rem;
            padding: 2.5rem 2.5rem 3rem;
            max-width: 520px;
            width: 100%;
            text-align: center;
            backdrop-filter: blur(24px);
            box-shadow: 0 40px 80px -30px rgba(239,68,68,0.22), 0 0 0 1px rgba(255,255,255,0.03);
        }

        .lottie-wrap {
            display: flex;
            justify-content: center;
            margin-bottom: 0.5rem;
        }
        lottie-player {
            filter: drop-shadow(0 0 24px rgba(239,68,68,0.35));
        }

        .number {
            font-size: 5.5rem;
            font-weight: 900;
            line-height: 1;
            background: linear-gradient(135deg, #EF4444 30%, #FA6001);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.04em;
        }
        h1 {
            margin-top: 0.75rem;
            font-size: 1.35rem;
            font-weight: 700;
            color: #F1F5F9;
            letter-spacing: -0.02em;
        }
        p {
            margin-top: 0.75rem;
            font-size: 0.875rem;
            color: #64748B;
            line-height: 1.65;
            max-width: 360px;
            margin-left: auto;
            margin-right: auto;
        }
        .btn-group {
            margin-top: 2rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            justify-content: center;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.65rem 1.5rem;
            border-radius: 0.875rem;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            transition: transform 0.18s cubic-bezier(.16,1,.3,1), box-shadow 0.18s;
            cursor: pointer;
            border: none;
        }
        .btn:hover { transform: translateY(-3px); }
        .btn-primary {
            background: linear-gradient(135deg, #FA6001, #C2410C);
            color: #FFF;
            box-shadow: 0 4px 20px rgba(250,96,1,0.4);
        }
        .btn-primary:hover { box-shadow: 0 8px 28px rgba(250,96,1,0.55); }
        .btn-secondary {
            background: rgba(255,255,255,0.06);
            color: #94A3B8;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .btn-secondary:hover { background: rgba(255,255,255,0.10); color: #F1F5F9; }
        .divider {
            margin: 2rem auto 0;
            height: 1px;
            width: 50%;
            background: linear-gradient(to right, transparent, rgba(239,68,68,0.25), transparent);
        }
        .hint {
            margin-top: 1rem;
            font-size: 0.72rem;
            color: #334155;
        }
    </style>
</head>
<body>
    <div class="orb1"></div>
    <div class="orb2"></div>
    <div class="card">
        <div class="lottie-wrap">
            <lottie-player
                src="https://assets2.lottiefiles.com/packages/lf20_afjnypgk.json"
                background="transparent"
                speed="0.8"
                style="width: 200px; height: 200px;"
                loop
                autoplay>
            </lottie-player>
        </div>

        <div class="number">500</div>
        <h1>Beklenmeyen Bir Sorun Oluştu</h1>
        <p>Lütfen tarayıcınızı tamamen kapatıp açın ve tekrar deneyin.<br>Sorun devam ediyorsa sistem yöneticinize danışın.</p>

        <div class="btn-group">
            @auth
                <a href="{{ route('admin.dashboard') }}" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
                    Panele Dön
                </a>
            @else
                <a href="{{ route('login') }}" class="btn btn-primary">
                    Giriş Yap
                </a>
            @endauth
            <a href="javascript:history.back()" class="btn btn-secondary">
                ← Geri Dön
            </a>
        </div>

        <div class="divider"></div>
        <p class="hint">HGB Bilişim AYKOME • destek@hgbilisim.com</p>
    </div>
</body>
</html>
