<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## Production Scheduler

The application relies on Laravel's task scheduler for bypass request expiration and escalation. In production, add the following cron entry to ensure these jobs fire every minute:

```
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

Without this cron entry, pending bypass requests will not expire automatically and escalation notifications to God Admins / email will not fire.

## Mobile Officer Check-In

Officers access the mobile check-in interface at `/officer/login` from their phone's browser. The UI is a responsive Blade + Alpine.js application that uses the browser's Geolocation API and MediaDevices camera API.

### Access

1. Open `https://your-domain.com/officer/login` on the officer's phone browser.
2. Log in with NRP (badge number) and password.
3. The token is stored in `sessionStorage` — it expires when the browser tab closes or after the configured `PH_TOKEN_EXPIRY_HOURS` (default 12h).

### HTTPS Requirement

The mobile officer UI **requires HTTPS** in production. The browser Geolocation and Camera APIs are only available in secure contexts. The UI includes a client-side guard that blocks usage over plain HTTP and displays an error message.

For local development, `http://localhost` and `http://127.0.0.1` are treated as secure contexts by browsers and will work without TLS.

### Screens

- **Login** — NRP + password form
- **Assignments** — today's assignments with ±7 day navigation
- **Assignment Detail** — location info, Leaflet mini-map, live GPS distance indicator
- **Check-In** — GPS acquisition → camera capture → preview → submit
- **Bypass Request** — form for rejected check-ins, pending status poller, terminal result screens
- **History** — paginated attendance history with photo lightbox

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
