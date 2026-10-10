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
   - `http://localhost/Jusbee_admin/public`
   - or `http://localhost/Jusbee_admin` if rewrite is enabled.

## Demo Endpoints

- `GET /` - mood-tracking inspired themed home view with CSRF-secured form
- `GET /ping` - JSON health check
- `POST /submit` - CSRF + validation demo
