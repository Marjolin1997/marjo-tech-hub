# Validation Checklist

Marjo Tech Hub is developed through stacked feature branches. A feature is not considered complete merely because its code exists in GitHub.

## Local prerequisites

- Supported PHP and Composer versions
- Supported Node.js and npm versions
- MySQL or the configured test database
- No production credentials or employer-specific configuration in local examples

## Backend

From `backend/`:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh
php artisan test
```

Validate manually:

- `GET /api/health` returns the expected service payload.
- Login creates an authenticated session and invalid credentials are rejected.
- Authenticated requests cannot read or mutate another user's categories, entries, tags, or favorites.
- Nested categories reject cycles.
- Entry create/update rejects categories and tags owned by another user.
- Favorite creation is idempotent.
- Entry search, category, tag and favorite filters remain user-scoped.

## Frontend

From `frontend/`:

```bash
npm install
cp .env.example .env
npm run build
npm run dev
```

Validate manually:

- Login, logout and protected-route behavior.
- Category and nested-category creation.
- Entry create, edit, delete and copy actions.
- Favorites and tags.
- Search and filters together.
- Pagination resets when filters change and preserves the active filters while paging.
- API failure states expose a useful retry path.
- Mobile layout remains usable.

## Stacked branch order

Validate from the lowest branch upward:

1. `feat/project-foundation`
2. `feat/authentication`
3. `feat/categories-entries`
4. `feat/favorites-tags`
5. `test/library-hardening`

Do not merge a higher stacked branch before its lower dependency has been validated and integrated.

## Before marking a PR ready

- Backend tests pass.
- Frontend production build passes.
- No secrets are present in the diff.
- Database migrations run from a clean database.
- README/docs describe implemented behavior accurately.
- PR base is updated after the lower stacked PR is merged.
