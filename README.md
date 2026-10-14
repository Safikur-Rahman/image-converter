# Image Converter

A production-ready SaaS that converts raster images (PNG, JPEG, WebP) into clean, optimized **SVG vectors**, with optional AI **background removal**. Built with Laravel 12, Alpine.js, and Tailwind CSS.

## Features

- **Raster → SVG vectorization** — full conversion pipeline with color/monochrome modes, adjustable quality, and path optimization run on a background queue.
- **Background removal** — provider-agnostic integration (bring your own API key); gracefully disabled when unconfigured.
- **Plans & usage limits** — monthly conversion quotas enforced per plan, soft limit warnings, and storage-cleanup of stale temporary files.
- **Web dashboard + REST API** — identical pipeline is reused by both (`App\Services\Conversion\ConversionProcessor`).
- **Admin panel** — users, plans, subscriptions, conversions, background removals, system logs, failed jobs, and settings, all behind an `admin` role gate.
- **Async job processing** — conversions run on the queue with configurable retries, backoff, and timeouts; status polling powers the live progress UI.
- **Security** — verified MIME types (GD decode check), dimension/size caps, ownership policies, Sanctum tokens for the API, thumbnails rendered as data URIs (no raw SVG injection).

## Requirements

- PHP 8.2+ with `gd`, `xml`, `mbstring`, and `sqlite` (or your preferred DB)
- Composer
- Node 20+ and npm (front-end build only)
- A queue worker (database or Redis queue supported)

## Installation

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate

# Database (SQLite default)
touch database/database.sqlite
php artisan migrate --seed

# Build the front-end assets
npm install-scripts approve esbuild
npm rebuild esbuild
npm run build

# Storage symlink for public files
php artisan storage:link

# Start the queue worker
php artisan queue:work
```

Then serve the app (`php artisan serve`) and open `http://localhost:8000`.

### Seeded demo accounts (password: `password`)

| Email | Plan | Role |
|---|---|---|
| `admin@imageconverter.test` | business (unlimited) | admin |
| `demo@imageconverter.test` | pro | user |

## Configuration

See `.env.example` for the full list. App-specific keys:

| Key | Default | Description |
|---|---|---|
| `MAX_UPLOAD_SIZE` | `10240` | Max upload size in KB |
| `MAX_IMAGE_DIMENSIONS` | `8000` | Max input width/height in px |
| `ALLOWED_FORMATS` | `png,jpg,jpeg,webp` | Comma-separated raster formats |
| `MAX_TRACE_DIMENSION` | `2048` | Downscale cap before tracing |
| `DEFAULT_SVG_QUALITY` | `high` | Default output quality (`low`, `medium`, `high`, `precision`) |
| `SVG_TRACING_ENGINE` | `pure_php` | Tracing engine (bundled engine only) |
| `STORAGE_RETENTION_DAYS` | `30` | Days to keep temporary files (`-1` disables) |
| `CONVERSION_JOB_RETRIES` | `3` | Queue retries per conversion |
| `CONVERSION_JOB_TIMEOUT` | `300` | Job timeout in seconds |
| `BACKGROUND_REMOVAL_PROVIDER` | *(empty)* | Provider name when enabled |
| `BACKGROUND_REMOVAL_API_KEY` | *(empty)* | Provider API key |
| `BACKGROUND_REMOVAL_API_URL` | *(empty)* | Custom provider endpoint |
| `BACKGROUND_REMOVAL_TIMEOUT` | `90` | Provider request timeout in seconds |

Background removal is **disabled** until a provider/key is configured — the UI and API then reject background-removal requests cleanly.

## Queue & cache

- `QUEUE_CONNECTION=database` (default) — run `php artisan queue:work` (or `queue:work --queue=conversions`).
- `CACHE_STORE=database` (default) powers rate limiting.
- Pending conversions store files under `storage/app/private`, processed SVG under `svg/`.

## Testing

```bash
php artisan test
```

The suite (80 tests) covers the tracing engine, SVG validation/optimization, storage layer, usage limits, background removal, the full upload flow, authorization policies, and the web/API endpoints. Tests use an in-memory SQLite database and a synchronous queue.

## REST API

Base URL: `/api/v1` — authenticate with a Bearer token (create via the web dashboard or `POST /api/v1/tokens`).

| Endpoint | Method | Description |
|---|---|---|
| `/me` | GET | Authenticated user profile |
| `/plans` | GET | Available plans |
| `/capabilities` | GET | Feature flags & limits for the caller |
| `/tokens` | GET | List personal tokens |
| `/tokens` | POST | Create a token (`name`) |
| `/conversions` | GET | Paginated conversions |
| `/conversions` | POST | Start a conversion (`image`, optional `remove_background`, `conversion_type`, `quality`, `path_optimization`) |
| `/conversions/{uuid}` | GET | Conversion detail |
| `/conversions/{uuid}/status` | GET | Current status |
| `/conversions/{uuid}/download/{type}` | GET | Download file (`svg`, `original`, `background_removed`) |
| `/conversions/{uuid}` | DELETE | Delete a conversion |

Uploads return `202 Accepted` with the conversion record; poll `/conversions/{uuid}/status` until `completed`.

## Commands

- `php artisan app:cleanup-temporary-images` — purge temporary files older than the retention window (schedule it, e.g. daily).

## License

Proprietary.