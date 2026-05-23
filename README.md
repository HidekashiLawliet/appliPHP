# Project Documentation

## Overview

This PHP application is a simple itinerary management system built for a XAMPP environment. The project includes:

- A **login system** with CSRF protection and session management.
- **Persistent login cookies** for user authentication across requests.
- An **itinerary creation/edit** interface for authenticated users.
- A **MariaDB / MySQL backend** with support for a `trajets` table.
- Test scripts in the `tests/` folder for login flow, itinerary creation, and database driver verification.

# appliPHP

## Environment configuration

Sensitive values should be stored in a local `.env` file and not committed to version control.
Create `.env` from `.env.example` and set your database credentials and cookie secret.

Example:

```env
DB_HOST=localhost
DB_NAME=tpak
DB_USER=root
DB_PASS=AdminRoot13
AUTH_COOKIE_SECRET=ChangeThisBeforeProduction
```

## Project Structure

```
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
  visuels/
tests/
  test_login.php
  test_create_itinerary.php
  test_db_drivers.php
text/
  consignes.txt
  explication.txt
  pdftexteexplaination
views/
  404.php
  create_itinerary.php
  dashboard.php
  footer.php
  header.php
  home.php
  login.php
```

## Main Components

### `backend/config.php`

Configuration file for database connection and authentication constants.

- `DB_HOST`: database host, usually `localhost`.
- `DB_NAME`: default database name, currently `tpak`.
- `DB_USER`: database username, currently `root`.
- `DB_PASS`: database password, currently `AdminRoot13`.
- `AUTH_COOKIE_SECRET`: secret used to sign persistent login cookies.

The file creates a shared PDO instance named `$pdo`.

### `backend/process_login.php`

Processes login form submission:

1. Starts a session.
2. Validates email and password are not empty.
3. Verifies CSRF token using `hash_equals`.
4. Queries `userdata` table for user credentials.
5. Compares submitted password to database password.
6. Regenerates session ID on successful login.
7. Stores user details in session variables and `$_SESSION['user']`.
8. Sets a persistent authentication cookie via `auth_cookie_set()`.
9. Redirects the user to `/home.php`.

### `backend/auth_cookie.php`

Manages persistent login cookies:

- Creates signed cookies using HMAC-SHA256.
- Reads and validates cookie payloads.
- Clears invalid or expired cookies.
- Restores session from a valid cookie if the user is not already logged in.

Cookie settings:

- Name: `user_login`
- TTL: `86400` seconds (1 day)
- `Secure` flag set when the request uses HTTPS.
- `HttpOnly` and `SameSite=Lax` to reduce CSRF risk.

### `backend/trajets_schema.php`

Defines `ensureTrajetsTable(PDO $pdo)` which creates the `trajets` table if needed.

Columns:

- `id`: auto increment primary key.
- `user_email`: user owner email.
- `agence_depart`: departure agency.
- `date_depart`: departure datetime.
- `agence_arrivee`: arrival agency.
- `date_arrivee`: arrival datetime.
- `places_total`: total seats.
- `places_disponibles`: available seats.
- `created_at`: automatic timestamp.

## Web Views

### `views/login.php`

Displays the login form with:

- Email input.
- Password input.
- Hidden CSRF token field.
- Displays session error messages.

### `views/create_itinerary.php`

Allows authenticated users to create or edit itineraries.

Key features:

- Ensures the user is authenticated via session or persistent cookie.
- Loads the current user profile from `userdata`.
- Reads agency names from `data/jeu-d-essais/agences.txt`.
- Validates:
     - `agence_depart` and `agence_arrivee` are set.
     - Departure and arrival agencies are different.
     - Dates are valid and arrival is after departure.
     - `places_total` is at least 1.
     - `places_disponibles` is between 0 and `places_total`.
- Inserts or updates `trajets` records.

## Database Details

### Environment variables

The application can load sensitive credentials from a local `.env` file in the project root.
Copy `.env.example` to `.env` and set your database login and cookie secret there.

Example `.env` entries:

```env
DB_HOST=localhost
DB_NAME=tpak
DB_USER=root
DB_PASS=AdminRoot13
AUTH_COOKIE_SECRET=ChangeThisBeforeProduction
```

The `.env` file is ignored by `.gitignore` and should not be committed.

### MariaDB / MySQL

The application uses MariaDB/MySQL through PDO.

Connection example from `backend/config.php`:

```php
$pdo = new PDO(
    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
```

### `userdata` table

This table stores user login data.

Required columns:

- `email`
- `password`
- `nom`
- `prenom`
- `admin`
- Optional `tel` field is used in some tests.

### `trajets` table

Stores trip records.

The schema is defined in `backend/trajets_schema.php`.

### Example default passwords

According to project notes, passwords are provided by HR.
For local testing, the default password can be used as:

- `Password123!`

## Tests

Three CLI PHP tests are included in the `tests/` folder.

### `tests/test_db_drivers.php`

- Lists available PDO drivers.
- Attempts MariaDB/MySQL connection using `backend/config.php`.
- Falls back to SQLite in-memory if MySQL/MariaDB driver is not available.

Run:

```bash
php tests/test_db_drivers.php
```

### `tests/test_login.php`

- Simulates login form submission.
- Creates a test user in SQLite or fallback array storage.
- Verifies CSRF token, password check, session variables, and persistent cookie creation.
- Cleans up the test user before exiting.

Run:

```bash
php tests/test_login.php
```

### `tests/test_create_itinerary.php`

- Simulates an authenticated user.
- Loads agencies from `data/jeu-d-essais/agences.txt`.
- Validates itinerary form input rules.
- Inserts a test `trajets` record.
- Cleans up test data before exit.

Run:

```bash
php tests/test_create_itinerary.php
```

## Setup and Usage

### Requirements

- XAMPP with Apache and PHP.
- MariaDB/MySQL server.
- PHP PDO extension for MySQL: `pdo_mysql`.
- The app uses the CLI PHP binary for tests, so configure the same PHP installation as Apache.

### Enable `pdo_mysql`

In `php.ini`:

```ini
extension=pdo_mysql
extension=mysqli
```

Then restart Apache.

### Start the app

Place the project in your web root, for example:

```
C:\xampp\htdocs\
```

Open in browser:

```
http://localhost/login.php
```

### Login process

1. Enter email and password.
2. CSRF token is verified.
3. User credentials are checked against `userdata`.
4. Session and persistent cookie are created.
5. User is redirected to `home.php`.

### Itinerary creation

1. User must be authenticated.
2. `create_itinerary.php` reads valid agencies from `data/jeu-d-essais/agences.txt`.
3. The user submits trip details.
4. The app validates and stores the trip in `trajets`.

## Notes

- The project currently stores passwords in plaintext and compares them directly.
- For production, use password hashing (`password_hash`, `password_verify`) and secure cookie secrets.
- Update `AUTH_COOKIE_SECRET` before deployment.

## Files to Review

- `backend/config.php`
- `backend/process_login.php`
- `backend/auth_cookie.php`
- `backend/trajets_schema.php`
- `views/login.php`
- `views/create_itinerary.php`
- `tests/test_login.php`
- `tests/test_create_itinerary.php`
- `tests/test_db_drivers.php`

---

This document was generated to explain the project architecture, database setup, test coverage, and usage instructions.
