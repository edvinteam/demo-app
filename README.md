# Task Board

A real-time collaborative task board built with Laravel 12. Designed to showcase the full range of hosting platform features: webapps, databases, daemons, containers, and PHP configuration.

## Features

| Feature | What it demonstrates |
|---|---|
| **Kanban board** | PHP webapp with MySQL database |
| **Real-time sync** | Reverb WebSocket daemon — changes broadcast to all open browsers |
| **Background jobs** | Queue worker daemon — task creation triggers a queued notification job |
| **Instant search** | Meilisearch container — typo-tolerant full-text search via Docker container |
| **PDF export** | Gotenberg container — HTML-to-PDF generation via Docker container |
| **File attachments** | PHP ini settings — uploading files >2 MB requires `upload_max_filesize` and `post_max_size` to be increased in the control panel |

## Architecture

```
Browser (Alpine.js + Laravel Echo)
    |
    |-- HTTP ---------> Laravel API (CRUD, search, PDF export)
    |                       |
    |                       |-- MySQL (tasks, queue jobs)
    |                       |-- Queue Worker daemon (background notifications)
    |                       |-- Meilisearch container (full-text search)
    |                       |-- Gotenberg container (PDF generation)
    |                       |-- Local storage (file attachments)
    |                       |
    |<-- WebSocket ---- Reverb daemon (real-time broadcasts)
```

## Tech Stack

- **Backend**: Laravel 12, PHP 8.2+
- **Frontend**: Alpine.js 3, Tailwind CSS, Laravel Echo
- **Database**: MySQL
- **WebSockets**: Laravel Reverb
- **Search**: Laravel Scout + Meilisearch
- **PDF**: Gotenberg (Chromium-based HTML-to-PDF)
- **Queue**: Laravel Queue (database driver)

## Local Development

```bash
# Install dependencies
composer install

# Configure environment
cp .env.example .env
php artisan key:generate

# Create the database (ensure MySQL is running)
php artisan migrate

# Create the storage symlink for file attachments
php artisan storage:link

# Start the app (run each in a separate terminal)
php artisan serve
php artisan queue:work
php artisan reverb:start
```

The board is now running at `http://localhost:8000`.

### Optional: Meilisearch (instant search)

```bash
docker run -d -p 7700:7700 getmeili/meilisearch
```

Then add to `.env`:
```
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
```

Import existing tasks: `php artisan scout:import "App\Models\Task"`

### Optional: Gotenberg (PDF export)

```bash
docker run -d -p 3000:3000 gotenberg/gotenberg
```

Then add to `.env`:
```
GOTENBERG_URL=http://127.0.0.1:3000
```

## Deployment

See [SETUP.md](SETUP.md) for full deployment instructions including container setup.

## Demonstrating PHP ini Settings

By default, PHP limits file uploads to **2 MB** (`upload_max_filesize`) and POST bodies to **8 MB** (`post_max_size`). To showcase changing PHP ini settings in the control panel:

1. Create a task and try to attach a file larger than 2 MB
2. The upload will fail with a clear error message explaining the PHP limit
3. Go to the control panel and increase `upload_max_filesize` to `10M` and `post_max_size` to `12M`
4. Retry the upload — it works

## Graceful Degradation

The app works fully without the optional containers:

- **No Meilisearch** → search bar is hidden, tasks are listed normally
- **No Gotenberg** → export button is hidden
- **Default PHP ini** → file attachments work for files under 2 MB; larger files show a helpful error
