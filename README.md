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
- `GET /pages/{slug}` - view a published page
- `GET /library` - admin-only library management
- `POST /library` - create a library item
- `POST /library/{id}/update` - update a library item
- `POST /library/{id}/delete` - delete a library item

Library items are stored in `ju_library` (slug, youtube, audio, video, link, image, status, created_at, modified_at); initialize the table with `database/ju_library.sql`. Media fields `youtube` and `link` are http(s) URLs; `audio`, `video` and `image` (jpg, png, gif, webp) are file uploads (stored under `public/uploads/library/`, max 100 MB and limited by PHP `upload_max_filesize`/`post_max_size`).

## App Users (admin)

Admins manage API-registered users at `/app-users` (sidebar: App Users): list with search/status filter, create, edit (blank password keeps the current one), activate/deactivate and delete. Deactivating a user, changing their password, or deleting them signs them out of the API.

- `GET /app-users` / `POST /app-users` / `POST /app-users/{id}/update` / `/status` / `/delete`  (admin + CSRF)

## Mobile/App User API

App users live in `ju_app_users` (name, email, phone, password hash, status, timestamps) and their login tokens in `ju_app_user_tokens`. Create both with `database/ju_app_users.sql` then `database/ju_app_user_tokens.sql` (if you created `ju_app_users` earlier with an `api_token` column, also run `database/migrate_drop_app_users_api_token.sql`). Send JSON (`Content-Type: application/json`). Responses are JSON with `success`, `message`, and `errors` on failure. No CSRF token is needed.

Public:
- `POST /api/register` - `name`, `email`, `phone`, `password` (min 8), optional `device_name` -> `201` with `token`, `token_type`, `expires_at`, `user`
- `POST /api/login` - `login` (email or phone, or `email`), `password`, optional `device_name` -> same response

Protected (header `Authorization: Bearer <token>`; handled by `ApiAuthMiddleware`, returns `401` for a missing, invalid or expired token and `403` for an inactive account):
- `GET /api/profile` - current user
- `PUT /api/profile` (or `POST`) - any of `name`, `email`, `phone`; to change the password send `password` plus `current_password` (all other sessions are then signed out)
- `POST /api/logout` - revoke the current token
- `POST /api/logout-all` - revoke every token of the user

Every login creates a separate token, so a user can be signed in on several devices. Tokens expire after 30 days (`AppUserToken::TTL_DAYS`); only their SHA-256 hash is stored.