
<p align="center">
  <img src="https://deskchat.live/images/logoBlue.webp" alt="deskchat live logo" width="220" />
</p>

# deskchat live — README

> Live chat as your Windows wallpaper. Write from a tiny tray app, render on Wallpaper Engine, served by a simple Laravel API.

**Website:** https://deskchat.live  
**API:** https://api.deskchat.live  
**Status:** MVP design complete — implementation in progress (2025-09-04)

---

## ✨ What is this?
- **Wallpaper Engine** web wallpaper that **displays** live chat messages (read‑only).
- **Windows tray app** (C#/.NET 8 WPF) to **post** messages (and read in a small list).
- **Laravel API** that validates input, filters profanity, rate‑limits abuse, and stores messages.

**Privacy‑by‑design:** no raw IPs stored. Server saves only `ip_hmac = HMAC_SHA256(ip, secret)` + an anonymous `device_id` from the tray app. Hostinger’s server access logs may still contain IPs (see Privacy).

---

## 🧱 Repository layout (suggested)
```
/api        # Laravel 11+ project (routes, controllers, migrations)
/tray       # .NET 8 WPF tray app
/wallpaper  # index.html (web wallpaper for Wallpaper Engine)
/docs       # FO/TO/Projectplan, privacy note, diagrams
```

---

## 🔌 API Quickstart (Laravel 11+)

### Requirements
- PHP 8.2/8.3, Composer
- MySQL 8.x (or MariaDB), CLI access (Hostinger or local)
- OpenSSL for TLS (production), Git (optional)

### Install (local)
```bash
cd api
composer create-project laravel/laravel .
cp .env.example .env
php artisan key:generate
# configure DB in .env (local MySQL or SQLite)
```

### Configure env
Add to `.env`:
```env
APP_URL=https://api.deskchat.live   # local: http://127.0.0.1:8000
IP_HMAC_SECRET=change_me_to_a_long_random_secret
CACHE_STORE=database                 # (Laravel 11) or CACHE_DRIVER=database
```

### Database & cache tables
```bash
php artisan make:migration create_messages_table
php artisan cache:table
# edit the migration to:
# Schema::create('messages', function (Blueprint $t) {
#   $t->id();
#   $t->string('handle', 24)->nullable();
#   $t->text('content');
#   $t->string('device_id', 64)->nullable()->index();
#   $t->char('ip_hmac', 64)->index();
#   $t->timestamps();
#   $t->index(['created_at','id']);
# });
php artisan migrate
```

### Rate limiting & privacy (snippets)
- **config/app.php**
```php
'ip_hmac_secret' => env('IP_HMAC_SECRET'),
```
- **app/Support/IpPrivacy.php**
```php
namespace App\Support;
class IpPrivacy {
  public static function hmac(string $ip): string {
    $secret = config('app.ip_hmac_secret');
    return hash_hmac('sha256', $ip, $secret);
  }
}
```
- **AppServiceProvider::boot()**
```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

RateLimiter::for('chat', function (Request $r) {
  $device = $r->header('X-Device-Id') ?: 'no-device';
  return [
    Limit::perMinute(20)->by($r->ip()),   // per-IP (ephemeral in cache)
    Limit::perMinute(15)->by($device),    // per-device
    Limit::perMinute(300)->by('global'),  // circuit breaker
  ];
});
```

### Routes (minimal)
```php
Route::get('/messages', [MessageController::class, 'index']);
Route::post('/messages', [MessageController::class, 'store'])->middleware('throttle:chat');
```

### Controller notes
- `store`: requires `X-Device-Id`; strips HTML; validates length; profanity check; computes `ip_hmac`; inserts message; returns `201`.
- `index`: supports `since_id` + `limit` (<=100), returns `{messages:[], last_id}` sorted ascending for easy appending.

### CORS (config/cors.php)
```php
'paths' => ['api/*'],
'allowed_methods' => ['GET','POST','OPTIONS'],
'allowed_origins' => ['https://deskchat.live','*'],  // GET may need * for Wallpaper (file://)
'allowed_headers' => ['*'],
'supports_credentials' => false,
```

### Run local
```bash
php artisan serve
# GET http://127.0.0.1:8000/api/messages
```

---

## 🚀 Hostinger deployment (shared hosting)
1. **Subdomain**: create `api.deskchat.live` → document root `domains/deskchat.live/public_html/_api/public`
2. **Upload/clone** your Laravel project into `_api` (above `public/`).
3. **.env**: start from `.env.production.example`, set DB creds from hPanel; set `APP_URL`, and a strong `IP_HMAC_SECRET`; keep `CACHE_STORE=database`.
4. **Migrate**: `php artisan migrate --force && php artisan cache:table && php artisan migrate --force`
5. **Optimize**: `php artisan config:cache route:cache view:cache`
6. **Cron** (hPanel → Cron Jobs): every minute  
   `php /home/USER/domains/deskchat.live/public_html/_api/artisan schedule:run > /dev/null 2>&1`

> Note: Hostinger **access logs** may include raw IPs. Your **app** should not store raw IPs; only `ip_hmac`. Mention Hostinger logging in your privacy note.

---

## 🖥️ Tray app (C#/.NET 8 WPF)

### Requirements
- .NET SDK 8.x, Visual Studio 2022 or JetBrains Rider

### Create project
```bash
dotnet new wpf -n DeskchatLive.Tray
cd DeskchatLive.Tray
dotnet add package System.Windows.Forms
```

### Base URL & device id
- Generate a persistent UUID in `%LOCALAPPDATA%/DeskchatLive/device_id.txt`.
- Add default header `X-Device-Id: <uuid>` to `HttpClient`.
- Base API: `https://api.deskchat.live` (override via config if needed).

### Build
```bash
dotnet publish -c Release -r win-x64 /p:PublishSingleFile=true /p:PublishTrimmed=true
```

### Errors to surface to the user
- `400 missing_device_id` → device id not set
- `422 profanity_blocked` / validation errors
- `429 rate_limited` with `retry_after` seconds

---

## 🖼️ Wallpaper Engine — web wallpaper

### Minimal `index.html`
- Poll every 2500 ms: `GET https://api.deskchat.live/api/messages?since_id=<last_id>&limit=30`
- Append messages; **cap DOM to ~100**; escape text; **pointer-events:none**
- Pause polling when `document.hidden === true`

**Import**: WE Editor → Create Wallpaper → *Web* → select `index.html`

---

## 🔐 Privacy & Security
- **No raw IP stored** by the app. We save only an **HMAC** of the client IP (`ip_hmac`) and an anonymous **device id**.
- **Hostinger access logs** may include IPs for security — disclose this in your privacy note.
- **Rate limits**: per IP + per device + global circuit breaker.
- **Sanitization**: strip HTML; 1–280 chars max; profanity wordlist (customizable).
- **Transport**: HTTPS required on both domains.

---

## 🧪 API reference (short)
### `GET /api/messages?since_id=<int>&limit=<1..100>[&room=<slug> (could)]`
**200 OK**
```json
{"messages":[{"id":1,"handle":"Anon","content":"hi","ts":"2025-09-04T10:00:00Z"}],"last_id":1}
```

### `POST /api/messages`
Headers: `Content-Type: application/json`, `X-Device-Id: <uuid>`  
Body:
```json
{"handle":"Senne","content":"Hallo!"}
```
Responses:
- **201** `{ok:true, message}`
- **400** `{ok:false, error:"missing_device_id"}`
- **422** `{ok:false, error:"validation_failed"|"profanity_blocked"}`
- **429** `{ok:false, error:"rate_limited", "retry_after":12}`

---

## 🗺️ Roadmap
- **Could‑have**: multiple rooms (`room` param, tray room selector; wallpaper per room or cycle)
- **Could‑have**: lightweight GIF embeds (Tenor/direct `.gif`) rendered client‑side only
- Optional: moderation helpers (shadow‑ban UI), simple keyword mutes

---

## 🛠️ Troubleshooting
- **CORS errors in wallpaper** → allow `GET` from `*` (or host the HTML on `deskchat.live`).
- **429 rate_limited** → back off; respect `retry_after`.
- **422 profanity_blocked** → update wordlist or message.
- **Hostinger 500** → run `php artisan config:clear route:clear view:clear` then `config:cache route:cache view:cache`.

---

## 📦 File-first mode (shared hosting)

To avoid MySQL connection limits on shared hosting, the API hot paths use files:

- Set in `.env`:
  - `CACHE_STORE=file`
  - `CACHE_DRIVER=file`
  - `SESSION_DRIVER=file`
  - `QUEUE_CONNECTION=sync`
- Ensure directory exists: `storage/app/messages/` with permissions for PHP to write.
- Files:
  - `feed.json` — ring buffer of latest ~200 messages
  - `spool.ndjson` — append-only inbox for database flush
  - `spool.offset` — last flushed byte offset
  - `seq.txt` — monotone numeric id counter
- Cron: ensure `php artisan schedule:run` runs every minute. The `messages:flush-spool` task runs every 10 minutes and uses a cache lock.

### Low DB Quota Deployment Notes

If you previously followed instructions that enabled database-backed cache, session, or queue drivers, you may hit provider limits like `max_connections_per_hour` quickly because:

- Database cache store: every rate-limit and cache call = 1 connection.
- Database sessions: every request that starts / reads a session = 1 connection.
- Database queue driver: workers poll the `jobs` table frequently.

Current defaults in `config/` have been changed to safer file/sync options:

```
CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
APP_MAINTENANCE_DRIVER=file
APP_MAINTENANCE_STORE=file
```

After updating `.env` (or pulling these config changes) run:

```
php artisan config:clear
php artisan cache:clear
```

If a cached config file exists at `bootstrap/cache/config.php` from a previous deploy, remove it before re-caching, otherwise old drivers may persist.


---

## 🤝 Contributing
PRs welcome. Keep changes minimal, logics small, and avoid heavy dependencies (the wallpaper must stay tiny).

---

## 📄 License
Choose a license for your repo (MIT recommended).

---

## 📑 Acknowledgements
- Wallpaper Engine (web wallpaper)
- Laravel, .NET, and their communities
