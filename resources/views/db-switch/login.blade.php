<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>AYKOME v6 — Veritabani Yonetimi</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif; background:#0f172a; color:#e2e8f0; display:flex; align-items:center; justify-content:center; min-height:100vh; }
.login-box { background:#1e293b; border:1px solid #334155; border-radius:12px; padding:32px; width:360px; max-width:90vw; }
.login-box h1 { font-size:18px; text-align:center; margin-bottom:4px; }
.login-box p { font-size:12px; color:#64748b; text-align:center; margin-bottom:24px; }
.login-box label { display:block; font-size:11px; color:#94a3b8; margin-bottom:4px; text-transform:uppercase; letter-spacing:.5px; }
.login-box input { width:100%; padding:10px 12px; background:#0f172a; border:1px solid #334155; border-radius:6px; font-size:14px; color:#e2e8f0; }
.login-box input:focus { border-color:#2563eb; outline:none; }
.login-box button { width:100%; padding:10px; background:#2563eb; color:#fff; border:none; border-radius:6px; font-size:14px; cursor:pointer; margin-top:16px; font-weight:600; }
.login-box button:hover { background:#1d4ed8; }
.login-box .error { background:#7f1d1d20; border:1px solid #7f1d1d; color:#fca5a5; padding:8px 12px; border-radius:6px; font-size:12px; margin-bottom:12px; }
</style>
</head>
<body>
<div class="login-box">
    <h1>AYKOME v6</h1>
    <p>Veritabani Yonetim Paneli</p>
    @if($errors->any())
        <div class="error">{{ $errors->first('password') }}</div>
    @endif
    <form method="post">
        @csrf
        <label>Guvenlik Sifresi</label>
        <input type="password" name="password" placeholder="Sifre" autofocus>
        <button type="submit">Giris</button>
    </form>
</div>
</body>
</html>
