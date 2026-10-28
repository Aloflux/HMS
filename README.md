# Tunzuri

Tunzuri is a multi-tenant hospital management system for clinics and hospitals. It covers patient registration, queues, triage and consultations, lab and imaging, pharmacy, wards and maternity, theatre, cashier, insurance, inventory and Ministry of Health reports. Each facility only sees its own data. A platform administrator opens and manages facilities but never sees clinical records.

Built with Laravel 12, PHP 8.3, PostgreSQL, Tailwind CSS 4 and Alpine.js.

## Requirements

- PHP 8.3 or newer, with the `pdo_pgsql` extension
- Composer
- Node.js and npm
- PostgreSQL

## Local development

```bash
composer run setup        # install dependencies, create .env, generate the app key, migrate, build assets
php artisan db:seed       # optional: ICD-10 codes and a demo hospital with sample accounts
composer run dev          # web server, queue worker, log tail and Vite together
```

Set the `DB_*` values in `.env` to point at your PostgreSQL database before `composer run setup` runs the migrations.

When `APP_ENV` is `local` or `development` and the demo data is seeded, the login screen lists sample accounts you can click to fill in. Their shared password is `HMS_DEMO_PASSWORD`, or `password` if that is unset. The login screen never shows them in production.

## Setting up the platform administrator

The platform administrator (super user) opens, deactivates and oversees facilities. There is no sign-up page for this role. Create it from the server's command line:

```bash
php artisan hms:create-platform-admin
```

The command asks for:

1. **Email address**
2. **Full name**
3. **Password**, then **Confirm password**. Typing is hidden. The password must meet the application's password rules (at least 8 characters by default). If the two entries differ, you are asked for both again.

The account is created active, with its email already verified and no facility attached. The command then prints the sign-in URL.

You can pre-fill the details that are not secret:

```bash
php artisan hms:create-platform-admin --email=ops@yourdomain.com --name="Platform Operator"
```

### Why the password is only ever typed

- There is no `--password` option. The password never appears in shell history, the process list, CI or deployment logs, `.env` or a seeder.
- The command refuses to run without an interactive terminal (for example with `--no-interaction` or inside a pipeline). An account can only be created by a person at the prompt.
- The password is stored hashed. The audit trail records that the account was created, but not the password.

Run it over SSH on the server, once per operator who needs access.

### Resetting a platform administrator's password

Run the same command with the operator's email:

```bash
php artisan hms:create-platform-admin --email=ops@yourdomain.com
```

If that email already belongs to a platform administrator, the command asks whether to reset the password (the default answer is no). On yes, it prompts for a new password and reactivates the account if it had been switched off. Use this to recover access if the only operator is locked out.

The command refuses an email that belongs to a facility account, so a hospital user can never be promoted to platform administrator by mistake.

### Do not use the demo seeder in production

`php artisan db:seed` creates a demo platform account, `platform@tunzuri.test`, with a known password. Only seed demo data on development machines. In production, create operators with `hms:create-platform-admin`.

## How facilities join

- **Self sign-up.** A clinic or hospital registers at `/register` with its own details and its owner's account. The owner confirms their email from a verification link. A short setup wizard then covers facility details, wards and beds, and going live. The owner becomes the facility administrator and adds the rest of the staff.
- **Provisioned by the platform administrator.** From **Facilities → Provision facility**, the operator creates a facility and its first administrator with a temporary password. These facilities go live straight away.

Either way, each new facility starts with standard departments, stock stores, two wards and starter prices. Its administrator can change all of these.

## Email

With `MAIL_MAILER=log`, the default, emails are written to `storage/logs/laravel.log` and nothing is sent. This includes verification links. For real delivery, set `MAIL_MAILER=smtp` with your provider's `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` and `MAIL_FROM_ADDRESS`. `.env.example` has examples for Gmail, Brevo and Mailtrap.

A short burst of messages is sent during the request. Once `HMS_MAIL_QUEUE_AFTER` messages have been sent, or that many are already waiting, further mail goes on the queue. Keep a queue worker running in production:

```bash
php artisan queue:work
```

## Configuration

Tunzuri-specific settings in `.env`:

| Variable | Default | Purpose |
| --- | --- | --- |
| `HMS_IDLE_TIMEOUT` | `15` | Minutes of inactivity before a signed-in session is logged out |
| `HMS_MAIL_QUEUE_AFTER` | `5` | Messages sent during a request before the rest are queued |
| `HMS_DEMO_PASSWORD` | `password` | Password shown for the sample accounts on the login screen (local and development only) |
| `AUTH_VERIFICATION_EXPIRE` | `60` | Minutes an email verification link stays valid |

## Deploying to a server

1. Copy `.env.example` to `.env` and set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, the database and the mail settings.
2. Install and build:
   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan key:generate
   php artisan migrate --force
   npm ci && npm run build
   ```
3. Create the first platform administrator with `php artisan hms:create-platform-admin`.
4. Keep `php artisan queue:work` running under a process manager such as Supervisor or systemd.

## Tests

```bash
php artisan test --compact
```
