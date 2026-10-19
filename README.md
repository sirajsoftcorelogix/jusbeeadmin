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

User accounts are stored in `ju_users`; initialize the table with `database/ju_users.sql`. Account deactivation is reversible, and the management screen prevents deactivating the signed-in administrator or removing the last active administrator.

Content pages are stored in `ju_content_pages`; initialize the table with `database/ju_content_pages.sql`. Page bodies are plain text, escaped on output, and only published pages are publicly viewable.
