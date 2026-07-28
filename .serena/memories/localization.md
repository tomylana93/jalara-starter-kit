# Localization

- Laravel framework language files live in `lang/{locale}`; English source is `lang/en` and Indonesian locale key is `id`.
- User-facing copy in every locale should sound natural, concise, and fully impersonal. Avoid direct address (“kamu”, “Anda”, “you”, “your”) and author/team voice (“kami”, “kita”, “we”, “our”, “us”). Indonesian wording should not feel formal or bureaucratic.
- Keep established technical terms in English when Indonesian wording would feel stiff or reduce clarity, including password, login, user, token, URL, JSON, encoding, key, upload, file, string, integer, array, and item.
- Preserve every translation key, nested structure, replacement placeholder, and HTML entity from the English source when adding or updating a locale.
- `tests/Feature/LocalizationTest.php` enforces English/Indonesian key parity, placeholder parity, translator loading, and the no-direct-address rule.