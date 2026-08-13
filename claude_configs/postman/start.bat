@echo off
title Claude Code - Postman Enterprise (Video Match Mode)

:: 1. YOLLARI HAZIRLA
set "PROJ_DIR=%~dp0\..\.."
set "LOCAL_DATA=%~dp0data"
if not exist "%LOCAL_DATA%" mkdir "%LOCAL_DATA%"

:: 2. POSTMAN API AYARLARI
:: Buradaki Key senin Enterprise hesabının anahtarı
set "P_KEY=PMAK-6a7d8bc66913b300014def0d-fa178046af7b45c8ee66def7f4cca49773"

:: 3. BAĞLANTI AYARLARINI DÜZELTME (Connection Refused Çözümü)
:: Postman Gateway için Base URL'yi videodaki protokole göre sadeleştiriyoruz.
set "P_URL=https://api.postman.com/v1/ai"

echo Postman MCP sunucusu bağlandığı ve sistem izole ediliyor...

:: 4. WINDOWS TERMINAL İLE BAŞLAT
:: Not: AUTH_TOKEN ve BASE_URL kısmını videodaki en stabil formata çektim.
wt -d "%PROJ_DIR%" -p "Windows PowerShell" cmd /k ^
"set USERPROFILE=%LOCAL_DATA%&& ^
set HOME=%LOCAL_DATA%&& ^
set ANTHROPIC_API_KEY=&& ^
set ANTHROPIC_AUTH_TOKEN=%P_KEY%&& ^
set ANTHROPIC_BASE_URL=%P_URL%&& ^
set CLAUDE_CODE_ENABLE_GATEWAY_MODEL_DISCOVERY=1&& ^
claude mcp add --transport http https://mcp.postman.com/mcp --header \"Authorization: Bearer %P_KEY%\" --force && ^
claude"

exit