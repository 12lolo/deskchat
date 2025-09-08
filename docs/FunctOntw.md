# Functioneel Ontwerp — deskchat live

**Project:** deskchat live  
**Versie:** 1.0  
**Datum:** 2025-09-04  
**Auteur:** Senne Visser  
**Website:** https://deskchat.live  
**API:** https://api.deskchat.live

---

## Inhoudsopgave
1. Voorwoord
2. Samenvatting
3. Analyse huidige situatie  
   3.1 Informatiewerking  
   3.2 Applicaties  
   3.3 Infrastructuur
4. Analyse gewenste situatie  
   4.1 Requirements (MoSCoW)  
   4.2 Informatieverwerking  
   4.3 Applicaties  
   4.4 Infrastructuur
5. Consequenties  
   5.1 Organisatorische consequenties  
   5.2 Technische consequenties  
   5.3 Kosten  
   5.4 Planning

---

## 1. Voorwoord
Dit FO beschrijft **wat** deskchat live moet doen: een Windows-achtergrond die live chatberichten toont (read-only), een Windows tray-app voor invoer en een Laravel API als backend. Privacy-by-design is uitgangspunt.

---

## 2. Samenvatting
- **UX:** Live feed als achtergrond; berichten plaatsen via tray-app met **nickname** (geen accounts).
- **API:** Validatie, **profanityfilter**, **rate limiting** (IP + device), opslag zonder raw IP (alleen `ip_hmac`).
- **Hosting:** Hostinger (shared), MySQL, cron voor retentie (≤ 90 dagen).
- **MVP:** 1 room (`global`), tekst-only; **Could-have:** meerdere rooms + gif-embeds (Tenor/links).

---

## 3. Analyse huidige situatie
### 3.1 Informatiewerking (as is)
- Geen bestaand chatsysteem; domeinen bestaan; geen data of moderatieproces.
### 3.2 Applicaties (as is)
- Wallpaper Engine beschikbaar; Hostinger beschikbaar; nog geen app/API geïmplementeerd.
### 3.3 Infrastructuur (as is)
- Hostinger (LiteSpeed/Apache + PHP + MySQL), SSL via hPanel, SSH-toegang.

---

## 4. Analyse gewenste situatie
### 4.1 Requirements (MoSCoW)

**Must-have**
- Wallpaper toont feed via `GET /api/messages?since_id=&limit=`.
- Trayapp plaatst berichten via `POST /api/messages` met `X-Device-Id: <UUID>`.
- Validatie: content 1–280; handle 1–24 (optioneel).
- Profanityfilter; rate limiting per-IP en per-device; privacy (geen raw IP).
- Retentie: purge ≤ 90 dagen.

**Should-have**
- `since_id` + `limit` voor lage load; pauze bij `document.hidden`.
- Foutmeldingen 422/429 in tray-app; configureerbare limieten en poll-interval.
- Privacy-notitie op deskchat.live.

**Could-have**
- **Meerdere chatrooms**:
    - API: `room`-parameter (default `global`), trayapp room-selector, wallpaper per-room of cyclisch.
- **GIF-embeds** (lichtgewicht) via Tenor/gelinkte `.gif` (client-side; geen uploads).
- Shadow-ban; eenvoudige emoji-weergave.

**Won’t-have (MVP)**
- Accounts/login, profielen, E2E-encryptie, uploads/media-hosting, WebSockets, moderatie-dashboard, AI-filtering.

---

### 4.2 Informatieverwerking (to be)
**Actoren:** Gebruiker, Tray-app, Wallpaper, API, DB.  
**Objecten:** `Message { id, handle, content, device_id, ip_hmac, created_at }`.  
**Stromen:**
1. **POST /api/messages** → trim/strip → validatie → profanity → rate-limit → `ip_hmac` → insert → 201.
2. **GET /api/messages?since_id&limit** → select > since_id → sort/limit → JSON + `last_id`.
3. **Purge job** (cron) → delete ouder dan 90 d.

**Fouten:** `400 missing_device_id`, `422 validation/profanity_blocked`, `429 rate_limited (retry_after)`.

---

### 4.3 Applicaties (to be)
**Wallpaper:** read-only; poll 2,5 s; cap 100; `pointer-events:none`; pauze bij `document.hidden`.  
**Tray-app (WPF .NET 8):** device UUID genereren; POST/GET; foutmeldingen tonen; kleine UI.  
**API (Laravel):** routes, throttle, profanity, HMAC, CORS; geen raw IP in DB/app-logs.

---

### 4.4 Infrastructuur (to be)
**Hostinger:** subdomein `api.deskchat.live` → `domains/deskchat.live/public_html/_api/public`; SSL; MySQL; cron scheduler.  
**Configuratie:** `.env` met `APP_URL`, DB, `IP_HMAC_SECRET`, `CACHE_STORE=database`; `config/cors.php` met open `GET`.

---

## 5. Consequenties
### 5.1 Organisatorische consequenties
- Privacyverklaring (device-id, ip_hmac; Hostinger logs), basis moderatiebeleid, minimaal beheer.
### 5.2 Technische consequenties
- Schaalbaar genoeg met polling; migratie naar VPS/WebSockets mogelijk bij groei.
### 5.3 Kosten
- Gedekt door bestaande Hostinger + domeinen; (optioneel) code signing voor tray-app.
### 5.4 Planning
- Zie projectplan; MVP gereed binnen ~2 weken met fasering Analyse → Backend → Tray → Wallpaper → E2E → Oplevering.
