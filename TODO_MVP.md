# TODO MVP - Call CRM

Pracovni plan pro prvni verzi interni webove aplikace `Call CRM MVP`.

## Aktualni stav (prubezny zapis)

### Hotovo (aktualni stav kodu)

- Navigace / layout:
  - leve menu zuzeno pro vice prostoru v seznamovych strankach
  - odstranen subtitle/claim pod `Call CRM`
- CRM MVP moduly: firmy, hovory, follow-upy, predani leadu, schuzky (list/detail/form workflow)
- CRM dashboard s prokliky do filtru a prioritami follow-upu
- Multi-user workflow:
  - role `manager` / `caller`
  - `Moje firmy`, `Moje follow-upy`, fronta uzivatele
  - admin prepinac dashboard pohledu na jineho uzivatele
- Samostatna stranka `Moje fronta` (new firmy k obvolani) + FIFO razeni
- `Caller mode` (mobilni workflow MVP):
  - moje dalsi firma
  - volat (`tel:`)
  - aktivni hovor / ukonceni hovoru
  - swipe vlevo/vpravo pro `dalsi` / `odlozit`
- Call workflow:
  - `Zahajit hovor` z detailu firmy i ze seznamu firem
  - jen 1 aktivni hovor na uzivatele (guard)
  - floating panel aktivniho hovoru (timer + quick note + navrat/ukonceni)
  - quick note behem hovoru (AJAX append s timestampem)
  - dvoufaze: aktivni hovor (poznamka) -> ukonceni -> vyber vysledku
  - ukonceni hovoru pres `POST` endpoint (`calls.end`) + `ended_at`
  - dokonceni hovoru s navazujicimi akcemi
  - `Ulozit a dalsi` (queue workflow)
  - `Odlozit + dalsi firma`
  - guard proti preskakovani firmy ve stavu `new`
- Finiš hovoru UX:
  - recap karta `Hovor ukoncen` (firma, od/do, delka)
  - vysledek hovoru jako velka tlacitka/chipy
  - quick callback presety + rychle datum/cas (2 pole) v jednom radku
  - compact accordion sekce (`Follow-up`, `Schuzka`, `Predani leadu`)
  - caller-mode finalize je zjednoduseny (ultra-minimal)
  - stav firmy se meni automaticky podle vysledku hovoru
- Inline rychle zmeny stavu/vysledku v seznamech:
  - firmy, hovory, follow-upy, predani leadu, schuzky
  - potvrzovaci tlacitko `OK` se zobrazi az po zmene hodnoty
- Hromadne akce nad firmami (bulk):
  - prirazeni `first caller` (admin komukoliv, caller sobe)
  - `Vzít si` / `Odebrat z fronty`
  - zmena statusu
  - append poznamky
  - `Vybrat vse new` na strance
- Queue model firem:
  - `first_caller_user_id`
  - `first_caller_assigned_at`
  - `first_contacted_at`
- UI/UX zlepseni:
  - sidebar navigace (seskupene menu)
  - row click do detailu
  - toast notifikace vlevo dole
  - datumova pole otevrou picker klikem do celeho pole
  - timeline firmy: zvyraznena posledni aktivita + rozbaleni delsi poznamky
  - web-safe hapticky/akusticky feedback po ulozeni (toast / AJAX quick note)
- Admin sprava uzivatelu (samostatne menu `Uzivatele`):
  - pridat / upravit / smazat
  - verejna registrace vypnuta
- Cile obvolani na uzivateli (`pocet firem` + `termin`) a zobrazeni ve fronte/firmach
- Dashboard cleanup:
  - odstraneno `Admin nastaveni cile obvolani` z dashboardu
  - prepinac pohledu uzivatele presunut do headeru dashboardu
- `Predani leadu` sekce schovana z hlavni navigace/dashboardu (modul zatim ponechan v kodu)
- Kalendar:
  - `Den / Tyden / Mesic` view
  - denni agenda follow-upu + schuzek
  - overdue filtr
  - barevne odliseni urgence a pretizeni dnu (8+)
  - tydenni souhrn (hotovo / neudelano)
  - klik z mesice -> den view
  - compact header ovladani (bez velkych boxu, auto-refresh filtru)
  - `Mesic` view jako rolling okno 5 tydnu kolem vybraneho dne (`-2/+2`)
  - opraveny filtr uzivatele (ma prioritu pred `Jen moje agenda`)
- Sjednoceni flow firmy a aktivit:
  - validace vysledku hovoru (callback/no-answer vyzaduje follow-up, meeting-booked vyzaduje schuzku, interested vyzaduje dalsi krok)
  - `Hotovo` u follow-upu nově umi zaroven dorovnat stav firmy (vychozi: `contacted`)
  - pri predani v hovoru se prepina aktivni resitel firmy (`first_caller_user_id`)
  - follow-up je veden jako pracovni krok (task), ne jako hlavni koncovy stav
- Opraveno tlacitko `Ukoncit hovor` v detailu aktivniho hovoru (samostatny end form, bez vnoreni formulare)
- Demo seedery (vcetne dat pro `petr.zvelebil@awebsys.cz`)

### Co melo nasledovat (dalsi rozumne kroky)

- Presunout spravu `cilu obvolani` pouze do `Sprava > Uzivatele` UI (pokud tam chybi edit pole/sekce)
- Doladit inline edit i pro dalsi pole (napr. prirazeny uzivatel u follow-upu/firem)
- Dodelat / zjemnit ceske popisky v inline selectech (misto internich enum hodnot)
- Rychle outcome akce pro call queue (napr. `Nezastizen + dalsi firma` primo z caller mode)
- Dotahnout mobile caller finalize jako 2-krok wizard (optional)
- Dopsat testy pro quick actions / queue workflow / single active call / call finalize flow
- Outlook/Google sync priprava:
  - external event IDs/sloupce pro `follow_ups` a `meetings`
  - `.ics` export den/tyden (viz bod 5)
- Kalendar UX:
  - doladit finalni layout headeru (spacing / zarovnani) podle realneho pouziti na malem monitoru
- Dotahnout follow-up resolution UI:
  - v quick akcich doplnit explicitni volbu "co dal s firmou" (kontaktovano/follow-up/schuzka/bez zajmu)
  - pripadne udelat mini modal misto skrytych defaultu
- `Predani leadu` modul:
  - overit v realnem workflow, zda je potreba samostatny seznam
  - pokud ne, pozdeji odstranit samostatny modul/sekci a ponechat jen procesni krok v hovoru
- Uklidit lokalni pomocne/backup soubory (pokud se objevi)
- XLSX import v2 (po stabilnim prototypu):
  - podpora vice listu (ne jen `sheet1`)
  - robustni podpora Excel datum/cas serial hodnot
  - importni dry-run report s diffem pred potvrzenim
  - rozsirene testy proti zip-bomb / velkym vstupum
- Detail firmy / kontakty:
  - telefony jsou klikaci jako samostatne hodnoty (ne jeden retezec)
  - odstranen horni quick-call button v detailu firmy
  - company kontakty rozsirene pro prakticke volani na konkretni cisla

## 1) Data model

- Navrhnout entity a vazby:
  - `companies`
  - `calls`
  - `follow_ups`
  - `lead_transfers`
  - `meetings`
- Ujasnit statusy a enum hodnoty (firma, hovor, follow-up, schuzka)
- Doresit vazbu na uzivatele (`users`) - kdo volal / komu predano / assigned
- Pripravit migrace a indexy pro filtrovani
- Pripravit seed testovacich dat (minimalne demo data)

## 2) CRUD firem

- Seznam firem (strankovani)
- Vytvoreni firmy
- Detail firmy
- Editace firmy
- Zakladni validace (nazev povinny, ICO/web volitelne)
- Zobrazeni navazanych hovoru a aktivit na detailu (aspon placeholder)

## 3) Hovory a timeline

- CRUD / zapis hovoru (minimalne create + list + detail + edit)
- Vazba hovoru na firmu a uzivatele
- Pole: datum/cas, vysledek, shrnuti, dalsi krok
- Timeline na detailu firmy:
  - hovory
  - follow-upy
  - predani leadu
  - schuzky
- Zakladni trideni timeline podle data

## 4) Filtry a follow-up

- Filtry ve vypisu firem:
  - status
  - assigned obchodnik
  - text (nazev / ICO)
- Filtry ve vypisu hovoru:
  - datum od/do
  - vysledek
  - obchodnik
- CRUD follow-upu
- Prehled otevrenych follow-upu (dashboard / seznam)
- Oznaceni follow-upu jako hotovy

## 5) Import XLSX (pozdeji)

- Upresnit format vstupniho XLSX (sloupce, mapovani)
- Navrhnout importni workflow:
  - upload souboru
  - validace
  - preview
  - import
- Reseni duplicit (firma podle ICO / nazvu)
- Log importu a chybove radky
- Pozdeji: export `.ics` (den/tyden) jako prvni krok k Outlook/Google integraci kalendare
- Omezit scope MVP: implementovat az po stabilnim CRUD

## Poznamky

- Priorita MVP: nejdriv funkcni firmy + hovory + follow-up.
- Breeze auth a zakladni dashboard jsou pripraveny / planned v zakladu projektu.
- Bez velkych refaktoru, dokud nebude potvrzeny workflow obchodniho tymu.
