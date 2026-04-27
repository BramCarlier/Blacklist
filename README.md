# Access Deny PWA

Lightweight Laravel 12 + Inertia/Vue PWA for registering and checking people denied access at a location.

## Important legal/privacy note
This project stores sensitive personal data. Before production use, define a lawful basis, minimization policy, retention/deletion process, access control policy, audit process, signage/notice flow, and local compliance checks. Do not scan or retain full ID-card data unless legally allowed and necessary.

## Features
- Login-protected admin interface
- Manual blacklist entry creation
- Camera QR/barcode scan via browser `BarcodeDetector` when supported
- Web NFC hook via `NDEFReader` when supported by Android Chrome over HTTPS
- Fast lookup/check page
- Statuses: active, expired, appealed
- Soft deletes and audit logs
- PWA manifest + service worker
- Sail + Forge-friendly build scripts

## Local setup with Sail
```bash
cp .env.example .env
composer install
yarn install
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail yarn dev
```

Default login from `.env.example`:

- `admin@example.com`
- `password`

## Production / Forge setup
1. Create MySQL database and set env vars.
2. Install dependencies and build assets:
```bash
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
yarn install --frozen-lockfile
yarn build
php artisan migrate --force
php artisan db:seed --force
php artisan optimize
```
3. Serve over HTTPS. Camera and NFC APIs require secure contexts on real devices.

## Suggested Forge deploy script
```bash
cd $FORGE_SITE_PATH
$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader
yarn install --frozen-lockfile
yarn build
$FORGE_PHP artisan migrate --force
$FORGE_PHP artisan optimize:clear
$FORGE_PHP artisan optimize
```

## Scanner payload format
The camera scanner works best with a QR code containing JSON:

```json
{
  "firstName": "Ada",
  "lastName": "Lovelace",
  "documentNumber": "ABC123456",
  "birthDate": "1815-12-10",
  "nationality": "GB"
}
```

Real national ID card NFC/eID reading from the browser is limited. Web NFC is designed for NDEF tags, not full eID smart-card certificate flows. For official eID chip reading you usually need native Android code or a dedicated reader/app bridge.

## Hardening checklist before real use
- Replace default admin credentials.
- Add roles/permissions if multiple venues or guards use the system.
- Add retention command to expire/delete old entries automatically.
- Add export/reporting only if legally required.
- Add encryption-at-rest for specific columns if your risk model requires it.
- Add rate limiting and device/session management.
