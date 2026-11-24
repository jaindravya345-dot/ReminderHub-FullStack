# ReminderHub — Dark Full-Stack Prototype

This is a PHP + MySQL full-stack starter prototype with a premium dark interface.

## Requirements
- PHP 8.1+ with PDO MySQL enabled
- MySQL 8+ or compatible MariaDB
- Apache (XAMPP is suitable)

## Install with XAMPP (Windows)
1. Copy the `ReminderHub-FullStack` folder into `C:\xampp\htdocs\`.
2. Start **Apache** and **MySQL** in XAMPP Control Panel.
3. Open `http://localhost/phpmyadmin`.
4. Import `database/schema.sql` using the Import tab.
5. If your local MySQL credentials are not the XAMPP defaults, edit the connection settings in `config/bootstrap.php` or set the `REMINDERHUB_DB_HOST`, `REMINDERHUB_DB_NAME`, `REMINDERHUB_DB_USER`, and `REMINDERHUB_DB_PASS` environment variables.
6. Visit `http://localhost/ReminderHub-FullStack/register.php` and create your account.

## Implemented in this prototype
- Dark responsive dashboard with a light/dark toggle
- Registration and login using PHP sessions
- Password hashing with `password_hash()` and verification with `password_verify()`
- Session ID regeneration after login/registration
- CSRF token checks for state-changing API requests
- PDO prepared statements for database queries
- Server-side validation for reminder fields
- Per-user reminder access checks for list, update and delete
- Add, list, complete, and delete reminders
- Browser-side text inserted using `textContent` for reminder titles/notes

## Not implemented yet
- Email, browser push, SMS or mobile notifications
- Recurring reminder scheduler
- Password reset / email verification
- Rate limiting and account lockout
- Production deployment, HTTPS configuration and security review
- TypeScript build pipeline (current browser UI uses plain JavaScript; TypeScript is a planned next learning step)

This is a learning prototype, not a production-audited service. Use HTTPS in deployment, configure a dedicated least-privilege database account, and review security before public launch.
