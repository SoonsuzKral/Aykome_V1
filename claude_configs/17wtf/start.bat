@echo off
title Claude Code - 17wtf (TAM IZOLE MOD)

:: Global ayarları görmemesi için "Home" dizinini buraya çekiyoruz
set "USERPROFILE=%~dp0data"
set "HOME=%~dp0data"
if not exist "%~dp0data" mkdir "%~dp0data"

:: --- 17WTF AYARLARI ---
set "ANTHROPIC_BASE_URL=https://api.17.wtf/v1"
set "ANTHROPIC_AUTH_TOKEN=sk-lm0-senin-keyin"
set "ANTHROPIC_MODEL=posiden/deepseek-v4-flash"

:: --- CALISTIRMA ---
echo 17wtf izole ortamda baslatiliyor...
echo.

cd /d "%~dp0\..\.."
claude

pause