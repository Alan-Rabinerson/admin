@echo off
cd /d "%~dp0"
npx tailwindcss -i ./styles/main.css -o ./styles/app.css --watch
