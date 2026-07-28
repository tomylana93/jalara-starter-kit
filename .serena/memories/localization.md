# Localization

- Laravel framework language files live in `lang/{locale}`; English source is `lang/en` and Indonesian locale key is `id`.
- Application-owned translations use one file per domain (for example `account.php`), nested first by subdomain/screen and then by semantic group such as `label`, `placeholder`, `button`, and `message`; backend code retrieves short dot-notation keys such as `account.profile.message.updated`.
- Framework-owned files (`auth.php`, `passwords.php`, `pagination.php`, `validation.php`) must retain Laravel's required key structure; the domain/nested convention applies to application-owned translations.
- User-facing copy in every locale should sound natural, concise, and fully impersonal. Avoid direct address (“kamu”, “Anda”, “you”, “your”) and author/team voice (“kami”, “kita”, “we”, “our”, “us”). Indonesian wording should not feel formal or bureaucratic.
- Keep established technical terms in English when Indonesian wording would feel stiff or reduce clarity, including password, login, user, token, URL, JSON, encoding, key, upload, file, string, integer, array, and item.
- Preserve every translation key, nested structure, replacement placeholder, and HTML entity from the English source when adding or updating a locale.
- `tests/Feature/LocalizationTest.php` enforces English/Indonesian key parity, placeholder parity, translator loading, and the no-direct-address rule.