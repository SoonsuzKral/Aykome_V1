@echo off
title Claude Code Launcher - OpenRouter Force

:: 1. IZOLE ORTAM (Burası kritik, mutlaka temizlenmeli)
set "PROJ_DIR=%~dp0\..\.."
set "USERPROFILE=%~dp0data"
set "HOME=%~dp0data"
if not exist "%~dp0data" mkdir "%~dp0data"

:: 2. OPENROUTER RESMİ DÖKÜMAN AYARLARI (Python ile doğruladığın yapı)
:: Önemli: Döküman /v1 ekleme diyor, sadece /api kalsın.
set "ANTHROPIC_BASE_URL=https://openrouter.ai/api"
set "ANTHROPIC_AUTH_TOKEN=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"
set "ANTHROPIC_API_KEY="

:: 3. MODEL ZORLAMA (Modeli 'Sonnet' gibi tanıtıyoruz)
set "CLAUDE_CODE_ENABLE_GATEWAY_MODEL_DISCOVERY=1"
set "ANTHROPIC_MODEL=poolside/laguna-s-2.1:free"
set "ANTHROPIC_DEFAULT_SONNET_MODEL=poolside/laguna-s-2.1:free"

:: 4. WINDOWS TERMINAL İLE BAŞLAT (Değişkenleri koruyarak)
echo OpenRouter zorlamali modda baslatiliyor...
wt -d "%PROJ_DIR%" -p "Windows PowerShell" cmd /k "set USERPROFILE=%USERPROFILE%&& set HOME=%HOME%&& set ANTHROPIC_BASE_URL=%ANTHROPIC_BASE_URL%&& set ANTHROPIC_AUTH_TOKEN=%ANTHROPIC_AUTH_TOKEN%&& set ANTHROPIC_API_KEY=&& set CLAUDE_CODE_ENABLE_GATEWAY_MODEL_DISCOVERY=1&& set ANTHROPIC_MODEL=%ANTHROPIC_MODEL%&& set ANTHROPIC_DEFAULT_SONNET_MODEL=%ANTHROPIC_MODEL%&& claude"