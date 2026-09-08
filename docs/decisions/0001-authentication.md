# ADR 0001: First-party SPA authentication

## Status

Accepted for implementation.

## Context

Marjo Tech Hub has a React frontend and a Laravel REST API. Both are first-party parts of the same product. Authentication should avoid exposing long-lived API credentials to browser JavaScript and should support protected routes cleanly.

## Decision

Use Laravel Sanctum's stateful SPA authentication model with the Laravel session cookie and CSRF protection.

The React application will:

1. initialize CSRF protection through `/sanctum/csrf-cookie`;
2. submit credentials to `/api/auth/login`;
3. load the current user from `/api/auth/user`;
4. protect authenticated React routes;
5. sign out through `/api/auth/logout`.

The backend will return JSON-only authentication responses. React remains responsible for every application screen.

## Security properties

- Passwords are hashed by Laravel and are never returned by the API.
- The browser session is represented by an HTTP-only cookie rather than a token stored in local storage.
- Login attempts are rate-limited.
- Protected API routes require an authenticated Sanctum session.
- Validation errors use predictable JSON responses for a friendly frontend experience.

## Consequences

Local development must configure the frontend origin and Sanctum stateful domains correctly. Production deployment must use HTTPS and an appropriate same-site/domain cookie configuration.
