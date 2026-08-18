@echo off
title Zei Perfumes - WhatsApp Bot & Dashboard
echo ===================================================
echo   Zei Perfumes - WhatsApp Web Bot & React Dashboard
echo ===================================================
echo.
cd /d "%~dp0\whatsapp_service"
echo Starting WhatsApp Bot Service on http://localhost:3001 ...
echo.
node server.js
pause
