# Architektura prototypu

Projekt je Laravel 12 aplikace s Blade sablonami a jednoduchymi controllery nad Query Builderem.

## Hlavni soubory

- `routes/web.php` definuje auth, herni a admin routy.
- `app/Http/Controllers/AuthController.php` resi registraci, login a logout.
- `app/Http/Controllers/GameController.php` resi hracske casti: Palouk, lokace, ukoly, napovedy, Mraveniste, budovy, customizaci, zebricek a zpravy.
- `app/Http/Controllers/AdminController.php` resi admin dashboard, hrace, zpravy, novinky a obsah.
- `database/migrations/2026_06_10_000000_create_game_tables.php` obsahuje herni schema.
- `database/seeders/DatabaseSeeder.php` zaklada admina, test hrace a herni obsah.

## Dulezite mechaniky

- Registrace vytvari hrace ve stavu `pending_approval`. Hrac muze hrat, ale v zebricku jsou jen `active` hraci.
- Palouk pouziva graf zavislosti v `location_requirements`. Stav lokace se pocita server-side.
- Odpovedi ukolu jsou ulozene jako hash pres Laravel `Hash`.
- Napovedy se kupuji za suroviny a zustavaji v `user_hint_purchases`.
- Sloty Mraveniste jsou v `building_slots`, nakoupene sloty v `user_building_slots`, postavene budovy v `user_buildings`.
- Kazdy typ budovy lze postavit jen jednou na hrace.
- SVG customizace se uklada jako JSON do `user_building_customizations`; zmena je omezena na jednou denne pres `last_customization_change_at`.
- Audit log zapisuje dulezite server-side akce do `audit_logs`.

## Placeholder SVG

Assety jsou v `public/assets/placeholders`. Budovy uz obsahuji testovaci ID:

- `edit_color__koberec`
- `edit_color__stena`
- `edit_color__vlajka`
- `edit_pattern__koberec__pruhy`
- `edit_variant__vlajka_symbol__mravenec`

Finalni ilustrace mohou tyto placeholdery nahradit bez zmen backendu.
