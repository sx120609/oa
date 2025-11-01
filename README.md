# Device Lifecycle API Skeleton

This repository provides a minimal, framework-free PHP project skeleton for building a device lifecycle management API. It demonstrates a single-entry HTTP endpoint, simple routing, PDO database access, and basic project structure ready for extending with real business logic.

## Project layout

```
public/           # Web root containing single entry point and optional rewrite rules
src/              # Configuration, bootstrap logic, helper utilities, and request handlers
scripts/          # CLI scripts such as database initialisation
migrations/       # SQL migration files with schema and seed data
storage/          # Default location for SQLite database file (created on demand)
```

## Requirements

- PHP 8.1+
- SQLite (default) or MySQL 8+

## Getting started

1. **Install dependencies** – none required beyond PHP and PDO extensions.
2. **Configure environment (optional)** – set environment variables to override defaults:
   - `API_KEY` (default `devkey`)
   - `DB_DRIVER` (`sqlite` or `mysql`, default `sqlite`)
   - `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_CHARSET` (for MySQL)
3. **Initialise the database**:
   ```bash
   php scripts/init_db.php
   ```
4. **Run the development server**:
   ```bash
   php -S 0.0.0.0:8000 -t public
   ```
5. **Send a test request** (replace API key if customised):
   ```bash
   curl -H "X-Api-Key: devkey" http://127.0.0.1:8000/assets
   ```

## Next steps

Implement application logic inside the handler classes under `src/Handlers`, utilising the shared helpers in `src/helpers.php` for routing, database access, and JSON responses.

## License

MIT
