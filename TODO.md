# Fix .env Permission Error in SettingsController - FIXED

## Steps:
- [x] 1. Edit app/Http/Controllers/Admin/SettingsController.php: Remove updateEnvKey call from updateGeneral()
- [x] 2. Remove unused updateEnvKey() method
- [x] 3. Clear caches: php artisan config:clear && php artisan cache:clear
- [x] 4. Fix permissions if needed: chmod 644 .env; chown -R www-data:www-data storage bootstrap/cache
- [x] 5. Test: Visit http://192.168.1.209/admin/settings/general, submit General settings form (should save to DB without .env error)
- [x] 7. Fix logo/favicon visibility: php artisan storage:link (missing symlink)
- [x] 6. Complete!

The .env write attempt has been removed. Settings now use only database storage via Setting model (best practice). No more permission errors on /admin/settings/general.
