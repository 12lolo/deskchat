# Projectplan — deskchat live (Wallpaper Engine + Windows Tray App + Laravel API)

**Projectnaam:** deskchat live  
**Auteur:** Senne Visser  
**Datum:** 2025-09-04  
**Versie:** 1.3 (Hostinger-deployment + privacy-by-design)  
**Website:** https://deskchat.live  
**API-base:** https://api.deskchat.live

---

## Aanleiding
Ik wil een Windows-achtergrond (Wallpaper Engine, web wallpaper) die live chatberichten toont van **deskchat live**. Invoer gebeurt **niet** via de wallpaper (om focus/keyboard-issues te vermijden), maar via een lichte Windows-trayapp. De backend is een **Laravel**-API met validatie, throttling en een basisprofanityfilter. We draaien op **Hostinger web hosting** (shared) en kiezen voor **geen accounts**: gebruikers stellen alleen een **nickname** in. Misbruikpreventie via **per-IP + per-device** rate limiting (zonder raw IP op te slaan) en **device-id** (UUID).

---

## Doelen
- **Functioneel**
    - Wallpaper (read-only) toont nieuwe berichten via polling naar **`GET https://api.deskchat.live/api/messages?since_id=…`**.
    - Trayapp verstuurt via **`POST https://api.deskchat.live/api/messages`** met header **`X-Device-Id: <UUIDv4>`**.
    - Profanityfilter, lengte-limieten en rate limiting (IP + device).
- **Niet-functioneel (meetbaar)**
    - Wallpaper CPU ≤ **2–3%**, RAM ≤ **150 MB**.
    - API latency **p50 ≤ 150 ms**, **p95 ≤ 350 ms** bij < 50 rps.
    - Dataverkeer wallpaper ≤ **10 kB/s** gemiddeld (poll 2,5 s).
    - Uptime backend **≥ 99%** tijdens testperiode.
- **Privacy-by-design**
    - **Geen raw IP** in DB of app-logs; alleen `ip_hmac = HMAC_SHA256(ip, secret)` en **device_id**.
    - Privacy-notitie op deskchat.live vermeldt dat **Hostinger** serverlogs IP’s kan bevatten (beveiliging).

---

## Resultaat
- **Componenten**
    1. **Laravel API** op **api.deskchat.live**  
       Endpoints: `GET /api/messages`, `POST /api/messages`  
       Model: `messages(id, handle, content, device_id, ip_hmac, created_at)`
    2. **Windows trayapp** (.NET 8 WPF): verzenden/ontvangen, tray-icon, foutafhandeling; stuurt `X-Device-Id`.
    3. **Wallpaper Engine** web-wallpaper (`index.html` één bestand): read-only feed (cap ~100 berichten).
- **Documentatie**
    - README (setup/deploy Hostinger), `.env`-voorbeeld, korte API-spec, FO/TO/Projectplan.
    - Testplan + kort testrapport; release build trayapp.

---

## Afbakening
- **Wel (MVP)**: één publieke room (`global`), nickname, eenvoudige profanityfilter, per-IP + per-device throttling, open `GET` CORS (of beperkt tot `deskchat.live`).
- **Could-have**:
    - **Meerdere chatrooms** (API `room`-parameter; trayapp room-selector; wallpaper per-room/cyclisch).
    - **Lichtgewicht GIF-embeds** via Tenor/gelinkte `.gif` (client-side; geen uploads).
- **Niet (MVP)**: accounts/login, bestands-uploads/media-hosting, WebSockets, E2E-encryptie, moderatie-dashboard, AI-filtering.

---

## Planning (indicatief)
| Fase | Periode (2025) | Activiteiten | Deliverables |
|---|---|---|---|
| 1. Analyse & ontwerp | 4–5 sep | Use-cases, API-contract, ERD, privacy-aanpak | API-schets, ERD, backlog |
| 2. Backend MVP | 6–8 sep | Laravel project, migraties, controllers, throttle/profanity, CORS | Werkende `GET/POST`, DB |
| 3. Trayapp MVP | 9–11 sep | WPF-UI, HttpClient, polling, `X-Device-Id`, tray-icon | Release build |
| 4. Wallpaper | 12 sep | Minimal `index.html`, performance-check, DOM-cap | Werkende wallpaper |
| 5. Integratie & test | 13–15 sep | E2E-test, perf-metingen, logging, fixes | Testrapport |
| 6. Oplevering & demo | 16–17 sep | Demo, README, privacytekst publiceren | MVP + docs |

> Data kunnen schuiven; weekenden optioneel.

---

## Risico’s
| Risico | Impact | Kans | Maatregel |
|---|---|---|---|
| Spam/misbruik | Hoog | Middel | Per-IP + per-device limit; profanity; lengte-limieten; optionele shadow-ban via `ip_hmac` |
| CPU/netwerk overhead | Middel | Middel | Polling met `since_id` + `limit`, DOM-cap 100, pauze bij `document.hidden` |
| Hostinger serverlogs bevatten IP | Middel | Middel | Privacy-notitie; in app/DB géén raw IP; korte retentie |
| CORS/focus issues | Laag | Middel | Wallpaper alleen `GET`; `pointer-events:none`; POST alleen via trayapp |
| DB-groei | Laag | Middel | Retentie (≤ 90 dagen), indexen op `id/created_at/device_id` |
| Latency door shared hosting | Laag | Middel | Gecachte config/routes; compacte payloads; `limit` per call |

---

## Randvoorwaarden
- **Hosting (Hostinger)**
    - Subdomein **`api.deskchat.live`** → document root: `domains/deskchat.live/public_html/_api/public`
    - **SSL/HTTPS** via hPanel voor `deskchat.live` en `api.deskchat.live`.
- **Techniek/stack**
    - PHP 8.2/8.3, **Laravel 11+**, MySQL 8.x, Composer
    - **Trayapp:** .NET 8 (C#) WPF
    - **Wallpaper:** HTML + JavaScript (zonder bundler)
    - Rate limiting via **database cache** (geen Redis nodig)
- **Configuratie**
    - `.env`: `APP_URL=https://api.deskchat.live`, DB-creds, `IP_HMAC_SECRET=<random lang geheim>`, `CACHE_STORE=database`.
    - CORS: `paths=['api/*']`, `GET` open (evt. `*`), `POST` alleen trayapp (device-id vereist).
    - Logging: app-logs geen raw IP; hostingleverancier beheert serverlogs.

---

## Validatie
- **Acceptatiecriteria**
    - Wallpaper toont nieuwe berichten binnen **≤ 3 s** (poll 2,5 s).
    - Trayapp toont duidelijke foutmeldingen bij **422** (profanity) en **429** (rate-limit).
    - In DB zijn **geen raw IP’s** aanwezig; `ip_hmac` + `device_id` zijn gevuld.
    - Retentiejob verwijdert berichten **> 90 dagen**.
    - API p95 **≤ 350 ms** bij licht verkeer op Hostinger.
    - Privacy-notitie gepubliceerd (melding Hostinger-logs + minimale verwerking).
- **Testcases (selectie)**
    - Leeg/te lang bericht → **422**.
    - 25 berichten/min per IP → **429** met `retry_after`.
    - Profanity trefwoord → **422**.
    - Wallpaper verborgen → geen onnodige netwerkactiviteit.
    - Handmatige check DB: kolommen `ip_hmac` en `device_id` gevuld; geen raw IP.
