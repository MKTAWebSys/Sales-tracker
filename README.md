# Call CRM (MVP skeleton)

Laravel základ pro interní evidenci obchodních callù.

## Co je pøipraveno

- Laravel 12.x skeleton (staenı zdrojovı projekt)
- Blade stránky pro landing, dashboard a placeholder pøehledy modulù
- Route struktura pro MVP moduly (`auth` middleware, kompatibilní s Breeze)
- Eloquent modely a migrace pro:
  - firmy
  - hovory
  - follow-upy
  - pøedání leadù
  - schùzky
- `.env.example` nastavenı na PostgreSQL
- `composer.json` doplnìnı o `laravel/breeze` v `require-dev`

## Dùleitá poznámka

V tomto pracovním prostøedí nebyly dostupné pøíkazy `php` a `composer`, proto nebylo moné skuteènì spustit instalaci závislostí ani `php artisan breeze:install`.

Projekt je pøipraven tak, aby šel po doinstalování toolchainu na Windows dokonèit nìkolika pøíkazy.

## Instalace na Windows (Laravel + Blade + Breeze)

### 1. Nainstaluj prerequisites

- PHP 8.2+ (doporuèeno 8.3/8.4)
- Composer
- Node.js 20+ a npm
- PostgreSQL 15+ (nebo kompatibilní verze)
- Volitelnì Git

Ovìøení v PowerShellu:

```powershell
php -v
composer --version
node -v
npm -v
psql --version
```

### 2. Nainstaluj PHP závislosti

V koøenu projektu:

```powershell
composer install
```

### 3. Vytvoø `.env` a app key

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

### 4. Nastav PostgreSQL pøipojení

Uprav `.env` (vıchozí hodnoty jsou u pøipravené):

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=call_crm
DB_USERNAME=postgres
DB_PASSWORD=postgres
```

V PostgreSQL vytvoø DB:

```sql
CREATE DATABASE call_crm;
```

### 5. Nainstaluj Laravel Breeze (Blade stack)

```powershell
composer require laravel/breeze --dev
php artisan breeze:install blade
```

Poznámka: `routes/web.php` u poèítá s Breeze a bezpeènì naète `routes/auth.php`, a vznikne.

### 6. Spus migrace

```powershell
php artisan migrate
```

### 7. Frontend assets

```powershell
npm install
npm run dev
```

Pro produkèní build:

```powershell
npm run build
```

### 8. Spuštìní aplikace

V novém PowerShell oknì:

```powershell
php artisan serve
```

Pak otevøi `http://127.0.0.1:8000`.

## MVP moduly (struktura)

- `companies` - evidence firem
- `calls` - historie hovorù
- `follow_ups` - follow-up termíny a stav
- `lead_transfers` - pøedání leadu mezi obchodníky
- `meetings` - schùzky / obchodní jednání

Další detaily: `docs/MVP_STRUCTURE.md`.
