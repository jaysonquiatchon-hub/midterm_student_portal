<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Student Portal

The application provides a public course catalog and a session-protected student portal for student records, course enrollment, and grades.

### Local setup

1. Use PHP 8.4.1 or newer and install the Composer dependencies.
2. Configure the database in `.env`. The example environment uses SQLite; create its database file before migrating:

   ```powershell
   if (-not (Test-Path database\database.sqlite)) {
       New-Item -ItemType File -Path database\database.sqlite
   }
   ```

   Then run the migrations:

   ```bash
   php artisan migrate
   ```

   If you prefer MySQL, create a MySQL database and set `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` instead.

3. (Optional) Add 20 fictional students and any missing sample programs:

   ```bash
   php artisan db:seed --class=StudentDemoSeeder
   ```

4. Start the local web server:

   ```bash
   php artisan serve
   ```

After portal login, the dashboard summarizes students, active courses, programs, pending applications, student counts by program, and recently added students. The course catalog is available at `/courses`. Student management and grade updates require portal access.

Students can create an account at `/portal/register`, log in immediately, and submit an enrollment application with the required documents for their student type. Common document requirements are configured in `config/enrollment.php`; uploaded PDF/JPG/PNG files are limited to 1 MB each, kept on the private local disk, and available to administrators from the application review page.

The public five-step enrollment form lets applicants choose active courses for their selected program, year, and semester before uploading their requirements. Programs and their 1st- through 4th-year courses are seeded from the supplied BSIT, BSCS, BSBA, BSA, and BSED curricula. Course codes are unique within each program, so shared general-education codes can appear in multiple programs. Run `php artisan db:seed --class=ProgramSeeder` and `php artisan db:seed --class=CourseSeeder` to load or refresh the catalog. The catalog no longer uses departments; the department-removal migration drops the old department table and its program/application links.

Email delivery is configured through `MAIL_*` values in `.env`. `MAIL_MAILER=log`, `MAIL_MAILER=array`, and Mailtrap's `sandbox.smtp.mailtrap.io` only log or capture test messages; they do not deliver to applicant inboxes. For real delivery, configure a live email provider's SMTP host, port, encryption, username, password/token, and verified sender address. After changing `.env`, run `php artisan config:clear` and use **Resend Email** on the approved application to retry a failed message.

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

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
