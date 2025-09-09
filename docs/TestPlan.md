# Testplan — deskchat live

Versie: 1.0  
Datum: 2025-09-09  
Auteur: Senne Visser

---

## 1. Doel en scope
Dit testplan beschrijft de testaanpak voor deskchat live (Laravel API + clients). Doel is om functionele eisen, acceptatiecriteria en niet‑functionele eisen te verifiëren voor de MVP:
- API kan berichten ophalen en plaatsen met correcte validatie, filtering en rate limiting.
- Privacy-by-design: geen raw IP in app/DB/logs; alleen ip_hmac.
- Eenvoudige en betrouwbare ervaring voor wallpaper (read-only) en tray-app (post/lees klein lijstje).

In scope (MVP):
- Laravel API endpoints: `GET /api/messages`, `POST /api/messages`, `GET /api/health`.
- Validatie, sanitization, profanityfilter, rate limiting (per device, per IP, globaal).
- Retentie van berichten (max 90 dagen) en opschoonjob (cron/scheduler).
- CORS-gedrag voor wallpaper (GET) en tray-app (POST).

Out-of-scope (nu):
- Accounts, moderatie-dashboard, uploads, meerdere rooms (could-have).
- Volledige client (tray/wallpaper) UI‑tests; we doen hiervoor beperkte handmatige E2E.

Referenties:
- Functioneel Ontwerp (docs/FunctOntw.md)
- Technisch Ontwerp (docs/TechOntw.md)
- Projectplan (docs/ProjectPlan.md)

---

## 2. Teststrategie
We combineren geautomatiseerde tests (PHPUnit) met gerichte handmatige E2E‑checks.

- Unit/Support
	- Profanity matching (woordlist), sanitization (strip_tags, whitespace), IP HMAC helper.

- Feature/API (PHPUnit, Laravel TestCase)
	- `POST /api/messages`: device‑id verplicht; validatie (1–280), profanity, sanitization, response 201/422/400.
	- `GET /api/messages`: since_id, limit (1..100), volgorde asc, last_id.
	- Rate limiting via throttle:chat: 429 met retry‑informatie (voor zover beschikbaar) per device en per IP.
	- Health endpoint `GET /api/health` 200 en tijdstempel.

- Integratie/E2E (handmatig, lichte scriptjes)
	- Tray → API → Wallpaper flow: bericht posten en binnen ~5s zichtbaar in wallpaper (simuleren met curl + eenvoudige HTML testpagina of Postman + log).
	- CORS voor wallpaper (GET open) en tray (POST met header `X-Device-Id`).

- Niet‑functioneel
	- Performance sanity: p99 < ~300 ms bij normaal gebruik (lokaal indicatief), wallpaper DOM‑cap (~100), poll interval ~2.5s.
	- Privacy: geen raw IP in DB‑schema en app‑logs; alleen `ip_hmac` opgeslagen.
	- Beheer: scheduler/cron draait retentiejob; healthcheck beschikbaar.

---

## 3. Testomgeving
- Taal/Framework: PHP 8.2/8.3, Laravel 11+, PHPUnit.
- Database: SQLite in‑memory of tijdelijke SQLite‑file voor tests; MySQL lokaal/CI voor integratie indien gewenst.
- Config:
	- `.env.testing` zet `DB_CONNECTION=sqlite` en `DB_DATABASE=:memory:` (of pad naar testdb)
	- `IP_HMAC_SECRET` op dummy waarde
	- `CACHE_STORE=database` en `php artisan cache:table` migratie uitgevoerd
- Logging: laravel.log mag geen raw IP bevatten (alleen ip_hmac in records); controle via asserts/log‑inspectie (optioneel).

---

## 4. Testdata
- Device‑IDs: geldige UUID strings (en 1 lege voor negative case).
- Profanitylijst: gebruik `config/profanity.php` woorden; voeg 1 testwoord toe (bijv. "vloekwoord") in testconfig of runtime override.
- Berichten: korte strings (1–20 tekens), grenswaarden (280 tekens), te lang (281), lege/HTML content (`<b>hi</b>` → "hi").
- Handles: null, kort ("Senne"), max 24 tekens.

---

## 5. Testgevallen (API)

### 5.1 POST /api/messages
1. Succes — minimaal: `handle` null, `content="hoi"`, header `X-Device-Id` → 201, message terug met id, ts, handle=Anon.
2. Succes — met handle: `handle="Senne"`, `content="Hallo wereld"`, `X-Device-Id` → 201; handle == "Senne".
3. Validatie — ontbrekende device id: geen `X-Device-Id` → 400 `{error: "missing_device_id"}`.
4. Validatie — content leeg: `content=""` → 422 `validation_failed`.
5. Validatie — content te lang (281): → 422 `validation_failed`.
6. Sanitization — HTML gestript: `content="<b>hoi</b>"` → opslaan "hoi"; 201.
7. Sanitization — leeg na strippen: `content="<b></b>   \n"` → 422 met `empty_after_sanitization`.
8. Profanity — geblokkeerd woord: `content` bevat lijstwoord → 422 `{error:"profanity_blocked"}`.
9. Device id max lengte — header >64 tekens → opgeslagen device_id is afgekapt tot 64 (assert DB/response niet vereist, alleen geen fout).
10. Rate limit — per device: 16 snelle POSTs/min (limiet 15) → laatste geeft 429.
11. Rate limit — per IP: meerdere devices vanaf zelfde IP overschrijden IP‑limiet → 429.
12. Response shape — velden en types: `id:int`, `handle:string`, `content:string`, `ts:ISO8601`.

### 5.2 GET /api/messages
13. Lege lijst — cold start: geen berichten → 200 `{messages:[], last_id:0}` (of last_id == since_id).
14. Basis — zonder parameters → laatste N (default 50 of minder) op volgorde asc; last_id == id van laatste.
15. Paginatie — since_id: eerst zonder, noteer last_id; vervolgens `since_id=<last_id>` → alleen nieuwere berichten.
16. Limit — boundaries: `limit=0` → behandeld als 1; `limit=101` → afgekapt naar 100.
17. Volgorde — altijd asc op `id`.

### 5.3 Health & CORS
18. Health — `GET /api/health` → 200 `{ok:true, time:...}`.
19. CORS (handmatig) — wallpaper GET toegestaan (evt. `*`), tray POST met `X-Device-Id` toegestaan; preflight indien nodig.

### 5.4 Retentie en privacy
20. Retentiejob — seed berichten met `created_at` >90d oud; draai job → oude berichten verwijderd, recente blijven (unit of feature met faked tijd/scheduler).
21. Privacy — DB‑schema bevat geen raw IP kolom; enkel `ip_hmac` (assert kolommen). App‑logs schrijven geen client IP (kwalitatieve check).

---

## 6. Acceptatiecriteria → testdekking
- Wallpaper toont nieuwe berichten binnen ~5s; max ~100 zichtbaar → TC 15–17 + handmatige E2E polling check.
- Tray‑app kan bericht (1–280) met bijnaam versturen; duidelijke fout bij te lang/woordfilter/rate limit/missing device → TC 1–12.
- API responses: 201/400(missing_device_id)/422(validation|profanity)/429(rate_limited) → TC 1–12.
- Geen accounts; geen persoonsgegevens; alleen device_id en ip_hmac → TC 21.
- Berichten ouder dan 90 dagen worden verwijderd → TC 20.
- Privacy‑notitie beschikbaar (documentair) → check docs (buiten scope testcode).

---

## 7. Uitvoering en planning
- Dagelijks tijdens MVP‑bouw: unit/feature tests lokaal draaien (auto bij CI, indien aanwezig).
- Integratiedagen (14–15 sep): E2E checks wallpaper+tray, CORS, performance sanity.
- Voor release (19 sep): volle regressie van feature suite + retentiejob + healthcheck.

---

## 8. Rapportage
- Resultaten: PHPUnit output en testrapport (optioneel JUnit XML/coverage).
- Incidenten/bugs: vastleggen als Git issues met stappen, logs en verwachte/feitelijke uitkomst.
- Exit‑criteria: alle kritieke cases (1–12, 15–17, 18, 20–21) groen; geen blocker‑bugs open.

---

## 9. Risico’s en mitigaties
- Rate‑limit flakiness in test: gebruik `withoutMiddleware()` of faked time, of verlaag limiet via config voor test.
- Profanitylijst variatie: test met eigen testwoord via config override.
- CORS omgevingsafhankelijk: test op gehoste wallpaper/localhost; documenteer hostnamen.

---

## 10. How to run (lokaal)

Optioneel — lokaal draaien van de test suite:

```bash
# Vereist: vendor dependencies en test-DB setup
composer install
php artisan key:generate
php artisan migrate --env=testing
phpunit
```

---

## 11. Bijlage: mapping naar code
- Controller: `app/Http/Controllers/MessageController.php` (index/store, sanitization, profanity, device‑id, ip_hmac)
- Routes: `routes/api.php` (health, messages GET/POST met throttle:chat)
- Support: `app/Support/IpPrivacy.php` (HMAC van IP)  
- Config: `config/profanity.php`, `config/cors.php`
- Testsuite: `tests/Feature/MessageApiTest.php` (aan te vullen met bovenstaande cases)

---

## 12. Conclusie
Met dit testplan borgen we de MVP‑kwaliteit en privacy‑principes. De focus ligt op API‑correctheid, eenvoudige E2E‑flow en basis‑NFR’s (performance, CORS, retentie).

