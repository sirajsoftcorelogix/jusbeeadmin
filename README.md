# Jusbee MVC Starter

A basic but modern MVC framework starter for PHP 8.2+, designed for XAMPP and following current market expectations:

- PSR-4 autoloading via Composer
- Front controller architecture (`public/index.php`)
- Dependency Injection container with autowiring
- Routing with route parameters
- Middleware pipeline support
- CSRF protection middleware
- Input validation helper
- PDO database layer and base model
- Error pages and security headers

## Project Structure

- `app/Core` - framework kernel components
- `app/Http/Controllers` - controllers
- `app/Http/Middleware` - route middleware
- `app/Models` - models
- `config` - app and database config
- `routes` - route definitions
- `views` - PHP view templates
- `public` - web entrypoint

## Setup

1. Copy environment file:
   - `copy .env.example .env`
2. Install dependencies:
   - `composer install`
3. Point Apache to this project root (or directly to `public/`).
4. Open:
   - `http://localhost/Jusbeeadmin`
   - or `http://localhost/Jusbeeadmin/public` if accessing the public directory directly.

## Demo Endpoints

- `GET /` - mood-tracking inspired themed home view with CSRF-secured form
- `GET /ping` - JSON health check
- `POST /submit` - CSRF + validation demo
- `GET /users` - admin-only searchable user directory with status filters
- `POST /users` - create a user with an admin or operations role
- `POST /users/{id}/update` - update a user's profile, role, or optional password
- `POST /users/{id}/status` - activate or deactivate a user account
- `GET /content` - admin-only page content management
- `POST /content` - create a draft or published page
- `POST /content/{id}/update` - update a content page
- `POST /content/{id}/status` - publish or unpublish a content page
- `POST /content/{id}/delete` - delete a content page
- `GET /test-content` - admin-only test content management
- `POST /test-content` / `POST /test-content/{id}/update` / `POST /test-content/{id}/delete` - create, edit, and delete test content
- `GET /content-categories` - admin-only test content category management
- `POST /content-categories` / `POST /content-categories/{id}/update` / `POST /content-categories/{id}/delete` - create, edit, and delete test content categories
- `GET /pages/{slug}` - view a published page
- `GET /library` - admin-only library management
- `POST /library` - create a library item
- `POST /library/{id}/update` - update a library item
- `POST /library/{id}/delete` - delete a library item
- `GET /media-categories` - admin-only media category management, including parent/child categories
- `POST /media-categories` / `POST /media-categories/{id}/update` / `POST /media-categories/{id}/delete` - create, update, and delete media categories
- `GET /counsellors` - admin-only counsellor directory management
- `POST /counsellors` / `POST /counsellors/{id}/update` / `POST /counsellors/{id}/delete` - create, update, and delete counsellor profiles
- `GET /riyaz` - admin-only Riyaz sync-file management
- `POST /riyaz` / `POST /riyaz/{id}/update` / `POST /riyaz/{id}/delete` - add, update, and delete Riyaz records

Library items are stored in `ju_library` (title, slug, thumbnail, media file, media type, play time, artist, description, updated by, status, and timestamps); initialize a new table with `database/ju_library.sql`. Upload WebP/JPEG thumbnails and MP3, MP4, WAV, MOV, M4V, or M4A media files under `public/uploads/library/` (max 100 MB and limited by PHP `upload_max_filesize`/`post_max_size`). `updated_by` is the ID of the admin who last saved the item. Existing installations should run `database/migrate_update_ju_library.sql`; it keeps legacy uploaded image/audio/video paths when they are stored under `uploads/library/`, but removes the old YouTube and link fields.

Media categories are stored in `Media_Categories` and assigned to library items through `category_media_map`, using category and media slugs. Create these tables with `database/media_categories.sql` after `ju_users`, `ju_library`, and their migrations are installed. Library item create/edit forms support assigning multiple categories.

Test content is stored in `test_content`, categorized with `Content_Categories`, and assigned through `category_content_map`. Create these tables with `database/test_content.sql` after `ju_users` exists. Test-content thumbnails accept WebP/JPEG and are stored under `public/uploads/test-content/`. The authenticated app APIs list test content by category and retrieve test content by slug; see [API.md](./API.md).

Counsellors are stored in `Counsellors`; initialize the table with `database/counsellors.sql` after `ju_users` exists. Admins manage counsellor profiles, verification status, fees, and WebP/JPEG profile thumbnails at `/counsellors`. Authenticated app APIs expose only verified counsellors via `GET /api/counsellors` and `GET /api/counsellors/{id}`. The requested `/api/conselor_list` and `/api/conselor_by_id/{id}` aliases are also available; see [API.md](./API.md).

Riyaz records are stored in `Riyaz`; initialize the table with `database/riyaz.sql` after `ju_library` and `ju_users` exist. Admins can add records at `/riyaz`, linking a library media item to a valid JSON file uploaded under `public/uploads/riyaz/` (maximum 10 MB).

## App Users (admin)

Admins manage API-registered users at `/app-users` (sidebar: App Users): list with search/status filter, create, edit (blank password keeps the current one), activate/deactivate and delete. Deactivating a user, changing their password, or deleting them signs them out of the API.

- `GET /app-users` / `POST /app-users` / `POST /app-users/{id}/update` / `/status` / `/delete`  (admin + CSRF)

## Mobile/App User API

See [API.md](./API.md) for endpoint details, request/response examples, and validation rules.

App users live in `ju_app_users` (first/last name, email, phone, password hash, profile thumbnail, device ID, current stage, birth date, wellness preferences, status, timestamps) and their login tokens in `ju_app_user_tokens`. Create both with `database/ju_app_users.sql` then `database/ju_app_user_tokens.sql`. For an existing `ju_app_users` table, run `database/migrate_add_app_user_profile_fields.sql` followed by `database/migrate_remove_app_user_name.sql`; the latter moves existing `name` values into `first_name` and `last_name` before removing `name`. If the table was created earlier with an `api_token` column, also run `database/migrate_drop_app_users_api_token.sql`. Send JSON (`Content-Type: application/json`). Responses are JSON with `success`, `message`, and `errors` on failure. No CSRF token is needed.

Public:
- `POST /api/register` - `first_name`, `last_name`, `email`, `phone`, `password` (min 8), optional profile fields (`profile_thumbnail`, `device_id`, `current_stage`, `birth_date`, `reduce_stress`, `sleep_better`, `build_heithy_habits`, `journal_throughts`, `find_professional_support`) and `device_name` -> `201` with `token`, `token_type`, `expires_at`, `user`
- `POST /api/login` - `login` (email or phone, or `email`), `password`, optional `device_name` -> same response

Protected (header `Authorization: Bearer <token>`; handled by `ApiAuthMiddleware`, returns `401` for a missing, invalid or expired token and `403` for an inactive account):
- `GET /api/profile` - current user
- `PUT /api/profile` (or `POST`) - any of `first_name`, `last_name`, `email`, `phone`, or the profile fields above; `birth_date` uses `YYYY-MM-DD` and the wellness preference fields are booleans. To change the password send `password` plus `current_password` (all other sessions are then signed out)
- `GET /api/journal-log?type=journal` - list the authenticated user's entries for one type (`journal`, `graditude`, `dialy_dream`, or `worry_tree`)
- `POST /api/journal-log` - create an entry with `type`, `title`, `description`, and optional boolean `favorite`; `app_user_id` is taken from the bearer token and `added_date_time` defaults to the current time
- `GET /api/media-categories/{slug}` - get category details and its direct child categories
- `GET /api/media-categories/{slug}/media` - list active media assigned to a category
- `GET /api/media/{slug}` - get active media content and its category assignments
- `POST /api/logout` - revoke the current token
- `POST /api/logout-all` - revoke every token of the user

Create the journal table with `database/journal_log.sql`.
Notification preferences are stored per app user in `Notification_settings`; create the table with `database/notification_settings.sql`. For an existing table with the previous mixed-case fields, run `database/migrate_notification_settings_lowercase_fields.sql`. Authenticated app APIs can set preferences using `POST /api/Set_user_notification` and fetch them using `GET /api/fetch_user_notification`; see [API.md](./API.md).
The media endpoints require the app user's bearer token and return active media only. See [API.md](./API.md) for request conventions and response examples.

Every login creates a separate token, so a user can be signed in on several devices. Tokens expire after 30 days (`AppUserToken::TTL_DAYS`); only their SHA-256 hash is stored.