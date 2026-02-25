# TODO MVP - Call CRM

Pracovni plan pro prvni verzi interni webove aplikace `Call CRM MVP`.

## Aktualni stav (prubezny zapis)

### Hotovo (aktualni stav kodu)

- CRM MVP moduly: firmy, hovory, follow-upy, predani leadu, schuzky (list/detail/form workflow)
- CRM dashboard s prokliky do filtru a prioritami follow-upu
- Multi-user workflow:
  - role `manager` / `caller`
  - `Moje firmy`, `Moje follow-upy`, fronta uzivatele
  - admin prepinac dashboard pohledu na jineho uzivatele
- Call workflow:
  - `Zahajit hovor` z detailu firmy i ze seznamu firem
  - dokonceni hovoru s navazujicimi akcemi
  - `Ukoncit a dalsi firma`
  - `Odlozit + dalsi firma`
  - guard proti preskakovani firmy ve stavu `new`
- Inline rychle zmeny stavu/vysledku v seznamech:
  - firmy, hovory, follow-upy, predani leadu, schuzky
  - potvrzovaci tlacitko `OK` se zobrazi az po zmene hodnoty
- UI/UX zlepseni:
  - sticky horni menu
  - row click do detailu
  - toast notifikace vlevo dole
  - datumova pole otevrou picker klikem do celeho pole
  - timeline firmy: zvyraznena posledni aktivita + rozbaleni delsi poznamky
- Admin sprava uzivatelu (samostatne menu `Uzivatele`):
  - pridat / upravit / smazat
  - verejna registrace vypnuta
- Cile obvolani na uzivateli (`pocet firem` + `termin`) a zobrazeni v dashboardu / firmach
- Demo seedery (vcetne dat pro `petr.zvelebil@awebsys.cz`)

### Co melo nasledovat (dalsi rozumne kroky)

- Doladit inline edit i pro dalsi pole (napr. prirazeny uzivatel u follow-upu/firem)
- Dodelat / zjemnit ceske popisky v inline selectech (misto internich enum hodnot)
- Pridat rychle outcome akce pro call queue (napr. `Nezastizen + dalsi firma`)
- Dopsat testy pro quick actions / queue workflow / admin user management
- Uklidit backup `routes/auth.php.bak` (lokalni pomocny soubor, necommitovat)

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
- Omezit scope MVP: implementovat az po stabilnim CRUD

## Poznamky

- Priorita MVP: nejdriv funkcni firmy + hovory + follow-up.
- Breeze auth a zakladni dashboard jsou pripraveny / planned v zakladu projektu.
- Bez velkych refaktoru, dokud nebude potvrzeny workflow obchodniho tymu.
