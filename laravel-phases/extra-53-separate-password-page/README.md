# Extra 53: separate Edit Profile and Change Password pages

- Edit Profile page = profile details only. Change Password page = password only (new GET route `{area}.password.edit`, `/x/profile/password`).
- My Profile: removed the role/status labels and the role numbers (stats); cleaner hero, "Edit" link, Password & Security shows when the password was last changed.
- New shared partial `partials/profile-edit-form.blade.php` (mode `profile` or `password`) for all 4 users.
- DELETE `resources/views/partials/profile-forms.blade.php` (no longer used).
- No migration. Run `php artisan optimize:clear`.
