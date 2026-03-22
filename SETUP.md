# Deploy to Hosting Platform

## 1. Create Resources

### Webapp
1. Go to the control panel and create a new webapp
2. Select **PHP 8.5** runtime
3. Set the public folder to `public`

### Database
1. Create a **MySQL** database
2. Push the database credentials to the webapp's environment variables (this sets `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` automatically)

### Environment Variables
Set these additional env vars on the webapp:

| Variable | Value |
|---|---|
| `APP_KEY` | Generate with `php artisan key:generate --show` locally |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `BROADCAST_CONNECTION` | `reverb` |
| `QUEUE_CONNECTION` | `database` |
| `REVERB_APP_ID` | `demo-app` |
| `REVERB_APP_KEY` | `demo-key` |
| `REVERB_APP_SECRET` | `demo-secret` |

## 2. Connect GitHub

1. Push this repo to a GitHub repository
2. In the control panel, go to the **Deploy** tab on your webapp
3. Connect your GitHub repository
4. Select the **Laravel** deploy template
5. Click **Set Up Automatic Deploys**

## 3. Add Daemons

Add two daemons to your webapp:

### Queue Worker
- **Command**: `php artisan queue:work --sleep=3 --tries=3`
- This processes background jobs (task notifications)

### WebSocket Server (Reverb)
- **Command**: `php artisan reverb:start --host=[$HOST] --port=$PORT`
- **Proxy path**: `/app`
- This enables real-time broadcasting via WebSockets

## 4. Add Containers (optional)

### Meilisearch (instant search)
1. Create a new container from image: `getmeili/meilisearch`
2. Set port to `7700`
3. Optionally set the env var `MEILI_MASTER_KEY` on the container for authentication
4. Add these env vars to the **webapp**:

| Variable | Value |
|---|---|
| `SCOUT_DRIVER` | `meilisearch` |
| `MEILISEARCH_HOST` | The container's internal URL (e.g. `http://meilisearch:7700`) |
| `MEILISEARCH_KEY` | Your master key (if set on the container) |

5. After deploying, import existing tasks into the search index:
```bash
php artisan scout:import "App\Models\Task"
```

### Gotenberg (PDF export)
1. Create a new container from image: `gotenberg/gotenberg`
2. Set port to `3000`
3. Add this env var to the **webapp**:

| Variable | Value |
|---|---|
| `GOTENBERG_URL` | The container's internal URL (e.g. `http://gotenberg:3000`) |

The "Export PDF" button will appear automatically once configured.

## 5. PHP Settings (optional — for file upload demo)

To demonstrate changing PHP ini settings:

1. Go to the webapp's **PHP Settings** in the control panel
2. Set `upload_max_filesize` to `10M`
3. Set `post_max_size` to `12M`

Without these changes, file attachments over 2 MB will fail with a helpful error message — which is the point of the demo.

## 6. Deploy

Push to the `main` branch. The GitHub Action will:
1. Install dependencies (`composer install`)
2. Deploy files to the server via SSH
3. Run migrations
4. Cache configuration and routes

The app is now live. Visit your webapp's URL to see the task board.

### Reindexing Meilisearch

After a fresh deploy or if the search index gets out of sync, run:
```bash
php artisan scout:flush "App\Models\Task"
php artisan scout:import "App\Models\Task"
```

This can also be added as a post-deploy step in the GitHub Actions workflow.

## How It All Fits Together

```
                    GitHub Push
                        |
                        v
                  GitHub Actions
                  (deploy-action)
                        |
                        v
    +-------------------------------------------+
    |              Hosting Platform              |
    |                                            |
    |   [PHP 8.5 Webapp] --- [MySQL Database]   |
    |        |                                   |
    |   [Queue Worker Daemon]                    |
    |   (php artisan queue:work)                 |
    |        |                                   |
    |   [Reverb Daemon]                          |
    |   (php artisan reverb:start)               |
    |        |                                   |
    |   [Meilisearch Container] (optional)       |
    |   (instant full-text search)               |
    |        |                                   |
    |   [Gotenberg Container] (optional)         |
    |   (HTML-to-PDF generation)                 |
    |        |                                   |
    +-------------------------------------------+
             |
             v
    Browser <-- WebSocket (real-time updates)
```
