# Extra 68: minimalist "Log in" pop-up

- `resources/views/home.blade.php`: the account type pop-up is a short list (small icon, name, one line, radio circle) instead of 4 big cards. Same design on phones and computers. `landing.css` link gets `?v=<file time>` so the hosting CDN loads the new CSS.
- `public/css/landing.css`: new pop-up styles at the end (they win over the old card styles in `animal.css`). The pop-up scrolls inside itself on small phones.
- `database/seeders/FreshStartSeeder.php`: same as Extra 56 (copy here for the Hostinger upload).

Deploy note (Linux hosting): folders in `resources/views` are all lowercase; folders in `app/Http/Controllers` start with a capital letter (`Admin`, `Auth`, `Clinic`, `Customer`, `Guest`, `Staff`, `SuperAdmin`).
