# Projectplan — Deskchat live

Projectnaam: Deskchat live  
Opdrachtgever: Sven Imholz  
Auteur: Senne Visser  
Datum: 2025-09-15  
Versie: 4.0

---

## Inleiding
Dit projectplan beschrijft het waarom, wat en hoe van Deskchat live: een lichte, sociale achtergrond voor het bureaublad die korte openbare berichten toont. Het document leidt de uitvoering (planning, scope, kwaliteitsbewaking) en biedt de opdrachtgever duidelijkheid over verwachtingen, mijlpalen en randvoorwaarden. We hanteren een iteratieve aanpak met een klein, toetsbaar MVP, gevolgd door korte verbetercycli.

---

## Projectomschrijving

### Aanleiding
Mensen werken lang achter een computer en missen informele, lichte sociale interactie. Bestaande chatapps onderbreken focus en vragen actieve deelname. Deskchat live biedt een passieve, niet-storende “ambient” stroom van korte berichten op de achtergrond.

### Doelen
- Productdoelen
  - Een werkende eerste versie (MVP) waarin gebruikers nieuwe berichten op de achtergrond zien en zelf korte berichten kunnen plaatsen met een bijnaam.
  - Duidelijke feedback bij beperkingen (lengte, frequentie, ongepast taalgebruik).
  - Lichtgewicht ervaring: lage systeemimpact, geen afleidende notificaties.
- Projectdoelen
  - Heldere oplevering met korte handleiding en privacy-notitie in begrijpelijke taal.
  - Basisarchitectuur die uitbreidbaar is zonder herbouw (bv. moderatie later mogelijk).

### Resultaat
- Opleveringen
  - Een live werkende versie van Deskchat live:
    - Publieke read-only weergave op de achtergrond van recente berichten.
    - Eenvoudige tray-/client-invoer voor het plaatsen van korte tekstberichten (1–280 tekens) met bijnaam.
    - Basisregels: lengtebeperking, throttling tegen spam, woordfilter tegen grove taal.
  - Documentatie: korte gebruikershandleiding en privacy-notitie (AVG-proof, beknopt).
  - Koppeling met test- en monitoring-hulpmiddelen (healthcheck, logging).
- Acceptatiecriteria (samengevat)
  - Nieuwe berichten verschijnen binnen 2–5 s na plaatsing in de achtergrondweergave.
  - 99% van de geldige post-acties resulteert binnen 500 ms in een bevestiging (API) onder normale belasting.
  - Ongeldige input geeft een duidelijke foutmelding met oorzaak (te lang, te vaak, ongepast).
  - Er worden geen accounts of persistente persoonlijke gegevens opgeslagen; IP’s worden geanonimiseerd conform privacy-notitie.

### Afbakening
- In scope
  - Eén openbare chatstroom; tekstberichten 1–280 tekens; bijnaam; basisfilters; eenvoudige rate limiting.
  - Web-achtergrond (wallpaper) die berichten toont; simpele invoer via client/tray.
- Buiten scope (nu)
  - Accounts/login, rechten/rollen, mediabestanden, geavanceerde moderatie of dashboards, encryptie end-to-end, meertalige UI. Deze kunnen later worden toegevoegd.

### Risico’s
- Misbruik/spam: rate limiting, woordfilter, simpele heuristieken; logging voor misbruikdetectie.
- Afleiding/te druk: rustige typografie/animatie, beperkte frequentie, korte berichten.
- Privacy-onzekerheid: minimale dataverwerking, IP-anonimisering, duidelijke privacy-notitie.
- Performance/kosten: lightweight backend, eenvoudige caching; limieten op berichtgrootte en retentie.
- Geen mac of linux support. Mischien later maar niet in deze scope

### Effecten
- Positief: lichte sociale verbondenheid zonder actieve chat; lage instap, geen accounts.
- Negatief/te mitigeren: mogelijke afleiding; mitigatie via rustige weergave en throttling.
- Organisatorisch: geen beheerlast voor accounts; wel basismonitoring/logging nodig.

### Randvoorwaarden (PvE samengevat)
- Functioneel
  - Berichten aanmaken, valideren (lengte, content, frequentie), en publiceren op de feed.
  - Achtergrondweergave toont recente berichten in chronologische volgorde; auto-refresh.
  - Foutafhandeling met duidelijke, korte meldingen in NL/EN (minimaal NL).
- Niet-functioneel
  - Privacy/AVG: geen accounts; IP’s gehashed/afgekapt; retentie-instellingen gedocumenteerd.
  - Prestatie: P95 API latency < 500 ms bij normaal gebruik; UI update binnen .5 tot 10 seconden, is aan te passen.
  - Beschikbaarheid: doel 99% tijdens pilot; geen harde 24/7-SLA.
  - Beveiliging: invoerfiltering, basisrate limiting, geen gevoelige PII-opslag.
- Technisch
  - Huidige stack uit dit repo (Laravel API, eenvoudige frontend); geen extra vendor lock-in.
  - Simpele deployment op goedkope hosting/VPS; logging beschikbaar.

---

## Fasering en planning

**Doel:** op **vrijdag 19 september 2025 om 23:29** staat de demo online en is de documentatie klaar.

### Overzicht per fase (in gewone taal)

**Fase 1 — Basis neerzetten (9–10 sep)**  
dingen opzetten: database, git repo, laravel project, tray app project allemaal opzetten. beginnen met laravel api die kan berichten ontvangen en teruggeven, scheldwoorden blokkeren, te snel posten afremmen en oude berichten automatisch opruimen.  
**Resultaat:** werkende basis-API.

**Fase 2 — Chatprogramma bouwen (11–12 sep)**  
Tray-app maken. Je kunt hiermee een bericht typen en versturen, foutmeldingen zijn duidelijk, en het programma onthoudt jouw bijnaam/apparaat.  
**Resultaat:** eerste versie van de tray-app (verzenden/ontvangen).

**Fase 3 — Achtergrond instellen (13 sep)**  
Achtergrond (Wallpaper Engine) die elke paar seconden nieuwe berichten ophaalt, maximaal 100 berichten laat zien en pauzeert als het scherm vergrendeld is.  
**Resultaat:** werkende achtergrond die berichten toont.

**Fase 4 — Alles koppelen en testen (14–15 sep)**  
Testen van de hele systeem: bericht sturen → zien verschijnen. We testen ook foutgevallen en schrijven de eerste versie van de privacy-tekst.  
**Resultaat:** end-to-end getest; concept privacy-tekst.

**Fase 5 — Online zetten en bijschaven (16 sep)**  
Ik zet alles online op de hosting, zorgen voor beveiligde verbinding (https), zetten de automatische taak aan (voor opruimen) en controleren of de “gezondheidspagina” werkt. We verwerken eerste feedback en maken de teksten netter.  
**Resultaat:** live omgeving met kleine verbeteringen.

**Fase 6 — Bufferdag (17 sep)**  
Oplossen van eventuele foutjes op en maken kleine verbeteringen.  
**Resultaat:** stabiele versie voor documenteren.

**Fase 7 — Documentatie afronden (18 sep)**  
Schrijven van een korte handleiding en de README.
**Resultaat:** documentatie klaar voor oplevering.

**Fase 8 — Oplevering (19 sep, vóór 23:29)**  
Een korte demo en leveren alles op (code, handleiding, privacy-notitie).  
**Resultaat:** project opgeleverd.

### Mijlpalen en beslismomenten
- **15 sep:** Go/No-Go om online te zetten.
- **16 sep:** Go/No-Go voor publieke test.
- **19 sep:** Eindoplevering en demo vóór **23:29**.

---

## Projectbeheersing

### Tijd
- Iteraties van 1 week; dagelijkse korte voortgangscheck; burndown per fase.
- Wijzigingen via light change control: impact op scope/tijd vooraf inschatten en vastleggen.

### Geld
- Doel: minimale kosten (low-cost hosting, geen betaalde third-party services voor MVP).
- Kostenposten: hosting/VPS, domein, basismonitoring/logging. Uitgaven worden per fase geëvalueerd.

### Kwaliteit
- Definition of Done: tests groen, lint schoon, performance- en privacy-checks uitgevoerd, documentatie bijgewerkt.
- Testniveaus: unit + feature tests; rooktest van API en wallpaper; pilotfeedback.
- Referentie: docs/TestPlan.md en docs/TechOntw.md voor test- en technische details.

### Informatie
- Communicatie: korte wekelijkse update (voortgang, risico’s, besluiten).
- Documentatie: dit projectplan, FunctOntw (functioneel ontwerp), TechOntw (technisch ontwerp), TestPlan, privacy-notitie.
- Logging/monitoring: basis logboeken en healthchecks; incidentenregistratie in issue tracker.

### Organisatie
- Rollen
  - Opdrachtgever/PO: Senne Visser (prioritering, acceptatie).
  - Ontwikkeling: Senne Visser.
  - Pilotgebruikers: geselecteerde kleine groep voor feedback.
- Besluitvorming: PO beslist over scopewijzigingen; wijzigingen worden gelogd met impact op planning.

---

## 6. Planning (tot vrijdag 19 september 2025, 23:29)
- 9–10 sep: Backend MVP afronden.
- 11–12 sep: Tray-app MVP.
- 13 sep: Wallpaper.
- 14-15 sep: Integratie en E2E tests.
- 16 sep: Hosting/deploy.
- 17 sep: bufferdag (bugfixes, kleine verbeteringen).
- 18 sep: Documentatie.
- 19 sep: Buffer & oplevering (demo, korte handleiding) vóór 23:29.
