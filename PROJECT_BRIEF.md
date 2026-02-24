# Call CRM MVP - Project Brief

Interni webova aplikace pro evidenci obchodnich callu a navazujicich aktivit.

## Cil MVP

Vytvorit jednoduchy interni CRM pro obchodni tym, ktery umozni:

- evidenci firem (lead/prospekt/zakaznik)
- zapis historie hovoru
- evidenci toho, kdo volal
- planovani follow-upu (kdy volat znovu)
- predani leadu mezi obchodniky
- evidenci schuzky / obchodniho jednani (zaklad)
- pozdeji import dat z XLSX

## Primarni uzivatele

- obchodnik
- team leader / sales manager

## MVP moduly

### 1. Firmy

- nazev firmy
- ICO (volitelne)
- web (volitelne)
- status (napr. `new`, `contacted`, `follow-up`, `qualified`, `lost`)
- poznamky
- prirazeny obchodnik

### 2. Hovory

- firma
- datum a cas hovoru
- kdo volal (uzivatel)
- vysledek hovoru
- shrnuti
- dalsi follow-up (volitelne)
- planovana schuzka (volitelne)

### 3. Follow-upy

- vazba na firmu / hovor
- termin follow-upu
- assigned obchodnik
- status (`open`, `done`, `cancelled`)
- poznamka

### 4. Predani leadu

- vazba na firmu / hovor
- kdo predava
- komu predava
- datum predani
- status
- poznamka

### 5. Schuzky / obchod

- vazba na firmu / hovor
- termin schuzky
- forma (`onsite`, `online`, `phone`)
- status (`planned`, `confirmed`, `done`, `cancelled`)
- poznamka

## MVP workflow (zjednoduseny)

1. Obchodnik zalozi firmu.
2. Zapise hovor a vysledek.
3. Pokud je potreba, vytvori follow-up.
4. Pokud lead patri jinemu cloveku, vytvori predani leadu.
5. Pokud je zajem, zalozi schuzku.

## Scope MVP (in)

- autentizace uzivatelu (Laravel Breeze)
- zakladni CRUD pro moduly
- dashboard s jednoduchymi metrikami
- PostgreSQL databaze
- Blade UI (bez SPA)

## Scope MVP (out / pozdeji)

- import z XLSX
- automaticke notifikace (e-mail/Slack)
- pokrocile reporty a funnel statistiky
- integrace telefonu / VoIP
- role & permission system nad ramec zakladni auth

## Doporuceny stack

- Laravel (PHP)
- Blade
- Laravel Breeze (auth)
- PostgreSQL
- Vite + npm

## Poznamky k implementaci

- Projekt je pripraven jako Laravel skeleton se strukturou modulu.
- Po instalaci prostredi na Windows postupuj podle `SETUP_WINDOWS.md`.
