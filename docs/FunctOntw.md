# Functioneel Ontwerp — deskchat live

**Project:** deskchat live  
**Versie:** 2.1  
**Datum:** 2025-09-09  
**Auteur:** Senne Visser  
**Website:** https://deskchat.live

---

## Inhoudsopgave
1. Voorwoord
2. Samenvatting
3. Huidige situatie (Hoe communiceren systemen)  
   3.1 Wat er nu is  
   3.2 Wat er ontbreekt
4. Gewenste situatie (Hoe moeten systemen communiceren)  
   4.1 Wat het systeem moet kunnen (MoSCoW)  
   4.2 Hoe gebruikers het systeem gebruiken  
   4.3 Wat voor onderdelen er komen
5. Gevolgen en impact  
   5.1 Voor gebruikers  
   5.2 Voor beheer  
   5.3 Kosten  
   5.4 Planning (tot vrijdag 19 september 2025, 23:29)
6. Acceptatiecriteria

---

## 1. Voorwoord
Dit document beschrijft **wat** deskchat live doet en waarom. Het systeem bestaat uit een bewegende achtergrond op de computer die live chatberichten toont, en een klein programma waarmee je zelf berichten kunt versturen. Het is bedoeld voor mensen die graag een sociale, interactieve achtergrond willen tijdens het werken.

---

## 2. Samenvatting
deskchat live is een chatprogramma dat bestaat uit twee delen:

**De achtergrond**: Een bewegende achtergrond voor je computer (gemaakt met Wallpaper Engine) waar je live berichten van andere gebruikers ziet verschijnen. Je kunt hier alleen kijken, niet typen.

**Het chatprogramma**: Een klein programma dat onzichtbaar op de achtergrond draait. Via een icoon onderin je scherm kun je chatberichten typen en versturen.

**Gebruiksgemak**: Geen ingewikkelde accounts aanmaken - je kiest gewoon een bijnaam en kunt meteen chatten. Berichten zijn openbaar en iedereen kan meepraten in één grote chatruimte.

**Veiligheid**: Het systeem houdt geen persoonlijke gegevens bij en beschermt tegen spam door limieten in te stellen op hoeveel berichten je kunt versturen.

---

## 3. Huidige situatie (Hoe communiceren systemen)
### 3.1 Wat er nu is
- Er bestaan websites (deskchat.live) maar er draait nog geen chatsysteem
- Mensen kunnen Wallpaper Engine installeren voor bewegende achtergronden
- Er is webhosting beschikbaar om het systeem te laten draaien

### 3.2 Wat er ontbreekt
- Een manier om live berichten te delen tussen gebruikers
- Een programma waarmee mensen berichten kunnen typen
- Een systeem dat berichten opslaat en doorgeeft
- Bescherming tegen spam en ongepast taalgebruik

---

## 4. Gewenste situatie (Hoe moeten systemen communiceren)
### 4.1 Wat het systeem moet kunnen (MoSCoW)

**Moet hebben (anders werkt het niet)**
- Achtergrond toont nieuwe berichten van andere gebruikers
- Gebruikers kunnen berichten typen en versturen via een klein programma
- Berichten mogen niet te lang zijn (maximaal 280 tekens, zoals Twitter)
- Gebruikers kunnen een bijnaam kiezen (geen echte naam vereist)
- Systeem blokkeert scheldwoorden en ongepaste taal
- Bescherming tegen spam (niet te veel berichten per persoon)
- Berichten worden automatisch verwijderd na een tijdje (maximaal 90 dagen)

**Zou fijn zijn**
- Berichten laden snel en de achtergrond gebruikt weinig computer-kracht
- Duidelijke foutmeldingen als er iets mis gaat
- Het programma stopt met werken als je computer vergrendeld is (bespaart internetverkeer)
- Informatie over privacy op de website

**Zou leuk zijn (misschien later)**
- **Meerdere chatruimtes**: bijvoorbeeld apart voor verschillende onderwerpen
- **Plaatjes delen**: kleine animaties (GIFs) kunnen delen via links
- Eenvoudige smileys in berichten

**Komt er niet (te ingewikkeld voor nu)**
- Inloggen met wachtwoorden
- Profielpagina's van gebruikers
- Bestanden uploaden
- Berichten die alleen jij kunt lezen
- Moderatoren die berichten kunnen verwijderen

---

### 4.2 Hoe gebruikers het systeem gebruiken

**Een gewone gebruiker wil chatten:**
1. Installeert Wallpaper Engine en zet de deskchat live achtergrond aan
2. Downloadt het kleine chatprogramma en start het op
3. Kiest een bijnaam (bijvoorbeeld "Alex" of "ChatLover")
4. Typt een bericht en drukt op verzenden
5. Ziet het bericht verschijnen op de achtergrond, samen met berichten van anderen
6. Kan altijd nieuwe berichten typen via het icoon onderin het scherm

**Wat er gebeurt als er problemen zijn:**
- Te lang bericht → gebruiker krijgt melding "Bericht te lang, maximaal 280 tekens"
- Scheldwoord gebruikt → gebruiker krijgt melding "Bericht bevat ongepaste taal"
- Te veel berichten gestuurd → gebruiker krijgt melding "Wacht even voordat je weer een bericht stuurt"
- Geen internetverbinding → programma probeert het later opnieuw

**Wat gebruikers zien op hun achtergrond:**
- Nieuwe berichten verschijnen automatisch (iedere paar seconden wordt er gekeken)
- Oude berichten verdwijnen van het scherm (alleen de laatste 100 berichten blijven zichtbaar)
- Als de computer vergrendeld is, stopt de achtergrond met laden van nieuwe berichten

---

### 4.3 Wat voor onderdelen er komen

**De bewegende achtergrond:**
- Toont alleen berichten (je kunt er niet in typen)
- Ververst automatisch elke paar seconden
- Toont maximaal 100 berichten tegelijk
- Werkt alleen als je computer niet vergrendeld is

**Het chatprogramma:**
- Klein programma dat onzichtbaar op de achtergrond draait
- Icoon onderin je scherm (bij de klok)
- Simpel venster om berichten te typen
- Toont foutmeldingen als er iets mis gaat
- Onthoud je bijnaam zodat je die niet steeds opnieuw hoeft in te voeren

**Het systeem achter de schermen:**
- Ontvangt berichten van gebruikers
- Controleert of berichten niet te lang zijn
- Blokkeert scheldwoorden
- Zorgt dat gebruikers niet te veel berichten versturen
- Slaat berichten op en deelt ze met alle gebruikers
- Verwijdert oude berichten automatisch

---

## 5. Gevolgen en impact
### 5.1 Voor gebruikers
- **Voordelen:** Leuke, interactieve achtergrond tijdens het werken; geen ingewikkelde accounts
- **Nadelen:** Alleen Windows computers; vereist Wallpaper Engine (kost een paar euro)
- **Privacy:** Geen persoonlijke gegevens opgeslagen; berichten zijn openbaar voor iedereen
- **Gebruik:** Geschikt voor mensen die van sociale interactie houden tijdens werken

### 5.2 Voor beheer
- **Onderhoud:** Minimaal; systeem draait grotendeels automatisch
- **Moderatie:** Automatische filter voor scheldwoorden; handmatige moderatie niet vereist
- **Kosten:** Gebruikt bestaande webhosting; geen extra kosten
- **Schaalbaarheid:** Kan groeien naar meer gebruikers zonder grote aanpassingen

### 5.3 Kosten
- **Gebruikers:** Wallpaper Engine (eenmalig ~€4), verder gratis
- **Ontwikkeling:** Geen extra kosten (gebruikt bestaande hosting)
- **Onderhoud:** Minimale tijd voor monitoring en updates en hostingkosten voor api en welcome page (~€ 4 per maand)

### 5.4 Planning (tot vrijdag 19 september 2025, 23:29)
- 9–10 sep: Backend MVP afronden (berichten GET/POST, validatie, woordfilter, throttling, retentie).
- 11–12 sep: Tray-app MVP (verzenden/ontvangen, foutmeldingen, device-id, tray-icoon).
- 13 sep: Wallpaper (polling, DOM-cap 100, pauze bij vergrendeling/hidden).
- 14-15 sep: Integratie en E2E tests (happy path + fouten, privacytekst 1e versie).
- 16 sep: Hosting/deploy, CORS check, scheduler, healthcheck, Feedbackronde, finetuning UX/teksten.
- 17 sep: bufferdag (bugfixes, kleine verbeteringen).
- 18 sep: Documentatie (korte handleiding, readme, privacy-notitie).
- 19 sep: Buffer & oplevering (demo, korte handleiding, definitieve privacy-notitie) vóór 23:29.

---

## 6. Acceptatiecriteria
- Wallpaper toont nieuwe berichten binnen ~5 seconden; maximaal ~100 zichtbaar.
- Tray-app kan bericht (1–280 tekens) met bijnaam versturen; duidelijke foutmelding bij: te lang, woordfilter, rate limit, ontbrekende device-id.
- API responses conform: 201 bij succes; 400 missing_device_id; 422 validation_failed/profanity_blocked; 429 rate_limited met retry_after.
- Geen accounts; geen persoonsgegevens opgeslagen; alleen anonieme device-id en ip_hmac (hash van IP) voor misbruikpreventie.
- Berichten ouder dan max 90 dagen worden automatisch verwijderd.
- Privacy-notitie in gewone taal is beschikbaar op de site.
