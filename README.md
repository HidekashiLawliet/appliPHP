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

The project already ignores `.env` via `.gitignore`.

for more project explication you can look into ./docs
