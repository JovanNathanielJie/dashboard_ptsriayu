@echo off
cd /d "%~dp0"
php artisan queue:work --tries=3 --sleep=5 --timeout=0
