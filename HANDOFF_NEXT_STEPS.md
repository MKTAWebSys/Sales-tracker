# Handoff / Pokracovani na druhem PC

## Aktualni branch (dulezite)

- Pokracovat na branche:
  - `codex/company-call-queue-bulk-actions`

## Co je uz pushnute na GitHub

- Vsechny posledni zmeny jsou pushnute na GitHub (`origin/codex/company-call-queue-bulk-actions`)
- Dashboard cleanup (bez admin panelu cile obvolani)
- Call workflow UI/UX (aktivni hovor, finalize recap, outcome chipy)
- Caller mode swipe UX
- Quick callback presety + rychly datum/cas
- Floating active call panel + quick note AJAX
- Kalendar header UX cleanup + auto-refresh filtry
- `Mesic` view = rolling 5 tydnu kolem vybraneho dne

## Dulezite workflow zmeny (shrnutí)

- Aktivni hovor:
  - jen 1 aktivni hovor na uzivatele
  - `pending` + `ended_at = null`
- Ukonceni hovoru:
  - `POST /calls/{call}/end`
  - hovor se zastavi hned (`ended_at`)
  - pak nasleduje finalize formular (`Hovor ukoncen`)
- Finalize formular:
  - recap karta je nahore (firma / od-do / delka)
  - `Vysledek hovoru` = velka tlacitka/chipy
  - callback preset + quick datum/cas
  - navazujici pole v accordion sekcich

## Co otestovat po otevreni na druhem PC

1. `Caller mode`:
   - `Zahajit hovor`
   - quick note ve floating panelu
   - `Ukoncit hovor`
   - finalize s outcome chipy
2. `Zavolat znovu`:
   - preset tlacitka (`Dnes odpoledne`, `Zitra...`)
   - quick datum/cas inputy
3. `Dashboard`:
   - prepinac `Pohled uzivatele` je v headeru
   - admin panel cile obvolani uz neni na dashboardu
4. `Moje fronta` a `Firmy`:
   - bulk akce pro `first caller`
5. `Kalendar`:
   - vyber uzivatele meni i denni agendu
   - `Jen moje agenda` je deaktivovane pri vyberu konkretniho uzivatele
   - mesicni pohled ukazuje 5 tydnu kolem vybraneho dne

## Spusteni lokalne (Windows)

```powershell
php -S 127.0.0.1:9000 -t public
```

- FE dev (pokud je potreba live reload):

```powershell
npm run dev
```

## Demo login (pokud DB se seedery)

- `petr.zvelebil@awebsys.cz` / `password`

## Doporučené dalsi kroky

1. Sloucit branch do `main` a pokracovat uz primo na `main` (pokud je to preferovany workflow)
2. Doladit posledni px v kalendar headeru (spacing / zarovnani) podle monitoru
3. Sprava `cilu obvolani` pouze v `Uzivatele` (UI sekce / edit)
4. Testy pro single-active-call a finalize flow
5. Outlook/Google sync priprava (`external_calendar_*` sloupce + `.ics` export)
