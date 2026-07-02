# Prazdninova hra

Prvni pracovni prototyp Laravel 12 aplikace s tematem mraveniste. Obsahuje registraci bez e-mailu, prihlaseni, schvalovani hracu, Palouk, Mraveniste, budovy, napovedy, customizaci SVG, zebricek, zpravy, novinky, odznacky, audit log a seed data.

## Lokalne

V tomto workspace je kvuli lokalni PHP instalaci pripraven `php.ini`, ktery zapina potrebna PHP extensions.

1. `php -c .\php.ini .\composer.phar install`
2. `npm.cmd install`
3. `copy .env.example .env`
4. `php -c .\php.ini artisan key:generate`
5. Nastavit databazi v `.env` podle potreby. Pro rychly lokalni test funguje SQLite v `database/database.sqlite`.
6. `php -c .\php.ini artisan migrate --seed`
7. `npm.cmd run dev`
8. `php -c .\php.ini artisan serve`

Testovaci prihlaseni jsou urcena jen pro lokalni vyvoj v `APP_ENV=local`. Produkcni prostredi nesmi pouzivat zname demo prihlasovaci udaje.

Testovaci kody ukolu na Palouku jsou shodne se slugem lokace, napriklad `startovni-kamen`. Kody specialnich ukolu budov maji tvar `slug-budovy-1`, napriklad `malirska-komora-1`.

## Nasazeni na PHP/MySQL hosting

- `public` musi byt document root.
- V `.env` nastavit produkcni `APP_KEY`, `APP_URL`, `DB_CONNECTION=mysql`, MySQL host, databazi, uzivatele a heslo.
- Na hostingu musi byt PHP 8.2+ a extensions `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `curl`, `zip`, `xml`, `ctype`, `json`, `tokenizer`.
- Spustit `composer install --no-dev --optimize-autoloader`.
- Spustit `php artisan migrate --force`.
- Herni obsah bez zasahu do hracu obnovit pres `php artisan db:seed --class=ContentRefreshSeeder --force`.
- Produkcni admin ucet nevytvaret ze znamych demo prihlasovacich udaju.
- Buildnout assets pres `npm run build` a nahrat vystup.
- E-maily jsou zatim vypnute, zpravy zustavaji uvnitr aplikace.

## Stav prototypu

Grafika je placeholder SVG v `public/assets/placeholders`. Finalni SVG pujde pozdeji nahradit, pokud zachova ID ve tvaru `edit_color__...`, `edit_pattern__...` a `edit_variant__...`.
