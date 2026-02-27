# SECURITY - Call CRM MVP

Tento dokument je technicky prehled bezpecnostnich uprav provedenych v ramci aktualniho hardeningu.

## Datum

- 2026-02-27

## Kriticke opravy (provedeno)

### 1) IDOR / neopravneny pristup k cizim zaznamum

Problem:
- Caller mohl otevrit nebo upravit cizi zaznamy pres prime URL (`/calls/{id}`, `/follow-ups/{id}`, `/meetings/{id}`, `/lead-transfers/{id}`, `/companies/{id}`).

Oprava:
- Doplneny guard metody `ensureCanAccess...` a pouziti v `show/edit/update/finish/quick-status` akcich.
- Indexy aktivit pro caller role omezeny jen na zaznamy, kde je uzivatel zapojeny.

Soubory:
- `app/Http/Controllers/CallController.php`
- `app/Http/Controllers/CompanyController.php`
- `app/Http/Controllers/FollowUpController.php`
- `app/Http/Controllers/MeetingController.php`
- `app/Http/Controllers/LeadTransferController.php`

### 2) State-changing GET endpointy (CSRF riziko)

Problem:
- Akce menici data byly dostupne pres `GET` (`quick-defer`, `start-call`).

Oprava:
- Route prevedeny na `POST`.
- UI odkazy nahradene formulary s `@csrf`.
- Swipe defer v caller mode preveden na `POST fetch` s CSRF tokenem.

Soubory:
- `routes/web.php`
- `resources/views/crm/companies/show.blade.php`
- `resources/views/crm/companies/queue-mine.blade.php`
- `resources/views/crm/caller-mode/index.blade.php`

### 3) XLSX import hardening (untrusted file input)

Problem:
- Nedostatecne limity pro XML obsah XLSX (potencial memory/zip-bomb scenare).
- Chybela role kontrola pro import endpoint.
- Slaby format `preview_token`.

Oprava:
- Import endpointy omezeny na managera.
- Pridane limity: max radku, max sloupcu, max delka bunky, max XML velikost.
- Validace `preview_token` regexem (UUID format).
- `simplexml_load_string(..., LIBXML_NONET)` pro zakaz sitovych entit.
- Osetrena povinna hlavicka `company_name`.
- Throttle na import endpointy.

Soubory:
- `app/Http/Controllers/ImportController.php`
- `routes/web.php`

### 4) Snapshot import hardening

Problem:
- Snapshot import akceptoval libovolne sloupce z JSON.

Oprava:
- Allowlist sloupcu po tabulkach.
- Max limit radku na tabulku.
- Sanitizace importovanych radku pred DB insert.
- Throttle na snapshot import endpoint.

Soubory:
- `app/Http/Controllers/DataTransferController.php`
- `routes/web.php`

## Doplneni dokumentace / backlog

- Odlozene zlepseni XLSX parseru je zapsane v `TODO_MVP.md` (sekce „XLSX import v2“).

## Stav zavislosti (audit)

- `composer audit`: bez nalezenych advisories.
- `npm audit --omit=dev`: bez nalezenych vulnerabilities.

## Doporučené dalsi kroky (po MVP stabilizaci)

- Pridat centralni Laravel Policies misto kontrol v controllerech.
- Pridat feature testy na autorizaci (hlavne IDOR scenare).
- Zapnout prod security headers (CSP/HSTS/X-Frame-Options) na web server vrstve.
- Nastavit centralizovany audit log pro citlive akce (importy, bulk akce, role zmeny).

## Externi reference

- Laravel Authorization: https://laravel.com/docs/12.x/authorization
- Laravel CSRF Protection: https://laravel.com/docs/12.x/csrf
- Laravel Rate Limiting: https://laravel.com/docs/12.x/rate-limiting
- OWASP File Upload Cheat Sheet: https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html
- OWASP CSRF Cheat Sheet: https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html
- OWASP Laravel Cheat Sheet: https://cheatsheetseries.owasp.org/cheatsheets/Laravel_Cheat_Sheet.html
- PHP `ZipArchive::statName`: https://www.php.net/manual/en/ziparchive.statname.php
- PHP `simplexml_load_string`: https://www.php.net/manual/en/function.simplexml-load-string.php
