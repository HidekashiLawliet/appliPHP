# Touche pas au klaxon

Intranet car-sharing application: employees offer trips between the company's
agencies, and an administrator manages users, agencies and trips.

Object-oriented PHP 8, MVC architecture, MySQL/MariaDB, Bootstrap 5 + Sass.

---

## 1. Requirements

| Tool               | Version      | Check with                            |
| ------------------ | ------------ | ------------------------------------- |
| PHP                | 8.2+         | `php -v`                              |
| Composer           | 2.x          | `composer -V`                         |
| MySQL or MariaDB   | 8.0+ / 10.4+ | `mysql --version`                     |
| Node.js (optional) | 18+          | `node -v`, only to recompile the SCSS |

PHP extensions: `pdo_mysql` and `mbstring` (application), plus `pdo_sqlite`
(tests). Find your `php.ini` with `php --ini` and make sure these lines are
**not** commented out (no `;` in front):

```ini
extension=mbstring
extension=pdo_mysql
extension=pdo_sqlite
```

With XAMPP, `php.ini` is in `C:\xampp\php\php.ini`. Restart Apache after a change.

## 2. Installation

```bash
# 1. Get the code
git clone <repository-url> appliPHP
cd appliPHP

# 2. Install the PHP dependencies (router, dotenv, PHPUnit, PHPStan)
composer install

# 3. Create your configuration from the template
cp .env.example .env        # Windows (cmd): copy .env.example .env
```

Edit `.env` with your database account. `DB_NAME` is the database that the
installer will **create**: it does not need to exist yet.

```env
APP_DEBUG=false
APP_SECRET=<output of: php -r "echo bin2hex(random_bytes(32));">
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=tpak
DB_USER=root
DB_PASS=your_password
```

```bash
# 4. Create the database, the tables, the test data and the admin account
composer db:install
```

> `composer db:install` connects to the server **without** selecting a
> database, drops and recreates `DB_NAME`, then runs the scripts of `database/`.
> It erases any existing data in that database.

<details>
<summary>Alternative: run the SQL scripts by hand</summary>

```bash
mysql -u root -p < database/00_create_database.sql          # creates "tpak"
mysql -u root -p tpak < database/01_schema.sql
mysql -u root -p tpak < database/02_seed.sql
mysql -u root -p tpak < database/03_create_admin.sql
```

(Use `DB_NAME=tpak` in `.env` in this case.)

</details>

```bash
# 5. Start the development server
composer serve
```

Open <http://localhost:80>.

### Accounts

| Role          | Email                                                                            | Password       |
| ------------- | -------------------------------------------------------------------------------- | -------------- |
| Administrator | `alexandre.martin@email.fr`                                                      | `Password123!` |
| Employee      | `sophie.dubois@email.fr` (or any other address of `data/jeu-d-essais/users.txt`) | `Password123!` |

Change the administrator password before any real use (see the header of
`database/03_create_admin.sql`).

### Running under Apache / XAMPP instead

The web server's document root must be the **`public/`** folder, never the
project root (which holds `.env`). Example virtual host:

```apache
<VirtualHost *:80>
    ServerName klaxon.local
    DocumentRoot "C:/xampp/htdocs/appliPHP/public"
    <Directory "C:/xampp/htdocs/appliPHP/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Enable `mod_rewrite`, add `127.0.0.1 klaxon.local` to your hosts file, and
open <http://klaxon.local>. `public/.htaccess` sends every request to
`public/index.php`.

---

## 3. Features

| Visitor                             | Employee                              | Administrator                         |
| ----------------------------------- | ------------------------------------- | ------------------------------------- |
| Sees upcoming trips with free seats | + trip details (author, phone, email) | Lists employees                       |
| Logs in                             | Creates a trip                        | Creates, renames and deletes agencies |
|                                     | Edits / deletes **their own** trips   | Lists and deletes any trip            |

After every write operation the user is redirected to the matching list with
a flash message (stored in the session, shown once).

## 4. Architecture

```text
public/index.php        Front controller: autoload, .env, session, router
config/routes.php       Every route (izniburak/router)
src/
  Core/                 Framework layer
    Controller.php        abstract: render, redirect, flash, access control, CSRF
    Model.php             abstract: PDO, find/all/delete/insert/update helpers
    Database.php          Singleton PDO connection (private constructor, getInstance())
    Config.php            reads .env (vlucas/phpdotenv)
    Auth.php              password_verify, session login, signed "remember me" cookie
    Session.php, Flash.php, Csrf.php, View.php, ErrorHandler.php, HttpException.php
  Controllers/          HomeController, AuthController, TrajetController
    Admin/              UtilisateurController, AgenceController, TrajetController
  Models/               Utilisateur, Agence, Trajet  (extend Core\Model)
  Validators/           TrajetValidator, AgenceValidator
  Views/                PHP templates only: no logic, no SQL
database/               SQL scripts (create database, schema, seed, admin)
docs/database.md        Data dictionary, MCD, MLD, MPD (Merise)
docs/api/               Generated PHPDoc
assets/scss/            Sass sources, compiled to public/css/app.css
tests/                  PHPUnit: Unit/ and Integration/
bin/                    install.php (database), docs.php (PHPDoc)
```

Request flow: `public/index.php` → router → `Controller` → `Model` (PDO
prepared statements) → `Controller::render()` → `View` template inside
`Views/layout/main.php` → `Response`.

Classes are autoloaded by Composer (PSR-4, `App\` → `src/`): there is no
manual `require` in the application code.

### Security

- Passwords hashed with `password_hash()` and checked with `password_verify()`;
  hashes are upgraded automatically at login (`password_needs_rehash()`).
- Session id regenerated at login; session cookie `HttpOnly` + `SameSite=Lax`.
- CSRF token on every form (logout included); every write route is `POST`.
- All SQL goes through prepared statements; all output is escaped (`View::e()`).
- Authorization in the controllers: `requireLogin()`, `requireAdmin()`, and
  ownership checks in SQL (`WHERE id = :id AND utilisateur_id = :user`).
- Input validated in PHP (validators) **and** in the database (FK, UNIQUE, CHECK).
- Errors are logged; users only see a generic message unless `APP_DEBUG=true`.
- Credentials live in `.env`, which is git-ignored.

## 5. Database

See [`docs/database.md`](docs/database.md) for the data dictionary, the
management rules, the MCD (named associations PROPOSER, PARTIR_DE, ARRIVER_A
with their cardinalities), the MLD and the physical choices.

```text
utilisateurs (id, nom, prenom, telephone, email, mot_de_passe, est_admin)
agences      (id, nom)
trajets      (id, #utilisateur_id, #agence_depart_id, #agence_arrivee_id,
              date_depart, date_arrivee, places_total, places_disponibles, cree_le)
```

The test data come from `data/jeu-d-essais/agences.txt` and
`data/jeu-d-essais/users.txt` (12 agencies, 20 employees) and are loaded by
`database/02_seed.sql`, along with 6 trips dated relative to today.

## 6. Quality tools

| Command                                | What it does                                                                                                                                                                                                                                                                          |
| -------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `composer test`                        | PHPUnit, 78 tests. Unit tests (validators, flash, CSRF, view) and integration tests on an in-memory SQLite database covering **every write operation**: create/update/delete trip, create/rename/delete agency, admin trip deletion, login, logout, password rehash. No MySQL needed. |
| `composer stan`                        | PHPStan, **level 8**, on `src/`, `tests/`, `bin/`, `config/`, `public/` (templates excluded).                                                                                                                                                                                         |
| `composer check`                       | Both of the above.                                                                                                                                                                                                                                                                    |
| `composer docs`                        | Generates the PHPDoc into `docs/api/index.html` (downloads phpDocumentor into `tools/` on first run).                                                                                                                                                                                 |
| `npm install` then `npm run build:css` | Compiles `assets/scss/app.scss` into `public/css/app.css` (`npm run watch:css` while editing). The compiled CSS is committed, so Node is only needed to change the styles.                                                                                                            |

## 7. Troubleshooting

| Message                                              | Fix                                                                                  |
| ---------------------------------------------------- | ------------------------------------------------------------------------------------ |
| `could not find driver`                              | `pdo_mysql` (or `pdo_sqlite` for the tests) is disabled in `php.ini`, see section 1. |
| `SQLSTATE[HY000] [1045] Access denied`               | Wrong `DB_USER` / `DB_PASS` in `.env`.                                               |
| `SQLSTATE[HY000] [2002]`                             | MySQL/MariaDB is not started, or wrong `DB_HOST` / `DB_PORT`.                        |
| `La variable de configuration "DB_NAME" est absente` | `.env` is missing: `cp .env.example .env`.                                           |
| Every page returns 404 under Apache                  | The document root is not `public/`, or `mod_rewrite` / `AllowOverride All` is off.   |
| "Une erreur interne est survenue"                    | Set `APP_DEBUG=true` in `.env` to see the real error.                                |
