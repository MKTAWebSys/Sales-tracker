# Handoff - pokracovani doma

## Branch a stav

- Aktivni branch: `main`
- Lokalni zmeny jsou commitnute v tomto kroku (viz posledni commit).

## Co je hotove (posledni iterace)

- Zuzene leve sidebar menu (vice mista pro obsah).
- Odebran claim pod `Call CRM` v levem menu.
- Detail firmy:
  - odebrano horni tlacitko `Zahajit hovor`,
  - telefony jsou zobrazene jako samostatne klikaci polozky,
  - stejne chovani i u kontaktnich osob.
- Kontakty firmy:
  - samostatna entita `company_contacts`,
  - pridani/smazani kontaktu v detailu firmy.
- Import XLSX:
  - aliasy sloupcu + rucni mapovani,
  - podpora telefonu + mobilu (slouceni do telefonu firmy),
  - import omezeni poctu radku pro testovaci import.
- Snapshot export/import dat:
  - zahrnuje i `company_contacts`.
- Security hardening je popsany v `SECURITY.md`.

## Jak to doma spustit (BE + FE)

1. Backend:
```powershell
php -S 127.0.0.1:9000 -t public
```

2. Frontend (druhe okno):
```powershell
npm run dev
```

3. Otevrit:
- `http://127.0.0.1:9000`

## DB - cisty start / migrace / seed

- Jen migrace:
```powershell
php artisan migrate
```

- Cisty reset (smaze vsechna data):
```powershell
php artisan migrate:fresh
```

- Demo data:
```powershell
php artisan db:seed --class=DemoCrmSeeder
php artisan db:seed --class=DemoPetrZvelebilSeeder
```

## Prihlaseni (demo)

- `petr.zvelebil@awebsys.cz` / `password`

## Co je dalsi krok

1. Doresit UX detailu firmy do kompaktniho layoutu i pro mensi monitor.
2. Dotahnout import flow pro velke davky (napr. 10k firem) vcetne validacniho reportu.
3. Pripravit navaznou agendu "obchodni pripady" (dealy, nabidky, fakturace) - zatim mimo MVP.

