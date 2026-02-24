# Setup Windows - Laravel prostredi pro Call CRM

Tento postup je urceny pro tento projekt (`Call CRM`) na Windows 10/11.

Cil: zprovoznit lokalni Laravel vyvojove prostredi s PHP, Composer, Node.js a PostgreSQL tak, aby slo spustit:

- `composer install`
- `php artisan breeze:install blade`
- `php artisan migrate`
- `npm run dev`

## 0. Co budes potrebovat

- Administrator pristup na PC (idealne)
- Internet pripojeni
- PowerShell

## 1. Instalace PHP 8.2+ (doporuceno 8.3)

Mas dve rozumne varianty. Pro tento projekt doporucuji variantu A (rychlejsi start).

### Varianta A (doporucena): Laravel Herd

Laravel Herd nainstaluje lokalni PHP runtime a usnadni Laravel vyvoj na Windows.

1. Stahni a nainstaluj Laravel Herd pro Windows.
2. Po instalaci restartuj PowerShell.
3. Over, ze `php` funguje:

```powershell
php -v
```

Pokud `php` porad nejde, odhlas/prihlas Windows session nebo restartuj PC.

### Varianta B: Samostatne PHP (manualne)

1. Stahni "Thread Safe" ZIP build PHP 8.3 pro Windows x64.
2. Rozbal napr. do:

```text
C:\php
```

3. Zkopiruj `php.ini-development` na `php.ini`.
4. V `php.ini` zapni rozsireni (odkomentuj):

```ini
extension=curl
extension=fileinfo
extension=mbstring
extension=openssl
extension=pdo_pgsql
extension=pgsql
extension=sodium
extension=zip
```

5. Pridej `C:\php` do `PATH`.
6. Otevri novy PowerShell a over:

```powershell
php -v
php --ini
```

## 2. Instalace Composeru

### Doporuceny zpusob (Composer-Setup.exe)

1. Stahni Composer installer pro Windows (`Composer-Setup.exe`).
2. Spust installer.
3. Pri dotazu na PHP executable vyber:
   - `C:\php\php.exe` (pokud mas manualni PHP)
   - nebo PHP z Herd instalace
4. Dokonci instalaci.
5. Otevri novy PowerShell a over:

```powershell
composer --version
```

### Co delat, kdyz `composer` neni nalezen

1. Zkontroluj PATH:

```powershell
$env:Path -split ';'
```

2. Typicky Composer byva v jednom z techto umisteni:

```text
C:\ProgramData\ComposerSetup\bin
C:\Users\<TVE_JMENO>\AppData\Roaming\Composer\vendor\bin
```

3. Zavri a znovu otevri PowerShell (nebo restart PC).

## 3. Instalace Node.js (pro Vite / frontend build)

1. Nainstaluj Node.js LTS (doporuceno 20+).
2. Otevri novy PowerShell a over:

```powershell
node -v
npm -v
```

## 4. Instalace PostgreSQL

1. Nainstaluj PostgreSQL (napr. 15/16/17).
2. Zapamatuj si heslo pro uzivatele `postgres`.
3. Over dostupnost `psql`:

```powershell
psql --version
```

Pokud `psql` nefunguje, pridej do PATH slozku `bin` z PostgreSQL, napr.:

```text
C:\Program Files\PostgreSQL\16\bin
```

## 5. Vytvoreni DB pro tento projekt

Spust:

```powershell
psql -U postgres -h localhost
```

V `psql`:

```sql
CREATE DATABASE call_crm;
\q
```

Pokud chces pouzit jine jmeno DB/uzivatele/heslo, uprav to pozdeji v `.env`.

## 6. Instalace projektu (Call CRM)

V rootu projektu (tato slozka):

```powershell
composer install
```

Pokud selze kvuli `ext-pgsql` nebo `ext-zip`, vrat se do kroku 1 a zapni rozsireni v `php.ini`.

## 7. Konfigurace `.env`

Zkopiruj `.env.example` na `.env`:

```powershell
Copy-Item .env.example .env
```

Vygeneruj app key:

```powershell
php artisan key:generate
```

Zkontroluj DB nastaveni v `.env` (vychozi hodnoty jsou pripraveny):

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=call_crm
DB_USERNAME=postgres
DB_PASSWORD=postgres
```

Pokud mas jine heslo pro `postgres`, zmen `DB_PASSWORD`.

## 8. Instalace Laravel Breeze (Blade auth)

Tento projekt uz ma `laravel/breeze` v `composer.json` (`require-dev`), ale scaffolding je potreba vygenerovat:

```powershell
php artisan breeze:install blade
```

Poznamka: pokud prikaz neexistuje, nejdriv zkus:

```powershell
composer install
composer dump-autoload
```

## 9. Migrace databaze

Spust vsechny migrace (Laravel core + Call CRM MVP):

```powershell
php artisan migrate
```

Volitelne seed (pokud pozdeji doplnis seedery):

```powershell
php artisan db:seed
```

## 10. Frontend assets (Vite)

```powershell
npm install
npm run dev
```

Pro produkcni build:

```powershell
npm run build
```

## 11. Spusteni aplikace

V dalsim PowerShell okne:

```powershell
php artisan serve
```

Aplikace pobezi na:

```text
http://127.0.0.1:8000
```

## 12. Rychly smoke test

Po spusteni over:

1. Otevre se landing page `Call CRM`.
2. Registrace / login funguje (po Breeze instalaci).
3. Po prihlaseni funguje `dashboard`.
4. Stranky modulu:
   - `/companies`
   - `/calls`
   - `/follow-ups`
   - `/lead-transfers`
   - `/meetings`

## Nejcastejsi problemy a reseni

### `php : The term 'php' is not recognized`

- PHP neni v PATH
- restartuj PowerShell / PC

### `composer : The term 'composer' is not recognized`

- Composer neni v PATH
- znovu otevri PowerShell
- doinstaluj Composer a zkontroluj krok 2

### `could not find driver (Connection: pgsql)`

- chybi `pdo_pgsql` / `pgsql` extension v `php.ini`
- po zmene `php.ini` restartuj terminal

### `Vite manifest not found`

- nespustil jsi `npm install` a `npm run dev` nebo `npm run build`

### `Class ... not found` po zmenach

```powershell
composer dump-autoload
```

## Doporuceny dalsi krok po setupu

1. Dokoncit CRUD formulare pro firmy a hovory.
2. Dodelat CRUD pro follow-upy, predani leadu a schuzky.
3. Dopsat seed data a feature testy.
