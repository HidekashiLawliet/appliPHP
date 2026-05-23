# appliPHP

This is a small PHP MVC-style project for managing itineraries. It was built to run locally with XAMPP, Apache, PHP, and MariaDB/MySQL.

The app lets a user log in, keeps their session active with a signed cookie, and gives authenticated users a screen where they can create or edit trips between agencies.

## What Is Inside

- A login flow with CSRF protection and PHP sessions.
- A persistent login cookie, signed with HMAC-SHA256.
- An itinerary form for creating and updating trips.
- A MariaDB/MySQL database connection through PDO.
- A `trajets` table that is created automatically when needed.
- A few CLI test scripts for login, itinerary creation, and database driver checks.

## Project Structure

```text
index.php
README.md
backend/
  auth_cookie.php
  config.php
  logout.php
  process_login.php
  setup_db.php
  trajets_schema.php
data/
  jeu-d-essais/
    agences.txt
    users.txt
tests/
  test_login.php
  test_create_itinerary.php
  test_db_drivers.php
views/
  404.php
  create_itinerary.php
  dashboard.php
  footer.php
  header.php
  home.php
  login.php
```

## Local Setup

You need:

- XAMPP with Apache and PHP.
- MariaDB or MySQL.
- The PHP PDO MySQL extension, usually `pdo_mysql`.
- The same PHP installation available in the terminal if you want to run the tests.

Put the project in your web root, for example:

```text
C:\xampp\htdocs\appliPHP
```

Then open:

```text
http://localhost:80/home.php
```

Depending on your Apache configuration, the URL may be slightly different.

## Environment File

Database credentials and cookie secrets should live in a local `.env` file. Start by copying `.env.example`:

```text
.env.example -> .env
```

Example:

```env
DB_HOST=localhost
DB_NAME=tpak
DB_USER=root
DB_PASS=AdminRoot13
AUTH_COOKIE_SECRET=ChangeThisBeforeProduction   bn
```

The `.env` file is ignored by Git, so it should stay local to your machine.

## Database

The app expects a MariaDB/MySQL database with a `userdata` table for accounts. The login code uses these fields:

- `email`
- `password`
- `nom`
- `prenom`
- `admin`
- `tel`, used by some tests

Trips are stored in the `trajets` table. Its schema is defined in `backend/trajets_schema.php`, and the app can create the table if it does not exist yet.

Each trip stores:

- the user email
- departure agency and date
- arrival agency and date
- total seats
- available seats
- creation date

Agency names are loaded from:

```text
data/jeu-d-essais/agences.txt
```

## Main Files

`backend/config.php` loads the database configuration and creates the shared PDO connection.

`backend/process_login.php` handles login form submissions. It checks the CSRF token, validates the user against `userdata`, starts the session, and creates the persistent login cookie.

`backend/auth_cookie.php` creates, validates, and clears the signed login cookie. The cookie is named `user_login` and lasts one day.

`backend/trajets_schema.php` contains the helper that creates the `trajets` table.

`views/login.php` displays the login form.

`views/create_itinerary.php` handles the itinerary form. It checks that the user is logged in, loads agencies, validates the submitted dates and seat counts, then inserts or updates the trip.

## Running Tests

The tests are simple PHP scripts in the `tests/` folder.

Check PDO drivers and the database connection:

```bash
php tests/test_db_drivers.php
```

Test the login flow:

```bash
php tests/test_login.php
```

Test itinerary validation and creation:

```bash
php tests/test_create_itinerary.php
```

If `pdo_mysql` is missing, enable it in `php.ini`:

```ini
extension=pdo_mysql
extension=mysqli
```

Then restart Apache.

## Test Login Notes

The project notes say passwords are provided by HR. For local testing, the default password can be:

```text
Password123!
```

## Security Notes

This project is still a learning/local project, so a few things should be improved before anything close to production:

- Passwords are currently compared as plain text. Use `password_hash()` and `password_verify()` instead.
- Change `AUTH_COOKIE_SECRET` to a strong private value.
- Keep `.env` out of Git.
- Use HTTPS if persistent login cookies are enabled outside local development.

## Useful Files To Review

- `backend/config.php`
- `backend/process_login.php`
- `backend/auth_cookie.php`
- `backend/trajets_schema.php`
- `views/login.php`
- `views/create_itinerary.php`
- `tests/test_login.php`
- `tests/test_create_itinerary.php`
- `tests/test_db_drivers.php`
